<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('unit_of_measures');

        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->string('unit_name', 50);
            $table->string('unit_code', 10);
            $table->string('unit_type', 30);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
