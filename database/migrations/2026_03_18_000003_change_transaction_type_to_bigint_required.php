<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incoming_documents')) {
            return;
        }

        $driver = DB::getDriverName();

        if (! Schema::hasColumn('incoming_documents', 'transaction_type')) {
            Schema::table('incoming_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('transaction_type')->default(1)->index();
            });

            DB::table('incoming_documents')
                ->whereNull('transaction_type')
                ->update(['transaction_type' => 1]);

            return;
        }

        if ($driver === 'mysql') {
            if (! Schema::hasColumn('incoming_documents', 'transaction_type_tmp')) {
                Schema::table('incoming_documents', function (Blueprint $table) {
                    $table->unsignedBigInteger('transaction_type_tmp')->default(1)->index();
                });
            }

            DB::statement("
                UPDATE incoming_documents
                SET transaction_type_tmp = CASE
                    WHEN UPPER(COALESCE(transaction_type, '')) = 'OUTGOING' THEN 2
                    WHEN UPPER(COALESCE(transaction_type, '')) = 'INCOMING' THEN 1
                    WHEN COALESCE(transaction_type, '') REGEXP '^[0-9]+$' THEN CAST(transaction_type AS UNSIGNED)
                    ELSE 1
                END
            ");

            DB::statement('ALTER TABLE incoming_documents DROP COLUMN transaction_type');
            DB::statement('ALTER TABLE incoming_documents CHANGE transaction_type_tmp transaction_type BIGINT UNSIGNED NOT NULL DEFAULT 1');

            return;
        }

        if ($driver === 'pgsql') {
            if (! Schema::hasColumn('incoming_documents', 'transaction_type_tmp')) {
                Schema::table('incoming_documents', function (Blueprint $table) {
                    $table->unsignedBigInteger('transaction_type_tmp')->default(1)->index();
                });
            }

            DB::statement("
                UPDATE incoming_documents
                SET transaction_type_tmp = CASE
                    WHEN UPPER(COALESCE(transaction_type::text, '')) = 'OUTGOING' THEN 2
                    WHEN UPPER(COALESCE(transaction_type::text, '')) = 'INCOMING' THEN 1
                    WHEN COALESCE(transaction_type::text, '') ~ '^[0-9]+$' THEN CAST(transaction_type::text AS BIGINT)
                    ELSE 1
                END
            ");

            DB::statement('ALTER TABLE incoming_documents DROP COLUMN transaction_type');
            DB::statement('ALTER TABLE incoming_documents RENAME COLUMN transaction_type_tmp TO transaction_type');
            DB::statement('ALTER TABLE incoming_documents ALTER COLUMN transaction_type SET NOT NULL');
            DB::statement('ALTER TABLE incoming_documents ALTER COLUMN transaction_type SET DEFAULT 1');

            return;
        }

        DB::table('incoming_documents')
            ->whereNull('transaction_type')
            ->update(['transaction_type' => 1]);

        DB::statement("UPDATE incoming_documents SET transaction_type = 1 WHERE UPPER(COALESCE(transaction_type, '')) = 'INCOMING'");
        DB::statement("UPDATE incoming_documents SET transaction_type = 2 WHERE UPPER(COALESCE(transaction_type, '')) = 'OUTGOING'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('incoming_documents') || ! Schema::hasColumn('incoming_documents', 'transaction_type')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE incoming_documents ADD COLUMN transaction_type_old VARCHAR(20) NULL');
            DB::statement("
                UPDATE incoming_documents
                SET transaction_type_old = CASE
                    WHEN transaction_type = 2 THEN 'OUTGOING'
                    ELSE 'INCOMING'
                END
            ");
            DB::statement('ALTER TABLE incoming_documents DROP COLUMN transaction_type');
            DB::statement('ALTER TABLE incoming_documents CHANGE transaction_type_old transaction_type VARCHAR(20) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE incoming_documents ADD COLUMN transaction_type_old VARCHAR(20) NULL');
            DB::statement("
                UPDATE incoming_documents
                SET transaction_type_old = CASE
                    WHEN transaction_type = 2 THEN 'OUTGOING'
                    ELSE 'INCOMING'
                END
            ");
            DB::statement('ALTER TABLE incoming_documents DROP COLUMN transaction_type');
            DB::statement('ALTER TABLE incoming_documents RENAME COLUMN transaction_type_old TO transaction_type');

            return;
        }
    }
};
