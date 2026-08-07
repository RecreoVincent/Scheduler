@extends('layouts.dean')

@section('title', 'Create Schedule')
@section('page-title', 'Automatic Schedule Generator')

@push('styles')
<style>
    .section-preview { grid-column:1/-1; margin-top:4px; padding:18px; background:#faf8fb; border:1px solid var(--border); border-radius:11px; }
    .section-preview-head { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; margin-bottom:13px; }
    .section-preview-head h3 { margin-bottom:4px; font-size:12px; color:var(--navy); }
    .section-preview-head p,.section-count-status { font-size:10px; color:var(--muted); }
    .section-count-status { padding:6px 9px; font-weight:800; color:var(--primary); background:#f2e7fa; border-radius:20px; white-space:nowrap; }
    .section-list { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:9px; }
    .section-item { padding:11px; background:white; border:1px solid #e3d8e9; border-radius:9px; }
    .section-item.selected { color:var(--primary); background:#f3e9fa; border-color:var(--primary-light); box-shadow:inset 3px 0 var(--gold); }
    .section-item strong,.section-item small { display:block; }
    .section-item strong { font-size:11px; }
    .section-item small { margin-top:3px; font-size:9px; color:var(--muted); }
    .section-empty { grid-column:1/-1; padding:15px; font-size:11px; color:var(--muted); text-align:center; background:white; border:1px dashed #d8cce0; border-radius:9px; }
    #number_of_sections { appearance:textfield; -moz-appearance:textfield; }
    #number_of_sections::-webkit-inner-spin-button,
    #number_of_sections::-webkit-outer-spin-button { margin:0; -webkit-appearance:none; }
    .year-multiselect { position:relative; }
    .year-multiselect-button { width:100%; min-height:42px; display:flex; align-items:center; justify-content:space-between; gap:12px; text-align:left; cursor:pointer; }
    .year-multiselect-button::after { content:''; width:8px; height:8px; border-right:2px solid currentColor; border-bottom:2px solid currentColor; transform:rotate(45deg) translateY(-2px); transition:transform .18s ease; }
    .year-multiselect.open .year-multiselect-button::after { transform:rotate(225deg) translate(-2px,-2px); }
    .year-options { position:absolute; z-index:30; top:calc(100% + 7px); left:0; right:0; display:grid; gap:4px; padding:8px; background:rgba(255,255,255,.97); border:1px solid var(--border); border-radius:11px; box-shadow:0 14px 34px rgba(48,10,91,.18); }
    .year-options[hidden] { display:none; }
    .year-option { display:flex; align-items:center; gap:10px; padding:9px 10px; border-radius:8px; cursor:pointer; font-size:11px; font-weight:700; color:var(--navy); }
    .year-option:hover { background:#f3e9fa; }
    .year-option:first-child { padding-bottom:11px; border-bottom:1px solid var(--border); border-radius:8px 8px 0 0; }
    .year-option input { width:17px; height:17px; margin:0; accent-color:var(--primary); }
    @media(max-width:700px) { .section-list { grid-template-columns:1fr; } .section-preview-head { flex-direction:column; } }
</style>
@endpush

@section('content')
@php
    $academicYears = $sections->pluck('academic_year')->unique()->values();
    $defaultAcademicYear = old('academic_year', $academicYears->first());
    $oldYearLevels = old('year_levels');
    $defaultYearLevel = (string) old('year_level', $sections->firstWhere('academic_year', $defaultAcademicYear)?->year_level ?? 1);
    $selectedYearLevels = collect(is_array($oldYearLevels) ? $oldYearLevels : ($defaultYearLevel === 'all' ? range(1, 4) : [$defaultYearLevel]))->map(fn($level) => (string) $level);
@endphp

<div class="page-header">
    <div><h2>Generate {{ $course }} Class Schedules</h2><p>Use the first existing sections for the selected year level and academic year.</p></div>
</div>

<div class="card" style="max-width:850px">

    <form id="scheduleForm" method="POST" action="{{ route('dean.schedules.store') }}">
        @csrf
        <div class="form-grid">
            <div><label>Program / Degree</label><input class="input" value="{{ $course }}" disabled></div>
            <div>
                <label for="academic_year">Academic Year</label>
                <select id="academic_year" class="input" name="academic_year" required>
                    @forelse($academicYears as $academicYear)<option value="{{ $academicYear }}" @selected($defaultAcademicYear === $academicYear)>{{ $academicYear }}</option>@empty<option value="">No academic years available</option>@endforelse
                </select>
                @error('academic_year')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="semester">Semester</label>
                <select id="semester" class="input" name="semester">@foreach(['1st','2nd','Summer'] as $semester)<option @selected(old('semester') === $semester)>{{ $semester }}</option>@endforeach</select>
                @error('semester')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="curriculum">Curriculum</label>
                <select id="curriculum" class="input" name="curriculum" required>
                    @foreach(['New' => 'New Curriculum', 'Old' => 'Old Curriculum'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('curriculum', 'New') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('curriculum')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label id="yearLevelLabel">Year Level</label>
                <div id="yearMultiselect" class="year-multiselect">
                    <button id="yearLevelButton" class="input year-multiselect-button" type="button" aria-haspopup="true" aria-expanded="false" aria-labelledby="yearLevelLabel yearLevelButton"><span id="yearLevelText">Select year levels</span></button>
                    <div id="yearOptions" class="year-options" hidden>
                        <label class="year-option"><input id="selectAllYears" type="checkbox" name="year_level" value="all"> <span>All Year Levels</span></label>
                        @for($level=1;$level<=4;$level++)
                            <label class="year-option"><input class="year-level-checkbox" type="checkbox" name="year_levels[]" value="{{ $level }}" @checked($selectedYearLevels->contains((string) $level))> <span>Year {{ $level }}</span></label>
                        @endfor
                    </div>
                </div>
                @error('year_levels')<div class="error">{{ $message }}</div>@enderror
                @error('year_levels.*')<div class="error">{{ $message }}</div>@enderror
                @error('year_level')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div id="sectionCountField">
                <label id="sectionCountLabel" for="number_of_sections">Number of Existing Sections to Use</label>
                <input id="number_of_sections" class="input" type="number" min="1" max="20" name="number_of_sections" value="{{ old('number_of_sections', 1) }}" required>
                @error('number_of_sections')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="section-preview">
                <div class="section-preview-head"><div><h3>Sections the Generator Will Use</h3><p>Automatically selected from the existing Sections list.</p></div><span id="sectionCountStatus" class="section-count-status">0 sections available</span></div>
                <div id="sectionList" class="section-list">
                    @foreach($sections as $section)
                        <div class="section-item" data-academic-year="{{ $section->academic_year }}" data-year-level="{{ $section->year_level }}" data-name="{{ $section->name }}"><strong>{{ $section->name }}</strong><small>Year {{ $section->year_level }} · {{ $section->academic_year }}</small></div>
                    @endforeach
                    <p id="sectionEmpty" class="section-empty" hidden>No existing sections match this academic year and year level. <a href="{{ route('dean.sections.create') }}" style="font-weight:800;color:var(--primary)">Add sections first.</a></p>
                </div>
            </div>
        </div>

        <div class="form-actions"><button id="generateButton" class="button" type="submit" @disabled($sections->isEmpty())>Create Schedule</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const academicYear = document.getElementById('academic_year');
        const yearMultiselect = document.getElementById('yearMultiselect');
        const yearLevelButton = document.getElementById('yearLevelButton');
        const yearLevelText = document.getElementById('yearLevelText');
        const yearOptions = document.getElementById('yearOptions');
        const selectAllYears = document.getElementById('selectAllYears');
        const yearCheckboxes = [...document.querySelectorAll('.year-level-checkbox')];
        const countInput = document.getElementById('number_of_sections');
        const countField = document.getElementById('sectionCountField');
        const items = [...document.querySelectorAll('.section-item')];
        const empty = document.getElementById('sectionEmpty');
        const status = document.getElementById('sectionCountStatus');
        const generateButton = document.getElementById('generateButton');

        function selectedYears() {
            return yearCheckboxes.filter(checkbox => checkbox.checked).map(checkbox => checkbox.value);
        }

        function updateYearLabel() {
            const years = selectedYears();
            selectAllYears.checked = years.length === yearCheckboxes.length;
            selectAllYears.indeterminate = years.length > 0 && years.length < yearCheckboxes.length;
            yearLevelText.textContent = years.length === yearCheckboxes.length
                ? 'All Year Levels'
                : years.length === 0
                    ? 'Select year levels'
                    : years.map(year => `Year ${year}`).join(', ');
        }

        function updatePreview() {
            const years = selectedYears();
            const matching = items
                .filter(item => item.dataset.academicYear === academicYear.value
                    && years.includes(item.dataset.yearLevel))
                .sort((left, right) => {
                    const yearComparison = Number(left.dataset.yearLevel) - Number(right.dataset.yearLevel);
                    return yearComparison !== 0
                        ? yearComparison
                        : left.dataset.name.localeCompare(right.dataset.name, undefined, { numeric:true, sensitivity:'base' });
                });
            const requested = Math.max(1, Number.parseInt(countInput.value || '1', 10));
            const multipleYearLevels = years.length > 1;
            const selected = multipleYearLevels ? matching : matching.slice(0, requested);

            items.forEach(item => { item.hidden = true; item.classList.remove('selected'); });
            matching.forEach(item => {
                item.hidden = false;
                item.classList.toggle('selected', selected.includes(item));
            });

            countField.hidden = multipleYearLevels;
            countInput.disabled = multipleYearLevels;
            countInput.required = !multipleYearLevels;
            countInput.max = Math.max(1, matching.length);
            empty.hidden = matching.length > 0;
            generateButton.disabled = years.length === 0 || matching.length === 0 || (!multipleYearLevels && requested > matching.length);
            status.textContent = matching.length === 0
                ? '0 sections available'
                : `Using ${selected.length} of ${matching.length} sections`;
        }

        academicYear.addEventListener('change', updatePreview);
        yearLevelButton.addEventListener('click', () => {
            const opening = yearOptions.hidden;
            yearOptions.hidden = !opening;
            yearMultiselect.classList.toggle('open', opening);
            yearLevelButton.setAttribute('aria-expanded', String(opening));
        });
        selectAllYears.addEventListener('change', () => {
            yearCheckboxes.forEach(checkbox => { checkbox.checked = selectAllYears.checked; });
            updateYearLabel();
            updatePreview();
        });
        yearCheckboxes.forEach(checkbox => checkbox.addEventListener('change', () => {
            updateYearLabel();
            updatePreview();
        }));
        document.addEventListener('click', event => {
            if (yearMultiselect.contains(event.target)) return;
            yearOptions.hidden = true;
            yearMultiselect.classList.remove('open');
            yearLevelButton.setAttribute('aria-expanded', 'false');
        });
        countInput.addEventListener('input', updatePreview);
        updateYearLabel();
        updatePreview();
    })();
</script>
@endpush
