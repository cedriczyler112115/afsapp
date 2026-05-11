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
        if (! Schema::hasTable('stock_transactions')) {
            Schema::create('stock_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('item_id'); // item_id from items table
                $table->unsignedBigInteger('unit_id'); // item_units.id
                $table->string('type')->default('IN'); // IN, OUT
                $table->dateTime('date_created')->useCurrent();
                $table->unsignedBigInteger('created_by')->nullable();
                // User schema: id, item_id, unit_id, type, date_created, created_by
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
