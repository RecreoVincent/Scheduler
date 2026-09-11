@extends('layouts.gec')
@php($editing = $subject->exists)
@section('title', $editing ? 'Edit Minor Subject' : 'Add Minor Subject')
@section('page-title', $editing ? 'Edit Minor Subject' : 'Add Minor Subject')

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $editing ? 'Update' : 'Create' }} Minor Subject</h2>
        <p>Enter the General Education curriculum information.</p>
    </div>
</div>
<div class="card" style="max-width:800px">
<form method="POST" action="{{ $editing ? route('gec.subjects.update', $subject) : route('gec.subjects.store') }}">
@csrf @if($editing) @method('PUT') @endif
<input type="hidden" name="curriculum" value="{{ old('curriculum', $subject->curriculum ?? 'New') }}">
<div class="form-grid">
    <div>
        <label>Department</label>
        <select class="input" name="course">
            @foreach(\App\Http\Controllers\Gec\GecController::REAL_DEPARTMENTS as $department)
                <option value="{{ $department }}" @selected(old('course', $subject->course) === $department)>{{ $department }}</option>
            @endforeach
        </select>
    </div>
    <div><label>Subject Code</label><input class="input" name="code" value="{{ old('code', $subject->code) }}" required></div>
    <div><label>Subject Name</label><input class="input" name="name" value="{{ old('name', $subject->name) }}" required></div>
    <div><label>Subject Type</label><select class="input" name="subject_type">@foreach(['Lecture','Laboratory'] as $type)<option @selected(old('subject_type', $subject->subject_type) === $type)>{{ $type }}</option>@endforeach</select></div>
    <div><label>Year Level</label><select class="input" name="year_level">@for($i=1;$i<=4;$i++)<option value="{{ $i }}" @selected(old('year_level', $subject->year_level) == $i)>Year {{ $i }}</option>@endfor</select></div>
    <div><label>Semester</label><select class="input" name="semester">@foreach(['1st','2nd','Summer'] as $sem)<option @selected(old('semester', $subject->semester) === $sem)>{{ $sem }}</option>@endforeach</select></div>
    <div><label>Units</label><input class="input" type="number" step="0.5" min="0.5" name="units" value="{{ old('units', $subject->units) }}" required></div>
</div>
<div class="form-actions"><button class="button">{{ $editing ? 'Save Changes' : 'Add Subject' }}</button><a class="button button-secondary" href="{{ route('gec.subjects.index') }}">Cancel</a></div>
</form>
</div>
@endsection
