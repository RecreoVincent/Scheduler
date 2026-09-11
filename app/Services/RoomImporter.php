<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Room;
use Illuminate\Validation\Rule;
use RuntimeException;

class RoomImporter
{
    private const REQUIRED_HEADERS = ['name', 'room_type'];

    private const ROOM_TYPE_ALIASES = [
        'lecture' => 'Lecture',
        'laboratory' => 'Laboratory',
        'lab' => 'Laboratory',
        'kitchen laboratory' => 'Kitchen Laboratory',
        'kitchen lab' => 'Kitchen Laboratory',
    ];

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
            $roomTypeRaw = strtolower((string) $this->clean($data['room_type'] ?? null));
            $roomType = self::ROOM_TYPE_ALIASES[$roomTypeRaw] ?? null;

            $validator = validator([
                'name' => $name,
                'room_type' => $roomType,
            ], [
                'name' => [
                    'required', 'string', 'max:80',
                    Rule::unique('rooms')->where(fn ($q) => $q->where('department_id', $departmentId)),
                ],
                'room_type' => ['required', Rule::in($this->allowedRoomTypes($course))],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            Room::create([
                'course' => $course,
                'name' => $name,
                'room_type' => $roomType,
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

    /** @return array<int, string> */
    private function allowedRoomTypes(string $course): array
    {
        return strtoupper($course) === 'BSHM'
            ? ['Lecture', 'Laboratory', 'Kitchen Laboratory']
            : ['Lecture', 'Laboratory'];
    }
}
