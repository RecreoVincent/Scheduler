<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class StudentController extends Controller
{
    protected function student(Request $request): User
    {
        /** @var User $student */
        $student = $request->user();

        return $student;
    }

    /** @return Builder<ClassSchedule> */
    protected function schedules(Request $request): Builder
    {
        $sectionId = $this->student($request)->academic_section_id;

        return ClassSchedule::query()->when(
            $sectionId,
            fn (Builder $query) => $query
                ->where('section_id', $sectionId)
                ->where('semester', $this->viewingSemester($request)),
            fn (Builder $query) => $query->whereRaw('1 = 0'),
        );
    }

    protected function viewingSemester(Request $request): string
    {
        $selectedSemester = $request->session()->get('student_viewing_semester');

        if (in_array($selectedSemester, ['1st', '2nd', 'Summer'], true)) {
            return $selectedSemester;
        }

        return $this->student($request)->department?->enabledSemesterCodes()[0] ?? '1st';
    }

    protected function weeklyMinutes(Builder $query): int
    {
        return $query->get(['day', 'start_time', 'end_time'])->sum(function (ClassSchedule $schedule): int {
            $start = strtotime((string) $schedule->start_time);
            $end = strtotime((string) $schedule->end_time);

            return max(0, (int) (($end - $start) / 60)) * count(ClassSchedule::daysForPattern($schedule->day));
        });
    }

    protected function formattedHours(int $minutes): string
    {
        $hours = $minutes / 60;

        return fmod($hours, 1.0) === 0.0 ? number_format($hours, 0) : number_format($hours, 1);
    }
}
