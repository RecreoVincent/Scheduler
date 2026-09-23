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
    .request-section-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:14px; }
    .request-section-header h3 { margin:0 0 4px; }
    .request-section-header p { max-width:690px; font-size:11px; line-height:1.5; color:var(--muted); }
    @media(max-width:600px) { .request-section-header { align-items:stretch; flex-direction:column; } .request-section-header .button { width:100%; } }
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
        @forelse($incomingActive as $instructorRequest)
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
            <tr><td colspan="6">No pending incoming instructor requests.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<section class="card" style="margin-bottom:20px">
    <div class="request-section-header">
        <div>
            <h3>Active Requests You Sent</h3>
            <p>These requests are awaiting a response from the requested department.</p>
        </div>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Requested Department</th><th>Subject</th><th>Status</th><th>Assigned Instructors</th></tr></thead>
        <tbody>
        @forelse($outgoingActive as $instructorRequest)
            <tr>
                <td><span class="badge">{{ $instructorRequest->requested_department }}</span></td>
                <td><strong>{{ $instructorRequest->subject?->code }}</strong> &middot; {{ $instructorRequest->subject?->name }}</td>
                <td><span class="badge">{{ str($instructorRequest->status)->title() }}</span></td>
                <td>{{ $instructorRequest->assignedInstructors->pluck('name')->join(', ') ?: 'Awaiting response' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No active outgoing instructor requests.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<section class="card">
    <div class="request-section-header">
        <div>
            <h3>Request History</h3>
            <p>Completed incoming and sent requests remain here until you clear them. Clearing history hides records only from your department without deleting any assignment.</p>
        </div>
        @if($requestHistory->isNotEmpty())
            <button id="openClearRequestHistory" type="button" class="button button-danger">Clear History</button>
        @endif
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Direction</th><th>Other Department</th><th>Subject</th><th>Status</th><th>Assigned Instructors</th><th>Completed</th></tr></thead>
        <tbody>
        @forelse($requestHistory as $instructorRequest)
            <tr>
                <td><span class="badge">{{ $instructorRequest->history_direction }}</span></td>
                <td><span class="badge">{{ $instructorRequest->history_department }}</span></td>
                <td><strong>{{ $instructorRequest->subject?->code }}</strong> &middot; {{ $instructorRequest->subject?->name }}</td>
                <td><span class="badge">{{ str($instructorRequest->status)->title() }}</span></td>
                <td>{{ $instructorRequest->assignedInstructors->pluck('name')->join(', ') ?: 'No instructor assigned' }}</td>
                <td>{{ $instructorRequest->fulfilled_at?->format('M j, Y g:i A') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No completed instructor request history.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

@if($requestHistory->isNotEmpty())
<div id="clearRequestHistoryModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="clearRequestHistoryTitle" aria-describedby="clearRequestHistoryMessage">
        <header class="admin-profile-header">
            <div>
                <h2 id="clearRequestHistoryTitle">Clear fulfilled request history?</h2>
                <p id="clearRequestHistoryMessage">Completed requests will be removed from your department's history. Active requests, the other department's history, and instructor assignments will not be affected.</p>
            </div>
            <button id="closeClearRequestHistory" class="admin-profile-close" type="button" aria-label="Close confirmation">&times;</button>
        </header>
        <form id="clearRequestHistoryForm" method="POST" action="{{ route('dean.instructor-requests.clear-history') }}">
            @csrf
        </form>
        <footer class="admin-profile-actions">
            <button id="cancelClearRequestHistory" type="button" class="button button-secondary">Cancel</button>
            <button id="confirmClearRequestHistory" type="submit" form="clearRequestHistoryForm" class="button button-danger">Clear History</button>
        </footer>
    </section>
</div>
@endif
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

    (()=>{
        const trigger=document.getElementById('openClearRequestHistory');
        const modal=document.getElementById('clearRequestHistoryModal');
        if(!trigger||!modal)return;

        const closeButton=document.getElementById('closeClearRequestHistory');
        const cancelButton=document.getElementById('cancelClearRequestHistory');
        const confirmButton=document.getElementById('confirmClearRequestHistory');
        const form=document.getElementById('clearRequestHistoryForm');
        const close=()=>{
            modal.hidden=true;
            document.body.classList.remove('modal-open');
            trigger.focus();
        };

        trigger.addEventListener('click',()=>{
            modal.hidden=false;
            document.body.classList.add('modal-open');
            cancelButton.focus();
        });
        closeButton.addEventListener('click',close);
        cancelButton.addEventListener('click',close);
        modal.addEventListener('click',event=>{if(event.target===modal)close();});
        document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)close();});
        form.addEventListener('submit',()=>{
            confirmButton.disabled=true;
            confirmButton.textContent='Clearing...';
        });
    })();
</script>
@endpush
