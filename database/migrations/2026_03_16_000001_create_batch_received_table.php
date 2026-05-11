<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('batch_received')) {
            return;
        }

        Schema::create('batch_received', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('batch_staff_name', 150);
            $table->unsignedBigInteger('created_by');
            $table->dateTime('date_created');

            $table->index('created_by');
            $table->index('date_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_received');
    }
};
