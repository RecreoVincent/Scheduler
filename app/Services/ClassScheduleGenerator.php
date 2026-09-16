<?php

namespace App\Services;

use App\Exceptions\ScheduleGenerationException;
use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassScheduleGenerator
{
    public const LABORATORY_PRIORITY_SUBJECT_CODES = [
        'ITE 111',
        'ITE 112',
        'ITE 113',
        'ITE 211',
        'ITE 212',
        'ITE 213',
        'ITE 214',
    ];

    private const WEEKDAY_DAY_PATTERNS = ['M - W', 'T - Th'];

    private const MAJOR_DAY_PATTERNS = ['M - W', 'T - Th', 'F - S'];

    private const MINOR_DAY_PATTERNS = ['M - W', 'T - Th', 'F - S'];

    private const MAX_INSTRUCTOR_SCHEDULES_PER_DAY_PATTERN = 3;

    private const BSHM_KITCHEN_KEYWORDS = [
        'cook',
        'cookery',
        'culinary',
        'kitchen',
        'food preparation',
        'food production',
        'baking',
        'bakery',
        'pastry',
        'beverage',
        'drink',
        'bartend',
        'bar service',
        'mixology',
    ];

    private const TIME_SLOTS = [
        ['08:30', '11:00'],
        ['13:00', '15:30'],
        ['16:30', '19:00'],
    ];

    /**
     * Schedules already present in the selected period, including entries
     * created during the current transaction, indexed by constrained resource.
     *
     * @var array<string, array<int, array<int, array{day:string,start:string,end:string,subject_code:string}>>>
     */
    private array $conflictIndex = [
        'section_id' => [],
        'instructor_id' => [],
        'room_id' => [],
    ];

    /**
     * Generate all requested entries as a single transaction.
     *
     * @param  Collection<int, AcademicSection>  $sections
     * @param  Collection<int, Subject>  $subjects
     * @param  Collection<int, Room>  $rooms
     * @param  Collection<int, User>  $fallbackInstructors
     * @param  array{academic_year:string, semester:string}  $period
     */
    public function generate(
        string $course,
        Collection $sections,
        Collection $subjects,
        Collection $rooms,
        Collection $fallbackInstructors,
        array $period,
        ?int $seed = null,
    ): int {
        // Keep one seed for the whole transaction so every retry produces the
        // same internally consistent plan, while a new generation request gets
        // a fresh section/subject arrangement.
        $seed ??= random_int(1, 2_147_483_647);

        return DB::transaction(function () use ($course, $sections, $subjects, $rooms, $fallbackInstructors, $period, $seed): int {
            $sections = $sections
                // Lower years still receive priority. Only sections within the
                // same year are randomized.
                ->sortBy(fn (AcademicSection $section): string => sprintf(
                    '%02d-%s',
                    (int) $section->year_level,
                    $this->randomRank($seed, 'section', $section->id),
                ))
                ->values();

            $classification = $subjects->first()?->classification;

            ClassSchedule::forDepartment($course)
                ->whereIn('section_id', $sections->pluck('id'))
                ->forAcademicPeriod($period['academic_year'], $period['semester'])
                ->when(
                    $classification !== null,
                    fn ($query) => $query->whereHas('subject', fn ($subjectQuery) => $subjectQuery->where('classification', $classification)),
                )
                ->delete();

            // Serialize competing generators against the existing schedules in this period.
            $periodSchedules = ClassSchedule::with('subject:id,code,units')
                ->forAcademicPeriod($period['academic_year'], $period['semester'])
                ->lockForUpdate()
                ->get();

            $this->initializeConflictIndex($periodSchedules);
            $workloads = $this->workloadsFromSchedules($periodSchedules);
            $created = 0;
            $assignedInstructorIds = collect();
            $sectionIndexesByYear = [];
            $sectionPlans = collect();

            foreach ($sections as $section) {
                $yearLevel = (int) $section->year_level;
                $sectionIndex = $sectionIndexesByYear[$yearLevel] ?? 0;
                $sectionIndexesByYear[$yearLevel] = $sectionIndex + 1;
                $yearSubjects = $subjects
                    ->where('year_level', $section->year_level)
                    ->values();
                $sectionSubjects = $this->randomizeSubjects(
                    $yearSubjects,
                    $seed,
                    $section->id,
                    $sectionIndex,
                );

                $sectionPlans->put($section->id, [
                    'subjects' => $sectionSubjects,
                    'day_pattern_loads' => array_fill_keys(array_keys(ClassSchedule::DAY_PATTERNS), 0),
                ]);
            }

            // Schedule lower years before upper years. Within each tier,
            // designated laboratory subjects reserve rooms across every
            // section before the remaining subjects are considered.
            $schedulingPhases = [
                ['lower_years' => true, 'laboratory_priority' => true],
                ['lower_years' => true, 'laboratory_priority' => false],
                ['lower_years' => false, 'laboratory_priority' => true],
                ['lower_years' => false, 'laboratory_priority' => false],
            ];

            foreach ($schedulingPhases as $phase) {
                foreach ($sections as $section) {
                    $isLowerYear = (int) $section->year_level <= 2;
                    if ($isLowerYear !== $phase['lower_years']) {
                        continue;
                    }

                    $plan = $sectionPlans->get($section->id);
                    $dayPatternLoads = $plan['day_pattern_loads'];
                    $phaseSubjects = $plan['subjects']->filter(
                        fn (Subject $subject): bool => $this->isLaboratoryPrioritySubject($subject) === $phase['laboratory_priority'],
                    );

                    foreach ($phaseSubjects as $subject) {
                        $roomMustBeTba = $course === 'BSIT' && $this->isMinor($subject);
                        $roomFallbackIsTba = $this->subjectCanUseTba($course, $subject);
                        $matchingRooms = $roomMustBeTba
                            ? collect([null])
                            : $rooms
                                ->filter(fn (Room $room): bool => $this->roomIsCompatible($course, $subject, $room))
                                ->values();

                        $preferred = $subject->instructors->where('account_status', 'active')->values();
                        $instructorPool = ($preferred->isNotEmpty() ? $preferred : $fallbackInstructors)
                            ->unique('id')
                            ->values();
                        $candidates = $instructorPool
                            ->filter(fn (User $instructor): bool => $this->canAcceptUnits($instructor, (float) $subject->units, $workloads));

                        // Subject instructors are stored in the Dean's chosen
                        // priority order. Preserve that order so Priority 1
                        // receives sections until unavailable or at capacity,
                        // followed by each selected backup priority.
                        if ($preferred->isEmpty()) {
                            $candidates = $candidates->sortByDesc(
                                fn (User $instructor): float => $this->targetUnits($instructor) - ($workloads[$instructor->id] ?? 0.0),
                            );
                        }

                        $candidates = $candidates->values();

                        if ($candidates->isEmpty()) {
                            $loadDetails = $instructorPool->map(function (User $instructor) use ($workloads): string {
                                $currentUnits = $workloads[$instructor->id] ?? 0.0;

                                return "{$instructor->name}: {$currentUnits}/{$this->targetUnits($instructor)} units";
                            })->join('; ');

                            throw new ScheduleGenerationException(
                                "No assigned instructor has enough workload capacity for {$subject->code}.",
                                "{$subject->code} needs {$subject->units} more units. Current assigned-instructor loads: {$loadDetails}. Assign an instructor whose remaining capacity can accommodate this subject.",
                            );
                        }

                        $allowTbaFallback = $roomFallbackIsTba && ! $roomMustBeTba;
                        // First Year sections have a hard requirement to cover
                        // every meeting pattern, so for them a still-needed
                        // pattern filled via TBA beats a real room in a
                        // pattern the section already has. Other year levels
                        // have no such requirement, so they keep maximizing
                        // real room usage first and only fall back to TBA
                        // when nothing else works at all.
                        $prioritizeSectionPattern = $allowTbaFallback && (int) $section->year_level === 1;

                        if ($prioritizeSectionPattern) {
                            $diagnosticRooms = $matchingRooms->concat([null]);
                            $assignment = $this->findAssignment($section, $subject, $candidates, $matchingRooms, $dayPatternLoads, true);
                        } else {
                            $diagnosticRooms = $matchingRooms;
                            $assignment = $matchingRooms->isEmpty()
                                ? null
                                : $this->findAssignment($section, $subject, $candidates, $matchingRooms, $dayPatternLoads);

                            if ($assignment === null && $allowTbaFallback) {
                                $diagnosticRooms = collect([null]);
                                $assignment = $this->findAssignment($section, $subject, $candidates, $diagnosticRooms, $dayPatternLoads);
                            }
                        }

                        if ($assignment === null) {
                            throw new ScheduleGenerationException(
                                "Unable to schedule {$section->name} - {$subject->code}: every allowed day and time is already being used by the section, an assigned instructor, or a compatible room.",
                                $this->assignmentFailureGuidance(
                                    $section,
                                    $subject,
                                    $candidates,
                                    $diagnosticRooms,
                                ),
                            );
                        }

                        ClassSchedule::create([
                            'course' => $course,
                            'section_id' => $section->id,
                            'subject_id' => $subject->id,
                            'instructor_id' => $assignment['instructor']->id,
                            'room_id' => $assignment['room']?->id,
                            'academic_year' => $period['academic_year'],
                            'semester' => $period['semester'],
                            'day' => $assignment['day'],
                            'start_time' => $assignment['start'],
                            'end_time' => $assignment['end'],
                        ]);
                        $this->addConflictEntry(
                            $section->id,
                            $assignment['instructor']->id,
                            $assignment['room']?->id,
                            $assignment['day'],
                            $assignment['start'],
                            $assignment['end'],
                            (string) $subject->code,
                        );

                        $workloads[$assignment['instructor']->id] = ($workloads[$assignment['instructor']->id] ?? 0.0) + (float) $subject->units;
                        $dayPatternLoads[$assignment['day']]++;
                        $assignedInstructorIds->push($assignment['instructor']->id);
                        $created++;
                    }

                    $plan['day_pattern_loads'] = $dayPatternLoads;
                    $sectionPlans->put($section->id, $plan);
                }
            }

            foreach ($sections as $section) {
                $this->ensureFirstYearCoversAllDays(
                    $section,
                    $sectionPlans->get($section->id)['day_pattern_loads'],
                );
            }

            $this->ensureCompletedWorkloadsAreValid($assignedInstructorIds->unique(), $workloads);

            return $created;
        });
    }

    public function isLaboratoryPrioritySubject(Subject $subject): bool
    {
        return in_array($this->normalizedSubjectCode($subject), self::LABORATORY_PRIORITY_SUBJECT_CODES, true);
    }

    public function subjectCanUseTba(string $course, Subject $subject): bool
    {
        // Year 1 and 2 sections are scheduled first and get first claim on
        // whatever compatible rooms exist. Once rooms run out — for any
        // department, not just BSIT — the remaining sections are marked
        // TBA instead of blocking generation entirely.
        return true;
    }

    public function roomIsCompatible(string $course, Subject $subject, Room $room): bool
    {
        // Minor subjects never claim a real room, in any department — they
        // always fall back to TBA so they can't compete with that
        // department's own Major-subject room bookings (e.g. GEC-generated
        // schedules for BSBA/BSED/BEED/BSHM must not contend with that
        // department's Major-subject scheduling for the same rooms).
        if ($this->isMinor($subject)) {
            return false;
        }

        return match (strtoupper($course)) {
            'BSIT' => $this->isLaboratoryRoom($room),
            'BSBA', 'BSED', 'BEED' => true,
            'BSHM' => $this->isBshmKitchenSubject($subject)
                ? $this->isKitchenLaboratoryRoom($room)
                : $this->isLectureRoom($room),
            default => strcasecmp((string) $room->room_type, (string) $subject->subject_type) === 0,
        };
    }

    public function roomRequirementLabel(string $course, Subject $subject): string
    {
        return match (strtoupper($course)) {
            'BSIT' => $this->isMinor($subject) ? 'TBA room' : 'laboratory room',
            'BSBA', 'BSED', 'BEED' => 'department room',
            'BSHM' => ! $this->isMinor($subject) && $this->isBshmKitchenSubject($subject) ? 'kitchen laboratory room' : 'lecture room',
            default => strtolower((string) $subject->subject_type).' room',
        };
    }

    private function isMinor(Subject $subject): bool
    {
        return strcasecmp((string) $subject->classification, 'Minor') === 0;
    }

    private function isBshmKitchenSubject(Subject $subject): bool
    {
        $description = strtolower(trim((string) $subject->code.' '.(string) $subject->name));

        return collect(self::BSHM_KITCHEN_KEYWORDS)
            ->contains(fn (string $keyword): bool => str_contains($description, $keyword));
    }

    private function isLaboratoryRoom(Room $room): bool
    {
        $description = strtolower(trim((string) $room->room_type.' '.(string) $room->name));

        return str_contains($description, 'laboratory') || preg_match('/\blab\b/', $description) === 1;
    }

    private function isKitchenLaboratoryRoom(Room $room): bool
    {
        $description = strtolower(trim((string) $room->room_type.' '.(string) $room->name));

        return $this->isLaboratoryRoom($room)
            || str_contains($description, 'kitchen')
            || str_contains($description, 'bar');
    }

    private function isLectureRoom(Room $room): bool
    {
        return ! $this->isKitchenLaboratoryRoom($room)
            && strcasecmp((string) $room->room_type, 'Lecture') === 0;
    }

    private function normalizedSubjectCode(Subject $subject): string
    {
        return preg_replace('/\s+/', ' ', strtoupper(trim((string) $subject->code))) ?? '';
    }

    /** @param Collection<int, Subject> $subjects */
    private function randomizeSubjects(Collection $subjects, int $seed, int $sectionId, int $sectionIndex): Collection
    {
        return $subjects
            ->sortBy(fn (Subject $subject): string => $this->randomRank(
                $seed,
                'subject',
                $sectionId,
                $sectionIndex,
                $subject->id,
            ))
            ->values();
    }

    private function randomRank(int $seed, string $type, int ...$identifiers): string
    {
        return hash('sha256', implode(':', [$seed, $type, ...$identifiers]));
    }

    public function assignmentRuleViolation(ClassSchedule $schedule, User $instructor, ?Room $room, string $day, string $start, string $end): ?string
    {
        $start = substr($start, 0, 5);
        $end = substr($end, 0, 5);

        if ($start < '07:00' || $end > '19:00') {
            return 'Classes must be scheduled between 7:00 AM and 7:00 PM.';
        }

        if ($start < '13:00' && $end > '12:00') {
            return 'Classes cannot overlap the 12:00 PM to 1:00 PM lunch break.';
        }

        $subject = $schedule->subject;
        $durationMinutes = (int) ((strtotime($end) - strtotime($start)) / 60);
        $requiredDuration = $this->isMinor($subject) ? 90 : 150;
        if ($durationMinutes !== $requiredDuration) {
            return ($this->isMinor($subject) ? 'Minor' : 'Major').' subjects must use a '.($requiredDuration === 90 ? '1 hour 30 minute' : '2 hour 30 minute').' time slot.';
        }

        $allowedPatterns = $this->allowedDayPatterns($subject);
        if (! in_array($day, $allowedPatterns, true)) {
            return ($this->isMinor($subject) ? 'Minor' : 'Major').' subjects may only use these meeting days: '.implode(' or ', $allowedPatterns).'.';
        }

        $tbaIsAllowed = $this->subjectCanUseTba($schedule->course, $subject);
        if ($room === null && ! $tbaIsAllowed) {
            return "{$subject->subject_type} subjects require an assigned room.";
        }

        if ($room !== null && ! $this->roomIsCompatible($schedule->course, $subject, $room)) {
            return "{$subject->code} requires a {$this->roomRequirementLabel($schedule->course, $subject)}.";
        }

        if (! $this->instructorIsAvailable($instructor, $day, $start)) {
            return 'This Industry Part-Time instructor is unavailable before the end of their outside work hours.';
        }

        $existingDayPatternLoad = ClassSchedule::query()
            ->forAcademicPeriod($schedule->academic_year, $schedule->semester)
            ->where('instructor_id', $instructor->id)
            ->whereKeyNot($schedule->id)
            ->whereIn('day', ClassSchedule::conflictingDayPatterns($day))
            ->count();

        if ($existingDayPatternLoad >= self::MAX_INSTRUCTOR_SCHEDULES_PER_DAY_PATTERN) {
            return $instructor->name.' already has the maximum of 3 schedules on '.$day.'.';
        }

        $existingUnits = ClassSchedule::with('subject')
            ->forAcademicPeriod($schedule->academic_year, $schedule->semester)
            ->where('instructor_id', $instructor->id)
            ->whereKeyNot($schedule->id)
            ->get()
            ->sum(fn (ClassSchedule $entry): float => (float) $entry->subject?->units);

        if ($existingUnits + (float) $subject->units > $this->targetUnits($instructor)) {
            return $instructor->name.' would exceed the '.$this->workloadLabel($instructor).' teaching-unit limit.';
        }

        return null;
    }

    /** @return array{0:int, 1:int} */
    public function workloadRange(User $instructor): array
    {
        return [0, $instructor->effectiveTeachingUnitLimit()];
    }

    private function targetUnits(User $instructor): float
    {
        return (float) $this->workloadRange($instructor)[1];
    }

    private function workloadLabel(User $instructor): string
    {
        [$minimum, $maximum] = $this->workloadRange($instructor);

        return $minimum === $maximum ? "{$maximum}-unit" : "{$minimum}-{$maximum}-unit";
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return array<int, float>
     */
    private function workloadsFromSchedules(Collection $schedules): array
    {
        return $schedules
            ->groupBy('instructor_id')
            ->map(fn (Collection $entries): float => $entries->sum(fn (ClassSchedule $entry): float => (float) $entry->subject?->units))
            ->all();
    }

    private function canAcceptUnits(User $instructor, float $units, array $workloads): bool
    {
        return ($workloads[$instructor->id] ?? 0.0) + $units <= $this->targetUnits($instructor);
    }

    private function ensureCompletedWorkloadsAreValid(Collection $instructorIds, array $workloads): void
    {
        foreach (User::whereIn('id', $instructorIds)->get() as $instructor) {
            [, $maximum] = $this->workloadRange($instructor);
            $units = $workloads[$instructor->id] ?? 0.0;

            if ($units > $maximum) {
                $excessUnits = $units - $maximum;

                throw new ScheduleGenerationException(
                    "{$instructor->name} received {$units} teaching units, which is {$excessUnits} units above the {$maximum}-unit limit.",
                    "Remove at least {$excessUnits} units from {$instructor->name} or increase the instructor's configured unit limit before generating again. Instructors are not required to use their full limit.",
                );
            }
        }
    }

    /** @param array<string, int> $dayPatternLoads */
    private function ensureFirstYearCoversAllDays(AcademicSection $section, array $dayPatternLoads): void
    {
        if ((int) $section->year_level !== 1) {
            return;
        }

        $missingPatterns = collect($dayPatternLoads)
            ->filter(fn (int $count): bool => $count === 0)
            ->keys()
            ->all();

        if ($missingPatterns !== []) {
            throw new \RuntimeException("{$section->name} is a First Year section and must have classes from Monday to Saturday. Missing meeting pattern(s): ".implode(', ', $missingPatterns).'.');
        }
    }

    /**
     * @return array{instructor:User, room:Room|null, day:string, start:string, end:string}|null
     */
    private function findAssignment(AcademicSection $section, Subject $subject, Collection $candidates, Collection $rooms, array $dayPatternLoads, bool $allowTbaFallback = false): ?array
    {
        $balancedPatterns = $this->balancedDayPatterns($section, $subject, $dayPatternLoads);

        // When a still-needed pattern must win out over an already-covered
        // one (First Year pattern completion), the pattern has to be the
        // outermost loop: every candidate instructor needs a chance at the
        // needed pattern before any instructor is allowed to settle for an
        // already-covered one. Looping instructor-first would let the first
        // candidate's exhausted availability in the needed pattern silently
        // fall back to an already-covered pattern for that same instructor,
        // even when a later candidate still had room in the needed pattern —
        // occasionally starving the section of that pattern entirely.
        [$outer, $inner] = $allowTbaFallback
            ? [$balancedPatterns, $candidates]
            : [$candidates, $balancedPatterns];

        foreach ($outer as $outerItem) {
            foreach ($inner as $innerItem) {
                [$instructor, $day] = $allowTbaFallback ? [$innerItem, $outerItem] : [$outerItem, $innerItem];

                foreach ($this->timeSlotsFor($section) as [$start, $end]) {
                    if (! $this->instructorIsAvailable($instructor, $day, $start)) {
                        continue;
                    }

                    // Exhaust real rooms for this (instructor, pattern, time)
                    // combination before moving on, but don't let that search
                    // spill into a less-needed pattern before TBA is tried
                    // here first — a First Year section still missing F - S
                    // must not settle for a real room in an already-covered
                    // pattern while F - S could still be filled via TBA.
                    foreach ($rooms as $room) {
                        if ($this->slotIsAvailable($section->id, $instructor->id, $room?->id, $day, $start, $end)) {
                            return compact('instructor', 'room', 'day', 'start', 'end');
                        }
                    }

                    if ($allowTbaFallback && $this->slotIsAvailable($section->id, $instructor->id, null, $day, $start, $end)) {
                        $room = null;

                        return compact('instructor', 'room', 'day', 'start', 'end');
                    }
                }
            }
        }

        return null;
    }

    private function assignmentFailureGuidance(
        AcademicSection $section,
        Subject $subject,
        Collection $candidates,
        Collection $rooms,
    ): string {
        $patterns = $this->allowedDayPatterns($subject);
        $patternLabel = implode(' or ', $patterns);
        $duration = $this->isMinor($subject) ? '1 hour 30 minutes' : '2 hours 30 minutes';
        $requirement = "{$subject->code} is a {$subject->classification} {$subject->subject_type} subject. It requires {$duration} on {$patternLabel}.";
        $sectionFreeSlots = collect();

        foreach ($patterns as $day) {
            foreach ($this->timeSlotsFor($section) as [$start, $end]) {
                if (! $this->scheduleConflictExists('section_id', $section->id, $day, $start, $end)) {
                    $sectionFreeSlots->push(compact('day', 'start', 'end'));
                }
            }
        }

        if ($sectionFreeSlots->isEmpty()) {
            return "{$requirement} Section {$section->name} already has a class in every allowed period. Move or remove one of that section’s {$patternLabel} classes before generating again.";
        }

        $instructorFreeSlots = collect();
        $blockedInstructorDetails = collect();

        foreach ($candidates as $instructor) {
            $availableForInstructor = $sectionFreeSlots->filter(function (array $slot) use ($instructor): bool {
                return $this->instructorIsAvailable($instructor, $slot['day'], $slot['start'])
                    && $this->instructorDayPatternLoad($instructor->id, $slot['day']) < self::MAX_INSTRUCTOR_SCHEDULES_PER_DAY_PATTERN
                    && ! $this->scheduleConflictExists(
                        'instructor_id',
                        $instructor->id,
                        $slot['day'],
                        $slot['start'],
                        $slot['end'],
                    );
            });

            if ($availableForInstructor->isEmpty()) {
                $conflictingCodes = $this->conflictingSubjectCodes(
                    'instructor_id',
                    $instructor->id,
                    $sectionFreeSlots,
                );
                $reachedDailyLimit = $sectionFreeSlots->every(
                    fn (array $slot): bool => $this->instructorDayPatternLoad($instructor->id, $slot['day']) >= self::MAX_INSTRUCTOR_SCHEDULES_PER_DAY_PATTERN,
                );
                $detail = $reachedDailyLimit
                    ? "{$instructor->name} already has 3 schedules on each remaining day pair"
                    : ($conflictingCodes->isEmpty()
                        ? "{$instructor->name} is unavailable during all remaining periods"
                        : "{$instructor->name} is already teaching ".implode(', ', $conflictingCodes->all()));
                $blockedInstructorDetails->push($detail);
            }

            foreach ($availableForInstructor as $slot) {
                $instructorFreeSlots->push([...$slot, 'instructor' => $instructor]);
            }
        }

        if ($instructorFreeSlots->isEmpty()) {
            return "{$requirement} Section {$section->name} has open periods, but ".implode('; ', $blockedInstructorDetails->all()).'. Assign another instructor with available units and no class during those periods.';
        }

        $roomNames = $rooms->filter()->pluck('name')->values();
        $hasAvailableRoom = $instructorFreeSlots->contains(function (array $slot) use ($rooms): bool {
            return $rooms->contains(function (?Room $room) use ($slot): bool {
                return $room === null || ! $this->scheduleConflictExists(
                    'room_id',
                    $room->id,
                    $slot['day'],
                    $slot['start'],
                    $slot['end'],
                );
            });
        });

        if (! $hasAvailableRoom && $roomNames->isNotEmpty()) {
            return "{$requirement} Section {$section->name} and its assigned instructors have matching open periods, but every compatible room is occupied then. Rooms checked: {$roomNames->join(', ')}. Free one of these rooms or add another {$this->roomRequirementLabel($section->course, $subject)}.";
        }

        return "{$requirement} The remaining periods conflict with the current section, instructor, and room combination. Review the existing timetable for section {$section->name} and the assigned instructors before trying again.";
    }

    private function scheduleConflictExists(
        string $column,
        int $id,
        string $day,
        string $start,
        string $end,
    ): bool {
        return $this->indexedEntriesConflict(
            $this->conflictIndex[$column][$id] ?? [],
            $day,
            $start,
            $end,
        );
    }

    private function conflictingSubjectCodes(
        string $column,
        int $id,
        Collection $slots,
    ): Collection {
        return collect($this->conflictIndex[$column][$id] ?? [])
            ->filter(fn (array $entry): bool => $slots->contains(
                fn (array $slot): bool => $this->entryConflicts($entry, $slot['day'], $slot['start'], $slot['end']),
            ))
            ->pluck('subject_code')
            ->filter()
            ->unique()
            ->take(5)
            ->values();
    }

    /** @return array<int, string> */
    private function allowedDayPatterns(Subject $subject): array
    {
        return $this->isMinor($subject) ? self::MINOR_DAY_PATTERNS : self::MAJOR_DAY_PATTERNS;
    }

    /** @param array<string, int> $dayPatternLoads */
    private function balancedDayPatterns(AcademicSection $section, Subject $subject, array $dayPatternLoads): Collection
    {
        $patterns = collect($this->allowedDayPatterns($subject));

        if (! $this->isMinor($subject)) {
            // sortBy() is stable, so ties (e.g. every pattern still at 0 for a
            // section's first subject) always resolved in fixed array order,
            // meaning F - S — always last in MAJOR_DAY_PATTERNS — was
            // systematically the last pattern offered whenever another
            // section's assignment fell back onto an already-used pattern.
            // Rotating the tie-break order per section spreads that risk
            // evenly across all three patterns instead of always starving
            // the same one.
            $rotation = $patterns->count() > 0 ? $section->id % $patterns->count() : 0;
            $rotatedPatterns = $patterns->slice($rotation)->concat($patterns->slice(0, $rotation))->values();

            return $rotatedPatterns->sortBy(fn (string $pattern): int => $dayPatternLoads[$pattern] ?? 0)->values();
        }

        if ((int) $section->year_level === 1 && ($dayPatternLoads['F - S'] ?? 0) === 0) {
            return collect(['F - S'])
                ->concat(collect(self::WEEKDAY_DAY_PATTERNS)->sortBy(fn (string $pattern): int => $dayPatternLoads[$pattern] ?? 0))
                ->values();
        }

        $underfilledWeekdays = collect(self::WEEKDAY_DAY_PATTERNS)
            ->filter(fn (string $pattern): bool => ($dayPatternLoads[$pattern] ?? 0) <= 2)
            ->sortBy(fn (string $pattern): int => $dayPatternLoads[$pattern] ?? 0)
            ->values();

        if ($underfilledWeekdays->isNotEmpty()) {
            return $underfilledWeekdays
                ->concat(['F - S'])
                ->concat(collect(self::WEEKDAY_DAY_PATTERNS)->diff($underfilledWeekdays))
                ->unique()
                ->values();
        }

        return $patterns->sortBy(fn (string $pattern): int => $dayPatternLoads[$pattern] ?? 0)->values();
    }

    /** @return array<int, array{0:string, 1:string}> */
    private function timeSlotsFor(AcademicSection $section): array
    {
        // Lower-year sections claim the earlier room periods first. Higher-year
        // sections search from the end of the day, preserving room availability
        // for Years 1 and 2 even when Years 3 or 4 are generated beforehand.
        return (int) $section->year_level <= 2 ? self::TIME_SLOTS : array_reverse(self::TIME_SLOTS);
    }

    private function instructorIsAvailable(User $instructor, string $day, string $start): bool
    {
        if ($instructor->employment_type !== 'industry_part_time') {
            return true;
        }

        $outsideWorkEnd = substr((string) ($instructor->outside_work_end_time ?: '17:00'), 0, 5);

        foreach (ClassSchedule::daysForPattern($day) as $meetingDay) {
            if ($meetingDay !== 'Saturday' && $start < $outsideWorkEnd) {
                return false;
            }
        }

        return true;
    }

    private function slotIsAvailable(int $sectionId, int $instructorId, ?int $roomId, string $day, string $start, string $end): bool
    {
        if ($this->instructorDayPatternLoad($instructorId, $day) >= self::MAX_INSTRUCTOR_SCHEDULES_PER_DAY_PATTERN) {
            return false;
        }

        if ($this->scheduleConflictExists('section_id', $sectionId, $day, $start, $end)
            || $this->scheduleConflictExists('instructor_id', $instructorId, $day, $start, $end)) {
            return false;
        }

        return $roomId === null
            || ! $this->scheduleConflictExists('room_id', $roomId, $day, $start, $end);
    }

    private function instructorDayPatternLoad(int $instructorId, string $day): int
    {
        $conflictingPatterns = ClassSchedule::conflictingDayPatterns($day);

        return collect($this->conflictIndex['instructor_id'][$instructorId] ?? [])
            ->filter(fn (array $entry): bool => in_array($entry['day'], $conflictingPatterns, true))
            ->count();
    }

    /** @param Collection<int, ClassSchedule> $schedules */
    private function initializeConflictIndex(Collection $schedules): void
    {
        $this->conflictIndex = [
            'section_id' => [],
            'instructor_id' => [],
            'room_id' => [],
        ];

        foreach ($schedules as $schedule) {
            $this->addConflictEntry(
                (int) $schedule->section_id,
                (int) $schedule->instructor_id,
                $schedule->room_id === null ? null : (int) $schedule->room_id,
                (string) $schedule->day,
                (string) $schedule->start_time,
                (string) $schedule->end_time,
                (string) ($schedule->subject?->code ?? ''),
            );
        }
    }

    private function addConflictEntry(
        int $sectionId,
        int $instructorId,
        ?int $roomId,
        string $day,
        string $start,
        string $end,
        string $subjectCode,
    ): void {
        $entry = [
            'day' => $day,
            'start' => substr($start, 0, 5),
            'end' => substr($end, 0, 5),
            'subject_code' => $subjectCode,
        ];

        $this->conflictIndex['section_id'][$sectionId][] = $entry;
        $this->conflictIndex['instructor_id'][$instructorId][] = $entry;

        if ($roomId !== null) {
            $this->conflictIndex['room_id'][$roomId][] = $entry;
        }
    }

    /**
     * @param  array<int, array{day:string,start:string,end:string,subject_code:string}>  $entries
     */
    private function indexedEntriesConflict(array $entries, string $day, string $start, string $end): bool
    {
        foreach ($entries as $entry) {
            if ($this->entryConflicts($entry, $day, $start, $end)) {
                return true;
            }
        }

        return false;
    }

    /** @param array{day:string,start:string,end:string,subject_code:string} $entry */
    private function entryConflicts(array $entry, string $day, string $start, string $end): bool
    {
        return in_array($entry['day'], ClassSchedule::conflictingDayPatterns($day), true)
            && $entry['start'] < substr($end, 0, 5)
            && $entry['end'] > substr($start, 0, 5);
    }
}
