<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstructorIdGenerator
{
    /**
     * Return the next ID that is expected to be issued. The value is only a
     * preview; next() remains the source of truth when the form is saved.
     */
    public function preview(): string
    {
        $year = (int) now()->format('Y');
        $sequence = DB::table('instructor_id_sequences')
            ->where('year', $year)
            ->value('next_sequence') ?? 0;

        return sprintf('%d-%04d', $year, $sequence);
    }

    /**
     * Reserve the next four-digit instructor number for the current year.
     *
     * Reserving the number in its own short transaction keeps simultaneous
     * instructor creations from receiving the same ID. A cancelled creation
     * may leave a harmless gap, but an ID is never reused.
     */
    public function next(): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($year): string {
            DB::table('instructor_id_sequences')->insertOrIgnore([
                'year' => $year,
                'next_sequence' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('instructor_id_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->value('next_sequence');

            if ($sequence === null || $sequence > 9999) {
                throw new RuntimeException("No instructor IDs remain for {$year}.");
            }

            DB::table('instructor_id_sequences')
                ->where('year', $year)
                ->update([
                    'next_sequence' => $sequence + 1,
                    'updated_at' => now(),
                ]);

            return sprintf('%d-%04d', $year, $sequence);
        }, 3);
    }
}
