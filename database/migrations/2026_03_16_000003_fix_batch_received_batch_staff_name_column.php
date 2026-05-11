<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('batch_received') || ! Schema::hasColumn('batch_received', 'batch_staff_name')) {
            return;
        }

        $column = DB::table('information_schema.columns')
            ->whereRaw('table_schema = database()')
            ->where('table_name', 'batch_received')
            ->where('column_name', 'batch_staff_name')
            ->first(['data_type', 'character_maximum_length']);

        $dataType = is_object($column) && isset($column->data_type) ? (string) $column->data_type : '';

        if (! in_array($dataType, ['varchar', 'text', 'mediumtext', 'longtext'], true)) {
            DB::statement('ALTER TABLE `batch_received` MODIFY `batch_staff_name` VARCHAR(255) NOT NULL');
        }
    }

    public function down(): void {}
};
