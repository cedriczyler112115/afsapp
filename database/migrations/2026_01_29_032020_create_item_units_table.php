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
        if (! Schema::hasTable('item_units')) {
            Schema::create('item_units', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('item_id'); // item_id from items table
                $table->string('serial')->nullable();
                $table->string('full_code')->nullable();
                $table->string('qr_code')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->dateTime('date_created')->useCurrent();
                $table->unsignedBigInteger('created_by')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};
