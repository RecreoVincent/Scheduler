<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Summary of Teaching Loads - {{ $course }}</title>
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
        .teaching-load-sheet { width:min(920px, 100%); min-height:1280px; margin:0 auto 28px; padding:18px 24px 26px; background:#fff; box-shadow:0 5px 24px rgba(15,23,42,.13); }
        .school-header { display:grid; grid-template-columns:82px minmax(420px, 620px) 82px; align-items:center; justify-content:center; gap:14px; text-align:center; }
        .brand-mark { width:70px; height:70px; display:grid; place-items:center; justify-self:center; }
        .brand-mark img { display:block; width:100%; height:100%; object-fit:contain; }
        .brand-mark.department-mark { width:82px!important; height:82px!important; }
        .brand-mark.department-mark img { width:82px!important; height:82px!important; border-radius:50%; transform:none!important; }
        .school-copy { padding-bottom:6px; border-bottom:1.5px solid #222; font-family:Georgia, 'Times New Roman', serif; }
        .school-name { margin:0; font-size:19px; font-weight:700; line-height:1.05; }
        .department-name { margin:2px 0 0; font-size:16px; font-weight:700; line-height:1.05; }
        .school-address,.school-email { margin:2px 0 0; font-size:9px; line-height:1.1; }
        .period-heading { margin:8px 0 7px; text-align:center; font-family:Georgia, 'Times New Roman', serif; }
        .period-heading strong { display:block; font-size:14px; }
        .period-heading span { display:block; margin-top:2px; font-size:11px; }
        .sheet-title { margin:0 -24px 10px; padding:5px 14px; color:#fff; background:#202020; text-align:center; font:700 13px Georgia, 'Times New Roman', serif; letter-spacing:.35px; }
        .teacher-group { margin-top:9px; }
        .group-title { margin:0 0 3px; font:700 12px Georgia, 'Times New Roman', serif; }
        .load-table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .load-table col.teacher { width:23%; }
        .load-table col.subjects { width:47%; }
        .load-table col.number { width:7.5%; }
        .load-table th,.load-table td { min-height:25px; padding:5px 6px; border:1px solid #222; vertical-align:middle; font-size:8px; overflow-wrap:anywhere; }
        .load-table th { text-align:center; font-family:Georgia, 'Times New Roman', serif; font-size:8.5px; }
        .load-table td { text-align:center; }
        .load-table td.teacher-name { font-weight:700; text-align:left; text-transform:uppercase; }
        .load-table td.subject-list { text-align:center; line-height:1.35; }
        .load-table td.subject-list span { display:block; }
        .load-table tr.blank-row td { height:27px; }
        .load-table tfoot td { font-weight:700; background:#e3efd9; }
        .load-table .total-label { text-align:right; }
        .signature-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:70px; margin:62px 45px 0; font-family:Georgia, 'Times New Roman', serif; }
        .signature { text-align:center; font-size:10px; }
        .signature-label { margin:0 0 23px; text-align:left; }
        .signature-name { margin:0; padding-top:4px; border-top:1px solid #111; font-size:11px; font-weight:700; text-transform:uppercase; }
        .signature-role { margin:3px 0 0; }
        .empty-report { width:min(900px, 100%); margin:60px auto; padding:70px 30px; text-align:center; background:#fff; border-top:5px solid #efbc3b; box-shadow:0 5px 24px rgba(15,23,42,.13); }
        .empty-report h1 { color:#450693; }
        @page { size:A4 portrait; margin:8mm; }
        @media print {
            html,body { background:#fff; }
            .print-toolbar { display:none !important; }
            .reports { padding:0; }
            .teaching-load-sheet { width:100%; min-height:0; margin:0; padding:0; box-shadow:none; break-after:page; page-break-after:always; }
            .teaching-load-sheet:last-child { break-after:auto; page-break-after:auto; }
            .teacher-group,.signature-grid,.load-table tr { break-inside:avoid; page-break-inside:avoid; }
            .sheet-title { margin-left:0; margin-right:0; }
            .empty-report { margin:0; box-shadow:none; }
        }
    </style>
    @include('layouts.partials.print-screen-theme')
</head>
<body class="print-preview">
    <div class="print-toolbar">
        <p>{{ $course }} summary of teaching loads &middot; {{ $teachingLoadReports->count() }} academic {{ Str::plural('period', $teachingLoadReports->count()) }}</p>
        <div class="toolbar-actions">
            <button class="exit-button" type="button" data-fallback="{{ route('dean.print.index') }}" onclick="window.close(); setTimeout(() => { window.location.href = this.dataset.fallback; }, 150);">Exit</button>
            <button class="print-button" type="button" onclick="window.print()">Print Summary of Teaching Loads</button>
        </div>
    </div>

    <main class="reports">
        @forelse($teachingLoadReports as $report)
            @php
                $semesterLabel = match(strtolower((string) $report['semester'])) {
                    '1st', 'first' => 'First Semester',
                    '2nd', 'second' => 'Second Semester',
                    'summer' => 'Summer',
                    default => $report['semester'] ?: 'Academic Period Not Assigned',
                };
                $formatNumber = fn (float $value): string => number_format($value, floor($value) === $value ? 0 : 1);
                $fullTimeTotals = [
                    'load' => (float) $report['full_time']->sum('load'),
                    'other' => (float) $report['full_time']->sum('other_load'),
                    'overload' => (float) $report['full_time']->sum('overload'),
                    'total' => (float) $report['full_time']->sum('total'),
                ];
                $partTimeTotals = [
                    'load' => (float) $report['part_time']->sum('load'),
                    'other' => (float) $report['part_time']->sum('other_load'),
                    'overload' => (float) $report['part_time']->sum('overload'),
                    'total' => (float) $report['part_time']->sum('total'),
                ];
            @endphp
            <article class="teaching-load-sheet">
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
                <h1 class="sheet-title">Summary of Teaching Loads</h1>

                <section class="teacher-group">
                    <h2 class="group-title">Full-Time Teachers</h2>
                    <table class="load-table">
                        <colgroup><col class="teacher"><col class="subjects"><col class="number"><col class="number"><col class="number"><col class="number"></colgroup>
                        <thead><tr><th>Name of Teachers</th><th>Subjects / Course</th><th>Load</th><th>Other Load</th><th>Overload</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach($report['full_time'] as $entry)
                                <tr>
                                    <td class="teacher-name">{{ $entry['instructor']->name }}</td>
                                    <td class="subject-list">
                                        @forelse($entry['subjects'] as $subject)<span>{{ $subject }}</span>@empty<span>No assigned subjects</span>@endforelse
                                    </td>
                                    <td>{{ $formatNumber($entry['load']) }}</td>
                                    <td>{{ $formatNumber($entry['other_load']) }}</td>
                                    <td>{{ $formatNumber($entry['overload']) }}</td>
                                    <td>{{ $formatNumber($entry['total']) }}</td>
                                </tr>
                            @endforeach
                            @for($row = $report['full_time']->count(); $row < 8; $row++)
                                <tr class="blank-row"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
                            @endfor
                        </tbody>
                        <tfoot><tr><td class="total-label" colspan="2">Full-Time Totals</td><td>{{ $formatNumber($fullTimeTotals['load']) }}</td><td>{{ $formatNumber($fullTimeTotals['other']) }}</td><td>{{ $formatNumber($fullTimeTotals['overload']) }}</td><td>{{ $formatNumber($fullTimeTotals['total']) }}</td></tr></tfoot>
                    </table>
                </section>

                <section class="teacher-group">
                    <h2 class="group-title">Part-Time Teachers</h2>
                    <table class="load-table">
                        <colgroup><col class="teacher"><col class="subjects"><col class="number"><col class="number"><col class="number"><col class="number"></colgroup>
                        <thead><tr><th>Name of Teachers</th><th>Subjects / Course</th><th>Load</th><th>Other Load</th><th>Overload</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach($report['part_time'] as $entry)
                                <tr>
                                    <td class="teacher-name">{{ $entry['instructor']->name }}</td>
                                    <td class="subject-list">
                                        @forelse($entry['subjects'] as $subject)<span>{{ $subject }}</span>@empty<span>No assigned subjects</span>@endforelse
                                    </td>
                                    <td>{{ $formatNumber($entry['load']) }}</td>
                                    <td>{{ $formatNumber($entry['other_load']) }}</td>
                                    <td>{{ $formatNumber($entry['overload']) }}</td>
                                    <td>{{ $formatNumber($entry['total']) }}</td>
                                </tr>
                            @endforeach
                            @for($row = $report['part_time']->count(); $row < 6; $row++)
                                <tr class="blank-row"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
                            @endfor
                        </tbody>
                        <tfoot><tr><td class="total-label" colspan="2">Part-Time Totals</td><td>{{ $formatNumber($partTimeTotals['load']) }}</td><td>{{ $formatNumber($partTimeTotals['other']) }}</td><td>{{ $formatNumber($partTimeTotals['overload']) }}</td><td>{{ $formatNumber($partTimeTotals['total']) }}</td></tr></tfoot>
                    </table>
                </section>

                <footer class="signature-grid">
                    <div class="signature">
                        <p class="signature-label">Prepared by:</p>
                        <p class="signature-name">{{ $dean->name }}</p>
                        <p class="signature-role">Program Head</p>
                    </div>
                    <div class="signature">
                        <p class="signature-label">Approved:</p>
                        <p class="signature-name">Dr. Florpisa A. Montecillo, LPT</p>
                        <p class="signature-role">College President</p>
                    </div>
                </footer>
            </article>
        @empty
            <section class="empty-report">
                <h1>No Teaching-Load Data Found</h1>
                <p>Add instructor accounts before opening this printable report.</p>
            </section>
        @endforelse
    </main>
</body>
</html>
