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
        Schema::table('issuances', function (Blueprint $table) {
            $table->string('receiver_name')->nullable()->after('user_id');
        });

        // Backfill receiver_name from users table in a cross-database compatible way
        \Illuminate\Support\Facades\DB::statement('UPDATE issuances SET receiver_name = (SELECT name FROM users WHERE users.id = issuances.user_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issuances', function (Blueprint $table) {
            $table->dropColumn('receiver_name');
        });
    }
};
