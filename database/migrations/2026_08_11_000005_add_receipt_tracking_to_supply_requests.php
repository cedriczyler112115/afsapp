<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->string('receipt_status', 30)->default('NOT_RECEIVED')->after('status')->index();
        });
        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->unsignedInteger('received_quantity')->default(0)->after('issued_quantity');
        });
        Schema::table('issuances', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('date_issued');
            $table->unsignedBigInteger('received_by')->nullable()->after('received_at')->index();
            $table->text('receipt_notes')->nullable()->after('received_by');
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('issuances', function (Blueprint $table) {
            $table->dropForeign(['received_by']);
            $table->dropColumn(['received_at', 'received_by', 'receipt_notes']);
        });
        Schema::table('supply_request_items', fn (Blueprint $table) => $table->dropColumn('received_quantity'));
        Schema::table('supply_requests', fn (Blueprint $table) => $table->dropColumn('receipt_status'));
    }
};
