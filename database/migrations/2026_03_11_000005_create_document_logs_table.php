<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incoming_document_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action_type', 30)->index();
            $table->dateTime('action_timestamp')->index();
            $table->string('status_from', 40)->nullable();
            $table->string('status_to', 40)->nullable();
            $table->unsignedBigInteger('related_user_id')->nullable()->index();
            $table->unsignedBigInteger('related_source_id')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('incoming_document_id')->references('id')->on('incoming_documents')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('related_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('related_source_id')->references('id')->on('document_sources')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_logs');
    }
};
