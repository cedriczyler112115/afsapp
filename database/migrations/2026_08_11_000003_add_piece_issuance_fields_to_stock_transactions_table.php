<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('issuance_id')->nullable()->after('unit_id')->index();
            $table->unsignedInteger('quantity')->default(1)->after('type');
            $table->string('issue_mode', 10)->nullable()->after('quantity');
            $table->unsignedInteger('pcs_before')->nullable()->after('issue_mode');
            $table->unsignedInteger('pcs_after')->nullable()->after('pcs_before');
        });

        // Preserve the linkage of stock-out records created before piece issuance existed.
        DB::table('stock_transactions')
            ->where('type', 'OUT')
            ->whereNull('issuance_id')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $issuanceId = DB::table('item_units')
                        ->where('id', $transaction->unit_id)
                        ->value('issuance_id');

                    DB::table('stock_transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'issuance_id' => $issuanceId,
                            'quantity' => 1,
                            'issue_mode' => 'BOX',
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['issuance_id']);
            $table->dropColumn(['issuance_id', 'quantity', 'issue_mode', 'pcs_before', 'pcs_after']);
        });
    }
};
