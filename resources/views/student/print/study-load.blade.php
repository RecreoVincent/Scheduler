@php
    $department = $student->department;
    $course = strtoupper((string) ($department?->code ?? $student->course));
    $departmentName = $department?->name ?? match($course) {
        'BSBA' => 'Business Administration Department',
        'BSHM' => 'Hospitality Management Department',
        'BSED', 'BEED' => 'Education Department',
        default => 'Information Technology Department',
    };
    $programName = $department?->program_name ?? match($course) {
        'BSBA' => 'Bachelor of Science in Business Administration',
        'BSHM' => 'Bachelor of Science in Hospitality Management',
        'BSED' => 'Bachelor of Secondary Education',
        'BEED' => 'Bachelor of Elementary Education',
        default => 'Bachelor of Science in Information Technology',
    };
    $departmentLogo = $department?->logo_path ?? match($course) {
        'BSBA' => 'images/bsba-department-logo.jpg',
        'BSHM' => 'images/bshm-department-logo.jpg',
        'BSED', 'BEED' => 'images/education-department-logo.jpg',
        default => 'images/bsit-department-logo.jpg',
    };
    $academicYears = $schedules->pluck('academic_year')->filter()->unique()->join(', ') ?: 'Not Assigned';
    $semesters = $schedules->pluck('semester')->filter()->unique()->map(fn ($semester) => match(strtolower((string) $semester)) {
        '1st', 'first' => 'First Semester',
        '2nd', 'second' => 'Second Semester',
        'summer' => 'Summer',
        default => $semester,
    })->join(', ') ?: 'Academic Period';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $student->name }} - Study Load</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; padding:30px; color:#1e293b; font-family:Arial,sans-serif; }
        .toolbar { display:flex; justify-content:flex-end; margin-bottom:20px; }
        .toolbar-actions { display:flex; align-items:center; gap:9px; }
        .button,.exit-button { padding:10px 16px; border-radius:8px; font-weight:700; cursor:pointer; }
        .button { color:#fff; background:#450693; border:0; }
        .exit-button { color:#450693; background:#fff; border:1px solid #450693; }
        .school-header { display:grid; grid-template-columns:110px minmax(420px,680px) 110px; align-items:center; justify-content:center; gap:18px; padding:4px 26px 8px; text-align:center; }
        .brand-mark { width:88px; height:88px; display:grid; place-items:center; justify-self:center; }
        .brand-mark img { display:block; width:100%; height:100%; object-fit:contain; }
        .brand-mark.department-mark { width:96px; height:96px; }
        .brand-mark.department-mark img { border-radius:50%; }
        .school-copy { padding:0 14px 9px; border-bottom:2px solid #333; font-family:Georgia,'Times New Roman',serif; }
        .school-name { margin:0; color:#302638; font-size:27px; line-height:1.03; font-weight:700; }
        .department-name { margin:2px 0 0; color:#302638; font-size:22px; line-height:1.06; font-weight:700; }
        .school-address,.school-email { margin:3px 0 0; color:#302638; font-size:13px; line-height:1.15; }
        .report-heading { padding:14px 24px 32px; color:#302638; text-align:center; text-transform:uppercase; }
        .report-heading p { margin:4px 0 0; font-size:16px; line-height:1.2; }
        .report-heading .program { font-size:18px; font-weight:850; }
        .report-heading h1 { margin:5px 0 0; font-size:19px; line-height:1.15; }
        .summary { display:flex; justify-content:space-between; gap:18px; margin-bottom:18px; padding:14px; background:#f8fafc; border:1px solid #e2e8f0; }
        table { width:100%; border-collapse:collapse; }
        th,td { padding:10px; border:1px solid #cbd5e1; text-align:left; font-size:12px; }
        th { background:#f3eafa; text-transform:uppercase; }
        .signature { width:250px; margin:70px 0 0 auto; padding-top:7px; border-top:1px solid #334155; text-align:center; font-size:12px; }
        @media print {
            body { padding:0; }
            .toolbar { display:none; }
            @page { size:landscape; margin:15mm; }
        }
    </style>
    @include('layouts.partials.print-screen-theme')
</head>
<body class="print-preview">
    <div class="toolbar">
        <p>Student Portal &middot; Printable Study Load</p>
        <div class="toolbar-actions">
            <button class="exit-button" type="button" data-fallback="{{ route('student.study-load.index') }}" onclick="window.close(); setTimeout(() => { window.location.href = this.dataset.fallback; }, 150);">Exit</button>
            <button class="button" type="button" onclick="window.print()">Print Study Load</button>
        </div>
    </div>

    <main class="print-sheet">
        <header class="school-header">
            <div class="brand-mark"><img src="{{ asset('images/mcc-college-logo.png') }}" alt="Madridejos Community College logo"></div>
            <div class="school-copy">
                <p class="school-name">Madridejos Community College</p>
                <p class="department-name">{{ $departmentName }}</p>
                <p class="school-address">Crossing Bunakan, Madridejos, Cebu</p>
                <p class="school-email">Email: collegeofinfotech2023@gmail.com</p>
            </div>
            <div class="brand-mark department-mark"><img src="{{ asset($departmentLogo) }}" alt="{{ $departmentName }} logo"></div>
        </header>

        <section class="report-heading">
            <p>{{ $semesters }}, A.Y. {{ $academicYears }}</p>
            <p class="program">{{ $programName }}</p>
            <h1>Student Study Load</h1>
            <p>{{ $student->name }} &middot; Year {{ $student->year_level }} &middot; {{ $student->academicSection?->name ?? 'Section not assigned' }}</p>
        </section>

        <div class="summary">
            <strong>Subjects: {{ $subjects->count() }}</strong>
            <strong>Total Units: {{ $totalUnits }}</strong>
            <strong>Section: {{ $student->academicSection?->name ?? 'Unassigned' }}</strong>
        </div>

        <table>
            <thead><tr><th>Day/Time</th><th>Subject</th><th>Units</th><th>Instructor</th><th>Room</th><th>Academic Period</th></tr></thead>
            <tbody>
                @forelse($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->day }}<br>{{ date('g:i A',strtotime($schedule->start_time)) }}&ndash;{{ date('g:i A',strtotime($schedule->end_time)) }}</td>
                        <td>{{ $schedule->subject?->code }} - {{ $schedule->subject?->name }}</td>
                        <td>{{ $schedule->subject?->units }}</td>
                        <td>{{ $schedule->instructor?->name }}</td>
                        <td>{{ $schedule->room?->name ?? 'TBA' }}</td>
                        <td>{{ $schedule->academic_year }} &middot; {{ $schedule->semester }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No assigned Study Load.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="signature">Student Signature</div>
    </main>
</body>
</html>
