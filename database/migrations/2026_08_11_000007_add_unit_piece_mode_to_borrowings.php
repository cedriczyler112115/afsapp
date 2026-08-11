<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->string('borrow_mode', 10)->default('UNIT')->after('quantity');
            $table->unsignedInteger('pcs_borrowed')->nullable()->after('borrow_mode');
            $table->unsignedInteger('pcs_before')->nullable()->after('pcs_borrowed');
            $table->unsignedInteger('pcs_after')->nullable()->after('pcs_before');
        });
    }

    public function down(): void
    {
        Schema::table('borrowings', fn (Blueprint $table) => $table->dropColumn(['borrow_mode', 'pcs_borrowed', 'pcs_before', 'pcs_after']));
    }
};
