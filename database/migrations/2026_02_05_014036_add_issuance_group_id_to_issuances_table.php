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
            if (! Schema::hasColumn('issuances', 'issuance_group_id')) {
                $table->unsignedBigInteger('issuance_group_id')->nullable()->after('id');
                $table->index('issuance_group_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issuances', function (Blueprint $table) {
            $table->dropColumn('issuance_group_id');
        });
    }
};
