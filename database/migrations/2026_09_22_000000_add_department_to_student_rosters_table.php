<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_rosters', function (Blueprint $table): void {
            $table->foreignId('department_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('course')->nullable()->after('section')->index();
        });

        $departmentIds = DB::table('departments')->pluck('id', 'code');
        $assignments = DB::table('academic_sections')
            ->select(['name', 'course', 'department_id'])
            ->whereNotNull('course')
            ->get()
            ->groupBy(fn (object $section): string => $this->sectionKey($section->name))
            ->map(function ($sections) use ($departmentIds): ?object {
                $courses = $sections->pluck('course')->filter()->unique()->values();

                if ($courses->count() !== 1) {
                    return null;
                }

                $section = $sections->firstWhere('course', $courses->first());
                $section->department_id ??= $departmentIds[$courses->first()] ?? null;

                return $section;
            });

        DB::table('student_rosters')
            ->select(['id', 'section'])
            ->whereNull('course')
            ->whereNotNull('section')
            ->orderBy('id')
            ->eachById(function (object $roster) use ($assignments): void {
                $assignment = $assignments->get($this->sectionKey($roster->section));

                if (! $assignment) {
                    return;
                }

                DB::table('student_rosters')
                    ->where('id', $roster->id)
                    ->update([
                        'course' => $assignment->course,
                        'department_id' => $assignment->department_id,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('student_rosters', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
            $table->dropIndex(['course']);
            $table->dropColumn('course');
        });
    }

    private function sectionKey(string $section): string
    {
        return strtolower(preg_replace('/[\s-]+/', '', trim($section)) ?? '');
    }
};
