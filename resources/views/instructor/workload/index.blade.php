@extends('layouts.instructor')
@section('title', 'Workload')
@section('page-title', 'Workload')
@section('content')
<div class="page-header"><div><h2>Teaching Workload</h2><p>View your assigned sections, subjects, rooms, and weekly schedule.</p></div><a class="button" target="_blank" href="{{ route('instructor.print.workload') }}">Print Workload</a></div>
<div class="card">
    <div class="table-wrap"><table><thead><tr><th>Time</th><th>Day</th><th>Subject Code</th><th>Subject Description</th><th>Section</th><th>Room</th></tr></thead><tbody>
    @forelse($schedules as $schedule)<tr><td>{{ date('g:i A',strtotime($schedule->start_time)) }}–{{ date('g:i A',strtotime($schedule->end_time)) }}</td><td><strong>{{ $schedule->day }}</strong></td><td><strong>{{ $schedule->subject?->code }}</strong></td><td>{{ $schedule->subject?->name }}</td><td>{{ $schedule->section?->name }}</td><td>{{ $schedule->room?->name ?? 'TBA' }}</td></tr>@empty<tr><td colspan="6">No assigned schedules found.</td></tr>@endforelse
    </tbody></table></div><x-pagination :paginator="$schedules" label="Workload pages" />
</div>
@endsection
