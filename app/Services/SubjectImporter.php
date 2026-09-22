<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Validation\Rule;
use RuntimeException;

class SubjectImporter
{
    private const REQUIRED_HEADERS = ['code', 'name', 'subject_type', 'year_level', 'semester', 'units'];

    private const SUBJECT_TYPE_ALIASES = [
        'lecture' => 'Lecture',
        'laboratory' => 'Laboratory',
        'lab' => 'Laboratory',
        'internship' => 'Internship',
    ];

    private const CLASSIFICATION_ALIASES = [
        'major' => 'Major',
        'minor' => 'Minor',
    ];

    private const CURRICULUM_ALIASES = [
        'new' => 'New',
        'old' => 'Old',
    ];

    private const SEMESTER_ALIASES = [
        '1st' => '1st',
        '2nd' => '2nd',
        'summer' => 'Summer',
    ];

    /** @return array{imported:int, skipped:int, errors:array<int,string>} */
    public function import(string $path, string $course, bool $managedByGec = false): array
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
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $data = array_combine($headers, array_slice($row, 0, count($headers)));

            $code = $this->clean($data['code'] ?? null);
            $name = $this->clean($data['name'] ?? null);
            $subjectTypeRaw = strtolower((string) $this->clean($data['subject_type'] ?? null));
            $subjectType = self::SUBJECT_TYPE_ALIASES[$subjectTypeRaw] ?? null;
            $classificationRaw = strtolower((string) ($this->clean($data['classification'] ?? null) ?? 'major'));
            $classification = $managedByGec ? 'Minor' : (self::CLASSIFICATION_ALIASES[$classificationRaw] ?? null);
            $yearLevel = $this->clean($data['year_level'] ?? null);
            $semesterRaw = strtolower((string) $this->clean($data['semester'] ?? null));
            $semester = self::SEMESTER_ALIASES[$semesterRaw] ?? null;
            $curriculumRaw = strtolower((string) ($this->clean($data['curriculum'] ?? null) ?? 'new'));
            $curriculum = self::CURRICULUM_ALIASES[$curriculumRaw] ?? null;
            $units = $this->clean($data['units'] ?? null);

            $validator = validator([
                'code' => $code,
                'name' => $name,
                'subject_type' => $subjectType,
                'classification' => $classification,
                'year_level' => $yearLevel,
                'semester' => $semester,
                'curriculum' => $curriculum,
                'units' => $units,
            ], [
                'code' => ['required', 'string', 'max:30'],
                'name' => ['required', 'string', 'max:150'],
                'subject_type' => ['required', Rule::in($managedByGec ? ['Lecture', 'Laboratory'] : ['Lecture', 'Laboratory', 'Internship'])],
                'classification' => ['required', Rule::in(['Major', 'Minor'])],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
                'curriculum' => ['required', Rule::in(['New', 'Old'])],
                'units' => ['required', 'numeric', 'between:0.5,12'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            if ($subjectType === 'Internship' && $classification !== 'Major') {
                $skipped++;
                $errors[] = "Row {$rowNumber}: Internship subjects must be classified as Major.";

                continue;
            }

            $duplicate = Subject::query()
                ->forDepartment($course)
                ->where('curriculum', $curriculum)
                ->where('code', $code)
                ->exists();

            if ($duplicate) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: {$code} already exists in {$curriculum} Curriculum for {$course}.";

                continue;
            }

            Subject::create([
                'course' => $course,
                'code' => $code,
                'name' => $name,
                'subject_type' => $subjectType,
                'classification' => $classification,
                'year_level' => (int) $yearLevel,
                'semester' => $semester,
                'curriculum' => $curriculum,
                'units' => (float) $units,
                'managed_by_gec' => $managedByGec,
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
