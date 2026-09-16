@extends('layouts.dean')
@section('title', 'Instructor Requests')
@section('page-title', 'Instructor Requests')

@push('styles')
<style>
    .request-priority-form { min-width:300px; }
    .request-priority-list { display:grid; gap:6px; }
    .request-priority-row { display:grid; grid-template-columns:72px minmax(0,1fr); align-items:center; gap:7px; }
    .request-priority-label { color:var(--navy); font-size:10px; font-weight:800; }
    .request-priority-form .button { width:100%; margin-top:9px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div><h2>Cross-Department Instructor Requests</h2><p>Review requests from other departments and assign up to four active {{ $course }} instructors in priority order.</p></div>
</div>

<section class="card" style="margin-bottom:20px">
    <h3 style="margin:0 0 14px">Incoming Requests</h3>
    <div class="table-wrap"><table>
        <thead><tr><th>Requesting Department</th><th>Subject</th><th>Year / Semester</th><th>Requested By</th><th>Status</th><th>Assignment</th></tr></thead>
        <tbody>
        @forelse($incoming as $instructorRequest)
            <tr>
                <td><span class="badge">{{ $instructorRequest->requesting_department }}</span></td>
                <td><strong>{{ $instructorRequest->subject?->code }}</strong><br><small>{{ $instructorRequest->subject?->name }}</small></td>
                <td>Year {{ $instructorRequest->subject?->year_level }} &middot; {{ $instructorRequest->subject?->semester }}</td>
                <td>{{ $instructorRequest->requestedBy?->name ?? 'Unknown' }}</td>
                <td><span class="badge">{{ str($instructorRequest->status)->title() }}</span></td>
                <td>
                    @if($instructorRequest->status === 'pending')
                        <form method="POST" action="{{ route('dean.instructor-requests.fulfill', $instructorRequest) }}" class="request-priority-form" data-request-priority-form>
                            @csrf
                            <div class="request-priority-list">
                                @for($priority = 1; $priority <= 4; $priority++)
                                    <div class="request-priority-row">
                                        <label class="request-priority-label" for="request-{{ $instructorRequest->id }}-priority-{{ $priority }}">Priority {{ $priority }}</label>
                                        <select id="request-{{ $instructorRequest->id }}-priority-{{ $priority }}" class="input request-priority-select" name="instructor_ids[]" @required($priority === 1)>
                                            <option value="">{{ $priority === 1 ? "Choose {$course} instructor" : 'Optional backup instructor' }}</option>
                                            @foreach($instructors as $instructor)
                                                <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endfor
                            </div>
                            @error('instructor_ids')<div class="error">{{ $message }}</div>@enderror
                            @error('instructor_ids.*')<div class="error">{{ $message }}</div>@enderror
                            <button class="button" type="submit">Assign Instructors</button>
                        </form>
                    @else
                        {{ $instructorRequest->assignedInstructors->pluck('name')->join(', ') ?: 'No instructor assigned' }}
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No incoming instructor requests.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<section class="card">
    <h3 style="margin:0 0 14px">Requests You Sent</h3>
    <div class="table-wrap"><table>
        <thead><tr><th>Requested Department</th><th>Subject</th><th>Status</th><th>Assigned Instructors</th></tr></thead>
        <tbody>
        @forelse($outgoing as $instructorRequest)
            <tr>
                <td><span class="badge">{{ $instructorRequest->requested_department }}</span></td>
                <td><strong>{{ $instructorRequest->subject?->code }}</strong> &middot; {{ $instructorRequest->subject?->name }}</td>
                <td><span class="badge">{{ str($instructorRequest->status)->title() }}</span></td>
                <td>{{ $instructorRequest->assignedInstructors->pluck('name')->join(', ') ?: 'Awaiting response' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No outgoing instructor requests.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection

@push('scripts')
<script>
    (()=>{
        document.querySelectorAll('[data-request-priority-form]').forEach(form=>{
            const selects=[...form.querySelectorAll('.request-priority-select')];
            const refresh=()=>{
                const selected=selects.map(select=>select.value).filter(Boolean);
                selects.forEach(select=>{
                    const ownValue=select.value;
                    [...select.options].forEach(option=>{
                        if(!option.value)return;
                        option.disabled=selected.includes(option.value)&&option.value!==ownValue;
                    });
                    if(select.value&&select.selectedOptions[0]?.disabled)select.value='';
                });
            };
            selects.forEach(select=>select.addEventListener('change',refresh));
        });
    })();
</script>
@endpush
