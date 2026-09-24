<?php

namespace App\Http\Controllers\Gec;

use App\Models\ClassSchedule;
use App\Models\User;
use App\Services\FacultyLoadWeeklyHours;
use App\Services\IndividualFacultyLoadExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

class PrintController extends GecController
{
    private const PROGRAM_LABEL = 'General Education Courses (Minor Subjects)';

    private const DEPARTMENT_LABEL = 'General Education Course — All Departments';

    public function __construct(private readonly IndividualFacultyLoadExcelExporter $excelExporter) {}

    public function index(): View
    {
        return view('gec.print.index', ['course' => 'GEC']);
    }

    public function report(Request $request, string $type): View
    {
        abort_unless(in_array($type, ['teaching-loads', 'instructor-workload', 'class-schedules'], true), 404);
        $course = 'GEC';
        $dean = $request->user();
        $schedules = $this->minorSchedules()
            ->with(['section', 'subject', 'instructor', 'room'])
            ->orderByRaw(ClassSchedule::dayOrderSql())->orderBy('start_time')->get();
        $instructors = User::where('role', 'instructor')
            ->forDepartment('GEC')
            ->orderBy('last_name')
            ->get();

        $program = self::PROGRAM_LABEL;
        $department = self::DEPARTMENT_LABEL;

        $instructorReports = $this->instructorReports($instructors, $schedules);

        $teachingLoadPeriods = $schedules->groupBy(fn (ClassSchedule $schedule): string => implode('|', [
            $schedule->academic_year,
            $schedule->semester,
        ]));

        if ($teachingLoadPeriods->isEmpty()) {
            $teachingLoadPeriods = collect(['unassigned' => collect()]);
        }

        $teachingLoadReports = $teachingLoadPeriods->map(function ($periodSchedules) use ($instructors): array {
            $first = $periodSchedules->first();
            $entries = $instructors->map(function (User $instructor) use ($periodSchedules): array {
                $assignedSchedules = $periodSchedules->where('instructor_id', $instructor->id);
                $totalUnits = (float) $assignedSchedules->sum(
                    fn (ClassSchedule $schedule): float => (float) ($schedule->subject?->units ?? 0),
                );
                $regularLimit = match ($instructor->employment_type) {
                    'full_time' => 30,
                    'industry_part_time', 'flexible_part_time', 'part_time' => 15,
                    default => $totalUnits,
                };
                $regularLoad = min($totalUnits, $regularLimit);
                $overload = max(0, $totalUnits - $regularLimit);
                $subjects = $assignedSchedules->map(function (ClassSchedule $schedule): string {
                    return (string) ($schedule->subject?->name ?? '');
                })->filter()->unique()->values();

                return [
                    'instructor' => $instructor,
                    'subjects' => $subjects,
                    'load' => $regularLoad,
                    'other_load' => 0.0,
                    'overload' => $overload,
                    'total' => $totalUnits,
                ];
            });

            return [
                'academic_year' => $first?->academic_year,
                'semester' => $first?->semester,
                'full_time' => $entries->where('instructor.employment_type', 'full_time')->values(),
                'part_time' => $entries->reject(
                    fn (array $entry): bool => $entry['instructor']->employment_type === 'full_time',
                )->values(),
            ];
        })->values();

        $scheduleReports = $schedules
            ->filter(fn (ClassSchedule $schedule): bool => $schedule->section !== null)
            ->groupBy(fn (ClassSchedule $schedule): string => implode('|', [
                $schedule->section_id,
                $schedule->academic_year,
                $schedule->semester,
            ]))
            ->map(function ($sectionSchedules): array {
                $first = $sectionSchedules->first();
                $subjects = $sectionSchedules->pluck('subject')->filter()->unique('id');

                return [
                    'section' => $first->section,
                    'academic_year' => $first->academic_year,
                    'semester' => $first->semester,
                    'schedules' => $sectionSchedules->values(),
                    'total_units' => (float) $subjects->sum('units'),
                ];
            })
            ->sortBy(fn (array $report): string => sprintf(
                '%02d|%s|%s|%s',
                (int) $report['section']->year_level,
                strtoupper((string) $report['section']->name),
                $report['academic_year'],
                $report['semester'],
            ))
            ->values();

        return view('gec.print.report', compact(
            'course',
            'type',
            'schedules',
            'instructors',
            'program',
            'department',
            'instructorReports',
            'teachingLoadReports',
            'scheduleReports',
            'dean',
        ));
    }

    public function instructorWorkloadExcel(Request $request): Response
    {
        $course = 'GEC';
        $dean = $request->user();
        $schedules = $this->minorSchedules()
            ->with(['section', 'subject', 'instructor', 'room'])
            ->orderByRaw(ClassSchedule::dayOrderSql())
            ->orderBy('start_time')
            ->get();
        $instructors = User::where('role', 'instructor')
            ->forDepartment('GEC')
            ->orderBy('last_name')
            ->get();

        abort_if($instructors->isEmpty(), 404, 'No GEC instructors are available to export.');

        $workbook = $this->excelExporter->export(
            $course,
            self::DEPARTMENT_LABEL,
            $dean,
            $this->instructorReports($instructors, $schedules),
        );
        $filename = 'individual-faculty-load-sheets-gec-'.now()->format('Ymd-His').'.xlsx';

        return response($workbook, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
            'Content-Transfer-Encoding' => 'binary',
            'Content-Length' => (string) strlen($workbook),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * @param  Collection<int, User>  $instructors
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return Collection<int, array<string, mixed>>
     */
    private function instructorReports(Collection $instructors, Collection $schedules): Collection
    {
        $defaultPeriod = $schedules->first();

        return $instructors->flatMap(function (User $instructor) use ($schedules, $defaultPeriod) {
            $assignedSchedules = $schedules->where('instructor_id', $instructor->id);
            $periods = $assignedSchedules->groupBy(fn (ClassSchedule $schedule): string => implode('|', [
                $schedule->academic_year,
                $schedule->semester,
            ]));

            if ($periods->isEmpty()) {
                $periods = collect(['unassigned' => collect()]);
            }

            return $periods->map(function ($periodSchedules) use ($instructor, $defaultPeriod): array {
                $first = $periodSchedules->first() ?? $defaultPeriod;
                $totalUnits = (float) $periodSchedules->sum(
                    fn (ClassSchedule $schedule): float => FacultyLoadWeeklyHours::totalUnitsForSubject($schedule->subject),
                );
                $totalHours = (float) $periodSchedules->sum(
                    fn (ClassSchedule $schedule): float => FacultyLoadWeeklyHours::forSubject($schedule->subject),
                );

                return [
                    'instructor' => $instructor,
                    'academic_year' => $first?->academic_year,
                    'semester' => $first?->semester,
                    'schedules' => $periodSchedules->values(),
                    'total_units' => $totalUnits,
                    'total_hours' => $totalHours,
                ];
            })->values();
        })->values();
    }
}
