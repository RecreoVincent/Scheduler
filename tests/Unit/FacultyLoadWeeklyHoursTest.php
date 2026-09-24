<?php

namespace Tests\Unit;

use App\Models\Subject;
use App\Services\FacultyLoadWeeklyHours;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FacultyLoadWeeklyHoursTest extends TestCase
{
    #[DataProvider('subjectTypes')]
    public function test_it_uses_the_configured_weekly_hours_for_each_subject_type(
        string $classification,
        string $subjectType,
        float $expectedHours,
    ): void {
        $subject = new Subject([
            'classification' => $classification,
            'subject_type' => $subjectType,
        ]);

        $this->assertSame($expectedHours, FacultyLoadWeeklyHours::forSubject($subject));
    }

    /** @return array<string, array{string, string, float}> */
    public static function subjectTypes(): array
    {
        return [
            'major lecture' => ['Major', 'Lecture', 3.0],
            'major laboratory' => ['Major', 'Laboratory', 5.0],
            'minor lecture' => ['Minor', 'Lecture', 3.0],
            'minor laboratory' => ['Minor', 'Laboratory', 3.0],
            'internship' => ['Major', 'Internship', 6.0],
        ];
    }
}
