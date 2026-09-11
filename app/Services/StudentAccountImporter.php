<?php

namespace App\Services;

use App\Models\AcademicSection;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class StudentAccountImporter
{
    private const REQUIRED_HEADERS = ['first_name', 'last_name', 'email', 'year_level'];

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
            $yearLevel = $this->clean($data['year_level'] ?? null);
            $sectionName = $this->clean($data['section'] ?? null);
            $password = $this->clean($data['password'] ?? null);

            $sectionId = null;
            $sectionError = null;
            if ($sectionName !== null && $yearLevel !== null) {
                $section = AcademicSection::forDepartment($course)
                    ->where('year_level', $yearLevel)
                    ->where('name', $sectionName)
                    ->first();

                if ($section) {
                    $sectionId = $section->id;
                } else {
                    $sectionError = "Section \"{$sectionName}\" was not found for year level {$yearLevel}.";
                }
            }

            $generatedPassword = null;
            if ($password === null) {
                $generatedPassword = Str::password(12);
                $password = $generatedPassword;
            }

            $validator = validator([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'year_level' => $yearLevel,
                'password' => $password,
            ], [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails() || $sectionError !== null) {
                $skipped++;
                $messages = $validator->errors()->all();
                if ($sectionError !== null) {
                    $messages[] = $sectionError;
                }
                $errors[] = "Row {$rowNumber}: ".implode(' ', $messages);

                continue;
            }

            User::create([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => $suffix,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'student',
                'course' => $course,
                'year_level' => (int) $yearLevel,
                'academic_section_id' => $sectionId,
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
}
