<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $instructor->name }} - Teaching Workload</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; padding:30px; color:#1e293b; font-family:Arial,sans-serif; }
        .toolbar { display:flex; justify-content:flex-end; margin-bottom:20px; }
        .toolbar-actions { display:flex; align-items:center; gap:9px; }
        .button,.exit-button { padding:10px 16px; border-radius:8px; font-weight:700; cursor:pointer; }
        .button { color:#fff; background:#450693; border:0; }
        .exit-button { color:#450693; background:#fff; border:1px solid #450693; }
        .header { margin-bottom:25px; text-align:center; }
        .header h1 { margin:0 0 7px; font-size:24px; }
        .header p { margin:4px; color:#64748b; }
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
        <p>Instructor Portal &middot; Printable teaching workload</p>
        <div class="toolbar-actions">
            <button class="exit-button" type="button" data-fallback="{{ route('instructor.workload.index') }}" onclick="window.close(); setTimeout(() => { window.location.href = this.dataset.fallback; }, 150);">Exit</button>
            <button class="button" type="button" onclick="window.print()">Print Workload</button>
        </div>
    </div>

    <main class="print-sheet">
        <header class="header">
            <h1>Instructor Teaching Workload</h1>
            <p>{{ $instructor->name }} &middot; {{ $instructor->course }} Department</p>
            <p>Generated {{ now()->format('F j, Y g:i A') }}</p>
        </header>

        <div class="summary">
            <strong>Employment: {{ str($instructor->employment_type ?? 'Unspecified')->replace('_',' ')->title() }}</strong>
            <strong>Estimated Weekly Hours: {{ $weeklyHours }}</strong>
            <strong>Scheduled Classes: {{ $schedules->count() }}</strong>
        </div>

        <table>
            <thead><tr><th>Day/Time</th><th>Subject</th><th>Section</th><th>Room</th><th>Academic Period</th></tr></thead>
            <tbody>
                @forelse($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->day }}<br>{{ date('g:i A',strtotime($schedule->start_time)) }}&ndash;{{ date('g:i A',strtotime($schedule->end_time)) }}</td>
                        <td>{{ $schedule->subject?->code }} - {{ $schedule->subject?->name }}</td>
                        <td>{{ $schedule->section?->name }}</td>
                        <td>{{ $schedule->room?->name ?? 'TBA' }}</td>
                        <td>{{ $schedule->academic_year }} &middot; {{ $schedule->semester }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No assigned schedules.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="signature">Instructor Signature</div>
    </main>
</body>
</html>
