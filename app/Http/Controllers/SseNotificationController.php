<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SseNotificationController extends Controller
{
    /**
     * Stream Server-Sent Events (SSE) for document notifications.
     */
    public function streamNotifications(Request $request)
    {
        $userId = Auth::id() ?: (int) $request->input('user_id', 0);
        if (! $userId) {
            $userId = (int) (DB::table('users')->value('id') ?? 1);
        }

        return response()->stream(function () use ($userId, $request) {
            set_time_limit(0);

            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', 1);
            }
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            @ob_implicit_flush(true);
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            // Send initial connection event
            echo "event: connected\n";
            echo 'data: '.json_encode(['connected' => true, 'timestamp' => now()->toIso8601String()])."\n\n";
            @flush();

            $emittedRecipientIds = [];
            $lastSeenId = (int) $request->input('last_recipient_id', 0);

            if ($userId > 0) {
                // If caller provided a last seen recipient ID, seed all IDs <= lastSeenId as emitted
                if ($lastSeenId > 0) {
                    $existingRecipientIds = DB::table('incoming_document_forward_recipients')
                        ->where('user_id', $userId)
                        ->where('id', '<=', $lastSeenId)
                        ->pluck('id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                    $emittedRecipientIds = $existingRecipientIds;
                } else {
                    // Initial connection: seed all currently pending docs so old items aren't re-notified as new
                    $existingRecipientIds = DB::table('incoming_document_forward_recipients')
                        ->where('user_id', $userId)
                        ->whereNull('date_received')
                        ->pluck('id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                    $emittedRecipientIds = $existingRecipientIds;
                }
            }

            for ($i = 0; $i < 60; $i++) {
                if (connection_aborted()) {
                    break;
                }

                if ($userId > 0) {
                    // Unread count
                    $unreadCount = DB::table('incoming_document_forward_recipients')
                        ->where('user_id', $userId)
                        ->whereNull('date_received')
                        ->count();

                    echo "event: unread_count\n";
                    echo 'data: '.json_encode(['count' => $unreadCount])."\n\n";
                    @flush();

                    // Pending docs
                    $pendingDocs = DB::table('incoming_document_forward_recipients')
                        ->join('incoming_documents', 'incoming_document_forward_recipients.incoming_document_id', '=', 'incoming_documents.id')
                        ->leftJoin('document_sources', 'incoming_documents.document_source_id', '=', 'document_sources.id')
                        ->leftJoin('document_types', 'incoming_documents.document_type_id', '=', 'document_types.id')
                        ->leftJoin('users as sender_users', 'incoming_documents.received_by', '=', 'sender_users.id')
                        ->where('incoming_document_forward_recipients.user_id', $userId)
                        ->whereNull('incoming_document_forward_recipients.date_received')
                        ->select([
                            'incoming_document_forward_recipients.id as recipient_id',
                            'incoming_documents.id as document_id',
                            'incoming_documents.document_reference_number as tracking_number',
                            'incoming_documents.drn as drn',
                            'incoming_documents.subject as subject',
                            'document_types.name as document_type',
                            'document_sources.name as origin_office',
                            'sender_users.name as sender',
                            'incoming_documents.date_forwarded as date_routed',
                            'incoming_documents.forward_remarks as remarks',
                            'incoming_documents.priority_level as priority',
                            'incoming_documents.attachment_path as attachment_path',
                            'incoming_documents.document_from_type as document_from_type',
                            'incoming_documents.date_received as date_received_origin',
                            'incoming_documents.description as description',
                            'incoming_documents.signed_by as signed_by',
                            'incoming_documents.date_signed as date_signed',
                            'incoming_documents.deadline_date as deadline_date',
                            'incoming_documents.received_remarks as received_remarks',
                        ])
                        ->orderByDesc('incoming_document_forward_recipients.id')
                        ->limit(20)
                        ->get();

                    foreach ($pendingDocs as $doc) {
                        $recId = (int) $doc->recipient_id;
                        if (! in_array($recId, $emittedRecipientIds, true)) {
                            $emittedRecipientIds[] = $recId;

                            $docPayload = [
                                'recipient_id' => $doc->recipient_id,
                                'document_id' => $doc->document_id,
                                'tracking_number' => $doc->tracking_number ?: 'N/A',
                                'drn' => $doc->drn ?: 'N/A',
                                'subject' => $doc->subject ?: 'No Subject Provided',
                                'document_type' => $doc->document_type ?: 'Standard',
                                'origin_office' => $doc->origin_office ?: 'N/A',
                                'sender' => $doc->sender ?: 'System Administrator',
                                'date_routed' => $doc->date_routed ?: now()->format('Y-m-d H:i:s'),
                                'priority' => $doc->priority ?: 'NORMAL',
                                'remarks' => $doc->remarks ?: 'None',
                                'attachment_path' => $doc->attachment_path,
                                'document_from_type' => $doc->document_from_type,
                                'date_received_origin' => $doc->date_received_origin,
                                'description' => $doc->description,
                                'signed_by' => $doc->signed_by,
                                'date_signed' => $doc->date_signed,
                                'deadline_date' => $doc->deadline_date,
                                'received_remarks' => $doc->received_remarks,
                            ];

                            echo "event: document_assigned\n";
                            echo 'data: '.json_encode($docPayload)."\n\n";
                            @flush();
                        }
                    }
                }

                echo ": ping\n\n";
                @flush();

                // Disconnect to release MySQL repeatable-read transaction snapshots
                DB::disconnect();

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Access-Control-Allow-Origin' => $request->header('Origin') ?: '*',
            'Access-Control-Allow-Credentials' => 'true',
        ]);
    }
}
