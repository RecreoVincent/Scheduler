<?php

namespace App\Services;

use App\Models\StudentRoster;
use RuntimeException;

class StudentRosterImporter
{
    private const STUDENT_ID_HEADERS = ['studentid', 'student id', 'student no', 'student number', 'id number', 'id no', 'idno', 'id'];

    private const NAME_HEADERS = ['fullname', 'full name', 'complete name', 'name', 'student name'];

    private const FIRST_NAME_HEADERS = ['first name', 'firstname', 'given name'];

    private const MIDDLE_NAME_HEADERS = ['middle name', 'middlename'];

    private const LAST_NAME_HEADERS = ['last name', 'lastname', 'surname', 'family name'];

    private const SECTION_HEADERS = ['section', 'section name', 'block', 'block name'];

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

        if ($studentIdColumn === null || ($nameColumn === null && ($firstNameColumn === null || $lastNameColumn === null))) {
            fclose($handle);
            throw new RuntimeException('The CSV must include a Student ID column and either a Name column or First Name and Last Name columns.');
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
            $studentId = $this->clean($row[$studentIdColumn] ?? null);
            $fullName = $nameColumn !== null
                ? $this->clean($row[$nameColumn] ?? null)
                : $this->combineName(
                    $firstNameColumn !== null ? $this->clean($row[$firstNameColumn] ?? null) : null,
                    $middleNameColumn !== null ? $this->clean($row[$middleNameColumn] ?? null) : null,
                    $lastNameColumn !== null ? $this->clean($row[$lastNameColumn] ?? null) : null,
                );
            $section = $sectionColumn !== null ? $this->clean($row[$sectionColumn] ?? null) : null;

            if ($studentId === null || $fullName === null) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: missing Student ID or Name.";

                continue;
            }

            StudentRoster::updateOrCreate(['student_id' => $studentId], [
                'full_name' => $fullName,
                'section' => $section,
                'imported_at' => now(),
            ]);
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
}
