<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incoming_documents')) {
            return;
        }

        Schema::table('incoming_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('incoming_documents', 'transaction_type')) {
                $table->string('transaction_type', 20)->nullable()->index()->after('document_from_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incoming_documents')) {
            return;
        }

        Schema::table('incoming_documents', function (Blueprint $table) {
            if (Schema::hasColumn('incoming_documents', 'transaction_type')) {
                $table->dropIndex(['transaction_type']);
                $table->dropColumn('transaction_type');
            }
        });
    }
};
