<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give current instructor accounts an ID as well, so the new table column
     * has a value for accounts created before automatic IDs were introduced.
     */
    public function up(): void
    {
        $year = (int) now()->format('Y');
        $nextSequence = (int) (DB::table('instructor_id_sequences')
            ->where('year', $year)
            ->value('next_sequence') ?? 0);

        $existingSequences = DB::table('users')
            ->where('instructor_id', 'like', "{$year}-%")
            ->pluck('instructor_id')
            ->map(fn (string $id): int => (int) substr($id, -4));

        if ($existingSequences->isNotEmpty()) {
            $nextSequence = max($nextSequence, $existingSequences->max() + 1);
        }

        DB::table('users')
            ->select('id')
            ->where('role', 'instructor')
            ->whereNull('instructor_id')
            ->orderBy('id')
            ->chunkById(100, function ($instructors) use ($year, &$nextSequence): void {
                foreach ($instructors as $instructor) {
                    if ($nextSequence > 9999) {
                        throw new RuntimeException("No instructor IDs remain for {$year}.");
                    }

                    DB::table('users')
                        ->where('id', $instructor->id)
                        ->update(['instructor_id' => sprintf('%d-%04d', $year, $nextSequence)]);

                    $nextSequence++;
                }
            });

        DB::table('instructor_id_sequences')->updateOrInsert(
            ['year' => $year],
            ['next_sequence' => $nextSequence, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /**
     * Instructor IDs must remain stable once assigned.
     */
    public function down(): void
    {
    }
};
