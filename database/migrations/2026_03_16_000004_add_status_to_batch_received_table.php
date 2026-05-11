<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('batch_received') || Schema::hasColumn('batch_received', 'status')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE batch_received ADD COLUMN status INTEGER NOT NULL DEFAULT 0 CHECK (status IN (0, 1))');

            return;
        }

        Schema::table('batch_received', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(0)->after('date_created');
            $table->index('status');
        });

        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE `batch_received` ADD CONSTRAINT `chk_batch_received_status` CHECK (`status` IN (0, 1))');
            } catch (\Throwable $t) {
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('batch_received') || ! Schema::hasColumn('batch_received', 'status')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE `batch_received` DROP CHECK `chk_batch_received_status`');
            } catch (\Throwable $t) {
            }
        }

        Schema::table('batch_received', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
