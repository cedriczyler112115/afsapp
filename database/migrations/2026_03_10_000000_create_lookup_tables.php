<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_level')) {
            Schema::create('user_level', function (Blueprint $table) {
                $table->id();
                $table->string('level_name');
                $table->tinyInteger('is_status')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('date_created')->nullable();
            });
        }

        if (! Schema::hasTable('lib_division')) {
            Schema::create('lib_division', function (Blueprint $table) {
                $table->id();
                $table->string('division_name')->nullable();
                $table->tinyInteger('is_status')->nullable();
            });
        }

        if (! Schema::hasTable('lib_section')) {
            Schema::create('lib_section', function (Blueprint $table) {
                $table->id();
                $table->string('section_name')->nullable();
                $table->unsignedBigInteger('division_id')->nullable();
            });
        }

        if (! Schema::hasTable('lib_provinces')) {
            Schema::create('lib_provinces', function (Blueprint $table) {
                $table->unsignedBigInteger('prov_code')->primary();
                $table->string('prov_name');
                $table->unsignedInteger('region_code')->nullable();
            });
        }

        if (! Schema::hasTable('lib_cities')) {
            Schema::create('lib_cities', function (Blueprint $table) {
                $table->unsignedBigInteger('city_code')->primary();
                $table->string('city_name');
                $table->unsignedBigInteger('prov_code')->nullable();
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'province')) {
                    $table->unsignedBigInteger('province')->nullable()->after('section_id');
                }
                if (! Schema::hasColumn('users', 'cluster')) {
                    $table->unsignedTinyInteger('cluster')->nullable()->after('province');
                }
                if (! Schema::hasColumn('users', 'municipality')) {
                    $table->unsignedBigInteger('municipality')->nullable()->after('cluster');
                }
            });
        }
    }

    public function down(): void
    {
        // Do not drop lookup tables on rollback to prevent data loss in existing databases
    }
};
