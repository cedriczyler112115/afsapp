<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')->constrained('users'); // Assuming borrowers are users
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->integer('quantity');
            $table->dateTime('borrow_date');
            $table->dateTime('expected_return_date');
            $table->string('status')->default('BORROWED'); // BORROWED, RETURNED, OVERDUE, CANCELLED
            $table->text('purpose')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->timestamps();

            // Foreign keys not using constrained() for items/item_units to avoid strict order dependency issues if not ideal,
            // but usually safe. However, items PK is item_id.
            // $table->foreign('item_id')->references('item_id')->on('items');
            // $table->foreign('item_unit_id')->references('id')->on('item_units');
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_id')->nullable()->constrained('borrowings');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->integer('quantity');
            $table->dateTime('return_date');
            $table->string('return_category'); // BORROWED_RETURN, WRONG_ITEM, etc.
            $table->text('remarks')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returns');
        Schema::dropIfExists('borrowings');
    }
};
