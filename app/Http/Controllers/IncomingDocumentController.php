<?php

namespace App\Http\Controllers;

use App\Models\DocumentLog;
use App\Models\DocumentSource;
use App\Models\DocumentType;
use App\Models\IncomingDocument;
use App\Models\IncomingDocumentForwardRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IncomingDocumentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = trim((string) $request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $creatorId = $request->filled('created_by') ? (int) $request->input('created_by') : null;
        $status = strtoupper(trim((string) $request->input('status', '')));
        $transactionType = $request->filled('transaction_type') ? (int) $request->input('transaction_type') : null;
        $allowedStatuses = $this->statuses();
        $allowedTransactionTypes = [1, 2];

        $documentsQuery = IncomingDocument::query()
            ->with(['source', 'type'])
            ->when(Schema::hasTable('incoming_document_forward_recipients'), function ($query) {
                $query->with(['forwardedRecipients.user:id,name']);
            })
            ->when(Schema::hasTable('incoming_document_forward_recipients') && Schema::hasColumn('incoming_document_forward_recipients', 'date_received'), function ($query) {
                $query->withCount([
                    'forwardedRecipients as total_recipients_count',
                    'forwardedRecipients as received_recipients_count' => fn ($q) => $q->whereNotNull('date_received'),
                ]);
            })
            ->when(Schema::hasColumn('incoming_documents', 'created_by'), function ($query) use ($creatorId) {
                $query->with('createdByUser');
                if ($creatorId) {
                    $query->where('created_by', $creatorId);
                }
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('document_reference_number', 'like', "%{$search}%")
                        ->orWhere('drn', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('date_received', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('date_received', '<=', $dateTo))
            ->when($status !== '' && in_array($status, $allowedStatuses, true), fn ($q) => $q->where('current_status', $status))
            ->when(
                $transactionType !== null
                    && in_array($transactionType, $allowedTransactionTypes, true)
                    && Schema::hasColumn('incoming_documents', 'transaction_type'),
                fn ($q) => $q->where('transaction_type', $transactionType)
            )
            ->orderByDesc('id');

        $documents = $documentsQuery
            ->paginate($perPage)
            ->appends([
                'per_page' => $perPage,
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'created_by' => $creatorId,
                'status' => $status,
                'transaction_type' => $transactionType,
            ]);

        if ($request->ajax()) {
            return view('incoming_documents.table', compact('documents'))->render();
        }

        $creators = Schema::hasColumn('incoming_documents', 'created_by')
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('incoming_documents.index', compact('documents', 'creators'));
    }

    public function monthlyReport(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $creatorId = $request->filled('created_by') ? (int) $request->input('created_by') : null;
        $status = strtoupper(trim((string) $request->input('status', '')));
        $transactionType = $request->filled('transaction_type') ? (int) $request->input('transaction_type') : null;

        $allowedStatuses = $this->statuses();
        $safeStatus = $status !== '' && in_array($status, $allowedStatuses, true) ? $status : '';
        $safeTransactionType = $transactionType !== null && in_array($transactionType, [1, 2], true) ? $transactionType : null;

        $query = IncomingDocument::query()
            ->with(['source', 'type'])
            ->when(Schema::hasColumn('incoming_documents', 'created_by'), function ($q) use ($creatorId) {
                $q->with('createdByUser');
                if ($creatorId) {
                    $q->where('created_by', $creatorId);
                }
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('document_reference_number', 'like', "%{$search}%")
                        ->orWhere('drn', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('date_received', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('date_received', '<=', $dateTo))
            ->when($safeStatus !== '', fn ($q) => $q->where('current_status', $safeStatus))
            ->when(
                $safeTransactionType !== null && Schema::hasColumn('incoming_documents', 'transaction_type'),
                fn ($q) => $q->where('transaction_type', $safeTransactionType)
            )
            ->orderBy('date_received')
            ->orderBy('id');

        $documents = $query->get();

        $criteria = [];
        if ($dateFrom || $dateTo) {
            $criteria[] = 'Date: '.trim(($dateFrom ?: '').' to '.($dateTo ?: ''), ' ');
        }
        if ($safeTransactionType !== null) {
            $criteria[] = 'Document Type: '.($safeTransactionType === 2 ? 'Outgoing' : 'Incoming');
        }
        if ($safeStatus !== '') {
            $criteria[] = 'Status: '.$safeStatus;
        }
        if ($creatorId) {
            $creatorName = Schema::hasColumn('incoming_documents', 'created_by')
                ? (string) (User::query()->whereKey($creatorId)->value('name') ?? '')
                : '';
            if (trim($creatorName) !== '') {
                $criteria[] = 'Created By: '.$creatorName;
            }
        }
        if ($search !== '') {
            $criteria[] = 'Search: '.$search;
        }

        $countsByTransactionType = $documents
            ->groupBy(fn ($d) => (int) ($d->transaction_type ?? 0))
            ->map(fn ($g) => $g->count())
            ->all();

        $periodLabel = null;
        $from = null;
        $to = null;
        if ($dateFrom) {
            try {
                $from = \Illuminate\Support\Carbon::parse($dateFrom);
            } catch (\Throwable $e) {
                $from = null;
            }
        }
        if ($dateTo) {
            try {
                $to = \Illuminate\Support\Carbon::parse($dateTo);
            } catch (\Throwable $e) {
                $to = null;
            }
        }
        if ($from && $to && $from->format('F Y') === $to->format('F Y')) {
            $periodLabel = $from->format('F Y');
        } elseif ($from) {
            $periodLabel = $from->format('F Y');
        } elseif ($to) {
            $periodLabel = $to->format('F Y');
        } else {
            $periodLabel = now()->format('F Y');
        }

        $countsByType = $documents
            ->groupBy(function ($d) {
                $name = '';
                if (isset($d->type) && isset($d->type->name)) {
                    $name = (string) $d->type->name;
                }
                $trimmed = trim($name);

                return $trimmed !== '' ? $trimmed : 'Unspecified';
            })
            ->map(function ($g) {
                return $g->count();
            })
            ->sortKeys()
            ->all();

        return view('incoming_documents.monthly_report', [
            'documents' => $documents,
            'generatedAt' => now(),
            'criteria' => $criteria,
            'countsByTransactionType' => $countsByTransactionType,
            'periodLabel' => $periodLabel,
            'countsByType' => $countsByType,
        ]);
    }

    public function inbox(Request $request)
    {
        if (
            ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'date_received')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_by')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $userId = (int) Auth::id();
        $usersTableAvailable = Schema::hasTable('users') && Schema::hasColumn('users', 'name');
        $search = trim((string) $request->input('search', ''));
        $sort = trim((string) $request->input('sort', 'date_forwarded'));
        $dir = strtolower(trim((string) $request->input('dir', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'document_number' => 'incoming_documents.document_reference_number',
            'drn' => 'incoming_documents.drn',
            'type' => 'document_types.name',
            'transaction_type' => 'incoming_documents.transaction_type',
            'title' => 'incoming_documents.subject',
            'origin_office' => 'document_sources.name',
            'date_forwarded' => 'incoming_documents.date_forwarded',
            'date_received' => 'incoming_document_forward_recipients.date_received',
            'received_in_behalf' => $usersTableAvailable ? 'behalf_users.name' : 'incoming_document_forward_recipients.received_in_behalf',
        ];
        $sortColumn = $sortMap[$sort] ?? $sortMap['date_forwarded'];

        $baseQuery = DB::table('incoming_documents')
            ->leftJoin('incoming_document_forward_recipients', 'incoming_documents.id', '=', 'incoming_document_forward_recipients.incoming_document_id')
            ->leftJoin('document_sources', 'incoming_documents.document_source_id', '=', 'document_sources.id')
            ->leftJoin('document_types', 'incoming_documents.document_type_id', '=', 'document_types.id')
            ->when($usersTableAvailable, function ($q) {
                $q->leftJoin('users as behalf_users', 'incoming_document_forward_recipients.received_in_behalf', '=', 'behalf_users.id');
            })
            ->where('incoming_document_forward_recipients.user_id', $userId)
            // ->whereNull('incoming_document_forward_recipients.date_received')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('incoming_documents.document_reference_number', 'like', "%{$search}%")
                        ->orWhere('incoming_documents.drn', 'like', "%{$search}%")
                        ->orWhere('incoming_documents.subject', 'like', "%{$search}%")
                        ->orWhere('document_sources.name', 'like', "%{$search}%");
                });
            });

        $select = [
            'incoming_document_forward_recipients.id as recipient_id',
            'incoming_documents.id as document_id',
            'incoming_documents.document_reference_number as document_number',
            'incoming_documents.drn as drn',
            'document_types.name as type',
            Schema::hasColumn('incoming_documents', 'transaction_type')
                ? 'incoming_documents.transaction_type as transaction_type'
                : DB::raw('NULL as transaction_type'),
            'incoming_documents.subject as title',
            'document_sources.name as origin_office',
            'incoming_documents.date_forwarded as date_forwarded',
            'incoming_document_forward_recipients.date_received as date_received',
            'incoming_document_forward_recipients.received_in_behalf as received_in_behalf',
        ];
        if ($usersTableAvailable) {
            $select[] = 'behalf_users.name as received_in_behalf_name';
        }

        if ((string) $request->input('export') === 'csv') {
            $rows = (clone $baseQuery)
                ->select($select)
                ->orderBy($sortColumn, $dir)
                ->orderByDesc('incoming_documents.id')
                ->get();

            $filename = 'inbox_'.now()->format('Ymd_His').'.csv';

            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Document Number', 'DRN', 'Type', 'Transaction Type', 'Title', 'Origin Office', 'Date Forwarded', 'Date Received', 'Received In Behalf']);

                foreach ($rows as $row) {
                    $txId = (int) ($row->transaction_type ?? 0);
                    $txLabel = $txId === 2 ? 'OUTGOING' : ($txId === 1 ? 'INCOMING' : '');
                    fputcsv($handle, [
                        (string) ($row->document_number ?? ''),
                        (string) ($row->drn ?? ''),
                        (string) ($row->type ?? ''),
                        $txLabel,
                        (string) ($row->title ?? ''),
                        (string) ($row->origin_office ?? ''),
                        (string) ($row->date_forwarded ?? ''),
                        (string) ($row->date_received ?? ''),
                        (string) ($usersTableAvailable ? ($row->received_in_behalf_name ?? '') : ($row->received_in_behalf ?? '')),
                    ]);
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        }

        $inboxRows = (clone $baseQuery)
            ->select($select)
            ->orderBy($sortColumn, $dir)
            ->orderByDesc('incoming_documents.id')
            ->paginate(25)
            ->appends([
                'search' => $search,
                'sort' => $sort,
                'dir' => $dir,
            ]);

        return view('inbox.index', [
            'rows' => $inboxRows,
            'search' => $search,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function inboxBatch(Request $request)
    {
        if (
            ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'date_received')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_by')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $authUserId = (int) Auth::id();
        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : $authUserId;
        if (
            $userId !== $authUserId
            && Schema::hasTable('users')
            && ! User::query()->whereKey($userId)->exists()
        ) {
            $userId = $authUserId;
        }

        $search = trim((string) $request->input('search', ''));
        $typeId = $request->filled('document_type_id') ? (int) $request->input('document_type_id') : null;
        $perPage = (int) $request->input('per_page', 25);

        $sort = trim((string) $request->input('sort', 'date_forwarded'));
        $dir = strtolower(trim((string) $request->input('dir', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'document_number' => 'incoming_documents.document_reference_number',
            'drn' => 'incoming_documents.drn',
            'type' => 'document_types.name',
            'transaction_type' => 'incoming_documents.transaction_type',
            'title' => 'incoming_documents.subject',
            'origin_office' => 'document_sources.name',
            'date_forwarded' => 'incoming_documents.date_forwarded',
        ];
        $sortColumn = $sortMap[$sort] ?? $sortMap['date_forwarded'];

        $baseQuery = DB::table('incoming_document_forward_recipients')
            ->leftJoin('incoming_documents', 'incoming_document_forward_recipients.incoming_document_id', '=', 'incoming_documents.id')
            ->leftJoin('document_sources', 'incoming_documents.document_source_id', '=', 'document_sources.id')
            ->leftJoin('document_types', 'incoming_documents.document_type_id', '=', 'document_types.id')
            ->leftJoin('users', 'incoming_document_forward_recipients.user_id', '=', 'users.id')
            ->where('incoming_document_forward_recipients.user_id', $userId)
            ->whereNull('incoming_document_forward_recipients.date_received')
            ->when($typeId, fn ($q) => $q->where('incoming_documents.document_type_id', $typeId))
            ->when(Schema::hasColumn('incoming_document_forward_recipients', 'batch_id'), fn ($q) => $q->whereNull('incoming_document_forward_recipients.batch_id'))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('incoming_documents.document_reference_number', 'like', "%{$search}%")
                        ->orWhere('incoming_documents.drn', 'like', "%{$search}%")
                        ->orWhere('incoming_documents.subject', 'like', "%{$search}%")
                        ->orWhere('document_sources.name', 'like', "%{$search}%");
                });
            });

        $rows = (clone $baseQuery)
            ->select([
                'incoming_document_forward_recipients.id as recipient_id',
                'incoming_documents.id as document_id',
                'incoming_documents.document_reference_number as document_number',
                'incoming_documents.drn as drn',
                'document_types.name as type',
                Schema::hasColumn('incoming_documents', 'transaction_type')
                    ? 'incoming_documents.transaction_type as transaction_type'
                    : DB::raw('NULL as transaction_type'),
                'incoming_documents.subject as title',
                'document_sources.name as origin_office',
                'incoming_documents.date_forwarded as date_forwarded',
                'users.name as recipient_name',
            ])
            ->orderBy($sortColumn, $dir)
            ->orderByDesc('incoming_documents.id')
            ->paginate($perPage)
            ->appends([
                'user_id' => $userId,
                'search' => $search,
                'document_type_id' => $typeId,
                'per_page' => $perPage,
                'sort' => $sort,
                'dir' => $dir,
            ]);

        $users = collect();
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
            $usersQuery = User::query()
                ->where('id', '!=', $authUserId)
                ->when(Schema::hasColumn('users', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->when(Schema::hasColumn('users', 'is_active'), fn ($q) => $q->where('is_active', 1))
                ->when(Schema::hasColumn('users', 'status'), fn ($q) => $q->where('status', 1))
                ->orderBy('name');

            $users = $usersQuery->get(['id', 'name']);
        }
        $types = DocumentType::query()->orderBy('name')->get(['id', 'name']);

        return view('inbox.batch_receive', [
            'rows' => $rows,
            'users' => $users,
            'types' => $types,
            'userId' => $userId,
            'authUserId' => $authUserId,
            'search' => $search,
            'typeId' => $typeId,
            'perPage' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function inboxBatchCreate(Request $request)
    {
        if (
            ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'date_received')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_by')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'batch_id')
            || ! Schema::hasTable('batch_received')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $authUserId = (int) Auth::id();
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'recipient_ids' => ['required', 'array', 'min:1', 'max:200'],
            'recipient_ids.*' => ['integer'],
        ]);

        $recipientIds = array_values(array_unique(array_map('intval', (array) $validated['recipient_ids'])));
        $targetUserId = array_key_exists('user_id', $validated) && $validated['user_id'] !== null
            ? (int) $validated['user_id']
            : $authUserId;

        if (
            $targetUserId !== $authUserId
            && Schema::hasTable('users')
            && ! User::query()->whereKey($targetUserId)->exists()
        ) {
            throw ValidationException::withMessages([
                'user_id' => ['Recipient user not found.'],
            ]);
        }

        $targetUserName = $targetUserId === $authUserId
            ? (string) (optional(Auth::user())->name ?? '')
            : (string) (User::query()->whereKey($targetUserId)->value('name') ?? '');

        $batchStaffName = trim($targetUserId.' - '.$targetUserName);
        $createdAt = now();
        $batchId = 0;

        DB::beginTransaction();
        try {
            $recipients = IncomingDocumentForwardRecipient::query()
                ->whereIn('id', $recipientIds)
                ->lockForUpdate()
                ->get(['id', 'user_id', 'date_received', 'batch_id']);

            if ($recipients->count() !== count($recipientIds)) {
                throw ValidationException::withMessages([
                    'recipient_ids' => ['One or more selected rows were not found. Please refresh and try again.'],
                ]);
            }

            $invalid = $recipients->first(function ($r) use ($targetUserId) {
                return (int) $r->user_id !== $targetUserId || $r->date_received !== null || $r->batch_id !== null;
            });
            if ($invalid) {
                throw ValidationException::withMessages([
                    'recipient_ids' => ['One or more selected rows are no longer eligible for batching. Please refresh and try again.'],
                ]);
            }

            $batchId = (int) DB::table('batch_received')->insertGetId([
                'batch_staff_name' => $batchStaffName,
                'created_by' => $authUserId,
                'date_created' => $createdAt,
            ]);

            $updated = DB::table('incoming_document_forward_recipients')
                ->whereIn('id', $recipientIds)
                ->where('user_id', $targetUserId)
                ->whereNull('date_received')
                ->whereNull('batch_id')
                ->update([
                    'batch_id' => $batchId,
                    'updated_at' => $createdAt,
                ]);

            if ($updated !== count($recipientIds)) {
                throw ValidationException::withMessages([
                    'recipient_ids' => ['Failed to update all selected rows. Please refresh and try again.'],
                ]);
            }

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'batch_id' => $batchId,
                'updated' => count($recipientIds),
                'date_created' => $createdAt->toDateTimeString(),
            ]);
        }

        return redirect()
            ->route('inbox.batch')
            ->with('success', 'Batch created successfully.');
    }

    public function inboxBatchReceivedList(Request $request)
    {
        if (
            ! Schema::hasTable('batch_received')
            || ! Schema::hasColumn('batch_received', 'status')
            || ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'batch_id')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $search = trim((string) $request->input('search', ''));
        $status = $request->filled('status') ? (int) $request->input('status') : null;
        $perPage = (int) $request->input('per_page', 10);

        $sort = trim((string) $request->input('sort', 'date_created'));
        $dir = strtolower(trim((string) $request->input('dir', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'batch_id' => 'batch_received.id',
            'batch_name' => 'batch_received.batch_staff_name',
            'status' => 'batch_received.status',
            'date_created' => 'batch_received.date_created',
            'document_number' => 'first_document_number',
            'drn' => 'first_drn',
            'type' => 'first_type',
            'subject' => 'first_subject',
            'origin_office' => 'first_origin_office',
        ];
        $sortColumn = $sortMap[$sort] ?? $sortMap['date_created'];

        $baseQuery = DB::table('batch_received')
            ->leftJoin('incoming_document_forward_recipients', 'incoming_document_forward_recipients.batch_id', '=', 'batch_received.id')
            ->leftJoin('incoming_documents', 'incoming_document_forward_recipients.incoming_document_id', '=', 'incoming_documents.id')
            ->leftJoin('document_types', 'incoming_documents.document_type_id', '=', 'document_types.id')
            ->leftJoin('document_sources', 'incoming_documents.document_source_id', '=', 'document_sources.id')
            ->when($status !== null && in_array($status, [0, 1], true), fn ($q) => $q->where('batch_received.status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('batch_received.id', 'like', "%{$search}%")
                        ->orWhere('batch_received.batch_staff_name', 'like', "%{$search}%")
                        ->orWhere('incoming_documents.document_reference_number', 'like', "%{$search}%")
                        ->orWhere('incoming_documents.drn', 'like', "%{$search}%")
                        ->orWhere('incoming_documents.subject', 'like', "%{$search}%")
                        ->orWhere('document_types.name', 'like', "%{$search}%")
                        ->orWhere('document_sources.name', 'like', "%{$search}%");
                });
            });

        $rows = $baseQuery
            ->groupBy([
                'batch_received.id',
                'batch_received.batch_staff_name',
                'batch_received.status',
                'batch_received.date_created',
            ])
            ->select([
                'batch_received.id as batch_id',
                'batch_received.batch_staff_name as batch_name',
                'batch_received.status as batch_status',
                'batch_received.date_created as batch_date_created',
                DB::raw('COUNT(DISTINCT incoming_document_forward_recipients.incoming_document_id) as documents_count'),
                DB::raw('MIN(incoming_documents.document_reference_number) as first_document_number'),
                DB::raw('MIN(incoming_documents.drn) as first_drn'),
                DB::raw('MIN(document_types.name) as first_type'),
                DB::raw('MIN(incoming_documents.subject) as first_subject'),
                DB::raw('MIN(document_sources.name) as first_origin_office'),
            ])
            ->orderBy($sortColumn, $dir)
            ->orderByDesc('batch_received.id')
            ->paginate($perPage)
            ->appends([
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
                'sort' => $sort,
                'dir' => $dir,
            ]);

        $batchIds = $rows->getCollection()->pluck('batch_id')->map(fn ($v) => (int) $v)->all();
        $docsByBatch = [];
        if (count($batchIds) > 0) {
            $docs = DB::table('incoming_document_forward_recipients')
                ->leftJoin('incoming_documents', 'incoming_document_forward_recipients.incoming_document_id', '=', 'incoming_documents.id')
                ->leftJoin('document_types', 'incoming_documents.document_type_id', '=', 'document_types.id')
                ->leftJoin('document_sources', 'incoming_documents.document_source_id', '=', 'document_sources.id')
                ->leftJoin('users as received_by_user', 'incoming_document_forward_recipients.received_by', '=', 'received_by_user.id')
                ->whereIn('incoming_document_forward_recipients.batch_id', $batchIds)
                ->select([
                    'incoming_document_forward_recipients.batch_id as batch_id',
                    'incoming_documents.id as incoming_document_id',
                    'incoming_documents.document_reference_number as document_number',
                    'incoming_documents.drn as drn',
                    'document_types.name as type',
                    Schema::hasColumn('incoming_documents', 'transaction_type')
                        ? 'incoming_documents.transaction_type as transaction_type'
                        : DB::raw('NULL as transaction_type'),
                    'incoming_documents.subject as subject',
                    'document_sources.name as origin_office',
                    'incoming_documents.current_status as document_status',
                    'incoming_documents.date_forwarded as date_forwarded',
                    'incoming_document_forward_recipients.date_received as date_received',
                    'received_by_user.name as received_by_name',
                ])
                ->orderBy('incoming_documents.document_reference_number')
                ->orderBy('incoming_documents.id')
                ->get();

            $seenDocIdsByBatch = [];
            foreach ($docs as $d) {
                $b = (int) ($d->batch_id ?? 0);
                if (! isset($docsByBatch[$b])) {
                    $docsByBatch[$b] = [];
                }
                if (! isset($seenDocIdsByBatch[$b])) {
                    $seenDocIdsByBatch[$b] = [];
                }

                $docId = (int) ($d->incoming_document_id ?? 0);
                if ($docId > 0 && isset($seenDocIdsByBatch[$b][$docId])) {
                    continue;
                }

                if ($docId > 0) {
                    $seenDocIdsByBatch[$b][$docId] = true;
                }

                $docsByBatch[$b][] = $d;
            }
        }

        $rows->setCollection($rows->getCollection()->map(function ($row) use ($docsByBatch) {
            $batchId = (int) ($row->batch_id ?? 0);
            $row->documents = $docsByBatch[$batchId] ?? [];

            return $row;
        }));

        return view('inbox.batch_received_list', [
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
            'perPage' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
            'pinEnabled' => Schema::hasTable('users')
                && Schema::hasColumn('users', 'pin_hash')
                && Schema::hasColumn('users', 'pin_fingerprint')
                && Schema::hasColumn('users', 'pin_failed_attempts')
                && Schema::hasColumn('users', 'pin_locked_until')
                && Schema::hasTable('batch_received_audits'),
        ]);
    }

    public function inboxBatchDocuments(Request $request, int $batch)
    {
        if (
            ! Schema::hasTable('batch_received')
            || ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'batch_id')
            || ! Schema::hasTable('incoming_documents')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $exists = DB::table('batch_received')->where('id', $batch)->exists();
        if (! $exists) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found.',
            ], 404);
        }

        $select = [
            'incoming_documents.id as incoming_document_id',
            'incoming_documents.document_reference_number as document_number',
            'incoming_documents.drn as drn',
            'document_types.name as type',
            Schema::hasColumn('incoming_documents', 'transaction_type')
                ? 'incoming_documents.transaction_type as transaction_type'
                : DB::raw('NULL as transaction_type'),
            'incoming_documents.subject as subject',
            'document_sources.name as origin_office',
        ];

        $docs = DB::table('incoming_document_forward_recipients')
            ->leftJoin('incoming_documents', 'incoming_document_forward_recipients.incoming_document_id', '=', 'incoming_documents.id')
            ->leftJoin('document_types', 'incoming_documents.document_type_id', '=', 'document_types.id')
            ->leftJoin('document_sources', 'incoming_documents.document_source_id', '=', 'document_sources.id')
            ->where('incoming_document_forward_recipients.batch_id', $batch)
            ->select($select)
            ->orderBy('incoming_documents.document_reference_number')
            ->orderBy('incoming_documents.id')
            ->get();

        $seen = [];
        $out = [];
        foreach ($docs as $d) {
            $docId = (int) ($d->incoming_document_id ?? 0);
            if ($docId > 0 && isset($seen[$docId])) {
                continue;
            }
            if ($docId > 0) {
                $seen[$docId] = true;
            }
            $out[] = [
                'id' => $docId,
                'document_number' => (string) ($d->document_number ?? ''),
                'drn' => (string) ($d->drn ?? ''),
                'type' => (string) ($d->type ?? ''),
                'transaction_type' => (int) ($d->transaction_type ?? 0),
                'subject' => (string) ($d->subject ?? ''),
                'origin_office' => (string) ($d->origin_office ?? ''),
            ];
        }

        return response()->json([
            'success' => true,
            'batch_id' => $batch,
            'documents' => $out,
        ]);
    }

    public function inboxBatchPinStatus(Request $request)
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'pin_hash')
            || ! Schema::hasColumn('users', 'pin_failed_attempts')
            || ! Schema::hasColumn('users', 'pin_locked_until')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $validated = $request->validate([
            'batch_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $authUserId = (int) Auth::id();
        if (array_key_exists('batch_id', $validated) && $validated['batch_id'] !== null) {
            $batchId = (int) $validated['batch_id'];
            $targetUserId = $this->resolveBatchRecipientUserId($batchId);
            if (array_key_exists('user_id', $validated) && $validated['user_id'] !== null && (int) $validated['user_id'] !== $targetUserId) {
                throw ValidationException::withMessages([
                    'user_id' => ['Recipient user does not match this batch.'],
                ]);
            }
        } else {
            $targetUserId = array_key_exists('user_id', $validated) && $validated['user_id'] !== null
                ? (int) $validated['user_id']
                : $authUserId;
        }

        $user = User::query()->whereKey($targetUserId)->firstOrFail([
            'id',
            'pin_hash',
            'pin_failed_attempts',
            'pin_locked_until',
        ]);

        $lockedUntil = $user->pin_locked_until ? \Carbon\Carbon::parse($user->pin_locked_until) : null;
        $isLocked = $lockedUntil !== null && now()->lt($lockedUntil);

        return response()->json([
            'success' => true,
            'user_id' => $targetUserId,
            'has_pin' => (string) ($user->pin_hash ?? '') !== '',
            'failed_attempts' => (int) ($user->pin_failed_attempts ?? 0),
            'locked_until' => $lockedUntil ? $lockedUntil->toIso8601String() : null,
            'locked_seconds' => $isLocked ? max(0, now()->diffInSeconds($lockedUntil, false)) : 0,
        ]);
    }

    public function inboxBatchPinCreate(Request $request)
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'pin_hash')
            || ! Schema::hasColumn('users', 'pin_fingerprint')
            || ! Schema::hasColumn('users', 'pin_failed_attempts')
            || ! Schema::hasColumn('users', 'pin_locked_until')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $validated = $request->validate([
            'batch_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'pin' => ['required', 'digits:4'],
            'pin_confirm' => ['required', 'digits:4', 'same:pin'],
        ]);

        $authUserId = (int) Auth::id();
        if (array_key_exists('batch_id', $validated) && $validated['batch_id'] !== null) {
            $batchId = (int) $validated['batch_id'];
            $targetUserId = $this->resolveBatchRecipientUserId($batchId);
            if (array_key_exists('user_id', $validated) && $validated['user_id'] !== null && (int) $validated['user_id'] !== $targetUserId) {
                throw ValidationException::withMessages([
                    'user_id' => ['Recipient user does not match this batch.'],
                ]);
            }
        } else {
            $targetUserId = array_key_exists('user_id', $validated) && $validated['user_id'] !== null
                ? (int) $validated['user_id']
                : $authUserId;
        }

        if ($targetUserId !== $authUserId && ! $this->canManageUsers()) {
            throw ValidationException::withMessages([
                'user_id' => ['Not authorized to set PIN for this user.'],
            ]);
        }

        if (
            $targetUserId !== $authUserId
            && Schema::hasTable('users')
            && ! User::query()->whereKey($targetUserId)->exists()
        ) {
            throw ValidationException::withMessages([
                'user_id' => ['Recipient user not found.'],
            ]);
        }

        $pin = (string) $validated['pin'];
        $fingerprint = hash_hmac('sha256', $pin, (string) config('app.key'));

        $user = User::query()->whereKey($targetUserId)->firstOrFail();
        $user->pin_hash = Hash::make($pin, ['rounds' => 10]);
        $user->pin_fingerprint = $fingerprint;
        $user->pin_failed_attempts = 0;
        $user->pin_locked_until = null;
        $user->save();

        return response()->json([
            'success' => true,
            'user_id' => $targetUserId,
            'pin' => $pin,
        ]);
    }

    public function inboxBatchPinReset(Request $request)
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'pin_hash')
            || ! Schema::hasColumn('users', 'pin_failed_attempts')
            || ! Schema::hasColumn('users', 'pin_locked_until')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $validated = $request->validate([
            'batch_id' => ['required', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'received_in_behalf' => ['nullable', 'boolean'],
            'received_in_behalf_user_id' => ['nullable', 'integer'],
        ]);

        $authUserId = (int) Auth::id();
        $batchId = (int) $validated['batch_id'];
        $receivedInBehalfEnabled = $request->boolean('received_in_behalf');
        $targetUserId = 0;
        if (! $receivedInBehalfEnabled) {
            try {
                $targetUserId = $this->resolveBatchRecipientUserId($batchId);
            } catch (ValidationException $e) {
                $fallbackUserId = array_key_exists('user_id', $validated) && $validated['user_id'] !== null
                    ? (int) $validated['user_id']
                    : 0;

                if ($fallbackUserId <= 0) {
                    throw $e;
                }

                $existsInBatch = DB::table('incoming_document_forward_recipients')
                    ->where('batch_id', $batchId)
                    ->where('user_id', $fallbackUserId)
                    ->exists();
                if (! $existsInBatch) {
                    throw $e;
                }

                $targetUserId = $fallbackUserId;
            }
        } else {
            $targetUserId = $this->resolveBatchRecipientUserId($batchId);
        }

        if (
            array_key_exists('user_id', $validated)
            && $validated['user_id'] !== null
            && ! $receivedInBehalfEnabled
            && (int) $validated['user_id'] !== $targetUserId
        ) {
            throw ValidationException::withMessages([
                'user_id' => ['Recipient user does not match this batch.'],
            ]);
        }

        if ($receivedInBehalfEnabled) {
            $targetUserId = (int) ($validated['received_in_behalf_user_id'] ?? 0);
            if ($targetUserId <= 0) {
                throw ValidationException::withMessages([
                    'received_in_behalf_user_id' => ['Please select staff to reset.'],
                ]);
            }
        }

        if ($targetUserId !== $authUserId && ! $this->canManageUsers()) {
            throw ValidationException::withMessages([
                'user_id' => ['Not authorized to reset PIN for this user.'],
            ]);
        }

        if (! User::query()->whereKey($targetUserId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['User not found.'],
            ]);
        }

        $updates = [
            'pin_hash' => null,
            'pin_failed_attempts' => 0,
            'pin_locked_until' => null,
        ];
        if (Schema::hasColumn('users', 'pin_fingerprint')) {
            $updates['pin_fingerprint'] = null;
        }

        User::query()->whereKey($targetUserId)->update($updates);

        return response()->json([
            'success' => true,
            'user_id' => $targetUserId,
        ]);
    }

    public function inboxPinCreate(Request $request)
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'pin_hash')
            || ! Schema::hasColumn('users', 'pin_failed_attempts')
            || ! Schema::hasColumn('users', 'pin_locked_until')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'pin' => ['required', 'digits:4'],
            'pin_confirm' => ['required', 'digits:4', 'same:pin'],
        ]);

        $authUserId = (int) Auth::id();
        $targetUserId = (int) $validated['user_id'];

        if ($targetUserId !== $authUserId && ! $this->canManageUsers()) {
            throw ValidationException::withMessages([
                'user_id' => ['Not authorized to set PIN for this user.'],
            ]);
        }

        $user = User::query()->whereKey($targetUserId)->firstOrFail();

        $pin = (string) $validated['pin'];
        $user->pin_hash = Hash::make($pin, ['rounds' => 10]);
        if (Schema::hasColumn('users', 'pin_fingerprint')) {
            $user->pin_fingerprint = hash_hmac('sha256', $pin, (string) config('app.key'));
        }
        $user->pin_failed_attempts = 0;
        $user->pin_locked_until = null;
        $user->save();

        return response()->json([
            'success' => true,
            'user_id' => $targetUserId,
        ]);
    }

    public function inboxBatchReceiveWithPin(Request $request, int $batch)
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'pin_hash')
            || ! Schema::hasColumn('users', 'pin_failed_attempts')
            || ! Schema::hasColumn('users', 'pin_locked_until')
            || ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'batch_id')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'user_id')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'date_received')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_by')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')
            || ! Schema::hasTable('batch_received')
            || ! Schema::hasColumn('batch_received', 'status')
            || ! Schema::hasTable('batch_received_audits')
            || ! Schema::hasTable('incoming_documents')
            || ! Schema::hasColumn('incoming_documents', 'current_status')
            || ! Schema::hasColumn('incoming_documents', 'received_by')
            || ! Schema::hasColumn('incoming_documents', 'document_type_id')
            || ! Schema::hasTable('document_logs')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'pin' => ['required', 'digits:4'],
            'received_in_behalf' => ['nullable', 'boolean'],
            'received_in_behalf_user_id' => ['nullable', 'integer'],
        ]);

        $authUserId = (int) Auth::id();
        $targetUserId = $this->resolveBatchRecipientUserId($batch);
        if (array_key_exists('user_id', $validated) && $validated['user_id'] !== null && (int) $validated['user_id'] !== $targetUserId) {
            throw ValidationException::withMessages([
                'user_id' => ['Recipient user does not match this batch.'],
            ]);
        }

        $pin = (string) $validated['pin'];

        $receivedInBehalfEnabled = $request->boolean('received_in_behalf');
        $receivedInBehalfUserId = null;
        if ($receivedInBehalfEnabled) {
            $receivedInBehalfUserId = (int) ($validated['received_in_behalf_user_id'] ?? 0);
            if ($receivedInBehalfUserId <= 0) {
                throw ValidationException::withMessages([
                    'received_in_behalf_user_id' => ['Please select staff to receive.'],
                ]);
            }
            if ($receivedInBehalfUserId === $targetUserId) {
                throw ValidationException::withMessages([
                    'received_in_behalf_user_id' => ['Please select a different user.'],
                ]);
            }

            $exists = DB::table('users')
                ->where('id', $receivedInBehalfUserId)
                ->when(Schema::hasColumn('users', 'is_status'), function ($q) {
                    $q->where(function ($w) {
                        $w->whereNull('is_status')->orWhere('is_status', 1);
                    });
                })
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'received_in_behalf_user_id' => ['Selected staff is not available.'],
                ]);
            }
        }

        $pinOwnerUserId = $receivedInBehalfEnabled ? (int) $receivedInBehalfUserId : $targetUserId;
        $pinUserSelect = [
            'id',
            'name',
            'pin_hash',
            'pin_failed_attempts',
            'pin_locked_until',
        ];
        if (Schema::hasColumn('users', 'pin_fingerprint')) {
            $pinUserSelect[] = 'pin_fingerprint';
        }

        $pinUser = User::query()->whereKey($pinOwnerUserId)->firstOrFail($pinUserSelect);

        if ((string) ($pinUser->pin_hash ?? '') === '') {
            if ($receivedInBehalfEnabled) {
                if ($pinOwnerUserId !== $authUserId && ! $this->canManageUsers()) {
                    throw ValidationException::withMessages([
                        'received_in_behalf_user_id' => ['Selected staff has no PIN set.'],
                    ]);
                }

                $fingerprint = null;
                if (Schema::hasColumn('users', 'pin_fingerprint')) {
                    $fingerprint = hash_hmac('sha256', $pin, (string) config('app.key'));
                }

                $pinUser->pin_hash = Hash::make($pin, ['rounds' => 10]);
                if ($fingerprint !== null) {
                    $pinUser->pin_fingerprint = $fingerprint;
                }
                $pinUser->pin_failed_attempts = 0;
                $pinUser->pin_locked_until = null;
                $pinUser->save();
            } else {
                throw ValidationException::withMessages([
                    'pin' => ['PIN is not set for this account.'],
                ]);
            }
        }

        $lockedUntil = $pinUser->pin_locked_until ? \Carbon\Carbon::parse($pinUser->pin_locked_until) : null;
        if ($lockedUntil !== null && now()->lt($lockedUntil)) {
            return response()->json([
                'success' => false,
                'locked_until' => $lockedUntil->toIso8601String(),
                'locked_seconds' => max(0, now()->diffInSeconds($lockedUntil, false)),
                'message' => 'Too many failed attempts. Try again later.',
            ], 423);
        }

        $ok = Hash::check($pin, (string) $pinUser->pin_hash);
        if (! $ok) {
            $failedAttempts = (int) ($pinUser->pin_failed_attempts ?? 0) + 1;
            $pinUser->pin_failed_attempts = $failedAttempts;
            if ($failedAttempts >= 3) {
                $lockedUntil = now()->addSeconds(30);
                $pinUser->pin_locked_until = $lockedUntil;
            }
            $pinUser->save();

            if ($failedAttempts >= 3) {
                return response()->json([
                    'success' => false,
                    'locked_until' => $lockedUntil ? $lockedUntil->toIso8601String() : null,
                    'locked_seconds' => $lockedUntil ? max(0, now()->diffInSeconds($lockedUntil, false)) : 30,
                    'message' => 'Too many failed attempts. Try again later.',
                ], 423);
            }

            return response()->json([
                'success' => false,
                'failed_attempts' => $failedAttempts,
                'attempts_left' => max(0, 3 - $failedAttempts),
                'message' => 'Incorrect PIN.',
            ], 422);
        }

        $pinUser->pin_failed_attempts = 0;
        $pinUser->pin_locked_until = null;
        $pinUser->save();

        DB::beginTransaction();
        try {
            $receivedAt = now();
            $actorUserId = $authUserId;
            $recipientUserId = $targetUserId;
            $recipientUserName = (string) (User::query()->whereKey($recipientUserId)->value('name') ?? '');
            $actorUserName = (string) (optional(Auth::user())->name ?? '');

            $batchRow = DB::table('batch_received')->where('id', $batch)->lockForUpdate()->first(['id', 'status']);
            if (! $batchRow) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found.',
                ], 404);
            }

            $recipientRows = DB::table('incoming_document_forward_recipients')
                ->where('batch_id', $batch)
                ->where('user_id', $targetUserId)
                ->lockForUpdate()
                ->get(['id', 'incoming_document_id', 'date_received']);
            if ($recipientRows->count() <= 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found.',
                ], 404);
            }
            $behalfUpdate = [
                'received_in_behalf' => $receivedInBehalfUserId,
                'updated_at' => $receivedAt,
            ];
            DB::table('incoming_document_forward_recipients')
                ->where('batch_id', $batch)
                ->where('user_id', $targetUserId)
                ->update($behalfUpdate);

            $alreadyReceived = (int) ($batchRow->status ?? 0) === 1;
            $unreceivedRows = $recipientRows->filter(fn ($r) => $r->date_received === null);
            $unreceivedRecipientIds = $unreceivedRows->pluck('id')->map(fn ($v) => (int) $v)->all();
            $docIds = $unreceivedRows
                ->pluck('incoming_document_id')
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values()
                ->all();
            if (count($docIds) > 0) {
                if (count($docIds) !== $unreceivedRows->count()) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'One or more batch items are missing document references.',
                    ], 422);
                }

                $hasDeletedAt = Schema::hasColumn('incoming_documents', 'deleted_at');
                $docs = IncomingDocument::query()
                    ->with(['type:id,name'])
                    ->whereIn('id', $docIds)
                    ->when($hasDeletedAt, fn ($q) => $q->whereNull('deleted_at'))
                    ->lockForUpdate()
                    ->get();

                if ($docs->count() !== count($docIds)) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'One or more documents in this batch are missing or archived.',
                    ], 422);
                }

                $recipientUpdate = [
                    'date_received' => $receivedAt,
                    'received_by' => $recipientUserId,
                    'updated_at' => $receivedAt,
                ];

                DB::table('incoming_document_forward_recipients')
                    ->whereIn('id', $unreceivedRecipientIds)
                    ->update($recipientUpdate);

                $docUpdates = $docs
                    ->filter(fn ($d) => (string) ($d->current_status ?? '') !== 'RECEIVED')
                    ->pluck('id')
                    ->map(fn ($v) => (int) $v)
                    ->all();

                if (! empty($docUpdates)) {
                    IncomingDocument::query()
                        ->whereIn('id', $docUpdates)
                        ->update([
                            'current_status' => 'RECEIVED',
                            'received_by' => $recipientUserId,
                            'updated_at' => $receivedAt,
                        ]);
                }

                $remarksRecipientName = trim($recipientUserName) !== '' ? mb_strtoupper(trim($recipientUserName), 'UTF-8') : null;
                $logRows = [];
                foreach ($docs as $doc) {
                    $fromStatus = (string) ($doc->current_status ?? '');
                    $documentTypeName = (string) (optional($doc->type)->name ?? '');

                    $logRows[] = [
                        'incoming_document_id' => (int) $doc->id,
                        'user_id' => $actorUserId,
                        'action_type' => 'INBOX_RECEIVED',
                        'action_timestamp' => $receivedAt,
                        'status_from' => $fromStatus !== '' ? $fromStatus : null,
                        'status_to' => 'INBOX_RECEIVED',
                        'related_user_id' => $recipientUserId,
                        'related_source_id' => null,
                        'remarks' => json_encode([
                            'kind' => 'inbox_received_v1',
                            'batch_id' => $batch,
                            'recipient_user_id' => $recipientUserId,
                            'recipient_user_name' => $remarksRecipientName,
                            'document_type_id' => $doc->document_type_id,
                            'document_type_name' => $documentTypeName !== '' ? mb_strtoupper($documentTypeName, 'UTF-8') : null,
                            'forwarded_to_user_id' => $doc->forwarded_to_user_id,
                            'forwarded_to_group_id' => $doc->forwarded_to_group_id,
                            'forwarded_to_source_id' => $doc->forwarded_to_source_id,
                            'date_forwarded' => optional($doc->date_forwarded)->toIso8601String(),
                            'processing_status' => 'INBOX_RECEIVED',
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'created_at' => $receivedAt,
                        'updated_at' => $receivedAt,
                    ];
                }

                if ($logRows !== []) {
                    DB::table('document_logs')->insert($logRows);
                }
            }

            $didReceiveNow = ! $alreadyReceived || count($unreceivedRecipientIds) > 0;
            if ($didReceiveNow && ! $alreadyReceived) {
                DB::table('batch_received')->where('id', $batch)->update(['status' => 1]);
            }

            if ($didReceiveNow) {
                DB::table('batch_received_audits')->insert([
                    'batch_id' => $batch,
                    'user_id' => $pinOwnerUserId,
                    'received_at' => $receivedAt,
                    'ip_address' => (string) $request->ip(),
                    'created_at' => $receivedAt,
                    'updated_at' => $receivedAt,
                ]);
            }

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        return response()->json([
            'success' => true,
            'batch_id' => $batch,
            'message' => 'Batch received successfully.',
        ]);
    }

    public function inboxReceive(Request $request, IncomingDocumentForwardRecipient $recipient)
    {
        if (
            ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'date_received')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_by')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $userId = (int) Auth::id();
        if ((int) $recipient->user_id !== $userId) {
            abort(403);
        }

        $request->validate([
            'received_in_behalf' => ['nullable', 'string', 'max:100'],
        ]);

        DB::beginTransaction();
        try {
            $freshRecipient = IncomingDocumentForwardRecipient::query()
                ->whereKey($recipient->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $freshRecipient->user_id !== $userId) {
                abort(403);
            }

            if ($freshRecipient->date_received) {
                DB::commit();
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'already_received' => true,
                        'date_received' => optional($freshRecipient->date_received)->toDateTimeString(),
                    ]);
                }

                return redirect()->route('inbox.index')->with('success', 'Already marked as received.');
            }

            $receivedInBehalfRaw = trim((string) $request->input('received_in_behalf', ''));
            $receivedInBehalf = $receivedInBehalfRaw !== '' ? mb_strtoupper($receivedInBehalfRaw, 'UTF-8') : null;
            $receivedAt = now();

            $freshRecipient->update([
                'date_received' => $receivedAt,
                'received_by' => $userId,
                'received_in_behalf' => $receivedInBehalf,
            ]);

            $incomingDocument = IncomingDocument::query()
                ->whereKey($freshRecipient->incoming_document_id)
                ->lockForUpdate()
                ->firstOrFail();

            $incomingDocument->loadMissing('type');
            $fromStatus = (string) $incomingDocument->current_status;
            $documentTypeName = (string) (optional($incomingDocument->type)->name ?? '');
            $recipientUserName = (string) (optional(Auth::user())->name ?? '');

            DocumentLog::create([
                'incoming_document_id' => $incomingDocument->id,
                'user_id' => $userId,
                'action_type' => 'INBOX_RECEIVED',
                'action_timestamp' => $receivedAt,
                'status_from' => $fromStatus !== '' ? $fromStatus : null,
                'status_to' => 'INBOX_RECEIVED',
                'related_user_id' => $userId,
                'related_source_id' => null,
                'remarks' => json_encode([
                    'kind' => 'inbox_received_v1',
                    'recipient_user_id' => $userId,
                    'recipient_user_name' => mb_strtoupper($recipientUserName, 'UTF-8'),
                    'received_in_behalf' => $receivedInBehalf,
                    'document_type_id' => $incomingDocument->document_type_id,
                    'document_type_name' => $documentTypeName !== '' ? mb_strtoupper($documentTypeName, 'UTF-8') : null,
                    'processing_status' => 'INBOX_RECEIVED',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            if ((string) $incomingDocument->current_status !== 'RECEIVED') {
                $incomingDocument->update([
                    'current_status' => 'RECEIVED',
                    'received_by' => $userId,
                ]);
            }

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'already_received' => false,
                'date_received' => $receivedAt->toDateTimeString(),
            ]);
        }

        return redirect()->route('inbox.index')->with('success', 'Marked as received.');
    }

    public function create()
    {
        $sources = DocumentSource::whereNull('deleted_at')->orderBy('source_type')->orderBy('name')->get();
        $types = DocumentType::whereNull('deleted_at')->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('incoming_documents.create', compact('sources', 'types', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateDocument($request);

        DB::beginTransaction();
        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('incoming_documents', 'public');
            }

            $payload = [
                'document_reference_number' => $validated['document_reference_number'],
                'date_received' => $validated['date_received'] ?? null,
                'document_from_type' => $validated['document_from_type'] ?? null,
                'transaction_type' => $validated['transaction_type'] ?? null,
                'document_source_id' => $validated['document_source_id'] ?? null,
                'drn' => $validated['drn'] ?? null,
                'document_type_id' => $validated['document_type_id'] ?? null,
                'subject' => $validated['subject'],
                'description' => $validated['description'] ?? null,
                'current_status' => 'RECEIVED',
                'signed_by' => $validated['signed_by'] ?? null,
                'date_signed' => $validated['date_signed'] ?? null,
                'received_by' => Auth::id(),
                'received_remarks' => $validated['received_remarks'] ?? null,
                'attachment_path' => $attachmentPath,
                'priority_level' => $validated['priority_level'] ?? null,
                'deadline_date' => $validated['deadline_date'] ?? null,
                'is_archived' => (bool) ($validated['is_archived'] ?? false),
            ];

            if (Schema::hasColumn('incoming_documents', 'created_by')) {
                $payload['created_by'] = Auth::id();
            }

            $doc = IncomingDocument::create($payload);

            DocumentLog::create([
                'incoming_document_id' => $doc->id,
                'user_id' => Auth::id(),
                'action_type' => 'CREATED',
                'action_timestamp' => now(),
                'status_from' => null,
                'status_to' => 'RECEIVED',
                'remarks' => $validated['received_remarks'] ?? null,
            ]);

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        return redirect()->route('incoming-documents.index')->with('success', 'Incoming document created successfully.');
    }

    public function show(IncomingDocument $incomingDocument, Request $request)
    {
        $from = trim((string) $request->query('from', ''));
        if ($from !== '') {
            $request->session()->put('incoming_documents.show_from', $from);
        }

        $canAddUpdate = false;
        if (Auth::id()) {
            $userId = (int) Auth::id();
            if (Schema::hasColumn('incoming_documents', 'created_by')) {
                $canAddUpdate = (int) $incomingDocument->created_by === $userId;
            }
            if (! $canAddUpdate) {
                $canAddUpdate = (int) $incomingDocument->received_by === $userId;
            }
            if (! $canAddUpdate) {
                $canAddUpdate = (int) $incomingDocument->forwarded_to_user_id === $userId;
            }
            if (! $canAddUpdate && Schema::hasColumn('incoming_documents', 'forwarded_to_group_id') && $incomingDocument->forwarded_to_group_id) {
                $userGroupId = User::where('id', $userId)->value('group_id');
                $canAddUpdate = (int) $userGroupId === (int) $incomingDocument->forwarded_to_group_id;
            }
            if (! $canAddUpdate && Schema::hasTable('incoming_document_forward_recipients')) {
                $canAddUpdate = DB::table('incoming_document_forward_recipients')
                    ->where('incoming_document_id', $incomingDocument->id)
                    ->where('user_id', $userId)
                    ->exists();
            }
        }

        $relations = [
            'source',
            'type',
            'forwardedToUser',
            'forwardedToSource',
            'forwardedToGroup',
            'receivedByUser',
        ];
        if (\Illuminate\Support\Facades\Schema::hasTable('incoming_document_forward_recipients')) {
            $relations[] = 'forwardedRecipients.user';
        }
        $incomingDocument->load($relations);

        $logsQuery = DocumentLog::query()
            ->where('incoming_document_id', $incomingDocument->id)
            ->with(['user', 'relatedUser', 'relatedSource'])
            ->orderBy('action_timestamp');

        $logUserId = $request->input('log_user_id');
        $logStatus = $request->input('log_status');
        $logFrom = $request->input('log_from');
        $logTo = $request->input('log_to');

        if ($logUserId) {
            $logsQuery->where('user_id', $logUserId);
        }
        if ($logStatus) {
            $logsQuery->where(function ($q) use ($logStatus) {
                $q->where('status_to', $logStatus)->orWhere('status_from', $logStatus);
            });
        }
        if ($logFrom) {
            $logsQuery->whereDate('action_timestamp', '>=', $logFrom);
        }
        if ($logTo) {
            $logsQuery->whereDate('action_timestamp', '<=', $logTo);
        }

        $logs = $logsQuery->get();
        $receivedInBehalfNames = [];
        if (
            Schema::hasTable('incoming_document_forward_recipients')
            && Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')
        ) {
            $pairs = $logs
                ->filter(fn ($l) => (string) ($l->action_type ?? '') === 'INBOX_RECEIVED')
                ->map(function ($l) {
                    return [
                        'doc_id' => (int) ($l->incoming_document_id ?? 0),
                        'user_id' => (int) ($l->related_user_id ?? 0),
                    ];
                })
                ->filter(fn ($p) => $p['doc_id'] > 0 && $p['user_id'] > 0)
                ->values();

            if ($pairs->count() > 0) {
                $docIds = $pairs->pluck('doc_id')->unique()->values()->all();
                $pairKeys = $pairs
                    ->map(fn ($p) => $p['doc_id'].':'.$p['user_id'])
                    ->flip();

                $rows = DB::table('incoming_document_forward_recipients')
                    ->whereIn('incoming_document_id', $docIds)
                    ->whereNotNull('received_in_behalf')
                    ->select(['incoming_document_id', 'user_id', 'received_in_behalf'])
                    ->get();

                $numericUserIds = $rows
                    ->map(function ($r) {
                        $raw = is_string($r->received_in_behalf) ? trim($r->received_in_behalf) : $r->received_in_behalf;

                        return is_numeric($raw) ? (int) $raw : 0;
                    })
                    ->filter(fn ($v) => $v > 0)
                    ->unique()
                    ->values()
                    ->all();

                $namesById = [];
                if ($numericUserIds !== []) {
                    $namesById = User::query()
                        ->whereIn('id', $numericUserIds)
                        ->pluck('name', 'id')
                        ->map(fn ($v) => (string) $v)
                        ->all();
                }

                foreach ($rows as $r) {
                    $docId = (int) ($r->incoming_document_id ?? 0);
                    $userId = (int) ($r->user_id ?? 0);
                    $key = $docId.':'.$userId;
                    if (! isset($pairKeys[$key])) {
                        continue;
                    }

                    $raw = is_string($r->received_in_behalf) ? trim($r->received_in_behalf) : $r->received_in_behalf;
                    $name = '';
                    if (is_numeric($raw) && (int) $raw > 0) {
                        $name = (string) ($namesById[(int) $raw] ?? '');
                    } else {
                        $name = is_string($raw) ? $raw : '';
                    }

                    if (trim($name) !== '') {
                        $receivedInBehalfNames[$key] = $name;
                    }
                }
            }
        }
        $users = User::orderBy('name')->get();
        $statuses = $this->statuses();

        $returnFromSources = DocumentSource::query()
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->orderBy('source_type')
            ->orderBy('name')
            ->get();

        $isAdminUser = $this->canManageUsers();

        return view('incoming_documents.show', compact('incomingDocument', 'logs', 'receivedInBehalfNames', 'users', 'statuses', 'returnFromSources', 'canAddUpdate', 'isAdminUser'));
    }

    public function updateLog(Request $request, IncomingDocument $incomingDocument, DocumentLog $documentLog)
    {
        if ((int) $documentLog->incoming_document_id !== (int) $incomingDocument->id) {
            return response()->json(['message' => 'Log not found.'], 404);
        }

        $authUser = Auth::user();
        if (! $authUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isOwner = (int) ($documentLog->user_id ?? 0) === (int) $authUser->id;
        $isAdmin = $this->canManageUsers();
        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $rawRemarks = (string) ($documentLog->remarks ?? '');
        $decoded = json_decode($rawRemarks, true);
        $jsonOk = json_last_error() === JSON_ERROR_NONE;
        $isManualUpdate = is_array($decoded) && ($decoded['kind'] ?? null) === 'manual_update_v1';

        if ($isManualUpdate) {
            $validated = $request->validate([
                'update_text' => ['required', 'string', 'min:1'],
            ]);

            $decoded['update_text'] = trim((string) $validated['update_text']);
            $documentLog->remarks = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $documentLog->save();

            return response()->json([
                'success' => 'Log updated successfully.',
                'data' => [
                    'id' => $documentLog->id,
                    'mode' => 'manual_update',
                    'update_text' => (string) ($decoded['update_text'] ?? ''),
                ],
            ]);
        }

        if ($rawRemarks !== '' && $jsonOk) {
            return response()->json(['message' => 'This log is not editable.'], 422);
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        $remarks = trim((string) ($validated['remarks'] ?? ''));
        $documentLog->remarks = $remarks !== '' ? $remarks : null;
        $documentLog->save();

        return response()->json([
            'success' => 'Log updated successfully.',
            'data' => [
                'id' => $documentLog->id,
                'mode' => 'remarks',
                'remarks' => (string) ($documentLog->remarks ?? ''),
            ],
        ]);
    }

    public function addUpdateLog(Request $request, IncomingDocument $incomingDocument)
    {
        if (! $this->canManageUsers()) {
            $this->assertDocumentOwner($incomingDocument);
        }

        $validated = $request->validate([
            'manual_update_party' => ['nullable', 'in:to,from'],
            'document_from_type' => ['required', 'in:section,staff'],
            'return_from_document_source_id' => ['required', 'integer', 'exists:document_sources,id,is_active,1'],
            'update_text' => ['required', 'string', 'min:1'],
        ]);

        $source = DocumentSource::findOrFail((int) $validated['return_from_document_source_id']);
        if ((string) $source->source_type !== (string) $validated['document_from_type']) {
            throw ValidationException::withMessages([
                'return_from_document_source_id' => ['Return from must match the selected Document From type.'],
            ]);
        }

        DocumentLog::create([
            'incoming_document_id' => $incomingDocument->id,
            'user_id' => Auth::id(),
            'action_type' => 'UPDATED',
            'action_timestamp' => now(),
            'remarks' => json_encode([
                'kind' => 'manual_update_v1',
                'party' => (string) ($validated['manual_update_party'] ?? 'from'),
                'document_from_type' => $validated['document_from_type'],
                'return_from' => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'source_type' => $source->source_type,
                ],
                'update_text' => $validated['update_text'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('incoming-documents.show', $incomingDocument)->with('success', 'Update added.');
    }

    public function edit(IncomingDocument $incomingDocument)
    {
        $this->assertDocumentOwner($incomingDocument);

        $sources = DocumentSource::whereNull('deleted_at')->orderBy('source_type')->orderBy('name')->get();
        $types = DocumentType::whereNull('deleted_at')->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('incoming_documents.edit', compact('incomingDocument', 'sources', 'types', 'users'));
    }

    public function update(Request $request, IncomingDocument $incomingDocument)
    {
        $this->assertDocumentOwner($incomingDocument);

        $validated = $this->validateDocument($request, $incomingDocument->id);

        DB::beginTransaction();
        try {
            $payload = [
                'document_reference_number' => $validated['document_reference_number'],
                'date_received' => $validated['date_received'] ?? null,
                'document_from_type' => $validated['document_from_type'] ?? null,
                'transaction_type' => $validated['transaction_type'] ?? null,
                'document_source_id' => $validated['document_source_id'] ?? null,
                'drn' => $validated['drn'] ?? null,
                'document_type_id' => $validated['document_type_id'] ?? null,
                'subject' => $validated['subject'],
                'description' => $validated['description'] ?? null,
                'signed_by' => $validated['signed_by'] ?? null,
                'date_signed' => $validated['date_signed'] ?? null,
                'priority_level' => $validated['priority_level'] ?? null,
                'deadline_date' => $validated['deadline_date'] ?? null,
                'is_archived' => (bool) ($validated['is_archived'] ?? false),
            ];

            if ($request->hasFile('attachment')) {
                if ($incomingDocument->attachment_path) {
                    Storage::disk('public')->delete($incomingDocument->attachment_path);
                }
                $payload['attachment_path'] = $request->file('attachment')->store('incoming_documents', 'public');
            }

            $incomingDocument->fill($payload);

            $dirty = $incomingDocument->getDirty();
            $changes = [];
            foreach ($dirty as $field => $newValue) {
                $changes[] = [
                    'field' => $field,
                    'old' => $incomingDocument->getOriginal($field),
                    'new' => $newValue,
                ];
            }

            $incomingDocument->save();

            DocumentLog::create([
                'incoming_document_id' => $incomingDocument->id,
                'user_id' => Auth::id(),
                'action_type' => 'UPDATED',
                'action_timestamp' => now(),
                'status_from' => null,
                'status_to' => null,
                'remarks' => json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            ]);

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        return redirect()->route('incoming-documents.show', $incomingDocument)->with('success', 'Incoming document updated successfully.');
    }

    public function destroy(IncomingDocument $incomingDocument)
    {
        $this->assertDocumentOwner($incomingDocument);

        DB::beginTransaction();
        try {
            $locked = IncomingDocument::query()
                ->whereKey($incomingDocument->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = (string) $locked->current_status;

            $payload = [
                'current_status' => 'ARCHIVED',
            ];
            if (Schema::hasColumn('incoming_documents', 'is_archived')) {
                $payload['is_archived'] = true;
            }

            $locked->update($payload);
            $locked->delete();

            DocumentLog::create([
                'incoming_document_id' => $locked->id,
                'user_id' => Auth::id(),
                'action_type' => 'ARCHIVED',
                'action_timestamp' => now(),
                'status_from' => $fromStatus,
                'status_to' => 'ARCHIVED',
                'remarks' => null,
            ]);

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        return redirect()->route('incoming-documents.index')->with('success', 'Incoming document archived.');
    }

    public function forward(Request $request, IncomingDocument $incomingDocument)
    {
        $this->assertDocumentOwner($incomingDocument);

        $request->validate([
            'forward_to' => ['required', 'in:user,group'],
            'forward_staff_mode' => ['nullable', 'in:0,1'],
            'forwarded_to_user_id' => [
                Rule::requiredIf($request->input('forward_to') === 'user'),
                'nullable',
                'integer',
                'exists:users,id',
                Rule::notIn([(int) Auth::id()]),
            ],
            'forwarded_to_group_id' => [
                Rule::requiredIf($request->input('forward_to') === 'group' && (string) $request->input('forward_staff_mode', '0') !== '1'),
                'nullable',
                'integer',
                Rule::exists('group', 'id')->where(fn ($q) => $q->where('status', 1)),
            ],
            'forwarded_to_user_ids' => [
                Rule::requiredIf($request->input('forward_to') === 'group' && (string) $request->input('forward_staff_mode', '0') === '1'),
                'array',
                'min:1',
                'max:20',
            ],
            'forwarded_to_user_ids.*' => [
                'integer',
                Rule::exists('users', 'id'),
                Rule::notIn([(int) Auth::id()]),
            ],
            'forward_remarks' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $fromStatus = $incomingDocument->current_status;

            $isStaffMode = $request->input('forward_to') === 'group' && (string) $request->input('forward_staff_mode', '0') === '1';
            $duplicateNames = [];
            $addedIds = [];

            $payload = [
                'current_status' => 'FORWARDED',
                'forwarded_to_user_id' => null,
                'forwarded_to_source_id' => null,
                'date_forwarded' => now(),
                'forward_remarks' => $request->input('forward_remarks'),
            ];

            if (Schema::hasColumn('incoming_documents', 'forwarded_to_group_id')) {
                $payload['forwarded_to_group_id'] = null;
            }

            if ($request->input('forward_to') === 'user') {
                $payload['forwarded_to_user_id'] = (int) $request->input('forwarded_to_user_id');
            }
            if ($request->input('forward_to') === 'group' && ! $isStaffMode && Schema::hasColumn('incoming_documents', 'forwarded_to_group_id')) {
                $payload['forwarded_to_group_id'] = (int) $request->input('forwarded_to_group_id');
            }

            $incomingDocument->update($payload);

            $existingIds = DB::table('incoming_document_forward_recipients')
                ->where('incoming_document_id', $incomingDocument->id)
                ->pluck('user_id')
                ->map(fn ($v) => (int) $v)
                ->all();
            $existingSet = array_flip($existingIds);

            $candidateIds = [];
            if ($request->input('forward_to') === 'user') {
                $candidateIds = [(int) $request->input('forwarded_to_user_id')];
            } elseif ($isStaffMode) {
                $candidateIds = array_values(array_map('intval', (array) $request->input('forwarded_to_user_ids', [])));
            } else {
                $groupId = (int) $request->input('forwarded_to_group_id');
                $candidateIds = DB::table('users')->where('group_id', $groupId)->pluck('id')->map(fn ($v) => (int) $v)->all();
            }
            $candidateIds = array_values(array_unique($candidateIds));
            $candidateIds = array_values(array_filter($candidateIds, fn ($id) => (int) $id !== (int) Auth::id()));

            $toInsert = [];
            $duplicates = [];
            $now = now();
            foreach ($candidateIds as $uid) {
                if (isset($existingSet[$uid])) {
                    $duplicates[] = $uid;
                } else {
                    $toInsert[] = [
                        'incoming_document_id' => $incomingDocument->id,
                        'user_id' => $uid,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($toInsert)) {
                DB::table('incoming_document_forward_recipients')->insert($toInsert);
                $addedIds = array_map(fn ($row) => (int) $row['user_id'], $toInsert);
            }

            if (! empty($duplicates)) {
                $names = User::query()->whereIn('id', $duplicates)->pluck('name')->all();
                $names = array_map(fn ($n) => mb_strtoupper((string) $n, 'UTF-8'), $names);
                $namesText = implode(', ', $names);
                session()->flash('warning', 'Already forwarded to: '.$namesText);
                $duplicateNames = $names;
            }

            $logRemarks = $request->input('forward_remarks');
            $relatedUserId = $payload['forwarded_to_user_id'] ?? null;
            if ($request->input('forward_to') === 'group') {
                $mode = $isStaffMode ? 'staff' : 'group';
                $groupId = ! $isStaffMode ? (int) $request->input('forwarded_to_group_id') : null;
                $groupName = null;
                if (! $isStaffMode) {
                    $groupName = (string) (DB::table('group')->where('id', $groupId)->value('group_name') ?? '');
                }
                $recipients = User::query()
                    ->whereIn('id', $addedIds)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($u) => ['id' => (int) $u->id, 'name' => mb_strtoupper((string) $u->name, 'UTF-8')])
                    ->all();

                $logRemarks = json_encode([
                    'kind' => 'forward_recipients_v1',
                    'mode' => $mode,
                    'group' => ! $isStaffMode ? ['id' => $groupId, 'name' => $groupName] : null,
                    'recipients' => $recipients,
                    'added_user_ids' => $addedIds,
                    'duplicate_user_ids' => array_values(array_map('intval', $duplicates)),
                    'note' => $request->input('forward_remarks'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $relatedUserId = null;
            }

            if (! empty($addedIds)) {
                DocumentLog::create([
                    'incoming_document_id' => $incomingDocument->id,
                    'user_id' => Auth::id(),
                    'action_type' => 'FORWARDED',
                    'action_timestamp' => now(),
                    'status_from' => $fromStatus,
                    'status_to' => 'FORWARDED',
                    'related_user_id' => $relatedUserId,
                    'related_source_id' => null,
                    'remarks' => $logRemarks,
                ]);
            }

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        if ($request->expectsJson()) {
            if (empty($addedIds)) {
                $namesText = '';
                if (! empty($duplicateNames)) {
                    $namesText = implode(', ', $duplicateNames);
                }
                $message = $request->input('forward_to') === 'user'
                    ? ('Already forwarded to: '.$namesText)
                    : ('No new recipients added'.($namesText ? (': '.$namesText) : ''));

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'warning' => null,
                    'added_user_ids' => [],
                ], 422);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Document forwarded successfully.',
                    'warning' => ! empty($duplicateNames) ? ('Already forwarded to: '.implode(', ', $duplicateNames)) : null,
                    'added_user_ids' => $addedIds,
                ]);
            }
        }

        return redirect()->route('incoming-documents.show', $incomingDocument)->with('success', 'Document forwarded successfully.');
    }

    public function groupOptions(Request $request)
    {
        try {
            $groups = DB::table('group')
                ->select(['id', DB::raw('group_name as name')])
                ->where('status', 1)
                ->orderBy('group_name')
                ->get();
        } catch (\Throwable $t) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load groups due to a database error.',
                'groups' => [],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'groups' => $groups,
        ]);
    }

    public function staffSearch(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $offset = (int) $request->input('offset', 0);
        $limit = 20;

        if (mb_strlen($q) < 2) {
            return response()->json([
                'success' => true,
                'items' => [],
                'next_offset' => null,
                'has_more' => false,
            ]);
        }

        try {
            $rows = DB::table('users')
                ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
                ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'lib_division.division_name',
                    'lib_section.section_name',
                ])
                ->where('users.id', '<>', (int) Auth::id())
                ->where(function ($w) use ($q) {
                    $w->where('users.name', 'like', "%{$q}%")
                        ->orWhere('users.email', 'like', "%{$q}%");
                })
                ->orderBy('users.name')
                ->offset($offset)
                ->limit($limit + 1)
                ->get();
        } catch (\Throwable $t) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load staff due to a database error.',
                'items' => [],
            ], 500);
        }

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $items = $rows->map(function ($r) {
            $dept = trim((string) ($r->division_name ?? ''));
            $sec = trim((string) ($r->section_name ?? ''));
            $department = $dept !== '' && $sec !== '' ? "{$dept} - {$sec}" : ($dept !== '' ? $dept : $sec);

            return [
                'id' => (int) $r->id,
                'full_name' => (string) $r->name,
                'email' => (string) $r->email,
                'department' => $department,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'items' => $items,
            'next_offset' => $hasMore ? $offset + $limit : null,
            'has_more' => $hasMore,
        ]);
    }

    public function inboxActiveUsers(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $excludeUserId = $request->filled('exclude_user_id') ? (int) $request->input('exclude_user_id') : 0;
        $page = max(1, (int) $request->input('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        try {
            $select = ['id', 'name'];
            if (Schema::hasColumn('users', 'pin_hash')) {
                $select[] = 'pin_hash';
            }

            $rows = DB::table('users')
                ->select($select)
                ->when($excludeUserId > 0, fn ($qq) => $qq->where('id', '<>', $excludeUserId))
                ->when(Schema::hasColumn('users', 'is_status'), function ($qq) {
                    $qq->where(function ($w) {
                        $w->whereNull('is_status')->orWhere('is_status', 1);
                    });
                })
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->where(function ($w) use ($q) {
                        $w->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
                })
                ->orderBy('name')
                ->offset($offset)
                ->limit($limit + 1)
                ->get();
        } catch (\Throwable $t) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
            ], 500);
        }

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);
        $results = $rows->map(function ($r) {
            $hasPin = (string) ($r->pin_hash ?? '') !== '';
            $name = (string) ($r->name ?? '');

            return [
                'id' => (int) $r->id,
                'text' => $name.($hasPin ? '' : ' (PIN not set)'),
                'has_pin' => $hasPin,
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $hasMore],
        ]);
    }

    public function receive(Request $request, IncomingDocument $incomingDocument)
    {
        $request->validate([
            'received_remarks' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $fromStatus = $incomingDocument->current_status;

            $incomingDocument->update([
                'current_status' => 'RECEIVED',
                'received_by' => Auth::id(),
                'received_remarks' => $request->input('received_remarks'),
            ]);

            DocumentLog::create([
                'incoming_document_id' => $incomingDocument->id,
                'user_id' => Auth::id(),
                'action_type' => 'RECEIVED',
                'action_timestamp' => now(),
                'status_from' => $fromStatus,
                'status_to' => 'RECEIVED',
                'remarks' => $request->input('received_remarks'),
            ]);

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        return redirect()->route('incoming-documents.show', $incomingDocument)->with('success', 'Document received successfully.');
    }

    private function validateDocument(Request $request, ?int $ignoreId = null): array
    {
        $docRef = trim((string) $request->input('document_reference_number', ''));
        if ($docRef === '') {
            $request->merge(['document_reference_number' => null]);
        }

        $rules = [
            'document_reference_number' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('incoming_documents', 'document_reference_number')->ignore($ignoreId),
            ],
            'date_received' => ['required', 'date'],
            'document_from_type' => ['required', 'in:section,staff'],
            'transaction_type' => ['required', 'integer', 'in:1,2'],
            'document_source_id' => ['required', 'integer', 'exists:document_sources,id'],
            'drn' => ['nullable', 'string', 'max:80'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'signed_by' => ['nullable', 'string', 'max:150'],
            'date_signed' => ['nullable', 'date'],
            'priority_level' => ['nullable', 'in:LOW,NORMAL,HIGH,URGENT'],
            'deadline_date' => ['nullable', 'date'],
            'is_archived' => ['nullable', 'boolean'],
            'received_remarks' => ['nullable', 'string'],
            'update_remarks' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];

        $validated = $request->validate($rules);

        if (isset($validated['document_source_id'], $validated['document_from_type'])) {
            $sourceType = DocumentSource::where('id', $validated['document_source_id'])->value('source_type');
            if ($sourceType && $sourceType !== $validated['document_from_type']) {
                throw ValidationException::withMessages([
                    'document_source_id' => 'Selected source does not match the chosen document from type.',
                ]);
            }
        }

        return $validated;
    }

    private function statuses(): array
    {
        return [
            'RECEIVED',
            'FORWARDED',
            'ARCHIVED',
        ];
    }

    private function canManageUsers(): bool
    {
        $authUser = Auth::user();
        if (! $authUser) {
            return false;
        }

        if ((int) $authUser->id === 1) {
            if (! Schema::hasColumn('users', 'level_id')) {
                return true;
            }

            if ((int) ($authUser->level_id ?? 0) === 0) {
                return true;
            }
        }

        if (! Schema::hasColumn('users', 'level_id')) {
            return true;
        }

        $levelId = (int) ($authUser->level_id ?? 0);
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

    private function resolveBatchRecipientUserId(int $batchId): int
    {
        if (
            ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'batch_id')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'user_id')
        ) {
            abort(503, 'Inbox requires the latest database migrations.');
        }

        $ids = DB::table('incoming_document_forward_recipients')
            ->where('batch_id', $batchId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values();

        if ($ids->count() === 1) {
            return (int) $ids->first();
        }

        if ($ids->count() === 0 && Schema::hasTable('batch_received') && Schema::hasColumn('batch_received', 'batch_staff_name')) {
            $staff = DB::table('batch_received')->where('id', $batchId)->value('batch_staff_name');
            if (is_string($staff) && preg_match('/^\s*(\d+)\s*-\s*/', $staff, $m)) {
                $candidate = (int) $m[1];
                if ($candidate > 0) {
                    return $candidate;
                }
            }
        }

        if ($ids->count() !== 1) {
            throw ValidationException::withMessages([
                'batch_id' => ['Batch recipient is ambiguous.'],
            ]);
        }
    }

    private function assertDocumentOwner(IncomingDocument $incomingDocument): void
    {
        $userId = Auth::id();
        if (! $userId) {
            abort(403);
        }

        $isAllowed = false;

        if (Schema::hasColumn('incoming_documents', 'created_by')) {
            $isAllowed = (int) $incomingDocument->created_by === (int) $userId;
        }

        if (! $isAllowed) {
            $isAllowed = (int) $incomingDocument->received_by === (int) $userId;
        }

        if (! $isAllowed) {
            $isAllowed = (int) $incomingDocument->forwarded_to_user_id === (int) $userId;
        }

        if (! $isAllowed && Schema::hasColumn('incoming_documents', 'forwarded_to_group_id') && $incomingDocument->forwarded_to_group_id) {
            $userGroupId = User::where('id', $userId)->value('group_id');
            $isAllowed = (int) $userGroupId === (int) $incomingDocument->forwarded_to_group_id;
        }

        if (! $isAllowed && Schema::hasTable('incoming_document_forward_recipients')) {
            $isAllowed = DB::table('incoming_document_forward_recipients')
                ->where('incoming_document_id', $incomingDocument->id)
                ->where('user_id', $userId)
                ->exists();
        }

        if (! $isAllowed) {
            abort(403);
        }
    }
}
