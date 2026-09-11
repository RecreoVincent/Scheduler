@extends('layouts.dean')
@php($editing = $instructor->exists)
@section('title', $editing ? 'Edit Instructor' : 'Add Instructor')
@section('page-title', $editing ? 'Edit Instructor' : 'Add Instructor')

@push('styles')
<style>
    .input:disabled { color:#8b8092; background:rgba(243,238,246,.75); cursor:not-allowed; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $editing ? 'Update' : 'Create' }} Instructor</h2>
        <p>{{ $editing ? "Update this instructor's profile for the {$course} department." : "Manually create an active instructor account for the {$course} department." }}</p>
    </div>
</div>
<div class="card" style="max-width:800px">
<form method="POST" action="{{ $editing ? route('dean.instructors.update', $instructor) : route('dean.instructors.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <div class="form-grid">
        <div><label for="first_name">First Name</label><input id="first_name" class="input" name="first_name" value="{{ old('first_name', $instructor->first_name) }}" required></div>
        <div><label for="last_name">Last Name</label><input id="last_name" class="input" name="last_name" value="{{ old('last_name', $instructor->last_name) }}" required></div>
        <div><label for="middle_name">Middle Name</label><input id="middle_name" class="input" name="middle_name" value="{{ old('middle_name', $instructor->middle_name) }}"></div>
        <div><label for="suffix">Suffix</label><input id="suffix" class="input" name="suffix" value="{{ old('suffix', $instructor->suffix) }}" placeholder="Jr., III, etc."></div>
        <div><label for="email">Email</label><input id="email" type="email" class="input" name="email" value="{{ old('email', $instructor->email) }}" required></div>
        <div>
            <label for="employment_type">Employment Type</label>
            <select id="employment_type" class="input" name="employment_type" required>
                @foreach(['full_time' => 'Full time', 'flexible_part_time' => 'Flexible Part-Time', 'industry_part_time' => 'Industry Part-Time'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('employment_type', $instructor->employment_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="outside_work_end_time">Outside Work End Time</label>
            <input id="outside_work_end_time" type="time" class="input" name="outside_work_end_time" value="{{ old('outside_work_end_time', $instructor->outside_work_end_time) }}">
            <small style="display:block;margin-top:5px;color:var(--muted)">Required only for Industry Part-Time instructors.</small>
        </div>
        <div><label for="password">Password</label><input id="password" type="password" class="input" name="password" placeholder="{{ $editing ? 'Leave blank to keep current password' : '' }}" @if(!$editing) required @endif></div>
        <div><label for="password_confirmation">Confirm Password</label><input id="password_confirmation" type="password" class="input" name="password_confirmation" @if(!$editing) required @endif></div>
    </div>
    <div class="form-actions"><button class="button">{{ $editing ? 'Save Changes' : 'Add Instructor' }}</button><a class="button button-secondary" href="{{ route('dean.instructors.index') }}">Cancel</a></div>
</form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const employmentType = document.getElementById('employment_type');
        const outsideWorkTime = document.getElementById('outside_work_end_time');

        function syncOutsideWorkField() {
            const isIndustryPartTime = employmentType.value === 'industry_part_time';
            outsideWorkTime.disabled = !isIndustryPartTime;
            outsideWorkTime.required = isIndustryPartTime;
            if (!isIndustryPartTime) outsideWorkTime.value = '';
        }

        employmentType.addEventListener('change', syncOutsideWorkField);
        syncOutsideWorkField();
    })();
</script>
@endpush
