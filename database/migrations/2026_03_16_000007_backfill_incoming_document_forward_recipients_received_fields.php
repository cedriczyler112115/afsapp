<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('incoming_document_forward_recipients')
            || ! Schema::hasTable('incoming_documents')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'incoming_document_id')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'date_received')
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_by')
            || ! Schema::hasColumn('incoming_documents', 'date_received')
            || ! Schema::hasColumn('incoming_documents', 'received_by')
        ) {
            return;
        }

        $hasDeletedAt = Schema::hasColumn('incoming_documents', 'deleted_at');

        DB::table('incoming_document_forward_recipients as r')
            ->join('incoming_documents as d', 'd.id', '=', 'r.incoming_document_id')
            ->when($hasDeletedAt, function ($q) {
                $q->whereNull('d.deleted_at');
            })
            ->where(function ($q) {
                $q->whereNull('r.date_received')->orWhereNull('r.received_by');
            })
            ->orderBy('r.id')
            ->select([
                'r.id as id',
                'r.date_received as r_date_received',
                'r.received_by as r_received_by',
                'd.date_received as d_date_received',
                'd.received_by as d_received_by',
            ])
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $updates = [];

                    if ($row->r_date_received === null && $row->d_date_received !== null) {
                        $updates['date_received'] = \Carbon\Carbon::parse($row->d_date_received)->startOfDay();
                    }

                    if ($row->r_received_by === null && $row->d_received_by !== null) {
                        $updates['received_by'] = (int) $row->d_received_by;
                    }

                    if ($updates !== []) {
                        DB::table('incoming_document_forward_recipients')
                            ->where('id', (int) $row->id)
                            ->update($updates);
                    }
                }
            }, 'r.id', 'id');
    }

    public function down(): void {}
};
