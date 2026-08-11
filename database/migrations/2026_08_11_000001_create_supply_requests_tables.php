<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 40)->unique();
            $table->unsignedBigInteger('requester_id');
            $table->text('purpose');
            $table->date('needed_at')->nullable();
            $table->string('status', 30)->default('PENDING')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('requester_id')->references('id')->on('users');
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('supply_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_request_id');
            // The deployed legacy items.item_id column is a signed INT.
            $table->integer('item_id');
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('approved_quantity')->nullable();
            $table->unsignedInteger('issued_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supply_request_id')->references('id')->on('supply_requests')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->unique(['supply_request_id', 'item_id']);
        });

        Schema::table('issuances', function (Blueprint $table) {
            $table->unsignedBigInteger('supply_request_item_id')->nullable()->after('id')->index();
            $table->foreign('supply_request_item_id')->references('id')->on('supply_request_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('issuances', function (Blueprint $table) {
            $table->dropForeign(['supply_request_item_id']);
            $table->dropColumn('supply_request_item_id');
        });
        Schema::dropIfExists('supply_request_items');
        Schema::dropIfExists('supply_requests');
    }
};
