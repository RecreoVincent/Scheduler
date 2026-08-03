@extends('layouts.dean')
@php($editing=$section->exists)
@section('title',$editing?'Edit Section':'Add Section') @section('page-title',$editing?'Edit Section':'Add Section')
@section('content')
<div class="page-header"><div><h2>{{ $editing?'Update':'Create' }} Section</h2><p>{{ $course }} department</p></div></div>
<div class="card" style="max-width:720px"><form method="POST" action="{{ $editing?route('dean.sections.update',$section):route('dean.sections.store') }}">@csrf @if($editing)@method('PUT')@endif
<div class="form-grid"><div class="form-group"><label>Section Name</label><input class="input" name="name" value="{{ old('name',$section->name) }}" placeholder="Year 1-A" required></div><div class="form-group"><label>Year Level</label><select class="input" name="year_level" required>@for($i=1;$i<=4;$i++)<option value="{{ $i }}" @selected(old('year_level',$section->year_level)==$i)>Year {{ $i }}</option>@endfor</select></div><div class="form-group"><label>Academic Year</label><input class="input" name="academic_year" value="{{ old('academic_year',$section->academic_year) }}" placeholder="2026-2027" required></div></div>
<div class="form-actions"><button class="button">{{ $editing?'Save Changes':'Add Section' }}</button><a class="button button-secondary" href="{{ route('dean.sections.index') }}">Cancel</a></div></form></div>
@endsection
