<?php

namespace App\Services;

use App\Models\AcademicSection;
use App\Models\Department;
use Illuminate\Validation\Rule;
use RuntimeException;

class SectionImporter
{
    private const REQUIRED_HEADERS = ['name', 'year_level', 'academic_year'];

    /** @return array{imported:int, skipped:int, errors:array<int,string>} */
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

        $departmentId = Department::query()->where('code', strtoupper($course))->value('id');

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $data = array_combine($headers, array_slice($row, 0, count($headers)));

            $name = $this->clean($data['name'] ?? null);
            $yearLevel = $this->clean($data['year_level'] ?? null);
            $academicYear = $this->clean($data['academic_year'] ?? null);

            $validator = validator([
                'name' => $name,
                'year_level' => $yearLevel,
                'academic_year' => $academicYear,
            ], [
                'name' => [
                    'required', 'string', 'max:80',
                    Rule::unique('academic_sections')->where(fn ($q) => $q
                        ->where('department_id', $departmentId)
                        ->where('academic_year', $academicYear)),
                ],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            AcademicSection::create([
                'course' => $course,
                'semester' => 'All',
                'name' => $name,
                'year_level' => (int) $yearLevel,
                'academic_year' => $academicYear,
            ]);

            $imported++;
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
