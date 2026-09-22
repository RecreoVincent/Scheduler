<?php

namespace App\Services;

use App\Models\AcademicSection;
use App\Models\Department;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminUserAccountImporter
{
    private const REQUIRED_HEADERS = ['first_name', 'last_name', 'email', 'role', 'course'];

    private const ROLES = ['dean', 'instructor', 'student'];

    private const EMPLOYMENT_TYPES = ['full_time', 'industry_part_time', 'flexible_part_time'];

    private const ACCOUNT_STATUSES = ['active', 'pending'];

    /**
     * @return array{imported:int, skipped:int, errors:array<int, string>, generated:array<int, array{email:string,password:string}>, created_user_ids:array<int, int>}
     */
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
            fn ($value): string => strtolower(trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B")),
            $headers,
        );
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);
        if ($missing !== []) {
            fclose($handle);
            throw new RuntimeException('The CSV is missing required column(s): '.implode(', ', $missing).'.');
        }

        $courses = Department::query()
            ->pluck('code')
            ->map(fn (string $course): string => strtoupper($course))
            ->all();
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $generated = [];
        $createdUserIds = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            $firstName = $this->clean($data['first_name'] ?? null);
            $middleName = $this->clean($data['middle_name'] ?? null);
            $lastName = $this->clean($data['last_name'] ?? null);
            $suffix = $this->clean($data['suffix'] ?? null);
            $email = strtolower((string) $this->clean($data['email'] ?? null));
            $role = strtolower((string) $this->clean($data['role'] ?? null));
            $course = strtoupper((string) $this->clean($data['course'] ?? null));
            $employmentType = $this->resolveEmploymentType($data['employment_type'] ?? null);
            $outsideWorkEndTime = $this->clean($data['outside_work_end_time'] ?? null);
            $yearLevel = $this->clean($data['year_level'] ?? null);
            $sectionName = $this->clean($data['section'] ?? null);
            $studentId = $this->clean($data['student_id'] ?? null);
            $accountStatus = strtolower((string) ($this->clean($data['account_status'] ?? null) ?? 'active'));
            $password = $this->clean($data['password'] ?? null);
            $generatedPassword = null;

            if ($password === null) {
                $generatedPassword = Str::password(12);
                $password = $generatedPassword;
            }

            $validator = validator([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'role' => $role,
                'course' => $course,
                'employment_type' => $employmentType,
                'outside_work_end_time' => $outsideWorkEndTime,
                'year_level' => $yearLevel,
                'student_id' => $studentId,
                'account_status' => $accountStatus,
                'password' => $password,
            ], [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'role' => ['required', Rule::in(self::ROLES)],
                'course' => ['required', Rule::in($courses)],
                'employment_type' => ['nullable', 'required_if:role,instructor', Rule::in(self::EMPLOYMENT_TYPES)],
                'outside_work_end_time' => ['nullable', 'required_if:employment_type,industry_part_time', 'date_format:H:i'],
                'year_level' => ['nullable', 'required_if:role,student', 'integer', 'between:1,4'],
                'student_id' => ['nullable', 'required_if:role,student', 'string', 'max:30', Rule::unique('users', 'student_id')],
                'account_status' => ['required', Rule::in(self::ACCOUNT_STATUSES)],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $section = null;
            $sectionError = null;
            if ($role === 'student' && $sectionName !== null && $yearLevel !== null && $course !== '') {
                $section = AcademicSection::query()
                    ->where('course', $course)
                    ->where('year_level', $yearLevel)
                    ->where('name', $sectionName)
                    ->first();

                if (! $section) {
                    $sectionError = "Section \"{$sectionName}\" was not found for {$course} Year {$yearLevel}.";
                }
            }

            $rosterError = null;
            if ($role === 'student' && $studentId !== null) {
                $rosterEntry = StudentRoster::query()->where('student_id', $studentId)->first();

                if (! $rosterEntry) {
                    $rosterError = "Student ID \"{$studentId}\" is not in the official Student Roster.";
                } elseif (filled($rosterEntry->course) && strtoupper($rosterEntry->course) !== $course) {
                    $rosterError = "Student ID \"{$studentId}\" belongs to {$rosterEntry->course} in the official Student Roster, not {$course}.";
                }
            }

            if ($validator->fails() || $sectionError !== null || $rosterError !== null) {
                $skipped++;
                $messages = $validator->errors()->all();
                if ($sectionError !== null) {
                    $messages[] = $sectionError;
                }
                if ($rosterError !== null) {
                    $messages[] = $rosterError;
                }
                $errors[] = "Row {$rowNumber}: ".implode(' ', $messages);

                continue;
            }

            try {
                $user = User::create([
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'suffix' => $suffix,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => $role,
                    'course' => $course,
                    'employment_type' => $role === 'instructor' ? $employmentType : null,
                    'outside_work_end_time' => $role === 'instructor' && $employmentType === 'industry_part_time'
                        ? $outsideWorkEndTime : null,
                    'year_level' => $role === 'student' ? (int) $yearLevel : null,
                    'academic_section_id' => $role === 'student' ? $section?->id : null,
                    'student_id' => $role === 'student' ? $studentId : null,
                    'account_status' => $accountStatus,
                ]);
            } catch (QueryException) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: this account could not be created. Check that the email and student ID are unique.";

                continue;
            }

            $imported++;
            $createdUserIds[] = $user->id;
            if ($generatedPassword !== null) {
                $generated[] = ['email' => $email, 'password' => $generatedPassword];
            }
        }

        fclose($handle);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'generated' => $generated,
            'created_user_ids' => $createdUserIds,
        ];
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveEmploymentType(mixed $raw): ?string
    {
        $value = $this->clean($raw);
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) preg_replace('/[^a-z]+/', ' ', strtolower($value)));
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, 'full')) {
            return 'full_time';
        }

        if (str_contains($normalized, 'industry')) {
            return 'industry_part_time';
        }

        if (str_contains($normalized, 'flexible') || str_contains($normalized, 'flexi') || str_contains($normalized, 'part')) {
            return 'flexible_part_time';
        }

        return null;
    }
}
