@extends('layouts.dean')
@section('title', 'Instructor Units')
@section('page-title', 'Instructor Unit Management')

@push('styles')
<style>
    .unit-summary { margin-bottom:20px; padding:17px 19px; color:#4b3264; background:linear-gradient(110deg,#f5edfc,#fffaf0); border:1px solid #e3d5ef; border-radius:12px; }
    .unit-summary strong { display:block; margin-bottom:5px; color:var(--navy); }
    .unit-summary p { margin:0; font-size:12px; line-height:1.6; }
    .unit-filters { grid-template-columns:2fr 1fr 1fr; align-items:end; }
    .unit-table td { vertical-align:middle; }
    .instructor-cell { display:flex; align-items:center; gap:10px; min-width:210px; }
    .instructor-mark { width:38px; height:38px; display:grid; place-items:center; flex:0 0 38px; font-weight:850; color:white; background:var(--primary); border-radius:10px; }
    .instructor-cell strong,.instructor-cell span { display:block; }
    .instructor-cell span { margin-top:2px; color:var(--muted); font-size:10px; }
    .unit-number { font-size:18px; font-weight:850; color:var(--navy); }
    .unit-capacity { min-width:145px; }
    .unit-meter { height:7px; margin-top:7px; overflow:hidden; background:#ede6f1; border-radius:10px; }
    .unit-meter span { display:block; height:100%; background:var(--primary); border-radius:inherit; }
    .unit-meter.over span { background:var(--danger); }
    .capacity-copy { display:block; margin-top:5px; font-size:9px; color:var(--muted); }
    .capacity-copy.over { color:var(--danger); font-weight:750; }
    .custom-limit { margin-left:5px; }
    .adjustment-note { max-width:230px; color:#665b6e; line-height:1.45; }
    .unit-modal[hidden] { display:none; }
    .unit-modal { position:fixed; z-index:1900; inset:0; display:grid; place-items:center; padding:20px; background:rgba(31,5,57,.62); backdrop-filter:blur(4px); }
    .unit-dialog { width:min(520px,100%); padding:28px; background:white; border-top:4px solid var(--gold); border-radius:16px; box-shadow:0 28px 80px rgba(24,3,48,.3); }
    .unit-dialog h2 { margin:0 0 7px; color:var(--navy); }
    .unit-dialog-intro { margin:0 0 20px; color:var(--muted); font-size:12px; line-height:1.55; }
    .unit-current-summary { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-bottom:18px; }
    .unit-current-summary div { padding:12px; background:#f8f3fb; border:1px solid #e9dcf2; border-radius:9px; }
    .unit-current-summary span,.unit-current-summary strong { display:block; }
    .unit-current-summary span { margin-bottom:3px; color:var(--muted); font-size:9px; font-weight:750; text-transform:uppercase; }
    .unit-current-summary strong { color:var(--navy); }
    .unit-warning { margin-top:12px; padding:11px 12px; color:#9f2424; background:#fff4f3; border:1px solid #fecaca; border-radius:9px; font-size:11px; line-height:1.5; }
    .unit-dialog-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
    @media(max-width:900px) { .unit-filters { grid-template-columns:1fr 1fr; } }
    @media(max-width:600px) { .unit-filters,.unit-current-summary { grid-template-columns:1fr; } .unit-dialog-actions .button { flex:1; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $course }} Instructor Units</h2>
        <p>Set a fair maximum teaching load for every instructor based on performance and department needs.</p>
    </div>
</div>

<div class="unit-summary">
    <strong>How unit limits work</strong>
    <p>The configured limit controls future subject assignments, manual timetable edits, and automatic schedule generation. Full-time instructors default to 30 units and part-time instructors default to 15 units until the Dean sets a custom limit.</p>
</div>

<div class="card" style="margin-bottom:20px">
    <form class="filters unit-filters" method="GET" data-auto-filter>
        <div>
            <label for="unitSearch">Search instructor</label>
            <input id="unitSearch" class="input" name="search" value="{{ request('search') }}" placeholder="Name or email address">
        </div>
        <div>
            <label for="unitAcademicYear">Academic year</label>
            <select id="unitAcademicYear" class="input" name="academic_year">
                @if($academicYears->isEmpty())
                    <option value="">No schedules yet</option>
                @else
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" @selected($academicYear === $year)>{{ $year }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div>
            <label for="unitSemester">Semester</label>
            <select id="unitSemester" class="input" name="semester">
                @foreach(['1st', '2nd', 'Summer'] as $semesterOption)
                    <option value="{{ $semesterOption }}" @selected($semester === $semesterOption)>{{ $semesterOption }} Semester</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<div class="card">
    @if($instructors->isEmpty())
        <div style="padding:35px 15px;text-align:center">
            <h3 style="color:var(--navy);margin-bottom:6px">No instructors found</h3>
            <p style="color:var(--muted);font-size:12px">Try another search or approve an instructor account first.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="unit-table">
                <thead>
                    <tr>
                        <th>Instructor</th>
                        <th>Employment</th>
                        <th>Scheduled Units</th>
                        <th>Unit Limit</th>
                        <th>Capacity</th>
                        <th>Last Adjustment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($instructors as $instructor)
                        @php
                            $usedUnits=(float)($scheduledUnits[$instructor->id]??0);
                            $unitLimit=$instructor->effectiveTeachingUnitLimit();
                            $remaining=$unitLimit-$usedUnits;
                            $percentage=$unitLimit>0?min(100,($usedUnits/$unitLimit)*100):($usedUnits>0?100:0);
                            $isOver=$remaining<0;
                        @endphp
                        <tr>
                            <td>
                                <div class="instructor-cell">
                                    <span class="instructor-mark">{{ strtoupper(substr($instructor->first_name ?: 'I',0,1)) }}</span>
                                    <span><strong>{{ $instructor->name }}</strong><span>{{ $instructor->email }}</span></span>
                                </div>
                            </td>
                            <td>{{ str($instructor->employment_type ?? 'Unspecified')->replace('_',' ')->title() }}</td>
                            <td><span class="unit-number">{{ number_format($usedUnits,0) }}</span></td>
                            <td>
                                <strong>{{ $unitLimit }}</strong>
                                @if($instructor->teaching_unit_limit !== null)<span class="badge custom-limit">Custom</span>@else<span class="badge custom-limit">Default</span>@endif
                            </td>
                            <td class="unit-capacity">
                                <strong>{{ number_format(abs($remaining),0) }} {{ $isOver ? 'over limit' : 'available' }}</strong>
                                <div class="unit-meter {{ $isOver ? 'over' : '' }}"><span style="width:{{ $percentage }}%"></span></div>
                                <span class="capacity-copy {{ $isOver ? 'over' : '' }}">{{ number_format($usedUnits,0) }} of {{ $unitLimit }} units used</span>
                            </td>
                            <td>
                                @if($instructor->unit_limit_updated_at)
                                    <div class="adjustment-note"><strong>{{ $instructor->unit_limit_updated_at->format('M j, Y') }}</strong><br>{{ $instructor->unit_limit_note ?: 'No adjustment note provided.' }}</div>
                                @else
                                    <span style="color:var(--muted)">Not adjusted</span>
                                @endif
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="button button-secondary unit-adjust-trigger"
                                    data-update-url="{{ route('dean.instructor-units.update',$instructor) }}"
                                    data-instructor-name="{{ $instructor->name }}"
                                    data-current-units="{{ $usedUnits }}"
                                    data-unit-limit="{{ $unitLimit }}"
                                    data-unit-note="{{ $instructor->unit_limit_note }}"
                                >Adjust Units</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$instructors" label="Instructor unit pages" />
    @endif
</div>

<div id="unitAdjustmentModal" class="unit-modal" hidden>
    <section class="unit-dialog" role="dialog" aria-modal="true" aria-labelledby="unitAdjustmentTitle">
        <h2 id="unitAdjustmentTitle">Adjust Teaching Units</h2>
        <p id="unitAdjustmentIntro" class="unit-dialog-intro"></p>
        <div class="unit-current-summary">
            <div><span>Currently scheduled</span><strong id="modalScheduledUnits">0 units</strong></div>
            <div><span>Current limit</span><strong id="modalCurrentLimit">0 units</strong></div>
        </div>
        <form id="unitAdjustmentForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label for="modalUnitLimit">New teaching-unit limit</label>
                <input id="modalUnitLimit" class="input" type="number" name="teaching_unit_limit" min="0" max="60" step="1" required>
                <p style="margin-top:5px;color:var(--muted);font-size:10px">Allowed range: 0 to 60 units.</p>
            </div>
            <div class="form-group" style="margin-top:14px">
                <label for="modalUnitNote">Reason for adjustment</label>
                <textarea id="modalUnitNote" class="input" name="unit_limit_note" rows="3" maxlength="500" placeholder="Example: Increased after excellent performance review"></textarea>
            </div>
            <p id="unitLimitWarning" class="unit-warning" hidden>The new limit is below this instructor's current scheduled load. Existing classes will remain, but no additional subjects can be assigned until the load is reduced.</p>
            <div class="unit-dialog-actions">
                <button id="cancelUnitAdjustment" type="button" class="button button-secondary">Cancel</button>
                <button id="saveUnitAdjustment" type="submit" class="button">Save Unit Limit</button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal=document.getElementById('unitAdjustmentModal');
        const form=document.getElementById('unitAdjustmentForm');
        const input=document.getElementById('modalUnitLimit');
        const note=document.getElementById('modalUnitNote');
        const warning=document.getElementById('unitLimitWarning');
        const cancel=document.getElementById('cancelUnitAdjustment');
        const save=document.getElementById('saveUnitAdjustment');
        let scheduledUnits=0;
        let activeTrigger=null;

        const updateWarning=()=>{ warning.hidden=Number(input.value)>=scheduledUnits; };
        const close=()=>{ modal.hidden=true; document.body.classList.remove('modal-open'); form.removeAttribute('action'); activeTrigger?.focus(); };

        document.querySelectorAll('.unit-adjust-trigger').forEach(trigger=>trigger.addEventListener('click',()=>{
            activeTrigger=trigger;
            scheduledUnits=Number(trigger.dataset.currentUnits||0);
            form.action=trigger.dataset.updateUrl;
            document.getElementById('unitAdjustmentIntro').textContent=`Set the maximum teaching load for ${trigger.dataset.instructorName}.`;
            document.getElementById('modalScheduledUnits').textContent=`${scheduledUnits} units`;
            document.getElementById('modalCurrentLimit').textContent=`${trigger.dataset.unitLimit} units`;
            input.value=trigger.dataset.unitLimit;
            note.value=trigger.dataset.unitNote||'';
            updateWarning();
            modal.hidden=false;
            document.body.classList.add('modal-open');
            input.focus();
        }));
        input.addEventListener('input',updateWarning);
        cancel.addEventListener('click',close);
        modal.addEventListener('click',event=>{if(event.target===modal)close();});
        document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)close();});
        form.addEventListener('submit',()=>{save.disabled=true;save.textContent='Saving...';});
    })();
</script>
@endpush
