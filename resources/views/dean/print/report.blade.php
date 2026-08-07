@if($type === 'class-schedules')
    @include('dean.print.class-schedules')
@elseif($type === 'instructor-workload')
    @include('dean.print.instructor-workloads')
@elseif($type === 'teaching-loads')
    @include('dean.print.teaching-loads')
@else
<!DOCTYPE html>
<html>
<head>
    @include('layouts.partials.favicon')
    <meta charset="UTF-8">
    <title>{{ str($type)->replace('-', ' ')->title() }} - {{ $course }}</title>
    <style>
        body { font-family:Arial, sans-serif; margin:30px; color:#111; }
        h1,p { text-align:center; }
        table { width:100%; margin-top:24px; border-collapse:collapse; }
        th,td { padding:9px; border:1px solid #999; text-align:left; font-size:12px; }
        .print { margin:20px 0; padding:10px 16px; }
        @media print { .print { display:none; } }
    </style>
</head>
<body>
    <button class="print" onclick="window.print()">Print Report</button>
    <h1>{{ $course }} {{ str($type)->replace('-', ' ')->title() }}</h1>
    <p>Generated {{ now()->format('F d, Y g:i A') }}</p>

    @if($type === 'instructor-workload')
        <table>
            <thead><tr><th>Instructor</th><th>Employment</th><th>Status</th><th>Scheduled Classes</th></tr></thead>
            <tbody>
                @foreach($instructors as $instructor)
                    <tr>
                        <td>{{ $instructor->name }}</td>
                        <td>{{ str($instructor->employment_type)->replace('_', ' ')->title() }}</td>
                        <td>{{ ucfirst($instructor->account_status) }}</td>
                        <td>{{ $schedules->where('instructor_id', $instructor->id)->count() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table>
            <thead><tr><th>Section</th><th>Subject</th><th>Instructor</th><th>Room</th><th>Day/Time</th><th>Period</th></tr></thead>
            <tbody>
                @foreach($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->section?->name }}</td>
                        <td>{{ $schedule->subject?->code }} - {{ $schedule->subject?->name }}</td>
                        <td>{{ $schedule->instructor?->name }}</td>
                        <td>{{ $schedule->room?->name ?? 'TBA' }}</td>
                        <td>{{ $schedule->day }} {{ date('g:i A', strtotime($schedule->start_time)) }}–{{ date('g:i A', strtotime($schedule->end_time)) }}</td>
                        <td>{{ $schedule->academic_year }} {{ $schedule->semester }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
@endif
