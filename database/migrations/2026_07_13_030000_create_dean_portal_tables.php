<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employment_type')->nullable()->after('course');
            $table->string('account_status')->default('active')->after('employment_type');
        });

        Schema::create('academic_sections', function (Blueprint $table) {
            $table->id();
            $table->string('course')->index();
            $table->string('name');
            $table->unsignedTinyInteger('year_level');
            $table->string('academic_year');
            $table->string('semester');
            $table->timestamps();
            $table->unique(['course', 'name', 'academic_year', 'semester'], 'sections_scope_unique');
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('course')->index();
            $table->string('code');
            $table->string('name');
            $table->string('subject_type');
            $table->unsignedTinyInteger('year_level');
            $table->string('semester');
            $table->decimal('units', 4, 1);
            $table->unsignedBigInteger('instructor_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['course', 'code'], 'subjects_course_code_unique');
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('course')->index();
            $table->string('name');
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['course', 'name']);
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('course')->index();
            $table->unsignedBigInteger('section_id')->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedBigInteger('instructor_id')->index();
            $table->unsignedBigInteger('room_id')->index();
            $table->string('academic_year');
            $table->string('semester');
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('academic_sections');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employment_type', 'account_status']);
        });
    }
};
