<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropUnique('subjects_course_code_unique');
            $table->unique(['course', 'curriculum', 'code'], 'subjects_course_curriculum_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropUnique('subjects_course_curriculum_code_unique');
            $table->unique(['course', 'code'], 'subjects_course_code_unique');
        });
    }
};
