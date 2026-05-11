<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_documents', function (Blueprint $table) {
            $table->id();

            $table->string('document_reference_number', 80)->nullable()->unique();
            $table->date('date_received')->nullable();

            $table->unsignedBigInteger('document_source_id')->nullable();
            $table->string('document_from_type', 20)->nullable();

            $table->string('drn', 80)->nullable()->index();
            $table->unsignedBigInteger('document_type_id')->nullable();

            $table->string('subject', 255);
            $table->text('description')->nullable();

            $table->string('current_status', 40)->default('RECEIVED')->index();

            $table->string('signed_by', 150)->nullable();
            $table->date('date_signed')->nullable();

            $table->unsignedBigInteger('forwarded_to_user_id')->nullable()->index();
            $table->unsignedBigInteger('forwarded_to_source_id')->nullable()->index();
            $table->dateTime('date_forwarded')->nullable();

            $table->unsignedBigInteger('received_by')->nullable()->index();
            $table->text('forward_remarks')->nullable();
            $table->text('received_remarks')->nullable();

            $table->string('attachment_path', 500)->nullable();
            $table->string('priority_level', 20)->nullable()->index();
            $table->date('deadline_date')->nullable()->index();
            $table->boolean('is_archived')->default(false)->index();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('document_source_id')->references('id')->on('document_sources');
            $table->foreign('document_type_id')->references('id')->on('document_types');
            $table->foreign('forwarded_to_user_id')->references('id')->on('users');
            $table->foreign('forwarded_to_source_id')->references('id')->on('document_sources');
            $table->foreign('received_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_documents');
    }
};
