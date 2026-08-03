@extends('layouts.dean')
@section('title', 'Edit Schedule')
@section('page-title', 'Edit Class Schedule')

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $timetable->section?->name }} &middot; {{ $timetable->subject?->code }}</h2>
        <p>Edit the selected class entry. The system will check the section, instructor, and room for conflicts.</p>
    </div>
    <a class="button button-secondary" href="{{ route('dean.timetable.index') }}">Back to Timetables</a>
</div>

<div class="card" style="max-width:820px">
    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:22px">
        <span class="badge">{{ $timetable->subject?->name }}</span>
        <span class="badge">{{ $timetable->academic_year }}</span>
        <span class="badge">{{ $timetable->semester }} Semester</span>
    </div>

    <form method="POST" action="{{ route('dean.timetable.update', $timetable) }}">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div>
                <label for="instructor_id">Instructor</label>
                <select id="instructor_id" class="input" name="instructor_id" required>
                    @foreach($instructors as $instructor)
                        <option value="{{ $instructor->id }}" @selected((string) old('instructor_id', $timetable->instructor_id) === (string) $instructor->id)>{{ $instructor->name }} ({{ $instructor->course }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="room_id">Room</label>
                <select id="room_id" class="input" name="room_id" required>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" @selected((string) old('room_id', $timetable->room_id) === (string) $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="day">Day</label>
                <select id="day" class="input" name="day" required>
                    @foreach(['M - W', 'T - Th', 'F - S'] as $day)
                        <option value="{{ $day }}" @selected(old('day', $timetable->day) === $day)>{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="start_time">Start Time</label>
                <input id="start_time" class="input" type="time" name="start_time" value="{{ old('start_time', substr($timetable->start_time, 0, 5)) }}" required>
            </div>
            <div>
                <label for="end_time">End Time</label>
                <input id="end_time" class="input" type="time" name="end_time" value="{{ old('end_time', substr($timetable->end_time, 0, 5)) }}" required>
            </div>
        </div>

        <div class="form-actions">
            <button class="button" type="submit">Save Schedule Changes</button>
            <a class="button button-secondary" href="{{ route('dean.timetable.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
