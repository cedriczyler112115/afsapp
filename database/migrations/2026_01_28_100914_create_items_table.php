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
        if (! Schema::hasTable('items')) {
            Schema::create('items', function (Blueprint $table) {
                $table->id('item_id'); // item_id, PK
                $table->integer('category_id')->nullable(); // FK to categories.category_id (int)
                $table->integer('supplier_id')->nullable(); // Just int, nullable
                $table->string('item_name', 150); // Not null
                $table->text('description')->nullable();
                $table->unsignedBigInteger('unit_id')->nullable(); // FK to unit_of_measures.id (bigint)
                $table->integer('reorder_level')->nullable();
                $table->integer('create_by')->nullable(); // user id?
                $table->dateTime('date_created')->nullable(); // Custom timestamp column

                // Standard timestamps (created_at, updated_at) - user asked for date_created.
                // I'll add timestamps() as well for standard practice, but date_created is explicit.
                // Or maybe date_created IS the created_at. I'll configure the model to use date_created.
                // For now, I'll add standard timestamps too or just date_created?
                // The schema ONLY shows date_created. I will stick to the schema strictly.
                // But wait, if I don't use timestamps(), I handle it manually.
                // I'll add $table->timestamps() just in case, but make them nullable or use date_created as created_at.
                // Actually, I'll just add date_created as requested.
                // And maybe updated_at? The schema doesn't show it.

                // Foreign Keys
                // Note: category_id in categories table is 'int' (increments), so here it should be integer.
                // unit_id in unit_of_measures is 'bigint' (id()), so here unsignedBigInteger.

                // $table->foreign('category_id')->references('category_id')->on('categories');
                // $table->foreign('unit_id')->references('id')->on('unit_of_measures');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
