<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('incoming_documents', 'created_by')) {
            Schema::table('incoming_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->index()->after('id');
                $table->foreign('created_by')->references('id')->on('users');
            });
        }

        if (Schema::hasColumn('incoming_documents', 'created_by') && Schema::hasColumn('incoming_documents', 'received_by')) {
            DB::table('incoming_documents')
                ->whereNull('created_by')
                ->whereNotNull('received_by')
                ->update(['created_by' => DB::raw('received_by')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('incoming_documents', 'created_by')) {
            Schema::table('incoming_documents', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
