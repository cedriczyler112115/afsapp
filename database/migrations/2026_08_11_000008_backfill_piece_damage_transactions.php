<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('stock_transactions')
            ->where('type', 'DAMAGED')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $unit = DB::table('item_units')->where('id', $transaction->unit_id)->first();
                    if (! $unit) {
                        continue;
                    }

                    $originalPcs = max(1, (int) (DB::table('items')->where('item_id', $transaction->item_id)->value('pcs_per_unit') ?? 1));
                    $isLegacyWholeBox = empty($transaction->issue_mode);
                    DB::table('stock_transactions')->where('id', $transaction->id)->update([
                        'issuance_id' => $transaction->issuance_id ?? $unit->issuance_id,
                        'quantity' => $isLegacyWholeBox ? $originalPcs : $transaction->quantity,
                        'issue_mode' => $transaction->issue_mode ?: 'BOX',
                        'pcs_before' => $transaction->pcs_before ?? $originalPcs,
                        'pcs_after' => $transaction->pcs_after ?? 0,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Historical damage details are intentionally retained.
    }
};
