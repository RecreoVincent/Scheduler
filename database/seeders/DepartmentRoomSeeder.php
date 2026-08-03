<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class DepartmentRoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            'BSIT' => $this->numberedRooms('Lab ', 1, 5),
            'BSED' => $this->numberedRooms('EDUC ', 101, 108),
            'BEED' => $this->numberedRooms('EDUC ', 101, 108),
            'BSBA' => $this->numberedRooms('BA ', 101, 112),
            'BSHM' => $this->numberedRooms('HM ', 101, 105),
        ];

        foreach ($rooms as $course => $roomNames) {
            foreach ($roomNames as $name) {
                Room::firstOrCreate([
                    'course' => $course,
                    'name' => $name,
                ]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function numberedRooms(string $prefix, int $start, int $end): array
    {
        return array_map(
            fn (int $number): string => $prefix.$number,
            range($start, $end),
        );
    }
}
