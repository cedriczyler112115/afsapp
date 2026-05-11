<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'pin_hash')) {
                $table->string('pin_hash', 60)->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'pin_fingerprint')) {
                $table->char('pin_fingerprint', 64)->nullable()->after('pin_hash');
                $table->unique('pin_fingerprint', 'users_pin_fingerprint_unique');
            }

            if (! Schema::hasColumn('users', 'pin_failed_attempts')) {
                $table->unsignedTinyInteger('pin_failed_attempts')->default(0)->after('pin_fingerprint');
            }

            if (! Schema::hasColumn('users', 'pin_locked_until')) {
                $table->dateTime('pin_locked_until')->nullable()->after('pin_failed_attempts');
            }
        });

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE `users` ADD CONSTRAINT `users_pin_fingerprint_hex_chk` CHECK (`pin_fingerprint` IS NULL OR `pin_fingerprint` REGEXP '^[0-9a-f]{64}$')"
            );

            return;
        }

        DB::statement(
            "ALTER TABLE users ADD CONSTRAINT users_pin_fingerprint_hex_chk CHECK (pin_fingerprint IS NULL OR pin_fingerprint ~ '^[0-9a-f]{64}$')"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'pin_fingerprint')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_pin_fingerprint_unique');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'pin_locked_until')) {
                $table->dropColumn('pin_locked_until');
            }

            if (Schema::hasColumn('users', 'pin_failed_attempts')) {
                $table->dropColumn('pin_failed_attempts');
            }

            if (Schema::hasColumn('users', 'pin_fingerprint')) {
                $table->dropColumn('pin_fingerprint');
            }

            if (Schema::hasColumn('users', 'pin_hash')) {
                $table->dropColumn('pin_hash');
            }
        });

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }

        try {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `users` DROP CHECK `users_pin_fingerprint_hex_chk`');
            } else {
                DB::statement('ALTER TABLE users DROP CONSTRAINT users_pin_fingerprint_hex_chk');
            }
        } catch (Throwable $e) {
        }
    }
};
