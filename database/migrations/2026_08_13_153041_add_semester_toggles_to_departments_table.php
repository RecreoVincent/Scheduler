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
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('semester_first_enabled')->default(true)->after('default_unit_limit_flexible_part_time');
            $table->boolean('semester_second_enabled')->default(true)->after('semester_first_enabled');
            $table->boolean('semester_summer_enabled')->default(true)->after('semester_second_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['semester_first_enabled', 'semester_second_enabled', 'semester_summer_enabled']);
        });
    }
};
