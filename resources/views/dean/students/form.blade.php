@extends('layouts.dean')
@php($editing = $student->exists)
@section('title', $editing ? 'Edit Student' : 'Add Student')
@section('page-title', $editing ? 'Edit Student' : 'Add Student')

@push('styles')
<style>
    .input:disabled { color:#8b8092; background:rgba(243,238,246,.75); cursor:not-allowed; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $editing ? 'Update' : 'Create' }} Student</h2>
        <p>{{ $editing ? "Update this student's profile for the {$course} department." : "Manually create an active student account for the {$course} department." }}</p>
    </div>
</div>
<div class="card" style="max-width:800px">
<form method="POST" action="{{ $editing ? route('dean.students.update', $student) : route('dean.students.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <div class="form-grid">
        <div><label for="first_name">First Name</label><input id="first_name" class="input" name="first_name" value="{{ old('first_name', $student->first_name) }}" required></div>
        <div><label for="last_name">Last Name</label><input id="last_name" class="input" name="last_name" value="{{ old('last_name', $student->last_name) }}" required></div>
        <div><label for="middle_name">Middle Name</label><input id="middle_name" class="input" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}"></div>
        <div><label for="suffix">Suffix</label><input id="suffix" class="input" name="suffix" value="{{ old('suffix', $student->suffix) }}" placeholder="Jr., III, etc."></div>
        <div><label for="email">Email</label><input id="email" type="email" class="input" name="email" value="{{ old('email', $student->email) }}" required></div>
        <div>
            <label for="year_level">Year Level</label>
            <select id="year_level" class="input" name="year_level" required>
                @for($level = 1; $level <= 4; $level++)
                    <option value="{{ $level }}" @selected((int) old('year_level', $student->year_level ?? 1) === $level)>Year {{ $level }}</option>
                @endfor
            </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
            <label for="academic_section_id">Section</label>
            <select id="academic_section_id" class="input" name="academic_section_id">
                <option value="">No section assigned</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" data-year-level="{{ $section->year_level }}" @selected((int) old('academic_section_id', $student->academic_section_id) === $section->id)>Year {{ $section->year_level }} — {{ $section->name }}</option>
                @endforeach
            </select>
            <small style="display:block;margin-top:5px;color:var(--muted)">Only sections matching the selected year level can be assigned.</small>
        </div>
        @if (! $editing)
            <div><label for="password">Password</label><input id="password" type="password" class="input" name="password" required></div>
            <div><label for="password_confirmation">Confirm Password</label><input id="password_confirmation" type="password" class="input" name="password_confirmation" required></div>
        @endif
    </div>
    <div class="form-actions"><button class="button">{{ $editing ? 'Save Changes' : 'Add Student' }}</button><a class="button button-secondary" href="{{ route('dean.students.index') }}">Cancel</a></div>
</form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const yearLevelSelect = document.getElementById('year_level');
        const sectionSelect = document.getElementById('academic_section_id');
        const sectionOptions = [...sectionSelect.options];

        function syncSectionOptions() {
            const currentYearLevel = yearLevelSelect.value;
            const currentSelection = sectionSelect.value;
            let stillValid = false;

            sectionOptions.forEach((option) => {
                const optionYearLevel = option.dataset.yearLevel;
                const visible = !optionYearLevel || optionYearLevel === currentYearLevel;
                option.hidden = !visible;
                if (visible && option.value === currentSelection) stillValid = true;
            });

            if (!stillValid) sectionSelect.value = '';
        }

        yearLevelSelect.addEventListener('change', syncSectionOptions);
        syncSectionOptions();
    })();
</script>
@endpush
