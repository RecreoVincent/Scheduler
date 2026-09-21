@php
    $semesterPreferenceRole = auth()->user()->role;
    $semesterPreferenceKey = "{$semesterPreferenceRole}_viewing_semester";
    $departmentDefaultSemester = auth()->user()->department?->enabledSemesterCodes()[0] ?? '1st';
    $viewingSemester = session($semesterPreferenceKey, $departmentDefaultSemester);
    $semesterPreferenceRoute = $semesterPreferenceRole === 'instructor'
        ? route('instructor.settings.semester')
        : route('student.settings.semester');
@endphp
<details class="personal-semester-menu">
    <summary class="personal-semester-trigger" aria-label="Choose viewing semester" title="Choose viewing semester"><x-icon name="gear" /></summary>
    <div class="personal-semester-dropdown">
        <strong>Viewing Semester</strong>
        <p>Choose the semester whose schedules you want to view. This does not change your department's schedule settings.</p>
        <form method="POST" action="{{ $semesterPreferenceRoute }}">
            @csrf
            @foreach(['1st' => '1st Semester', '2nd' => '2nd Semester', 'Summer' => 'Summer'] as $semesterCode => $semesterLabel)
                <label class="personal-semester-row">
                    <span>{{ $semesterLabel }}</span>
                    <span class="personal-semester-switch"><input type="radio" name="semester" value="{{ $semesterCode }}" @checked($viewingSemester === $semesterCode)><span class="personal-semester-track"></span></span>
                </label>
            @endforeach
            <button class="button" type="submit" style="width:100%;margin-top:12px">Save</button>
        </form>
    </div>
</details>
