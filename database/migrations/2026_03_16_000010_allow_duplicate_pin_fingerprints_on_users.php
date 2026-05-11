<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'pin_fingerprint')) {
            return;
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_pin_fingerprint_unique');
            });
        } catch (\Throwable $t) {
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('pin_fingerprint', 'users_pin_fingerprint_index');
            });
        } catch (\Throwable $t) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'pin_fingerprint')) {
            return;
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_pin_fingerprint_index');
            });
        } catch (\Throwable $t) {
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('pin_fingerprint', 'users_pin_fingerprint_unique');
            });
        } catch (\Throwable $t) {
        }
    }
};
