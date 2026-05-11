<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'u_level') && Schema::hasColumn('users', 'level_id')) {
            DB::table('users')
                ->whereNull('level_id')
                ->whereNotNull('u_level')
                ->update(['level_id' => DB::raw('u_level')]);
        }

        if (Schema::hasColumn('users', 'u_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('u_level');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'u_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('u_level')->nullable()->after('password');
            });
        }

        if (Schema::hasColumn('users', 'u_level') && Schema::hasColumn('users', 'level_id')) {
            DB::table('users')
                ->whereNull('u_level')
                ->whereNotNull('level_id')
                ->update(['u_level' => DB::raw('level_id')]);
        }
    }
};
