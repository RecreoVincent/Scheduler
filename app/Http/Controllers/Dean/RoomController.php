<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $rooms = Room::with(['schedules' => fn ($query) => $query->with(['section', 'subject'])->orderByRaw(ClassSchedule::dayOrderSql())->orderBy('start_time')])
            ->where('course', $course)->orderBy('name')->paginate(10);

        return view('dean.rooms.index', compact('course', 'rooms'));
    }

    public function create(Request $request): View
    {
        return view('dean.rooms.form', ['room' => new Room, 'course' => $this->course($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        Room::create(['course' => $this->course($request), ...$this->validated($request)]);

        return redirect()->route('dean.rooms.index')->with('success', 'Room added successfully.');
    }

    public function edit(Request $request, Room $room): View
    {
        $this->ensureCourse($request, $room);

        return view('dean.rooms.form', ['room' => $room, 'course' => $this->course($request)]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $this->ensureCourse($request, $room);
        $room->update($this->validated($request, $room));

        return redirect()->route('dean.rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroy(Request $request, Room $room): RedirectResponse
    {
        $this->ensureCourse($request, $room);
        abort_if(ClassSchedule::withTrashed()->where('room_id', $room->id)->exists(), 422, 'Remove the active and archived room schedules before deleting this room.');
        $room->delete();

        return back()->with('success', 'Room deleted successfully.');
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('rooms')->where(fn ($q) => $q->where('course', $this->course($request)))->ignore($room?->id)],
            'room_type' => ['required', Rule::in(['Lecture', 'Laboratory', 'Kitchen Laboratory'])],
        ]);
    }
}
