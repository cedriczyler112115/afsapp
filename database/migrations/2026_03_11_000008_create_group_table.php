<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('group')) {
            Schema::create('group', function (Blueprint $table) {
                $table->id();
                $table->string('group_name', 50);
                $table->unsignedTinyInteger('status');
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamp('date_created')->useCurrent();

                $table->index('group_name');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('group');
    }
};
