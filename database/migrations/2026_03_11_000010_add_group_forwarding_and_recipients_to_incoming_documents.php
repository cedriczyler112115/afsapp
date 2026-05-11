<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $createdRecipientsTable = false;

        if (! Schema::hasColumn('incoming_documents', 'forwarded_to_group_id')) {
            Schema::table('incoming_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('forwarded_to_group_id')->nullable()->after('forwarded_to_source_id');
                $table->index('forwarded_to_group_id');
                $table->foreign('forwarded_to_group_id')->references('id')->on('group');
            });
        }

        if (! Schema::hasTable('incoming_document_forward_recipients')) {
            Schema::create('incoming_document_forward_recipients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('incoming_document_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->unique(['incoming_document_id', 'user_id'], 'doc_recipient_unique');
                $table->index('incoming_document_id');
                $table->index('user_id');

                $table->foreign('incoming_document_id')->references('id')->on('incoming_documents')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
            $createdRecipientsTable = true;
        }

        if (Schema::hasTable('incoming_document_forward_recipients') && ! $createdRecipientsTable) {
            $hasUnique = false;
            try {
                $exists = DB::select("
                    SELECT COUNT(*) AS cnt
                    FROM information_schema.statistics
                    WHERE table_schema = DATABASE()
                      AND table_name = 'incoming_document_forward_recipients'
                      AND index_name = 'doc_recipient_unique'
                ");
                $hasUnique = isset($exists[0]) && (int) ($exists[0]->cnt ?? 0) > 0;
            } catch (\Throwable $t) {
                $hasUnique = false;
            }
            if (! $hasUnique) {
                Schema::table('incoming_document_forward_recipients', function (Blueprint $table) {
                    $table->unique(['incoming_document_id', 'user_id'], 'doc_recipient_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('incoming_document_forward_recipients')) {
            Schema::drop('incoming_document_forward_recipients');
        }

        if (Schema::hasColumn('incoming_documents', 'forwarded_to_group_id')) {
            Schema::table('incoming_documents', function (Blueprint $table) {
                $table->dropForeign(['forwarded_to_group_id']);
                $table->dropIndex(['forwarded_to_group_id']);
                $table->dropColumn('forwarded_to_group_id');
            });
        }
    }
};
