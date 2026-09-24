@extends('layouts.dean')
@section('title', 'Instructor Units')
@section('page-title', 'Instructor Unit Management')

@push('styles')
<style>
    .unit-filters { grid-template-columns:2fr 1fr; align-items:end; }
    #unitSearch, #unitAcademicYear { width:100%; border:1.5px solid var(--primary-light); }
    #unitSearch:focus, #unitAcademicYear:focus { border-color:var(--primary); }
    .unit-table { min-width:980px; }
    .unit-table th,.unit-table td { padding:11px 12px; vertical-align:middle; }
    .unit-table th:last-child,.unit-table td:last-child { width:145px; text-align:center; white-space:nowrap; }
    .unit-table td:last-child .actions { justify-content:center; flex-wrap:nowrap; }
    .instructor-cell { display:flex; align-items:center; gap:10px; min-width:210px; }
    .instructor-mark { width:38px; height:38px; display:grid; place-items:center; flex:0 0 38px; font-weight:850; color:white; background:var(--primary); border-radius:10px; }
    .instructor-cell strong,.instructor-cell span { display:block; }
    .instructor-cell > .instructor-mark { display:grid; place-items:center; margin-top:0; color:#fff !important; line-height:1; }
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
    .unit-current-summary { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-bottom:18px; }
    .unit-current-summary div { padding:12px; background:#f8f3fb; border:1px solid #e9dcf2; border-radius:9px; }
    .unit-current-summary span,.unit-current-summary strong { display:block; }
    .unit-current-summary span { margin-bottom:3px; color:var(--muted); font-size:9px; font-weight:750; text-transform:uppercase; }
    .unit-current-summary strong { color:var(--navy); }
    .unit-warning { margin-top:12px; padding:11px 12px; color:#9f2424; background:#fff4f3; border:1px solid #fecaca; border-radius:9px; font-size:11px; line-height:1.5; }
    .unit-capacity-alert { display:flex; align-items:flex-start; gap:13px; margin:0 0 20px; padding:17px 19px; color:#8a1c1c; background:linear-gradient(110deg,#fff4f3,#fff9ef); border:1px solid #fecaca; border-radius:12px; box-shadow:0 8px 22px rgba(159,36,36,.07); }
    .unit-capacity-alert-mark { width:34px; height:34px; display:grid; place-items:center; flex:0 0 34px; color:#fff; background:#c62828; border-radius:10px; font-size:17px; font-weight:850; }
    .unit-capacity-alert h3 { margin:0 0 4px; color:#8a1c1c; font-size:14px; }
    .unit-capacity-alert p { margin:0; font-size:11px; line-height:1.6; }
    @media(max-width:900px) { .unit-filters { grid-template-columns:1fr 1fr; } }
    @media(max-width:600px) { .unit-filters,.unit-current-summary { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $course }} Instructor Workload Hours</h2>
        <p>Set the maximum workload hours for every instructor based on performance and department needs.</p>
    </div>
    <button id="openDefaultUnit" class="button button-secondary" type="button">Default Hour Limit</button>
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
    </form>
</div>

@if($capacitySummary['unitShortfall'] > 0)
    @php
        $formatUnits = static function (float $units): string {
            return rtrim(rtrim(number_format($units, 1), '0'), '.');
        };
    @endphp
    <section class="unit-capacity-alert" role="alert">
        <div class="unit-capacity-alert-mark" aria-hidden="true">!</div>
        <div>
            <h3>Instructor capacity shortage</h3>
            <p>
                {{ $semester }} Semester Major subjects require <strong>{{ $formatUnits($capacitySummary['totalSubjectUnits']) }} workload hours</strong>,
                while the department's active instructors can carry only <strong>{{ $formatUnits($capacitySummary['totalInstructorCapacity']) }} workload hours</strong>.
                The shortfall is <strong>{{ $formatUnits($capacitySummary['unitShortfall']) }} workload hours</strong>.
                @if($capacitySummary['recommendedHires'] !== null)
                    Hire at least <strong>{{ $capacitySummary['recommendedHires'] }} additional full-time {{ Str::plural('instructor', $capacitySummary['recommendedHires']) }}</strong>
                    at the current {{ $capacitySummary['fullTimeCapacity'] }}-hour default, or increase approved capacity before generating schedules.
                @else
                    Set a positive full-time workload-hour default, then add enough instructors to cover the shortfall before generating schedules.
                @endif
            </p>
        </div>
    </section>
@endif

<div class="card">
    @if($instructors->isEmpty())
        <div style="padding:35px 15px;text-align:center">
            <h3 style="color:var(--navy);margin-bottom:6px">No instructors found</h3>
            <p style="color:var(--muted);font-size:12px">Try another search or approve an instructor account first.</p>
        </div>
    @else
        <x-pagination :paginator="$instructors" label="Instructor unit pages" />
        <div class="table-wrap">
            <table class="unit-table">
                <thead>
                    <tr>
                        <th>Instructor</th>
                        <th>Employment</th>
                        <th>Scheduled Hours</th>
                        <th>Hour Limit</th>
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
                                <span class="capacity-copy {{ $isOver ? 'over' : '' }}">{{ number_format($usedUnits,0) }} of {{ $unitLimit }} hours used</span>
                            </td>
                            <td>
                                @if($instructor->unit_limit_updated_at)
                                    <div class="adjustment-note"><strong>{{ $instructor->unit_limit_updated_at->format('M j, Y') }}</strong><br>{{ $instructor->unit_limit_note ?: 'No adjustment note provided.' }}</div>
                                @else
                                    <span style="color:var(--muted)">Not adjusted</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <button
                                        type="button"
                                        class="button button-secondary unit-adjust-trigger"
                                        data-update-url="{{ route('dean.instructor-units.update',$instructor) }}"
                                        data-instructor-name="{{ $instructor->name }}"
                                        data-current-units="{{ $usedUnits }}"
                                        data-unit-limit="{{ $unitLimit }}"
                                        data-unit-note="{{ $instructor->unit_limit_note }}"
                                    >Adjust Hours</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('portal-profile-overlay')
<div id="unitAdjustmentModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="unitAdjustmentTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="unitAdjustmentTitle">Adjust Workload Hours</h2>
                <p id="unitAdjustmentIntro"></p>
            </div>
            <button id="closeUnitAdjustment" class="admin-profile-close" type="button" aria-label="Close unit adjustment form">&times;</button>
        </header>
        <div class="unit-current-summary">
            <div><span>Currently scheduled</span><strong id="modalScheduledUnits">0 hours</strong></div>
            <div><span>Current limit</span><strong id="modalCurrentLimit">0 hours</strong></div>
        </div>
        <form id="unitAdjustmentForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="admin-profile-field">
                <label for="modalUnitLimit">New workload-hour limit</label>
                <input id="modalUnitLimit" class="input" type="number" name="teaching_unit_limit" min="0" max="60" step="1" required>
                <p style="margin-top:5px;color:var(--muted);font-size:10px">Allowed range: 0 to 60 hours.</p>
            </div>
            <div class="admin-profile-field" style="margin-top:14px">
                <label for="modalUnitNote">Reason for adjustment</label>
                <textarea id="modalUnitNote" class="input" name="unit_limit_note" rows="3" maxlength="500" placeholder="Example: Increased after excellent performance review"></textarea>
            </div>
            <p id="unitLimitWarning" class="unit-warning" hidden>The new limit is below this instructor's current scheduled load. Existing classes will remain, but no additional subjects can be assigned until the load is reduced.</p>
            <footer class="admin-profile-actions">
                <button id="cancelUnitAdjustment" type="button" class="button button-secondary">Cancel</button>
                <button id="saveUnitAdjustment" type="submit" class="button">Save Hour Limit</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('portal-profile-overlay')
<div id="defaultUnitModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="defaultUnitTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="defaultUnitTitle">Default Workload-Hour Limits</h2>
                <p>Set the {{ $course }} department-wide default workload-hour limit used for instructors who don't have an individual custom override.</p>
            </div>
            <button id="closeDefaultUnit" class="admin-profile-close" type="button" aria-label="Close default unit form">&times;</button>
        </header>
        <form id="defaultUnitForm" method="POST" action="{{ route('dean.instructor-units.defaults') }}">
            @csrf
            @method('PATCH')
            <div class="admin-profile-field">
                <label for="default_unit_limit_full_time">Full time</label>
                <input id="default_unit_limit_full_time" class="input" type="number" name="default_unit_limit_full_time" min="0" max="60" step="1" value="{{ old('default_unit_limit_full_time', $defaultUnitLimits['full_time']) }}" required>
            </div>
            <div class="admin-profile-field" style="margin-top:14px">
                <label for="default_unit_limit_industry_part_time">Industry part-time</label>
                <input id="default_unit_limit_industry_part_time" class="input" type="number" name="default_unit_limit_industry_part_time" min="0" max="60" step="1" value="{{ old('default_unit_limit_industry_part_time', $defaultUnitLimits['industry_part_time']) }}" required>
            </div>
            <div class="admin-profile-field" style="margin-top:14px">
                <label for="default_unit_limit_flexible_part_time">Flexible part-time</label>
                <input id="default_unit_limit_flexible_part_time" class="input" type="number" name="default_unit_limit_flexible_part_time" min="0" max="60" step="1" value="{{ old('default_unit_limit_flexible_part_time', $defaultUnitLimits['flexible_part_time']) }}" required>
            </div>
            <p style="margin-top:12px;color:var(--muted);font-size:11px">Instructors with an individual custom limit (marked "Custom" below) are not affected by this change.</p>
            <footer class="admin-profile-actions">
                <button id="cancelDefaultUnit" type="button" class="button button-secondary">Cancel</button>
                <button id="saveDefaultUnit" type="submit" class="button">Save Defaults</button>
            </footer>
        </form>
    </section>
</div>
@endpush

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
        const closeButton=document.getElementById('closeUnitAdjustment');
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
            document.getElementById('modalScheduledUnits').textContent=`${scheduledUnits} hours`;
            document.getElementById('modalCurrentLimit').textContent=`${trigger.dataset.unitLimit} hours`;
            input.value=trigger.dataset.unitLimit;
            note.value=trigger.dataset.unitNote||'';
            updateWarning();
            modal.hidden=false;
            document.body.classList.add('modal-open');
            input.focus();
        }));
        input.addEventListener('input',updateWarning);
        cancel.addEventListener('click',close);
        closeButton.addEventListener('click',close);
        modal.addEventListener('click',event=>{if(event.target===modal)close();});
        document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)close();});
        form.addEventListener('submit',()=>{save.disabled=true;save.textContent='Saving...';});
    })();

    (() => {
        const modal = document.getElementById('defaultUnitModal');
        const openButton = document.getElementById('openDefaultUnit');
        const cancelButton = document.getElementById('cancelDefaultUnit');
        const closeButton = document.getElementById('closeDefaultUnit');
        const saveButton = document.getElementById('saveDefaultUnit');
        const form = document.getElementById('defaultUnitForm');
        const firstInput = document.getElementById('default_unit_limit_full_time');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            openButton.focus();
        }

        openButton.addEventListener('click', openModal);
        cancelButton.addEventListener('click', closeModal);
        closeButton.addEventListener('click', closeModal);
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
        form.addEventListener('submit', () => { saveButton.disabled = true; saveButton.textContent = 'Saving...'; });

        @if($errors->hasAny(['default_unit_limit_full_time', 'default_unit_limit_industry_part_time', 'default_unit_limit_flexible_part_time']))
            openModal();
        @endif
    })();
</script>
@endpush
