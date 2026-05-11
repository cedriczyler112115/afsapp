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
        Schema::create('issuances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Assuming references users table or just an ID
            $table->text('remarks')->nullable();
            $table->dateTime('date_issued');
            $table->timestamps();
        });

        Schema::table('item_units', function (Blueprint $table) {
            $table->unsignedBigInteger('issuance_id')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->dropColumn('issuance_id');
        });

        Schema::dropIfExists('issuances');
    }
};
