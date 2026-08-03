<?php

namespace App\Http\Controllers\Instructor;

use App\Models\ClassSchedule;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomScannerController extends InstructorController
{
    public function index(): View
    {
        $rooms = Room::query()->orderBy('course')->orderBy('name')->get();

        return view('instructor.scanner.index', compact('rooms'));
    }

    public function status(Request $request, Room $room): JsonResponse
    {
        $now = now();
        $currentSchedule = ClassSchedule::with(['section', 'subject', 'instructor'])
            ->where('room_id', $room->id)
            ->whereIn('day', ClassSchedule::patternsForDay($now->format('l')))
            ->whereTime('start_time', '<=', $now->format('H:i:s'))
            ->whereTime('end_time', '>', $now->format('H:i:s'))
            ->first();

        $nextSchedule = ClassSchedule::with(['section', 'subject', 'instructor'])
            ->where('room_id', $room->id)
            ->whereIn('day', ClassSchedule::patternsForDay($now->format('l')))
            ->whereTime('start_time', '>', $now->format('H:i:s'))
            ->orderBy('start_time')
            ->first();

        return response()->json([
            'room' => ['id' => $room->id, 'name' => $room->name, 'course' => $room->course],
            'checked_at' => $now->format('l, F j, Y g:i A'),
            'in_use' => $currentSchedule !== null,
            'current' => $this->scheduleData($currentSchedule),
            'next' => $this->scheduleData($nextSchedule),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function scheduleData(?ClassSchedule $schedule): ?array
    {
        if (! $schedule) {
            return null;
        }

        return [
            'section' => $schedule->section?->name,
            'subject' => trim(($schedule->subject?->code ?? '').' - '.($schedule->subject?->name ?? ''), ' -'),
            'instructor' => $schedule->instructor?->name,
            'time' => date('g:i A', strtotime($schedule->start_time)).'–'.date('g:i A', strtotime($schedule->end_time)),
        ];
    }
}
