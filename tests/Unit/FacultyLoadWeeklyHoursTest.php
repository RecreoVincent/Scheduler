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

    #[DataProvider('facultyLoadSheetValues')]
    public function test_it_uses_the_required_values_on_the_individual_faculty_load_sheet(
        string $classification,
        string $subjectType,
        array $expectedLoad,
    ): void {
        $subject = new Subject([
            'classification' => $classification,
            'subject_type' => $subjectType,
        ]);

        $this->assertSame($expectedLoad, FacultyLoadWeeklyHours::loadForSubject($subject));
    }

    /** @return array<string, array{string, string, array{lecture_units:float, laboratory_units:float, total_units:float, total_hours:float}}> */
    public static function facultyLoadSheetValues(): array
    {
        return [
            'major lecture' => ['Major', 'Lecture', [
                'lecture_units' => 3.0, 'laboratory_units' => 0.0, 'total_units' => 3.0, 'total_hours' => 3.0,
            ]],
            'major laboratory' => ['Major', 'Laboratory', [
                'lecture_units' => 2.0, 'laboratory_units' => 1.0, 'total_units' => 5.0, 'total_hours' => 5.0,
            ]],
            'minor subject' => ['Minor', 'Lecture', [
                'lecture_units' => 3.0, 'laboratory_units' => 0.0, 'total_units' => 3.0, 'total_hours' => 3.0,
            ]],
            'internship' => ['Major', 'Internship', [
                'lecture_units' => 6.0, 'laboratory_units' => 0.0, 'total_units' => 6.0, 'total_hours' => 6.0,
            ]],
        ];
    }

    #[DataProvider('requiredCreditUnits')]
    public function test_it_uses_the_standard_credit_units_for_each_subject_type(
        string $subjectType,
        float $expectedUnits,
    ): void {
        $this->assertSame($expectedUnits, FacultyLoadWeeklyHours::requiredCreditUnits($subjectType));
    }

    /** @return array<string, array{string, float}> */
    public static function requiredCreditUnits(): array
    {
        return [
            'lecture' => ['Lecture', 3.0],
            'laboratory' => ['Laboratory', 3.0],
            'internship' => ['Internship', 6.0],
        ];
    }
}
