<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incoming_documents') || ! Schema::hasColumn('incoming_documents', 'document_reference_number')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `incoming_documents` MODIFY `document_reference_number` VARCHAR(80) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('incoming_documents') || ! Schema::hasColumn('incoming_documents', 'document_reference_number')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('incoming_documents')
            ->whereNull('document_reference_number')
            ->update(['document_reference_number' => DB::raw("CONCAT('AUTO-', id)")]);

        DB::statement('ALTER TABLE `incoming_documents` MODIFY `document_reference_number` VARCHAR(80) NOT NULL');
    }
};
