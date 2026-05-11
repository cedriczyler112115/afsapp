<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'level_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'division_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('division_id')->nullable()->after('level_id');
            });
        }

        if (! Schema::hasColumn('users', 'section_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('section_id')->nullable()->after('division_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'section_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('section_id');
            });
        }

        if (Schema::hasColumn('users', 'division_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('division_id');
            });
        }

        if (Schema::hasColumn('users', 'level_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('level_id');
            });
        }
    }
};
