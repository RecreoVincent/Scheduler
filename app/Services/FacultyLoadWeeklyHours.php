<?php

namespace App\Services;

use App\Models\Subject;

/**
 * Converts a scheduled subject into its faculty-load-sheet values.
 *
 * The catalog credit-unit value is kept separately on the subject record.
 * These values are the official workload representation used for faculty
 * loading and workload-capacity checks.
 */
final class FacultyLoadWeeklyHours
{
    /**
     * @return array{lecture_units:float, laboratory_units:float, total_units:float, total_hours:float}
     */
    public static function loadForSubject(?Subject $subject): array
    {
        if ($subject === null) {
            return [
                'lecture_units' => 0.0,
                'laboratory_units' => 0.0,
                'total_units' => 0.0,
                'total_hours' => 0.0,
            ];
        }

        $subjectType = strtolower(trim((string) $subject->subject_type));
        $classification = strtolower(trim((string) $subject->classification));

        if ($subjectType === 'internship') {
            return [
                'lecture_units' => 6.0,
                'laboratory_units' => 0.0,
                'total_units' => 6.0,
                'total_hours' => 6.0,
            ];
        }

        if ($classification === 'major' && $subjectType === 'laboratory') {
            return [
                'lecture_units' => 2.0,
                'laboratory_units' => 1.0,
                'total_units' => 5.0,
                'total_hours' => 5.0,
            ];
        }

        return [
            'lecture_units' => 3.0,
            'laboratory_units' => 0.0,
            'total_units' => 3.0,
            'total_hours' => 3.0,
        ];
    }

    public static function forSubject(?Subject $subject): float
    {
        return self::loadForSubject($subject)['total_hours'];
    }

    public static function totalUnitsForSubject(?Subject $subject): float
    {
        return self::loadForSubject($subject)['total_units'];
    }

    public static function requiredCreditUnits(string $subjectType): float
    {
        return strtolower(trim($subjectType)) === 'internship' ? 6.0 : 3.0;
    }

    public static function sqlExpression(string $subjectTable = 'subjects'): string
    {
        return "CASE
            WHEN LOWER(TRIM({$subjectTable}.subject_type)) = 'internship' THEN 6
            WHEN LOWER(TRIM({$subjectTable}.classification)) = 'major'
                AND LOWER(TRIM({$subjectTable}.subject_type)) = 'laboratory' THEN 5
            ELSE 3
        END";
    }
}
