<?php

namespace App\Http\Controllers\Student;

use App\Models\ClassSchedule;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RoomScannerController extends StudentController
{
    public function index(): View
    {
        $rooms = Room::query()->orderBy('course')->orderBy('name')->get();

        return view('student.scanner.index', compact('rooms'));
    }

    public function status(Room $room): JsonResponse
    {
        $now = now();
        $base = ClassSchedule::with(['section', 'subject', 'instructor'])
            ->where('room_id', $room->id)
            ->whereIn('day', ClassSchedule::patternsForDay($now->format('l')));
        $current = (clone $base)->whereTime('start_time', '<=', $now->format('H:i:s'))->whereTime('end_time', '>', $now->format('H:i:s'))->first();
        $next = (clone $base)->whereTime('start_time', '>', $now->format('H:i:s'))->orderBy('start_time')->first();

        return response()->json([
            'room' => ['id' => $room->id, 'name' => $room->name, 'course' => $room->course],
            'checked_at' => $now->format('l, F j, Y g:i A'),
            'in_use' => $current !== null,
            'current' => $this->scheduleData($current),
            'next' => $this->scheduleData($next),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function scheduleData(?ClassSchedule $schedule): ?array
    {
        return $schedule ? [
            'section' => $schedule->section?->name,
            'subject' => trim(($schedule->subject?->code ?? '').' - '.($schedule->subject?->name ?? ''), ' -'),
            'instructor' => $schedule->instructor?->name,
            'time' => date('g:i A', strtotime($schedule->start_time)).'–'.date('g:i A', strtotime($schedule->end_time)),
        ] : null;
    }
}
