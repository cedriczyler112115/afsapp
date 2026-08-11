<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->string('issue_mode', 10)->default('PCS')->after('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('supply_request_items', fn (Blueprint $table) => $table->dropColumn('issue_mode'));
    }
};
