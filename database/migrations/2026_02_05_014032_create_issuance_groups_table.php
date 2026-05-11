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
        if (! Schema::hasTable('issuance_groups')) {
            Schema::create('issuance_groups', function (Blueprint $table) {
                $table->id();
                $table->text('purpose');
                $table->datetime('date_printed');
                $table->unsignedBigInteger('printed_by');
                $table->string('received_conformed_by')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('issuance_groups', function (Blueprint $table) {
                if (! Schema::hasColumn('issuance_groups', 'received_conformed_by')) {
                    $table->string('received_conformed_by')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issuance_groups');
    }
};
