<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Class Schedules - {{ $course }}</title>
    <style>
        :root { color-scheme:light; }
        * { box-sizing:border-box; }
        html,body { margin:0; padding:0; background:#eef1f5; color:#111; font-family:Arial, Helvetica, sans-serif; }
        .print-toolbar { position:sticky; z-index:10; top:0; display:flex; align-items:center; justify-content:space-between; gap:20px; padding:12px 24px; color:#fff; background:#450693; box-shadow:0 3px 14px rgba(32,7,61,.25); }
        .print-toolbar p { margin:0; font-size:14px; font-weight:700; }
        .toolbar-actions { display:flex; align-items:center; gap:10px; }
        .print-button { padding:10px 22px; color:#291500; background:#efbc3b; border:0; border-radius:7px; font-size:14px; font-weight:800; cursor:pointer; }
        .exit-button { padding:9px 20px; color:#fff; background:transparent; border:1px solid rgba(255,255,255,.72); border-radius:7px; font-size:14px; font-weight:800; cursor:pointer; }
        .reports { padding:24px; }
        .schedule-sheet { width:min(1300px, 100%); min-height:760px; margin:0 auto 28px; background:#fff; box-shadow:0 5px 24px rgba(15,23,42,.13); }
        .school-header { display:grid; grid-template-columns:110px minmax(420px, 680px) 110px; align-items:center; justify-content:center; gap:18px; padding:14px 26px 8px; text-align:center; }
        .brand-mark { width:88px; height:88px; display:grid; place-items:center; justify-self:center; }
        .brand-mark img { display:block; width:100%; height:100%; object-fit:contain; }
        .brand-mark.department-mark { width:82px!important; height:82px!important; }
        .brand-mark.department-mark img { width:82px!important; height:82px!important; border-radius:50%; transform:none!important; }
        .school-copy { padding:0 14px 9px; border-bottom:2px solid #333; font-family:Georgia, 'Times New Roman', serif; }
        .school-name { margin:0; font-size:25px; line-height:1.05; font-weight:700; }
        .department-name { margin:2px 0 0; font-size:21px; line-height:1.08; font-weight:700; }
        .school-address,.school-email { margin:3px 0 0; font-size:13px; line-height:1.15; }
        .report-heading { padding:9px 24px 12px; text-align:center; text-transform:uppercase; }
        .report-heading h1 { margin:0; font-size:20px; line-height:1.15; }
        .report-heading p { margin:4px 0 0; font-size:16px; line-height:1.15; }
        .report-heading .program { font-weight:800; }
        .report-heading .section-name { font-weight:800; }
        .heading-spacer { height:44px; }
        .schedule-table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .schedule-table col.time { width:13%; }
        .schedule-table col.days { width:8%; }
        .schedule-table col.code { width:11%; }
        .schedule-table col.description { width:30%; }
        .schedule-table col.unit { width:6%; }
        .schedule-table col.room { width:10%; }
        .schedule-table col.instructor { width:22%; }
        .schedule-table th,.schedule-table td { height:58px; padding:8px 10px; border:1px solid #222; text-align:center; vertical-align:middle; overflow-wrap:anywhere; }
        .schedule-table th { height:52px; font-size:12px; letter-spacing:.25px; text-transform:uppercase; }
        .schedule-table td { font-size:13px; }
        .schedule-table .subject-description { font-weight:700; text-transform:uppercase; }
        .schedule-table .subject-code,.schedule-table .days { font-weight:700; }
        .schedule-table tfoot td { height:38px; font-weight:800; text-transform:uppercase; background:#dcefcf; }
        .schedule-table tfoot .total-label { text-align:center; }
        .empty-report { width:min(900px, 100%); margin:60px auto; padding:70px 30px; text-align:center; background:#fff; border-top:5px solid #efbc3b; box-shadow:0 5px 24px rgba(15,23,42,.13); }
        .empty-report h1 { color:#450693; }
        @page { size:A4 landscape; margin:8mm; }
        @media print {
            html,body { background:#fff; }
            .print-toolbar { display:none !important; }
            .reports { padding:0; }
            .schedule-sheet { width:100%; min-height:0; margin:0; box-shadow:none; break-after:page; page-break-after:always; }
            .schedule-sheet:last-child { break-after:auto; page-break-after:auto; }
            .school-header { padding-top:0; }
            .schedule-table th,.schedule-table td { height:auto; min-height:0; }
            .schedule-table thead { display:table-header-group; }
            .schedule-table tr { break-inside:avoid; page-break-inside:avoid; }
            .empty-report { margin:0; box-shadow:none; }
        }
    </style>
    @include('layouts.partials.print-screen-theme')
</head>
<body class="print-preview">
    <div class="print-toolbar">
        <p>{{ $course }} class schedules &middot; {{ $scheduleReports->count() }} section {{ Str::plural('report', $scheduleReports->count()) }}</p>
        <div class="toolbar-actions">
            <button class="exit-button" type="button" data-fallback="{{ route('dean.print.index') }}" onclick="window.close(); setTimeout(() => { window.location.href = this.dataset.fallback; }, 150);">Exit</button>
            <button class="print-button" type="button" onclick="window.print()">Print Class Schedules</button>
        </div>
    </div>

    <main class="reports">
        @forelse($scheduleReports as $report)
            @php
                $semesterLabel = match(strtolower((string) $report['semester'])) {
                    '1st', 'first' => 'First Semester',
                    '2nd', 'second' => 'Second Semester',
                    'summer' => 'Summer',
                    default => $report['semester'],
                };
                $normalizedSection = preg_replace('/\s*-\s*/', '', strtoupper((string) $report['section']->name));
                $sectionLabel = $course.'-'.$normalizedSection;
            @endphp
            <article class="schedule-sheet">
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

                <section class="report-heading">
                    <p>{{ $semesterLabel }}, A.Y. {{ $report['academic_year'] }}</p>
                    <p class="program">{{ $program }}</p>
                    <p class="section-name">{{ $sectionLabel }}</p>
                </section>

                <div class="heading-spacer" aria-hidden="true"></div>

                <table class="schedule-table">
                    <colgroup>
                        <col class="time"><col class="days"><col class="code"><col class="description">
                        <col class="unit"><col class="room"><col class="instructor">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Days</th>
                            <th>Subject Code</th>
                            <th>Subject Description</th>
                            <th>Unit</th>
                            <th>Room</th>
                            <th>Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['schedules'] as $schedule)
                            <tr>
                                <td>{{ date('g:i A', strtotime($schedule->start_time)) }}&ndash;{{ date('g:i A', strtotime($schedule->end_time)) }}</td>
                                <td class="days">{{ $schedule->day }}</td>
                                <td class="subject-code">{{ $schedule->subject?->code ?? 'TBA' }}</td>
                                <td class="subject-description">{{ $schedule->subject?->name ?? 'TBA' }}</td>
                                <td>{{ number_format((float) ($schedule->subject?->units ?? 0), 0) }}</td>
                                <td>{{ $schedule->room?->name ?? 'TBA' }}</td>
                                <td>{{ $schedule->instructor?->name ?? 'TBA' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="total-label" colspan="4">Total No. of Units</td>
                            <td>{{ number_format($report['total_units'], 0) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </article>
        @empty
            <section class="empty-report">
                <h1>No Class Schedules Found</h1>
                <p>Generate a class schedule before opening this printable report.</p>
            </section>
        @endforelse
    </main>
</body>
</html>
