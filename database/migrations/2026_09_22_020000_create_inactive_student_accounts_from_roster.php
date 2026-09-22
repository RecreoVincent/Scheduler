<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $departmentIds = DB::table('departments')
            ->pluck('id', 'code')
            ->mapWithKeys(fn (int $id, string $code): array => [strtoupper($code) => $id])
            ->all();
        $sections = [];
        $placeholderPassword = Hash::make(Str::random(64));

        DB::table('academic_sections')
            ->select(['id', 'course', 'name', 'year_level'])
            ->orderByDesc('academic_year')
            ->orderByDesc('id')
            ->eachById(function (object $section) use (&$sections): void {
                $key = strtoupper((string) $section->course).'|'.$this->sectionKey((string) $section->name);
                $sections[$key] ??= $section;
            }, column: 'id');

        $now = now();
        DB::table('student_rosters')
            ->select(['id', 'student_id', 'full_name', 'course', 'section'])
            ->whereNotNull('course')
            ->orderBy('id')
            ->eachById(function (object $roster) use ($departmentIds, $sections, $now, $placeholderPassword): void {
                $course = strtoupper((string) $roster->course);
                $existing = DB::table('users')->where('student_id', $roster->student_id)->first();
                $section = filled($roster->section)
                    ? ($sections[$course.'|'.$this->sectionKey($roster->section)] ?? null)
                    : null;

                if ($existing) {
                    if ($existing->role === 'student' && $existing->deleted_at === null) {
                        $updates = [
                            'course' => $course,
                            'department_id' => $departmentIds[$course] ?? null,
                            'updated_at' => $now,
                        ];
                        if ($section) {
                            $updates['year_level'] = $section->year_level;
                            $updates['academic_section_id'] = $section->id;
                        }
                        DB::table('users')->where('id', $existing->id)->update($updates);
                    }

                    return;
                }

                [$firstName, $middleName, $lastName] = $this->nameParts($roster->full_name);
                DB::table('users')->insert([
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'email' => 'student-'.substr(hash('sha256', $roster->student_id), 0, 24).'@roster.mcc.local',
                    'password' => $placeholderPassword,
                    'role' => 'student',
                    'course' => $course,
                    'department_id' => $departmentIds[$course] ?? null,
                    'year_level' => $section?->year_level,
                    'academic_section_id' => $section?->id,
                    'student_id' => $roster->student_id,
                    'account_status' => 'inactive',
                    'last_login_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        // Roster accounts are legitimate student records and should not be
        // removed automatically during a rollback.
    }

    /** @return array{0:string,1:?string,2:string} */
    private function nameParts(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');

        if (str_contains($fullName, ',')) {
            [$lastName, $remainingNames] = array_map('trim', explode(',', $fullName, 2));
            $parts = preg_split('/\s+/', $remainingNames) ?: [];

            return [$parts[0] ?? $lastName, count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null, $lastName];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $firstName = array_shift($parts) ?: 'Student';
        $lastName = array_pop($parts) ?: $firstName;

        return [$firstName, $parts === [] ? null : implode(' ', $parts), $lastName];
    }

    private function sectionKey(string $section): string
    {
        return strtolower(preg_replace('/[\s-]+/', '', trim($section)) ?? '');
    }
};
