<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incoming_document_forward_recipients')) {
            return;
        }

        Schema::table('incoming_document_forward_recipients', function (Blueprint $table) {
            if (! Schema::hasColumn('incoming_document_forward_recipients', 'batch_id')) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('received_in_behalf');
                $table->index('batch_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incoming_document_forward_recipients')) {
            return;
        }

        Schema::table('incoming_document_forward_recipients', function (Blueprint $table) {
            if (Schema::hasColumn('incoming_document_forward_recipients', 'batch_id')) {
                $table->dropIndex(['batch_id']);
                $table->dropColumn('batch_id');
            }
        });
    }
};
