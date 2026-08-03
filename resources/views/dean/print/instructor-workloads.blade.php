<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Individual Faculty Load Sheet - {{ $course }}</title>
    <style>
        :root { color-scheme:light; }
        * { box-sizing:border-box; }
        html,body { margin:0; padding:0; color:#111; background:#eef1f5; font-family:Arial, Helvetica, sans-serif; }
        .print-toolbar { position:sticky; z-index:10; top:0; display:flex; align-items:center; justify-content:space-between; gap:20px; padding:12px 24px; color:#fff; background:#450693; box-shadow:0 3px 14px rgba(32,7,61,.25); }
        .print-toolbar p { margin:0; font-size:14px; font-weight:700; }
        .toolbar-actions { display:flex; align-items:center; gap:10px; }
        .print-button { padding:10px 22px; color:#291500; background:#efbc3b; border:0; border-radius:7px; font-size:14px; font-weight:800; cursor:pointer; }
        .exit-button { padding:9px 20px; color:#fff; background:transparent; border:1px solid rgba(255,255,255,.72); border-radius:7px; font-size:14px; font-weight:800; cursor:pointer; }
        .reports { padding:24px; }
        .faculty-sheet { width:min(920px, 100%); min-height:1280px; margin:0 auto 28px; padding:14px 18px 18px; background:#fff; box-shadow:0 5px 24px rgba(15,23,42,.13); }
        .school-header { display:grid; grid-template-columns:82px minmax(420px, 620px) 82px; align-items:center; justify-content:center; gap:14px; text-align:center; }
        .brand-mark { width:70px; height:70px; display:grid; place-items:center; justify-self:center; }
        .brand-mark img { display:block; width:100%; height:100%; object-fit:contain; }
        .brand-mark.department-mark { width:82px!important; height:82px!important; }
        .brand-mark.department-mark img { width:82px!important; height:82px!important; border-radius:50%; transform:none!important; }
        .school-copy { padding-bottom:6px; border-bottom:1.5px solid #222; font-family:Georgia, 'Times New Roman', serif; }
        .school-name { margin:0; font-size:19px; font-weight:700; line-height:1.05; }
        .department-name { margin:2px 0 0; font-size:16px; font-weight:700; line-height:1.05; }
        .school-address,.school-email { margin:2px 0 0; font-size:9px; line-height:1.1; }
        .period-heading { margin:7px 0 6px; text-align:center; font-family:Georgia, 'Times New Roman', serif; }
        .period-heading strong { display:block; font-size:14px; }
        .period-heading span { display:block; margin-top:1px; font-size:11px; }
        .sheet-title { margin:0 -18px 8px; padding:5px 14px; color:#fff; background:#202020; text-align:center; font:700 13px Georgia, 'Times New Roman', serif; letter-spacing:.35px; }
        .faculty-details { display:grid; grid-template-columns:1.25fr 1fr 1fr .85fr; gap:8px 14px; margin-bottom:6px; font-family:Georgia, 'Times New Roman', serif; font-size:10px; }
        .detail { display:flex; align-items:flex-end; gap:5px; min-width:0; }
        .detail-label { white-space:nowrap; }
        .detail-value { min-width:30px; flex:1; padding:0 3px 2px; border-bottom:1px solid #111; font-weight:700; text-transform:uppercase; }
        .employment { grid-column:1 / -1; display:flex; flex-wrap:wrap; align-items:center; gap:8px 18px; padding-top:1px; }
        .employment strong { margin-right:3px; }
        .check-option { display:inline-flex; align-items:center; gap:4px; }
        .check-box { width:11px; height:11px; display:inline-grid; place-items:center; border:1px solid #111; font:700 9px/1 Arial, sans-serif; }
        .check-box.checked::after { content:'✓'; }
        .load-section { margin-top:5px; }
        .section-title { margin:0 0 2px; font:700 10px Georgia, 'Times New Roman', serif; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th,td { height:19px; padding:2px 3px; border:1px solid #222; text-align:center; vertical-align:middle; font-size:7.5px; overflow-wrap:anywhere; }
        th { font-family:Georgia, 'Times New Roman', serif; font-weight:700; }
        td.description { text-align:left; text-transform:uppercase; }
        .load-table col.code { width:8%; }
        .load-table col.description { width:25%; }
        .load-table col.day { width:8%; }
        .load-table col.time { width:12%; }
        .load-table col.section { width:11%; }
        .load-table col.room { width:9%; }
        .load-table col.units { width:6%; }
        .load-table col.total-units { width:7%; }
        .load-table col.hours { width:8%; }
        .function-table col.code { width:10%; }
        .function-table col.description { width:28%; }
        .function-table col.day { width:10%; }
        .function-table col.time { width:13%; }
        .function-table col.students { width:11%; }
        .function-table col.units { width:7%; }
        .function-table col.total { width:8%; }
        .consultation-table col.day { width:22%; }
        .consultation-table col.time { width:25%; }
        .consultation-table col.venue { width:37%; }
        .consultation-table col.hours { width:16%; }
        .total-row td { height:17px; font-weight:700; background:#f5f5f5; }
        .total-label { text-align:right; }
        .grand-total td { background:#e3efd9; }
        .signature-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:17px 48px; margin:16px 30px 0; font-family:Georgia, 'Times New Roman', serif; }
        .signature { min-height:55px; text-align:center; font-size:9px; }
        .signature-label { margin:0 0 16px; text-align:left; }
        .signature-name { margin:0; padding-top:3px; border-top:1px solid #111; font-size:10px; font-weight:700; text-transform:uppercase; }
        .signature-role { margin:2px 0 0; }
        .signature-date { margin:4px 0 0; text-align:left; }
        .empty-report { width:min(900px, 100%); margin:60px auto; padding:70px 30px; text-align:center; background:#fff; border-top:5px solid #efbc3b; box-shadow:0 5px 24px rgba(15,23,42,.13); }
        .empty-report h1 { color:#450693; }
        @page { size:A4 portrait; margin:7mm; }
        @media print {
            html,body { background:#fff; }
            .print-toolbar { display:none !important; }
            .reports { padding:0; }
            .faculty-sheet { width:100%; min-height:0; margin:0; padding:0; box-shadow:none; break-after:page; page-break-after:always; }
            .faculty-sheet:last-child { break-after:auto; page-break-after:auto; }
            .load-section,.signature-grid,table tr { break-inside:avoid; page-break-inside:avoid; }
            .empty-report { margin:0; box-shadow:none; }
        }
    </style>
    @include('layouts.partials.print-screen-theme')
</head>
<body class="print-preview">
    <div class="print-toolbar">
        <p>{{ $course }} individual faculty load sheets &middot; {{ $instructorReports->count() }} {{ Str::plural('sheet', $instructorReports->count()) }}</p>
        <div class="toolbar-actions">
            <button class="exit-button" type="button" data-fallback="{{ route('dean.print.index') }}" onclick="window.close(); setTimeout(() => { window.location.href = this.dataset.fallback; }, 150);">Exit</button>
            @if($instructorReports->isNotEmpty())
                <form method="GET" action="{{ route('dean.print.instructor-workload.excel') }}">
                    <button class="print-button" type="submit">Export to Excel</button>
                </form>
            @endif
            <button class="print-button" type="button" onclick="window.print()">Print Individual Faculty Load Sheets</button>
        </div>
    </div>

    <main class="reports">
        @forelse($instructorReports as $report)
            @php
                $instructor = $report['instructor'];
                $semesterLabel = match(strtolower((string) $report['semester'])) {
                    '1st', 'first' => 'First Semester',
                    '2nd', 'second' => 'Second Semester',
                    'summer' => 'Summer',
                    default => $report['semester'] ?: 'Academic Period Not Assigned',
                };
                $employment = $instructor->employment_type;
                $isRegular = $employment === 'full_time';
                $isPartTime = in_array($employment, ['industry_part_time', 'flexible_part_time', 'part_time'], true);
                $middleInitial = filled($instructor->middle_name) ? strtoupper(substr($instructor->middle_name, 0, 1)).'.' : '—';
                $formatNumber = fn (float $value): string => number_format($value, floor($value) === $value ? 0 : 1);
            @endphp
            <article class="faculty-sheet">
                <header class="school-header">
                    <div class="brand-mark" aria-label="Madridejos Community College seal"><img src="{{ asset('images/mcc-college-logo.png') }}" alt="Madridejos Community College logo"></div>
                    <div class="school-copy">
                        <p class="school-name">Madridejos Community College</p>
                        <p class="department-name">{{ $department }}</p>
                        <p class="school-address">Crossing Bunakan, Madridejos, Cebu</p>
                        <p class="school-email">Email: collegeofinfotech2023@gmail.com</p>
                    </div>
                    <div class="brand-mark department-mark" aria-label="Information Technology Department seal"><img src="{{ asset('images/bsit-department-logo.jpg') }}" alt="Information Technology Department logo"></div>
                </header>

                <div class="period-heading">
                    <strong>{{ $department }}</strong>
                    <span>{{ $semesterLabel }}@if($report['academic_year']), School Year {{ $report['academic_year'] }}@endif</span>
                </div>
                <h1 class="sheet-title">Individual Faculty Load Sheet</h1>

                <section class="faculty-details" aria-label="Instructor details">
                    <div class="detail"><span class="detail-label">Family Name:</span><span class="detail-value">{{ $instructor->last_name }}</span></div>
                    <div class="detail"><span class="detail-label">First Name:</span><span class="detail-value">{{ $instructor->first_name }}</span></div>
                    <div class="detail"><span class="detail-label">Middle Initial:</span><span class="detail-value">{{ $middleInitial }}</span></div>
                    <div class="detail"><span class="detail-label">Suffix:</span><span class="detail-value">{{ $instructor->suffix ?: '—' }}</span></div>
                    <div class="employment">
                        <strong>Employment Status:</strong>
                        <span class="check-option"><span class="check-box {{ $isRegular ? 'checked' : '' }}"></span> Regular / Full-Time</span>
                        <span class="check-option"><span class="check-box"></span> Probationary</span>
                        <span class="check-option"><span class="check-box"></span> Contractual</span>
                        <span class="check-option"><span class="check-box {{ $isPartTime ? 'checked' : '' }}"></span> Part-Time</span>
                    </div>
                </section>

                <section class="load-section">
                    <h2 class="section-title">A. Basic Load / Built-In</h2>
                    <table class="load-table">
                        <colgroup><col class="code"><col class="description"><col class="day"><col class="time"><col class="section"><col class="room"><col class="units"><col class="units"><col class="total-units"><col class="hours"></colgroup>
                        <thead><tr><th>Code</th><th>Descriptive Title</th><th>Day</th><th>Time</th><th>Section</th><th>Room</th><th>Units<br>(Lec)</th><th>Units<br>(Lab)</th><th>Total<br>Units</th><th>Total<br>Hours</th></tr></thead>
                        <tbody>
                            @foreach($report['schedules'] as $schedule)
                                @php
                                    $units = (float) ($schedule->subject?->units ?? 0);
                                    $isLaboratory = strcasecmp((string) $schedule->subject?->subject_type, 'Laboratory') === 0;
                                    $hours = ((strtotime($schedule->end_time) - strtotime($schedule->start_time)) / 3600) * count(\App\Models\ClassSchedule::daysForPattern($schedule->day));
                                    $sectionName = $course.'-'.preg_replace('/\s*-\s*/', '', strtoupper((string) $schedule->section?->name));
                                @endphp
                                <tr>
                                    <td>{{ $schedule->subject?->code ?? 'TBA' }}</td>
                                    <td class="description">{{ $schedule->subject?->name ?? 'TBA' }}</td>
                                    <td>{{ $schedule->day }}</td>
                                    <td>{{ date('g:i A', strtotime($schedule->start_time)) }}&ndash;{{ date('g:i A', strtotime($schedule->end_time)) }}</td>
                                    <td>{{ $sectionName }}</td>
                                    <td>{{ $schedule->room?->name ?? 'TBA' }}</td>
                                    <td>{{ $isLaboratory ? '—' : $formatNumber($units) }}</td>
                                    <td>{{ $isLaboratory ? $formatNumber($units) : '—' }}</td>
                                    <td>{{ $formatNumber($units) }}</td>
                                    <td>{{ $formatNumber($hours) }}</td>
                                </tr>
                            @endforeach
                            @for($row = $report['schedules']->count(); $row < 5; $row++)
                                <tr>@for($column = 0; $column < 10; $column++)<td>&nbsp;</td>@endfor</tr>
                            @endfor
                        </tbody>
                        <tfoot><tr class="total-row"><td class="total-label" colspan="8">Total Number of Units / Hours (Basic)</td><td>{{ $formatNumber($report['total_units']) }}</td><td>{{ $formatNumber($report['total_hours']) }}</td></tr></tfoot>
                    </table>
                </section>

                <section class="load-section">
                    <h2 class="section-title">B. Other Academic-Related Functions</h2>
                    <table class="function-table">
                        <colgroup><col class="code"><col class="description"><col class="day"><col class="time"><col class="students"><col class="units"><col class="units"><col class="total"><col class="total"></colgroup>
                        <thead><tr><th>Code</th><th>Descriptive Title</th><th>Day</th><th>Time</th><th>No. of Students</th><th>Units<br>(Lec)</th><th>Units<br>(Lab)</th><th>Total<br>Units</th><th>Total<br>Hours</th></tr></thead>
                        <tbody>@for($row = 0; $row < 4; $row++)<tr>@for($column = 0; $column < 9; $column++)<td>&nbsp;</td>@endfor</tr>@endfor</tbody>
                        <tfoot><tr class="total-row"><td class="total-label" colspan="7">Total Number of Units / Hours (Other Functions)</td><td>0</td><td>0</td></tr></tfoot>
                    </table>
                </section>

                <section class="load-section">
                    <h2 class="section-title">C. Consultation Hours</h2>
                    <table class="consultation-table">
                        <colgroup><col class="day"><col class="time"><col class="venue"><col class="hours"></colgroup>
                        <thead><tr><th>Day</th><th>Time</th><th>Venue</th><th>Number of Hours</th></tr></thead>
                        <tbody>@for($row = 0; $row < 4; $row++)<tr><td>&nbsp;</td><td></td><td></td><td></td></tr>@endfor</tbody>
                        <tfoot><tr class="total-row"><td class="total-label" colspan="3">Total Number of Units / Hours (Consultation)</td><td>0</td></tr></tfoot>
                    </table>
                </section>

                <section class="load-section">
                    <h2 class="section-title">D. Overload</h2>
                    <table class="load-table">
                        <colgroup><col class="code"><col class="description"><col class="day"><col class="time"><col class="section"><col class="room"><col class="units"><col class="units"><col class="total-units"><col class="hours"></colgroup>
                        <thead><tr><th>Code</th><th>Descriptive Title</th><th>Day</th><th>Time</th><th>Section</th><th>Room</th><th>Units<br>(Lec)</th><th>Units<br>(Lab)</th><th>Total<br>Units</th><th>Total<br>Hours</th></tr></thead>
                        <tbody>@for($row = 0; $row < 3; $row++)<tr>@for($column = 0; $column < 10; $column++)<td>&nbsp;</td>@endfor</tr>@endfor</tbody>
                        <tfoot>
                            <tr class="total-row"><td class="total-label" colspan="8">Total Number of Units / Hours (Overload)</td><td>0</td><td>0</td></tr>
                            <tr class="total-row grand-total"><td class="total-label" colspan="8">Grand Total Number of Units / Hours (A&ndash;D)</td><td>{{ $formatNumber($report['total_units']) }}</td><td>{{ $formatNumber($report['total_hours']) }}</td></tr>
                        </tfoot>
                    </table>
                </section>

                <footer class="signature-grid">
                    <div class="signature">
                        <p class="signature-label">Prepared by:</p>
                        <p class="signature-name">{{ $dean->name }}</p>
                        <p class="signature-role">Program Head / College Dean</p>
                        <p class="signature-date">Date Signed: ____________________</p>
                    </div>
                    <div class="signature">
                        <p class="signature-label">Recommending Approval:</p>
                        <p class="signature-name">Dr. Florpisa A. Montecillo, LPT</p>
                        <p class="signature-role">College President</p>
                        <p class="signature-date">Date Signed: ____________________</p>
                    </div>
                    <div class="signature">
                        <p class="signature-label">Approved by:</p>
                        <p class="signature-name">Hon. Romeo A. Villaceran</p>
                        <p class="signature-role">Chairman, Board of Trustees</p>
                        <p class="signature-date">Date Signed: ____________________</p>
                    </div>
                    <div class="signature">
                        <p class="signature-label">Conforme:</p>
                        <p class="signature-name">{{ $instructor->name }}</p>
                        <p class="signature-role">Instructor</p>
                        <p class="signature-date">Date Signed: ____________________</p>
                    </div>
                </footer>
            </article>
        @empty
            <section class="empty-report">
                <h1>No Instructor Accounts Found</h1>
                <p>Add an instructor account before opening this printable report.</p>
            </section>
        @endforelse
    </main>
</body>
</html>
