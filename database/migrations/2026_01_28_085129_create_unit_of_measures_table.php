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
        if (! Schema::hasTable('unit_of_measures')) {
            Schema::create('unit_of_measures', function (Blueprint $table) {
                $table->id('unit_id');
                $table->string('unit_name');
                $table->text('description')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
