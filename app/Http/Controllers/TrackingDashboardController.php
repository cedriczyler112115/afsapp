<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrackingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $types = DocumentType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = [];
        $authUser = Auth::user();
        if ($authUser && $this->canViewAll($authUser) && Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
            $users = User::query()
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name']);
        }

        return view('tracking_dashboard.index', [
            'types' => $types,
            'users' => $users,
        ]);
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        try {
            $filters = $this->validatedFilters($request);
            $base = $this->baseQuery($filters, $user);

            $total = (clone $base)->count();

            $statusCounts = (clone $base)
                ->select('incoming_documents.current_status', DB::raw('COUNT(*) as c'))
                ->groupBy('incoming_documents.current_status')
                ->pluck('c', 'incoming_documents.current_status')
                ->all();

            $typeCounts = (clone $base)
                ->leftJoin('document_types', 'incoming_documents.document_type_id', '=', 'document_types.id')
                ->select(DB::raw('COALESCE(NULLIF(TRIM(document_types.name), \'\'), \'Unspecified\') as type_name'), DB::raw('COUNT(*) as c'))
                ->groupBy('type_name')
                ->orderBy('type_name')
                ->pluck('c', 'type_name')
                ->all();

            $bucketCounts = $this->processingBuckets((clone $base));

            $avgHours = $this->averageProcessingHours((clone $base));

            $page = max(1, (int) ($request->input('page') ?? 1));
            $perPage = min(100, max(10, (int) ($request->input('per_page') ?? 25)));
            $offset = ($page - 1) * $perPage;

            $rowsQuery = $this->rowsQuery($filters, $user)
                ->orderByDesc('incoming_documents.created_at')
                ->offset($offset)
                ->limit($perPage);

            $rows = $rowsQuery->get()->map(function ($r) {
                $txId = (int) ($r->transaction_type ?? 0);
                $tx = $txId === 2 ? 'OUTGOING' : ($txId === 1 ? 'INCOMING' : '');

                return [
                    'id' => (int) ($r->id ?? 0),
                    'document_reference_number' => (string) ($r->document_reference_number ?? ''),
                    'drn' => (string) ($r->drn ?? ''),
                    'subject' => (string) ($r->subject ?? ''),
                    'transaction_type' => $tx,
                    'document_type' => (string) ($r->document_type_name ?? ''),
                    'source' => (string) ($r->source_name ?? ''),
                    'created_by' => (string) ($r->created_by_name ?? ''),
                    'assigned_to' => (string) ($r->assigned_to_name ?? ''),
                    'current_status' => (string) ($r->current_status ?? ''),
                    'uploaded_at' => $this->formatDateTime($r->created_at ?? null),
                    'date_received' => $this->formatDate($r->date_received ?? null),
                    'date_forwarded' => $this->formatDateTime($r->date_forwarded ?? null),
                    'delivery_confirmed_at' => $this->formatDateTime($r->delivery_confirmed_at ?? null),
                    'last_action_at' => $this->formatDateTime($r->last_action_at ?? null),
                    'last_action' => (string) ($r->last_action ?? ''),
                ];
            })->all();

            return response()->json([
                'success' => true,
                'meta' => [
                    'generated_at' => now()->toIso8601String(),
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                ],
                'charts' => [
                    'status' => [
                        'labels' => array_values(array_map('strval', array_keys($statusCounts))),
                        'values' => array_values(array_map('intval', array_values($statusCounts))),
                    ],
                    'types' => [
                        'labels' => array_values(array_map('strval', array_keys($typeCounts))),
                        'values' => array_values(array_map('intval', array_values($typeCounts))),
                    ],
                    'processing_buckets' => [
                        'labels' => array_values(array_keys($bucketCounts)),
                        'values' => array_values($bucketCounts),
                    ],
                ],
                'summary' => [
                    'average_processing_hours' => $avgHours,
                ],
                'rows' => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('tracking_dashboard.errors.load_failed'),
            ], 500);
        }
    }

    public function exportJson(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        try {
            $filters = $this->validatedFilters($request);
            $limit = min(2000, max(1, (int) ($request->input('limit') ?? 2000)));

            $rows = $this->rowsQuery($filters, $user)
                ->orderByDesc('incoming_documents.created_at')
                ->limit($limit)
                ->get()
                ->map(function ($r) {
                    $txId = (int) ($r->transaction_type ?? 0);
                    $tx = $txId === 2 ? 'OUTGOING' : ($txId === 1 ? 'INCOMING' : '');

                    return [
                        'document_reference_number' => (string) ($r->document_reference_number ?? ''),
                        'drn' => (string) ($r->drn ?? ''),
                        'subject' => (string) ($r->subject ?? ''),
                        'transaction_type' => $tx,
                        'document_type' => (string) ($r->document_type_name ?? ''),
                        'source' => (string) ($r->source_name ?? ''),
                        'current_status' => (string) ($r->current_status ?? ''),
                        'uploaded_at' => $this->formatDateTime($r->created_at ?? null),
                        'delivery_confirmed_at' => $this->formatDateTime($r->delivery_confirmed_at ?? null),
                    ];
                })
                ->all();

            return response()->json([
                'success' => true,
                'meta' => [
                    'generated_at' => now()->toIso8601String(),
                    'limit' => $limit,
                    'count' => count($rows),
                ],
                'rows' => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('tracking_dashboard.errors.load_failed'),
            ], 500);
        }
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $filters = $this->validatedFilters($request);
        $rows = $this->rowsQuery($filters, $user)
            ->orderByDesc('incoming_documents.created_at')
            ->get();

        $filename = 'tracking_dashboard_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Document Reference Number',
                'DRN',
                'Subject',
                'Transaction Type',
                'Document Type',
                'Source',
                'Created By',
                'Assigned To',
                'Current Status',
                'Uploaded At',
                'Date Received',
                'Date Forwarded',
                'Delivery Confirmed At',
                'Last Action',
                'Last Action At',
            ]);

            foreach ($rows as $r) {
                $txId = (int) ($r->transaction_type ?? 0);
                $tx = $txId === 2 ? 'OUTGOING' : ($txId === 1 ? 'INCOMING' : '');

                fputcsv($handle, [
                    (string) ($r->document_reference_number ?? ''),
                    (string) ($r->drn ?? ''),
                    (string) ($r->subject ?? ''),
                    $tx,
                    (string) ($r->document_type_name ?? ''),
                    (string) ($r->source_name ?? ''),
                    (string) ($r->created_by_name ?? ''),
                    (string) ($r->assigned_to_name ?? ''),
                    (string) ($r->current_status ?? ''),
                    $this->formatDateTime($r->created_at ?? null),
                    $this->formatDate($r->date_received ?? null),
                    $this->formatDateTime($r->date_forwarded ?? null),
                    $this->formatDateTime($r->delivery_confirmed_at ?? null),
                    (string) ($r->last_action ?? ''),
                    $this->formatDateTime($r->last_action_at ?? null),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $filters = $this->validatedFilters($request);
        $rows = $this->rowsQuery($filters, $user)
            ->orderByDesc('incoming_documents.created_at')
            ->get();

        $generatedAt = now();

        if (! class_exists(\Dompdf\Dompdf::class)) {
            return response()->view('tracking_dashboard.report_print', [
                'rows' => $rows,
                'generatedAt' => $generatedAt,
            ]);
        }

        $html = view('tracking_dashboard.report_pdf', [
            'rows' => $rows,
            'generatedAt' => $generatedAt,
        ])->render();

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'tracking_dashboard_'.now()->format('Ymd_His').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $transactionType = $request->filled('transaction_type') ? (int) $request->input('transaction_type') : null;
        if ($transactionType !== null && ! in_array($transactionType, [1, 2], true)) {
            $transactionType = null;
        }

        $status = strtoupper(trim((string) $request->input('status', '')));
        $allowedStatuses = ['RECEIVED', 'FORWARDED', 'ARCHIVED'];
        if ($status === '' || ! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $typeId = $request->filled('document_type_id') ? (int) $request->input('document_type_id') : null;
        if ($typeId !== null && $typeId <= 0) {
            $typeId = null;
        }

        $personnelId = $request->filled('personnel_id') ? (int) $request->input('personnel_id') : null;
        if ($personnelId !== null && $personnelId <= 0) {
            $personnelId = null;
        }

        $q = trim((string) $request->input('q', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        return [
            'q' => $q,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'transaction_type' => $transactionType,
            'status' => $status,
            'document_type_id' => $typeId,
            'personnel_id' => $personnelId,
        ];
    }

    private function baseQuery(array $filters, User $user)
    {
        $query = DB::table('incoming_documents')
            ->when(Schema::hasColumn('incoming_documents', 'deleted_at'), fn ($q) => $q->whereNull('incoming_documents.deleted_at'));

        $query = $this->applyFilters($query, $filters);

        if (! $this->canViewAll($user)) {
            $userId = (int) $user->id;
            $query->where(function ($q) use ($userId) {
                $q->where('incoming_documents.created_by', $userId)
                    ->orWhere('incoming_documents.received_by', $userId)
                    ->orWhere('incoming_documents.forwarded_to_user_id', $userId);

                if (Schema::hasTable('incoming_document_forward_recipients')) {
                    $q->orWhereExists(function ($sq) use ($userId) {
                        $sq->selectRaw('1')
                            ->from('incoming_document_forward_recipients')
                            ->whereColumn('incoming_document_forward_recipients.incoming_document_id', 'incoming_documents.id')
                            ->where('incoming_document_forward_recipients.user_id', $userId);
                    });
                }
            });
        }

        return $query;
    }

    private function rowsQuery(array $filters, User $user)
    {
        $hasUsers = Schema::hasTable('users');
        $hasDelivery = Schema::hasTable('incoming_document_forward_recipients') && Schema::hasColumn('incoming_document_forward_recipients', 'date_received');
        $hasLogs = Schema::hasTable('document_logs') && Schema::hasColumn('document_logs', 'action_timestamp');

        $query = $this->baseQuery($filters, $user)
            ->leftJoin('document_types', 'incoming_documents.document_type_id', '=', 'document_types.id')
            ->leftJoin('document_sources', 'incoming_documents.document_source_id', '=', 'document_sources.id');

        if ($hasUsers) {
            $query->leftJoin('users as created_by_user', 'incoming_documents.created_by', '=', 'created_by_user.id');
            $query->leftJoin('users as assigned_to_user', 'incoming_documents.forwarded_to_user_id', '=', 'assigned_to_user.id');
            $query->leftJoin('users as received_by_user', 'incoming_documents.received_by', '=', 'received_by_user.id');
        }

        if ($hasDelivery) {
            $latestDelivery = DB::table('incoming_document_forward_recipients')
                ->select('incoming_document_id', DB::raw('MAX(date_received) as delivery_confirmed_at'))
                ->groupBy('incoming_document_id');

            $query->leftJoinSub($latestDelivery, 'delivery', function ($join) {
                $join->on('delivery.incoming_document_id', '=', 'incoming_documents.id');
            });
        }

        if ($hasLogs) {
            $latestLog = DB::table('document_logs')
                ->select('incoming_document_id', DB::raw('MAX(action_timestamp) as last_action_at'))
                ->groupBy('incoming_document_id');

            $query->leftJoinSub($latestLog, 'latest_log', function ($join) {
                $join->on('latest_log.incoming_document_id', '=', 'incoming_documents.id');
            });

            $query->leftJoin('document_logs as last_log', function ($join) {
                $join->on('last_log.incoming_document_id', '=', 'incoming_documents.id')
                    ->on('last_log.action_timestamp', '=', 'latest_log.last_action_at');
            });
        }

        $query->select([
            'incoming_documents.id',
            'incoming_documents.document_reference_number',
            'incoming_documents.drn',
            'incoming_documents.subject',
            'incoming_documents.transaction_type',
            'incoming_documents.current_status',
            'incoming_documents.created_at',
            'incoming_documents.date_received',
            'incoming_documents.date_forwarded',
            DB::raw('COALESCE(NULLIF(TRIM(document_types.name), \'\'), \'\') as document_type_name'),
            DB::raw('COALESCE(NULLIF(TRIM(document_sources.name), \'\'), \'\') as source_name'),
            $hasUsers ? DB::raw('COALESCE(NULLIF(TRIM(created_by_user.name), \'\'), \'\') as created_by_name') : DB::raw('\'\' as created_by_name'),
            $hasUsers ? DB::raw('COALESCE(NULLIF(TRIM(received_by_user.name), \'\'), \'\') as received_by_name') : DB::raw('\'\' as received_by_name'),
            $hasUsers ? DB::raw('COALESCE(NULLIF(TRIM(assigned_to_user.name), \'\'), \'\') as assigned_to_user_name') : DB::raw('\'\' as assigned_to_user_name'),
            $hasDelivery ? DB::raw('delivery.delivery_confirmed_at as delivery_confirmed_at') : DB::raw('NULL as delivery_confirmed_at'),
            $hasLogs ? DB::raw('latest_log.last_action_at as last_action_at') : DB::raw('NULL as last_action_at'),
            $hasLogs ? DB::raw('COALESCE(NULLIF(TRIM(last_log.status_to), \'\'), \'\') as last_action') : DB::raw('\'\' as last_action'),
        ]);

        $query->addSelect(DB::raw(
            "CASE
                WHEN incoming_documents.current_status = 'FORWARDED' AND ".($hasUsers ? "COALESCE(NULLIF(TRIM(assigned_to_user.name), ''), '')" : "''")." <> '' THEN ".($hasUsers ? 'assigned_to_user.name' : "''")."
                WHEN incoming_documents.current_status = 'RECEIVED' AND ".($hasUsers ? "COALESCE(NULLIF(TRIM(received_by_user.name), ''), '')" : "''")." <> '' THEN ".($hasUsers ? 'received_by_user.name' : "''")."
                ELSE ''
            END as assigned_to_name"
        ));

        return $query;
    }

    private function applyFilters($query, array $filters)
    {
        $q = (string) ($filters['q'] ?? '');
        $query->when($q !== '', function ($query) use ($q) {
            $like = '%'.$q.'%';

            $query->where(function ($w) use ($like) {
                $w->where('incoming_documents.document_reference_number', 'like', $like)
                    ->orWhere('incoming_documents.drn', 'like', $like)
                    ->orWhere('incoming_documents.subject', 'like', $like);

                foreach (['description', 'signed_by', 'forward_remarks', 'received_remarks', 'priority_level'] as $col) {
                    if (Schema::hasColumn('incoming_documents', $col)) {
                        $w->orWhere('incoming_documents.'.$col, 'like', $like);
                    }
                }

                if (Schema::hasColumn('incoming_documents', 'current_status')) {
                    $w->orWhere('incoming_documents.current_status', 'like', $like);
                }

                if (Schema::hasTable('document_sources')) {
                    $w->orWhereExists(function ($sq) use ($like) {
                        $sq->selectRaw('1')
                            ->from('document_sources')
                            ->whereColumn('document_sources.id', 'incoming_documents.document_source_id')
                            ->where('document_sources.name', 'like', $like);
                    });
                }

                if (Schema::hasTable('document_types')) {
                    $w->orWhereExists(function ($sq) use ($like) {
                        $sq->selectRaw('1')
                            ->from('document_types')
                            ->whereColumn('document_types.id', 'incoming_documents.document_type_id')
                            ->where('document_types.name', 'like', $like);
                    });
                }

                if (Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
                    $w->orWhereExists(function ($sq) use ($like) {
                        $sq->selectRaw('1')
                            ->from('users')
                            ->whereColumn('users.id', 'incoming_documents.created_by')
                            ->where('users.name', 'like', $like);
                    });
                    $w->orWhereExists(function ($sq) use ($like) {
                        $sq->selectRaw('1')
                            ->from('users')
                            ->whereColumn('users.id', 'incoming_documents.received_by')
                            ->where('users.name', 'like', $like);
                    });
                    $w->orWhereExists(function ($sq) use ($like) {
                        $sq->selectRaw('1')
                            ->from('users')
                            ->whereColumn('users.id', 'incoming_documents.forwarded_to_user_id')
                            ->where('users.name', 'like', $like);
                    });
                }
            });
        });

        $query->when(! empty($filters['status']), fn ($q2) => $q2->where('incoming_documents.current_status', $filters['status']));
        $query->when(! empty($filters['transaction_type']), fn ($q2) => $q2->where('incoming_documents.transaction_type', (int) $filters['transaction_type']));
        $query->when(! empty($filters['document_type_id']), fn ($q2) => $q2->where('incoming_documents.document_type_id', (int) $filters['document_type_id']));

        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        if ($dateFrom) {
            $query->whereDate('incoming_documents.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('incoming_documents.created_at', '<=', $dateTo);
        }

        $personnelId = (int) ($filters['personnel_id'] ?? 0);
        if ($personnelId > 0) {
            $query->where(function ($q2) use ($personnelId) {
                $q2->where('incoming_documents.created_by', $personnelId)
                    ->orWhere('incoming_documents.received_by', $personnelId)
                    ->orWhere('incoming_documents.forwarded_to_user_id', $personnelId);

                if (Schema::hasTable('incoming_document_forward_recipients')) {
                    $q2->orWhereExists(function ($sq) use ($personnelId) {
                        $sq->selectRaw('1')
                            ->from('incoming_document_forward_recipients')
                            ->whereColumn('incoming_document_forward_recipients.incoming_document_id', 'incoming_documents.id')
                            ->where('incoming_document_forward_recipients.user_id', $personnelId);
                    });
                }
            });
        }

        return $query;
    }

    private function processingBuckets($base): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $bucketExpr = "CASE
                WHEN ((julianday(CASE WHEN incoming_documents.current_status = 'ARCHIVED' THEN incoming_documents.updated_at ELSE CURRENT_TIMESTAMP END) - julianday(incoming_documents.created_at)) * 24) < 24 THEN '0-24h'
                WHEN ((julianday(CASE WHEN incoming_documents.current_status = 'ARCHIVED' THEN incoming_documents.updated_at ELSE CURRENT_TIMESTAMP END) - julianday(incoming_documents.created_at)) * 24) < 72 THEN '24-72h'
                WHEN ((julianday(CASE WHEN incoming_documents.current_status = 'ARCHIVED' THEN incoming_documents.updated_at ELSE CURRENT_TIMESTAMP END) - julianday(incoming_documents.created_at)) * 24) < 168 THEN '3-7d'
                ELSE '>7d'
            END";
        } else {
            $bucketExpr = "CASE
                WHEN TIMESTAMPDIFF(HOUR, incoming_documents.created_at, CASE WHEN incoming_documents.current_status = 'ARCHIVED' THEN incoming_documents.updated_at ELSE NOW() END) < 24 THEN '0-24h'
                WHEN TIMESTAMPDIFF(HOUR, incoming_documents.created_at, CASE WHEN incoming_documents.current_status = 'ARCHIVED' THEN incoming_documents.updated_at ELSE NOW() END) < 72 THEN '24-72h'
                WHEN TIMESTAMPDIFF(HOUR, incoming_documents.created_at, CASE WHEN incoming_documents.current_status = 'ARCHIVED' THEN incoming_documents.updated_at ELSE NOW() END) < 168 THEN '3-7d'
                ELSE '>7d'
            END";
        }

        $pairs = (clone $base)
            ->select(DB::raw($bucketExpr.' as bucket'), DB::raw('COUNT(*) as c'))
            ->groupBy('bucket')
            ->pluck('c', 'bucket')
            ->all();

        $labels = ['0-24h', '24-72h', '3-7d', '>7d'];
        $out = [];
        foreach ($labels as $label) {
            $out[$label] = (int) ($pairs[$label] ?? 0);
        }

        return $out;
    }

    private function averageProcessingHours($base): ?float
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $expr = "AVG((julianday(CASE WHEN incoming_documents.current_status = 'ARCHIVED' THEN incoming_documents.updated_at ELSE CURRENT_TIMESTAMP END) - julianday(incoming_documents.created_at)) * 24)";
        } else {
            $expr = "AVG(TIMESTAMPDIFF(SECOND, incoming_documents.created_at, CASE WHEN incoming_documents.current_status = 'ARCHIVED' THEN incoming_documents.updated_at ELSE NOW() END) / 3600)";
        }

        $value = (clone $base)->select(DB::raw($expr.' as avg_h'))->value('avg_h');
        if ($value === null) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function formatDate($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function formatDateTime($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function canViewAll(User $user): bool
    {
        if ((int) $user->id === 1) {
            return true;
        }

        if (! Schema::hasColumn('users', 'level_id')) {
            return false;
        }

        $levelId = (int) ($user->level_id ?? 0);
        if ($levelId === 0) {
            return false;
        }

        if ($levelId === 1) {
            return true;
        }

        $levelName = null;
        if (Schema::hasTable('user_level') && Schema::hasColumn('user_level', 'level_name')) {
            $levelName = DB::table('user_level')->where('id', $levelId)->value('level_name');
        }

        if (! is_string($levelName) || $levelName === '') {
            return false;
        }

        return (bool) preg_match('/ADMIN|SUPER|ROOT|SYSTEM/i', $levelName);
    }
}
