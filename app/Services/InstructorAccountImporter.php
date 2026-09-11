<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class InstructorAccountImporter
{
    private const REQUIRED_HEADERS = ['first_name', 'last_name', 'email', 'employment_type'];

    /** @return array{imported:int, skipped:int, errors:array<int,string>, generated:array<int,array{email:string,password:string}>} */
    public function import(string $path, string $course): array
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
            fn ($value) => strtolower(trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B")),
            $headers,
        );

        $missing = array_diff(self::REQUIRED_HEADERS, $headers);
        if ($missing !== []) {
            fclose($handle);
            throw new RuntimeException('The CSV is missing required column(s): '.implode(', ', $missing).'.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $generated = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $data = array_combine($headers, array_slice($row, 0, count($headers)));

            $firstName = $this->clean($data['first_name'] ?? null);
            $middleName = $this->clean($data['middle_name'] ?? null);
            $lastName = $this->clean($data['last_name'] ?? null);
            $suffix = $this->clean($data['suffix'] ?? null);
            $email = strtolower((string) $this->clean($data['email'] ?? null));
            $employmentType = $this->resolveEmploymentType($data['employment_type'] ?? null);
            $outsideWorkEndTime = $this->clean($data['outside_work_end_time'] ?? null);
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
                'employment_type' => $employmentType,
                'outside_work_end_time' => $outsideWorkEndTime,
                'password' => $password,
            ], [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'employment_type' => ['required', Rule::in(['full_time', 'industry_part_time', 'flexible_part_time'])],
                'outside_work_end_time' => ['nullable', 'required_if:employment_type,industry_part_time', 'date_format:H:i'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            User::create([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => $suffix,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'instructor',
                'course' => $course,
                'employment_type' => $employmentType,
                'outside_work_end_time' => $employmentType === 'industry_part_time' ? $outsideWorkEndTime : null,
                'account_status' => 'active',
            ]);

            $imported++;
            if ($generatedPassword !== null) {
                $generated[] = ['email' => $email, 'password' => $generatedPassword];
            }
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors', 'generated');
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Read a CSV employment_type cell loosely: case, spacing, hyphens, and
     * underscores are all ignored ("Full-Time", "full time", "FULLTIME" all
     * resolve the same). A bare "part time"/"part-time" with no "industry"
     * or "flexible" qualifier is accepted too, defaulting to flexible part
     * time since that type doesn't require the extra outside-work-end-time
     * field a plain "part time" label wouldn't have specified.
     */
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

        if (str_contains($normalized, 'flexible') || str_contains($normalized, 'flexi')) {
            return 'flexible_part_time';
        }

        if (str_contains($normalized, 'part')) {
            return 'flexible_part_time';
        }

        return null;
    }
}
