<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'google_name')) {
                $table->string('google_name')->nullable()->after('google_id');
            }

            if (! Schema::hasColumn('users', 'google_email')) {
                $table->string('google_email')->nullable()->after('google_name');
            }

            if (! Schema::hasColumn('users', 'google_avatar')) {
                $table->text('google_avatar')->nullable()->after('google_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['google_name', 'google_email', 'google_avatar'],
                fn (string $column): bool => Schema::hasColumn('users', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
