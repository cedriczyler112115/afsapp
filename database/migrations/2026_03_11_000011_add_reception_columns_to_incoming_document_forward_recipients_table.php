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
            if (! Schema::hasColumn('incoming_document_forward_recipients', 'date_received')) {
                $table->dateTime('date_received')->nullable()->after('user_id');
                $table->index('date_received');
            }

            if (! Schema::hasColumn('incoming_document_forward_recipients', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->after('date_received');
                $table->index('received_by');
                $table->foreign('received_by')->references('id')->on('users');
            }

            if (! Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')) {
                $table->string('received_in_behalf', 100)->nullable()->after('received_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incoming_document_forward_recipients')) {
            return;
        }

        Schema::table('incoming_document_forward_recipients', function (Blueprint $table) {
            if (Schema::hasColumn('incoming_document_forward_recipients', 'received_by')) {
                $table->dropForeign(['received_by']);
                $table->dropIndex(['received_by']);
                $table->dropColumn('received_by');
            }

            if (Schema::hasColumn('incoming_document_forward_recipients', 'date_received')) {
                $table->dropIndex(['date_received']);
                $table->dropColumn('date_received');
            }

            if (Schema::hasColumn('incoming_document_forward_recipients', 'received_in_behalf')) {
                $table->dropColumn('received_in_behalf');
            }
        });
    }
};
