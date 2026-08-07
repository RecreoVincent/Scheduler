<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('program_name');
            $table->string('logo_path')->nullable();
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('academic_terms', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label');
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });

        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('academic_year', 20);
            $table->foreignId('academic_term_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['academic_year', 'academic_term_id']);
        });

        $now = now();
        DB::table('departments')->insert([
            ['code' => 'BSIT', 'name' => 'Information Technology Department', 'program_name' => 'Bachelor of Science in Information Technology', 'logo_path' => 'images/bsit-department-logo.jpg', 'email' => 'collegeofinfotech2023@gmail.com', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'BSBA', 'name' => 'Business Administration Department', 'program_name' => 'Bachelor of Science in Business Administration', 'logo_path' => 'images/bsba-department-logo.jpg', 'email' => null, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'BSHM', 'name' => 'Hospitality Management Department', 'program_name' => 'Bachelor of Science in Hospitality Management', 'logo_path' => 'images/bshm-department-logo.jpg', 'email' => null, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'BSED', 'name' => 'Education Department', 'program_name' => 'Bachelor of Secondary Education', 'logo_path' => 'images/education-department-logo.jpg', 'email' => null, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'BEED', 'name' => 'Education Department', 'program_name' => 'Bachelor of Elementary Education', 'logo_path' => 'images/education-department-logo.jpg', 'email' => null, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('academic_terms')->insert([
            ['code' => '1st', 'label' => 'First Semester', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2nd', 'label' => 'Second Semester', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'Summer', 'label' => 'Summer', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'All', 'label' => 'All Semesters', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);

        if (DB::getDriverName() === 'mysql') {
            foreach (['users', 'academic_sections', 'subjects', 'rooms', 'class_schedules'] as $table) {
                DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            }
        }

        foreach (['users', 'academic_sections', 'subjects', 'rooms'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreignId('department_id')->nullable()->index()->constrained()->restrictOnDelete();
            });
        }

        Schema::table('subjects', function (Blueprint $table): void {
            $table->foreignId('academic_term_id')->nullable()->index()->constrained()->restrictOnDelete();
        });

        Schema::table('academic_sections', function (Blueprint $table): void {
            $table->foreignId('academic_period_id')->nullable()->index()->constrained()->restrictOnDelete();
        });

        Schema::table('class_schedules', function (Blueprint $table): void {
            $table->foreignId('academic_period_id')->nullable()->index()->constrained()->restrictOnDelete();
        });

        $departmentIds = DB::table('departments')->pluck('id', 'code');
        foreach (['users', 'academic_sections', 'subjects', 'rooms'] as $table) {
            foreach ($departmentIds as $code => $id) {
                DB::table($table)->whereRaw('UPPER(course) = ?', [$code])->update(['department_id' => $id]);
            }
        }

        $termIds = DB::table('academic_terms')->pluck('id', 'code');
        foreach ($termIds as $code => $id) {
            DB::table('subjects')->where('semester', $code)->update(['academic_term_id' => $id]);
        }

        $periods = DB::table('academic_sections')->select('academic_year', 'semester')
            ->union(DB::table('class_schedules')->select('academic_year', 'semester'))
            ->distinct()->get();

        foreach ($periods as $period) {
            $termId = $termIds[$period->semester] ?? null;
            if (! $termId || blank($period->academic_year)) {
                continue;
            }

            $periodId = DB::table('academic_periods')->insertGetId([
                'academic_year' => $period->academic_year,
                'academic_term_id' => $termId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('academic_sections')->where('academic_year', $period->academic_year)->where('semester', $period->semester)->update(['academic_period_id' => $periodId]);
            DB::table('class_schedules')->where('academic_year', $period->academic_year)->where('semester', $period->semester)->update(['academic_period_id' => $periodId]);
        }
    }

    public function down(): void
    {
        Schema::table('class_schedules', fn (Blueprint $table) => $table->dropConstrainedForeignId('academic_period_id'));
        Schema::table('academic_sections', fn (Blueprint $table) => $table->dropConstrainedForeignId('academic_period_id'));
        Schema::table('subjects', fn (Blueprint $table) => $table->dropConstrainedForeignId('academic_term_id'));
        foreach (['rooms', 'subjects', 'academic_sections', 'users'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropConstrainedForeignId('department_id'));
        }
        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('academic_terms');
        Schema::dropIfExists('departments');
    }
};
