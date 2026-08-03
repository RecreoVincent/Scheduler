@extends('layouts.dean')
@php($editing = $room->exists)
@section('title', $editing ? 'Edit Room' : 'Add Room')
@section('page-title', $editing ? 'Edit Room' : 'Add Room')
@section('content')
<div class="page-header"><div><h2>{{ $editing ? 'Update' : 'Create' }} Room</h2><p>Room assigned to the {{ $course }} department.</p></div></div>
<div class="card" style="max-width:650px">
    <form method="POST" action="{{ $editing ? route('dean.rooms.update', $room) : route('dean.rooms.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="form-grid">
            <div><label for="name">Room Name</label><input id="name" class="input" name="name" value="{{ old('name', $room->name) }}" placeholder="Room 101" required></div>
            <div><label for="room_type">Room Type</label><select id="room_type" class="input" name="room_type" required>@foreach(['Lecture', 'Laboratory', 'Kitchen Laboratory'] as $type)<option value="{{ $type }}" @selected(old('room_type', $room->room_type ?? 'Lecture') === $type)>{{ $type }}</option>@endforeach</select></div>
        </div>
        <div class="form-actions"><button class="button">{{ $editing ? 'Save Changes' : 'Add Room' }}</button><a class="button button-secondary" href="{{ route('dean.rooms.index') }}">Cancel</a></div>
    </form>
</div>
@endsection
