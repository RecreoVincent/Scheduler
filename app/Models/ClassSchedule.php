<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['course', 'section_id', 'subject_id', 'instructor_id', 'room_id', 'academic_year', 'semester', 'day', 'start_time', 'end_time'])]
class ClassSchedule extends Model
{
    use SoftDeletes;

    public const DAY_PATTERNS = [
        'M - W' => ['Monday', 'Wednesday'],
        'T - Th' => ['Tuesday', 'Thursday'],
        'F - S' => ['Friday', 'Saturday'],
    ];

    /** @return array<int, string> */
    public static function daysForPattern(string $pattern): array
    {
        return self::DAY_PATTERNS[$pattern] ?? [$pattern];
    }

    /** @return array<int, string> */
    public static function patternsForDay(string $day): array
    {
        return collect(self::DAY_PATTERNS)
            ->filter(fn (array $days): bool => in_array($day, $days, true))
            ->keys()
            ->push($day)
            ->all();
    }

    /** @return array<int, string> */
    public static function conflictingDayPatterns(string $pattern): array
    {
        $days = self::daysForPattern($pattern);
        $patterns = collect(self::DAY_PATTERNS)
            ->filter(fn (array $candidateDays): bool => array_intersect($days, $candidateDays) !== [])
            ->keys();

        return $patterns->merge($days)->unique()->values()->all();
    }

    public static function dayOrderSql(): string
    {
        return "CASE day WHEN 'M - W' THEN 1 WHEN 'Monday' THEN 1 WHEN 'Wednesday' THEN 1 WHEN 'T - Th' THEN 2 WHEN 'Tuesday' THEN 2 WHEN 'Thursday' THEN 2 WHEN 'F - S' THEN 3 WHEN 'Friday' THEN 3 WHEN 'Saturday' THEN 3 ELSE 4 END";
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcademicSection::class, 'section_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
