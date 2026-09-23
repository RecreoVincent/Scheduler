<?php

namespace App\Http\Controllers\Gec;

use App\Http\Controllers\Controller;
use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class GecController extends Controller
{
    /** Real academic departments whose Minor subjects GEC staffs and schedules. */
    public const REAL_DEPARTMENTS = ['BSIT', 'BSBA', 'BSHM', 'BSED', 'BEED'];

    protected function course(Request $request): string
    {
        return 'GEC';
    }

    /** @return array<int, string> */
    protected function enabledSemesters(Request $request): array
    {
        $semester = $request->session()->get('gec.active_semester');

        if (in_array($semester, ['1st', '2nd', 'Summer'], true)) {
            return [$semester];
        }

        // Keep the existing department configuration as the first-visit default.
        // Subsequent selections are stored in this browser session only.
        return $request->user()->department?->enabledSemesterCodes() ?? ['1st', '2nd', 'Summer'];
    }

    protected function minorSubjects(): Builder
    {
        return Subject::whereIn('course', self::REAL_DEPARTMENTS)->where('classification', 'Minor');
    }

    protected function ensureMinorSubject(Subject $subject): void
    {
        abort_unless(
            in_array(strtoupper((string) $subject->course), self::REAL_DEPARTMENTS, true)
                && strcasecmp((string) $subject->classification, 'Minor') === 0,
            404,
        );
    }

    protected function minorSchedules(): Builder
    {
        return ClassSchedule::whereIn('course', self::REAL_DEPARTMENTS)
            ->whereHas('subject', fn (Builder $query) => $query->where('classification', 'Minor'));
    }

    protected function ensureMinorSchedule(ClassSchedule $schedule): void
    {
        $schedule->loadMissing('subject');
        abort_unless(
            in_array(strtoupper((string) $schedule->course), self::REAL_DEPARTMENTS, true)
                && strcasecmp((string) $schedule->subject?->classification, 'Minor') === 0,
            404,
        );
    }

    protected function ensureRealDepartmentSection(AcademicSection $section): void
    {
        abort_unless(in_array(strtoupper((string) $section->course), self::REAL_DEPARTMENTS, true), 404);
    }
}
