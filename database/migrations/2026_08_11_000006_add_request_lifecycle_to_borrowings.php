<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->string('request_no', 40)->nullable()->after('id')->index();
            $table->timestamp('requested_at')->nullable()->after('purpose');
            $table->timestamp('approved_at')->nullable()->after('requested_at');
            $table->timestamp('received_at')->nullable()->after('approved_at');
            $table->timestamp('return_requested_at')->nullable()->after('received_at');
            $table->timestamp('returned_at')->nullable()->after('return_requested_at');
            $table->text('approval_notes')->nullable()->after('returned_at');
        });

        $activeBorrowedUnitIds = DB::table('borrowings')
            ->whereIn('status', ['BORROWED', 'OVERDUE'])
            ->whereNotNull('item_unit_id')
            ->pluck('item_unit_id');
        DB::table('item_units')->whereIn('id', $activeBorrowedUnitIds)->update(['status' => 3]);
    }

    public function down(): void
    {
        DB::table('item_units')->where('status', 3)->update(['status' => 2]);
        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropColumn(['request_no', 'requested_at', 'approved_at', 'received_at', 'return_requested_at', 'returned_at', 'approval_notes']);
        });
    }
};
