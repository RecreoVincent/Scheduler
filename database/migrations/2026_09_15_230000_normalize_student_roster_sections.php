<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $canonicalSectionNames = [];

        DB::table('academic_sections')
            ->orderByDesc('academic_year')
            ->orderBy('id')
            ->pluck('name')
            ->each(function (string $name) use (&$canonicalSectionNames): void {
                $canonicalSectionNames[$this->sectionKey($name)] ??= $name;
            });

        if ($canonicalSectionNames === []) {
            return;
        }

        DB::table('student_rosters')
            ->select(['id', 'section'])
            ->whereNotNull('section')
            ->orderBy('id')
            ->eachById(function (object $roster) use ($canonicalSectionNames): void {
                $canonicalName = $canonicalSectionNames[$this->sectionKey($roster->section)] ?? null;

                if ($canonicalName && $canonicalName !== $roster->section) {
                    DB::table('student_rosters')
                        ->where('id', $roster->id)
                        ->update(['section' => $canonicalName, 'updated_at' => now()]);
                }
            });
    }

    public function down(): void
    {
        // The original spelling is not retained, so this data normalization is irreversible.
    }

    private function sectionKey(string $section): string
    {
        $section = preg_replace('/[\s-]+/', '', trim($section)) ?? '';

        return strtolower($section);
    }
};
