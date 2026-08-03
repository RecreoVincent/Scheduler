<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class InstructorController extends Controller
{
    protected function instructor(Request $request): User
    {
        /** @var User $instructor */
        $instructor = $request->user();

        return $instructor;
    }

    /** @return Builder<ClassSchedule> */
    protected function schedules(Request $request): Builder
    {
        return ClassSchedule::query()->where('instructor_id', $this->instructor($request)->id);
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
