<?php

namespace App\Services;

use App\Models\AcademicSection;
use App\Models\Department;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class StudentRosterImporter
{
    private const STUDENT_ID_HEADERS = ['studentid', 'student id', 'student no', 'student number', 'id number', 'id no', 'idno', 'id'];

    private const NAME_HEADERS = ['fullname', 'full name', 'complete name', 'name', 'student name'];

    private const FIRST_NAME_HEADERS = ['first name', 'firstname', 'given name'];

    private const MIDDLE_NAME_HEADERS = ['middle name', 'middlename'];

    private const LAST_NAME_HEADERS = ['last name', 'lastname', 'surname', 'family name'];

    private const SECTION_HEADERS = ['section', 'section name', 'block', 'block name'];

    private const COURSE_HEADERS = ['course', 'department', 'program', 'program/degree', 'program / degree', 'program degree', 'department/course', 'department / course'];

    /** @var array<string, array<int, array{id:int,course:string,name:string,year_level:int}>>|null */
    private ?array $sectionAssignments = null;

    private ?string $placeholderPassword = null;

    /** @return array{imported:int, skipped:int, errors:array<int,string>} */
    public function import(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('The CSV file could not be opened.');
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            throw new RuntimeException('The CSV file is empty.');
        }

        $headers = array_map(
            fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"),
            $headers,
        );
        $normalizedHeaders = array_map(fn ($value) => strtolower((string) $value), $headers);

        $studentIdColumn = $this->findColumn($normalizedHeaders, self::STUDENT_ID_HEADERS);
        $nameColumn = $this->findColumn($normalizedHeaders, self::NAME_HEADERS);
        $firstNameColumn = $this->findColumn($normalizedHeaders, self::FIRST_NAME_HEADERS);
        $middleNameColumn = $this->findColumn($normalizedHeaders, self::MIDDLE_NAME_HEADERS);
        $lastNameColumn = $this->findColumn($normalizedHeaders, self::LAST_NAME_HEADERS);
        $sectionColumn = $this->findColumn($normalizedHeaders, self::SECTION_HEADERS);
        $courseColumn = $this->findColumn($normalizedHeaders, self::COURSE_HEADERS);

        if ($studentIdColumn === null || ($nameColumn === null && ($firstNameColumn === null || $lastNameColumn === null))) {
            fclose($handle);
            throw new RuntimeException('The CSV must include a Student ID column and either a Name column or First Name and Last Name columns.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;
        $courses = Department::query()
            ->pluck('code')
            ->mapWithKeys(fn (string $course): array => [strtoupper($course) => true])
            ->all();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $studentId = $this->clean($row[$studentIdColumn] ?? null);
            $fullName = $nameColumn !== null
                ? $this->clean($row[$nameColumn] ?? null)
                : $this->combineName(
                    $firstNameColumn !== null ? $this->clean($row[$firstNameColumn] ?? null) : null,
                    $middleNameColumn !== null ? $this->clean($row[$middleNameColumn] ?? null) : null,
                    $lastNameColumn !== null ? $this->clean($row[$lastNameColumn] ?? null) : null,
                );
            $section = $sectionColumn !== null ? $this->clean($row[$sectionColumn] ?? null) : null;
            $courseValue = $courseColumn !== null ? $this->clean($row[$courseColumn] ?? null) : null;
            $course = $courseValue === null ? null : strtoupper($courseValue);

            if ($studentId === null || $fullName === null) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: missing Student ID or Name.";

                continue;
            }

            if ($course !== null && ! isset($courses[$course])) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: department \"{$course}\" was not found.";

                continue;
            }

            $assignment = $this->matchingSection($section, $course);
            $sectionCourses = collect($section === null ? [] : ($this->sectionAssignments[$this->sectionKey($section)] ?? []))
                ->pluck('course')
                ->unique();
            if ($course === null && $sectionCourses->count() > 1) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: department is required because section \"{$section}\" is used by multiple departments.";

                continue;
            }
            if ($assignment !== null) {
                $section = $assignment['name'];
                $course ??= $assignment['course'];
            }

            StudentRoster::updateOrCreate(['student_id' => $studentId], [
                'full_name' => $fullName,
                'section' => $section,
                'course' => $course,
                'imported_at' => now(),
            ]);
            $this->syncStudentAccount($studentId, $fullName, $course, $assignment);
            $imported++;
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }

    /** @param array<int, string> $normalizedHeaders
     *  @param array<int, string> $candidates */
    private function findColumn(array $normalizedHeaders, array $candidates): ?int
    {
        foreach ($normalizedHeaders as $index => $header) {
            if (in_array($header, $candidates, true)) {
                return $index;
            }
        }

        return null;
    }

    private function combineName(?string $firstName, ?string $middleName, ?string $lastName): ?string
    {
        if (strtolower((string) $middleName) === 'n/a') {
            $middleName = null;
        }

        $name = implode(' ', array_filter([$firstName, $middleName, $lastName], fn (?string $part): bool => filled($part)));

        return $name === '' ? null : $name;
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @return array{id:int,course:string,name:string,year_level:int}|null */
    private function matchingSection(?string $section, ?string $course): ?array
    {
        if ($section === null) {
            return null;
        }

        if ($this->sectionAssignments === null) {
            $this->sectionAssignments = [];

            AcademicSection::query()
                ->orderByDesc('academic_year')
                ->orderByDesc('id')
                ->get(['id', 'course', 'name', 'year_level'])
                ->each(function (AcademicSection $academicSection): void {
                    if (blank($academicSection->course)) {
                        return;
                    }

                    $this->sectionAssignments[$this->sectionKey($academicSection->name)][] = [
                        'id' => $academicSection->id,
                        'course' => strtoupper($academicSection->course),
                        'name' => $academicSection->name,
                        'year_level' => $academicSection->year_level,
                    ];
                });
        }

        $assignments = $this->sectionAssignments[$this->sectionKey($section)] ?? [];
        if ($course !== null) {
            return collect($assignments)->firstWhere('course', $course);
        }

        $courses = collect($assignments)->pluck('course')->unique();

        return $courses->count() === 1 ? $assignments[0] : null;
    }

    /** @param array{id:int,course:string,name:string,year_level:int}|null $assignment */
    private function syncStudentAccount(string $studentId, string $fullName, ?string $course, ?array $assignment): void
    {
        $student = User::withTrashed()
            ->where('student_id', $studentId)
            ->first();

        if ($student && $student->role !== 'student') {
            return;
        }

        if (! $student) {
            // A roster row becomes a visible, inactive student account right
            // away. The first verified student-portal login activates it.
            if ($course === null) {
                return;
            }

            [$firstName, $middleName, $lastName] = $this->accountNameParts($fullName);
            User::create([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => 'student-'.substr(hash('sha256', $studentId), 0, 24).'@roster.mcc.local',
                'password' => $this->placeholderPassword(),
                'role' => 'student',
                'course' => $course,
                'year_level' => $assignment['year_level'] ?? null,
                'academic_section_id' => $assignment['id'] ?? null,
                'student_id' => $studentId,
                'account_status' => 'inactive',
            ]);

            return;
        }

        if ($student->trashed()) {
            $student->restore();
        }

        $updates = [];
        if ($course !== null) {
            $updates['course'] = $course;
        }
        if ($assignment !== null) {
            $updates = array_replace($updates, [
                'course' => $assignment['course'],
                'year_level' => $assignment['year_level'],
                'academic_section_id' => $assignment['id'],
            ]);
        }

        $student->fill($updates);
        if ($student->isDirty()) {
            $student->save();
        }
    }

    public function syncRosterRecord(StudentRoster $roster, ?string $previousStudentId = null): void
    {
        $previousStudentId = $this->clean($previousStudentId);
        $studentId = $this->clean($roster->student_id);

        if ($studentId === null) {
            return;
        }

        if ($previousStudentId !== null
            && $previousStudentId !== $studentId
            && ! User::withTrashed()->where('student_id', $studentId)->exists()) {
            User::withTrashed()
                ->where('role', 'student')
                ->where('student_id', $previousStudentId)
                ->update(['student_id' => $studentId]);
        }

        $course = $this->clean($roster->course);
        $course = $course === null ? null : strtoupper($course);
        $assignment = $this->matchingSection($roster->section, $course);

        $this->syncStudentAccount($studentId, $roster->full_name, $course, $assignment);
    }

    /** @return array{0:string,1:?string,2:string} */
    private function accountNameParts(string $fullName): array
    {
        $fullName = Str::squish($fullName);

        if (str_contains($fullName, ',')) {
            [$lastName, $remainingNames] = array_map('trim', explode(',', $fullName, 2));
            $parts = preg_split('/\s+/', $remainingNames) ?: [];

            return [
                $parts[0] ?? $lastName,
                count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null,
                $lastName,
            ];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $firstName = array_shift($parts) ?: 'Student';
        $lastName = array_pop($parts) ?: $firstName;

        return [$firstName, $parts === [] ? null : implode(' ', $parts), $lastName];
    }

    private function sectionKey(string $section): string
    {
        $section = preg_replace('/[\s-]+/', '', trim($section)) ?? '';

        return strtolower($section);
    }

    private function placeholderPassword(): string
    {
        return $this->placeholderPassword ??= Hash::make(Str::random(64));
    }
}
