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
        Schema::table('item_units', function (Blueprint $table) {
            if (! Schema::hasColumn('item_units', 'pcs_per_unit')) {
                $table->integer('pcs_per_unit')->nullable()->after('qr_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            if (Schema::hasColumn('item_units', 'pcs_per_unit')) {
                $table->dropColumn('pcs_per_unit');
            }
        });
    }
};
