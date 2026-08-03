<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedSmallInteger('teaching_unit_limit')->nullable()->after('outside_work_end_time');
            $table->string('unit_limit_note', 500)->nullable()->after('teaching_unit_limit');
            $table->timestamp('unit_limit_updated_at')->nullable()->after('unit_limit_note');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['teaching_unit_limit', 'unit_limit_note', 'unit_limit_updated_at']);
        });
    }
};
