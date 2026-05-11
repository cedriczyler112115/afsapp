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
            || ! Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')
        ) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `incoming_document_forward_recipients` MODIFY `received_in_behalf` BIGINT UNSIGNED NULL');

        $hasIndex = false;
        try {
            $idx = DB::table('information_schema.statistics')
                ->whereRaw('table_schema = database()')
                ->where('table_name', 'incoming_document_forward_recipients')
                ->where('index_name', 'idx_received_in_behalf')
                ->count();
            $hasIndex = (int) $idx > 0;
        } catch (\Throwable $t) {
            $hasIndex = false;
        }
        if (! $hasIndex) {
            DB::statement('ALTER TABLE `incoming_document_forward_recipients` ADD INDEX `idx_received_in_behalf` (`received_in_behalf`)');
        }

        try {
            DB::statement('ALTER TABLE `incoming_document_forward_recipients` ADD CONSTRAINT `fk_received_in_behalf_users` FOREIGN KEY (`received_in_behalf`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        } catch (\Throwable $t) {
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE `incoming_document_forward_recipients` DROP FOREIGN KEY `fk_received_in_behalf_users`');
        } catch (\Throwable $t) {
        }
        try {
            DB::statement('ALTER TABLE `incoming_document_forward_recipients` DROP INDEX `idx_received_in_behalf`');
        } catch (\Throwable $t) {
        }
        // Revert type back to VARCHAR(100) if needed
        DB::statement('ALTER TABLE `incoming_document_forward_recipients` MODIFY `received_in_behalf` VARCHAR(100) NULL');
    }
};
