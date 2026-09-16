<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\Room;
use App\Services\RoomImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoomController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $enabledSemesters = $this->enabledSemesters($request);
        $query = Room::with(['schedules' => fn ($query) => $query
            ->whereIn('semester', $enabledSemesters)
            ->with(['section', 'subject'])
            ->orderByRaw(ClassSchedule::dayOrderSql())
            ->orderBy('start_time')])
            ->forDepartment($course);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('room_type')) {
            $query->where('room_type', $request->input('room_type'));
        }

        $rooms = $query->orderBy('name')->paginate(10)->withQueryString();

        $editingRoom = null;
        if ($request->filled('edit')) {
            $editingRoom = Room::forDepartment($course)->find($request->input('edit'));
        }

        $roomTypes = $this->roomTypes($course);

        return view('dean.rooms.index', compact('course', 'rooms', 'editingRoom', 'roomTypes'));
    }

    public function create(Request $request): View
    {
        $course = $this->course($request);

        return view('dean.rooms.form', ['room' => new Room, 'course' => $course, 'roomTypes' => $this->roomTypes($course)]);
    }

    public function store(Request $request): RedirectResponse
    {
        Room::create(['course' => $this->course($request), ...$this->validated($request)]);

        return redirect()->route('dean.rooms.index')->with('success', 'Room added successfully.');
    }

    public function import(Request $request, RoomImporter $importer): RedirectResponse
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        try {
            $result = $importer->import($request->file('csv_file')->getRealPath(), $this->course($request));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $response = back()->with('success', "Import complete: {$result['imported']} room(s) created, {$result['skipped']} skipped.");

        if ($result['errors'] !== []) {
            $shown = array_slice($result['errors'], 0, 15);
            $note = implode(' | ', $shown);
            if (count($result['errors']) > 15) {
                $note .= ' | +'.(count($result['errors']) - 15).' more.';
            }
            $response->with('error_note', $note);
        }

        return $response;
    }

    public function importTemplate(): StreamedResponse
    {
        $headers = ['name', 'room_type'];
        $sample = ['ITE 101', 'Lecture'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'room-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function edit(Request $request, Room $room): View
    {
        $this->ensureCourse($request, $room);
        $course = $this->course($request);

        return view('dean.rooms.form', ['room' => $room, 'course' => $course, 'roomTypes' => $this->roomTypes($course)]);
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
        DB::transaction(function () use ($room): void {
            // Keep active and archived schedule records, but make them TBA once their room is removed.
            ClassSchedule::withTrashed()->where('room_id', $room->id)->update(['room_id' => null]);
            $room->delete();
        });

        return back()->with('success', 'Room deleted successfully.');
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('rooms')->where(fn ($q) => $q->where('department_id', $request->user()->department_id))->ignore($room?->id)],
            'room_type' => ['required', Rule::in($this->roomTypes($this->course($request)))],
        ]);
    }

    /** @return array<int, string> */
    private function roomTypes(string $course): array
    {
        return strtoupper($course) === 'BSHM'
            ? ['Lecture', 'Laboratory', 'Kitchen Laboratory']
            : ['Lecture', 'Laboratory'];
    }
}
