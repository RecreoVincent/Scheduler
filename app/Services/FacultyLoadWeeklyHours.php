<?php

namespace App\Services;

use App\Models\Subject;

/**
 * Converts a scheduled subject into its credited weekly faculty-load hours.
 */
final class FacultyLoadWeeklyHours
{
    public static function forSubject(?Subject $subject): float
    {
        if ($subject === null) {
            return 0.0;
        }

        $subjectType = strtolower(trim((string) $subject->subject_type));
        $classification = strtolower(trim((string) $subject->classification));

        return match (true) {
            $subjectType === 'internship' => 6.0,
            $classification === 'major' && $subjectType === 'laboratory' => 5.0,
            default => 3.0,
        };
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
