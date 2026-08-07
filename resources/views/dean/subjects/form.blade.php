@extends('layouts.dean')
@php($editing = $subject->exists)
@section('title', $editing ? 'Edit Subject' : 'Add Subject')
@section('page-title', $editing ? 'Edit Subject' : 'Add Subject')

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $editing ? 'Update' : 'Create' }} Subject</h2>
        <p>Enter the curriculum information for {{ $course }}.</p>
    </div>
</div>
<div class="card" style="max-width:800px">
<form method="POST" action="{{ $editing ? route('dean.subjects.update', $subject) : route('dean.subjects.store') }}">
@csrf @if($editing) @method('PUT') @endif
<input type="hidden" name="curriculum" value="{{ old('curriculum', $subject->curriculum ?? 'New') }}">
<div class="form-grid">
    <div><label>Subject Code</label><input class="input" name="code" value="{{ old('code', $subject->code) }}" required></div>
    <div><label>Subject Name</label><input class="input" name="name" value="{{ old('name', $subject->name) }}" required></div>
    <div><label>Subject Type</label><select class="input" name="subject_type">@foreach(['Lecture','Laboratory'] as $type)<option @selected(old('subject_type', $subject->subject_type) === $type)>{{ $type }}</option>@endforeach</select></div>
    <div><label>Subject Classification</label><select class="input" name="classification">@foreach(['Major','Minor'] as $classification)<option @selected(old('classification', $subject->classification ?? 'Major') === $classification)>{{ $classification }}</option>@endforeach</select><small style="color:#64748b">Major and minor subjects may use M–W, T–Th, or F–S. The generator balances the available day pairs.</small></div>
    <div><label>Year Level</label><select class="input" name="year_level">@for($i=1;$i<=4;$i++)<option value="{{ $i }}" @selected(old('year_level', $subject->year_level) == $i)>Year {{ $i }}</option>@endfor</select></div>
    <div><label>Semester</label><select class="input" name="semester">@foreach(['1st','2nd','Summer'] as $sem)<option @selected(old('semester', $subject->semester) === $sem)>{{ $sem }}</option>@endforeach</select></div>
    <div><label>Units</label><input class="input" type="number" step="0.5" min="0.5" name="units" value="{{ old('units', $subject->units) }}" required></div>
</div>
<div class="form-actions"><button class="button">{{ $editing ? 'Save Changes' : 'Add Subject' }}</button><a class="button button-secondary" href="{{ route('dean.subjects.index') }}">Cancel</a></div>
</form>
</div>
@endsection
