<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'employment_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('employment_type')->nullable()->after('course');
            });
        }

        if (! Schema::hasColumn('users', 'account_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('account_status')->default('active')->after('employment_type');
            });
        }

        Schema::create('academic_sections', function (Blueprint $table) {
            $table->id();
            $table->string('course', 20)->index();
            $table->string('name', 100);
            $table->unsignedTinyInteger('year_level');
            $table->string('academic_year', 20);
            $table->string('semester', 20);
            $table->timestamps();
            $table->unique(['course', 'name', 'academic_year', 'semester'], 'sections_scope_unique');
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('course', 20)->index();
            $table->string('code', 50);
            $table->string('name');
            $table->string('subject_type');
            $table->unsignedTinyInteger('year_level');
            $table->string('semester', 20);
            $table->decimal('units', 4, 1);
            $table->unsignedBigInteger('instructor_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['course', 'code'], 'subjects_course_code_unique');
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('course', 20)->index();
            $table->string('name', 100);
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['course', 'name']);
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('course', 20)->index();
            $table->unsignedBigInteger('section_id')->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedBigInteger('instructor_id')->index();
            $table->unsignedBigInteger('room_id')->index();
            $table->string('academic_year', 20);
            $table->string('semester', 20);
            $table->string('day', 20);
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
