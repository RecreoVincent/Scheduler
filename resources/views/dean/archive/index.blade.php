@extends('layouts.dean')
@section('title', 'Schedule Archive')
@section('page-title', 'Schedule Archive')

@push('styles')
<style>
    .archive-filters { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:22px; }
    .archive-date-groups { display:grid; gap:34px; }
    .archive-date-group { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); grid-auto-flow:row dense; align-items:stretch; gap:20px; }
    .archive-date-group > .archive-date-header { grid-column:1; }
    .archive-date-group > .archive-periods,
    .archive-date-group .archive-period { display:contents; }
    .archive-date-group .archive-period-header { grid-column:2; }
    .archive-date-group .archive-list { grid-column:1 / -1; }
    .archive-date-header { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:20px 22px; border-left:5px solid var(--gold); }
    .archive-date-heading { display:flex; align-items:center; gap:14px; }
    .archive-date-mark { width:48px; height:48px; display:grid; place-items:center; flex:0 0 48px; color:#fff; background:var(--primary); border-radius:12px; font-size:20px; }
    .archive-date-header h3 { margin-bottom:4px; color:var(--navy); font-size:19px; }
    .archive-date-header p { color:#55465f; font-size:11px; }
    .archive-periods { display:grid; gap:28px; }
    .archive-period { display:grid; gap:16px; }
    .archive-period-header { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:18px 20px; }
    .archive-period-heading { display:flex; align-items:center; gap:13px; }
    .archive-period-mark { width:44px; height:44px; display:grid; place-items:center; flex:0 0 44px; color:#fff; background:var(--primary); border-radius:11px; font-size:11px; font-weight:900; }
    .archive-period-header h3 { margin-bottom:4px; color:var(--navy); font-size:17px; }
    .archive-period-header p { color:#55465f; font-size:11px; }
    .archive-period-count { padding:7px 11px; color:var(--primary); background:rgba(255,255,255,.65); border:1px solid rgba(69,6,147,.14); border-radius:20px; font-size:10px; font-weight:800; white-space:nowrap; }
    .archive-list { display:grid; gap:22px; }
    .archive-section { padding:0; overflow:hidden; }
    .archive-section-header { display:flex; justify-content:space-between; align-items:center; gap:18px; padding:20px 22px; background:linear-gradient(135deg,#fbf8fd,#fffaf0); border-bottom:1px solid var(--border); }
    .archive-section-title { display:flex; align-items:center; gap:13px; }
    .archive-section-mark { width:40px; height:40px; display:grid; place-items:center; flex:0 0 40px; color:#fff; background:var(--primary); border-radius:10px; font-weight:900; }
    .archive-section-title h3 { margin-bottom:4px; color:var(--primary); }
    .archive-section-title p { color:var(--muted); font-size:11px; }
    .archive-section-controls { display:flex; align-items:center; gap:8px; }
    .archive-section-controls .button { min-height:38px; padding:8px 13px; }
    .archive-section .cancel-selection { display:none; }
    .archive-section[data-mode] .cancel-selection { display:inline-flex; }
    .archive-section[data-mode] .mode-button { display:none; }
    .selection-notice { display:none; align-items:center; justify-content:space-between; gap:14px; padding:11px 22px; color:#4b3264; font-size:11px; font-weight:700; background:#f6efff; border-bottom:1px solid #e8daf8; }
    .archive-section[data-mode=restore] .selection-notice { display:flex; }
    .archive-table { width:100%; min-width:0; table-layout:fixed; }
    .archive-section .table-wrap { overflow-x:hidden; }
    .archive-table th,.archive-table td { overflow-wrap:anywhere; vertical-align:middle; }
    .archive-table tbody tr:last-child td { border-bottom:0; }
    .archive-date { white-space:nowrap; color:var(--muted); font-size:10px; }
    .selection-column { display:none; width:120px; text-align:right; }
    .archive-section[data-mode=restore] .selection-column { display:table-cell; }
    .restore-entry-action { min-width:98px; }
    .archive-empty { padding:48px 22px; text-align:center; }
    .archive-empty h3 { margin-bottom:8px; color:var(--primary); }
    .archive-empty p { color:var(--muted); }
    .archive-modal[hidden] { display:none; }
    .archive-modal { position:fixed; z-index:2200; inset:0; display:grid; place-items:center; padding:20px; background:rgba(24,9,39,.68); backdrop-filter:blur(4px); }
    .archive-dialog { width:min(430px,100%); padding:30px; text-align:center; background:white; border-top:4px solid var(--gold); border-radius:15px; box-shadow:0 28px 80px rgba(24,3,48,.3); }
    .archive-dialog-icon { width:52px; height:52px; display:grid; place-items:center; margin:0 auto 15px; color:#2d1b00; background:var(--gold-soft); border:1px solid #efd486; border-radius:50%; font-size:21px; font-weight:900; }
    .archive-dialog h2 { margin-bottom:10px; color:var(--primary); }
    .archive-dialog p { color:var(--muted); line-height:1.6; }
    .archive-dialog-actions { display:flex; justify-content:center; gap:10px; margin-top:22px; }
    @media(max-width:760px) {
        .archive-filters { grid-template-columns:1fr; }
        .archive-date-group { display:grid; grid-template-columns:1fr; }
        .archive-date-group > .archive-date-header,
        .archive-date-group .archive-period-header,
        .archive-date-group .archive-list { grid-column:1; }
        .archive-date-header,.archive-period-header,.archive-section-header { align-items:flex-start; flex-direction:column; }
        .archive-section-controls { width:100%; flex-wrap:wrap; }
        .archive-section-controls .button { flex:1; }
        .selection-notice { align-items:flex-start; flex-direction:column; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div><h2>{{ $course }} Schedule Archive</h2><p>Deleted class schedules are organized by deletion date, academic year, semester, and section.</p></div>
    <a class="button button-secondary" href="{{ route('dean.timetable.index') }}">Back to Timetable</a>
</div>

<form class="card archive-filters" method="GET" data-auto-filter>
    <select class="input" name="academic_year">
        <option value="">All academic years</option>
        @foreach($academicYears as $academicYear)
            <option value="{{ $academicYear }}" @selected(request('academic_year') === $academicYear)>{{ $academicYear }}</option>
        @endforeach
    </select>
    <select class="input" name="semester">
        <option value="">All semesters</option>
        @foreach(['1st', '2nd', 'Summer'] as $semester)
            <option value="{{ $semester }}" @selected(request('semester') === $semester)>{{ $semester }}</option>
        @endforeach
    </select>
    <select class="input" name="deleted_on">
        <option value="">All deletion dates</option>
        @foreach($deletionDates as $deletionDate)
            <option value="{{ $deletionDate }}" @selected(request('deleted_on') === $deletionDate)>
                {{ \Illuminate\Support\Carbon::parse($deletionDate)->format('F j, Y') }}
            </option>
        @endforeach
    </select>
</form>

@if($archivePages->isEmpty())
    <div class="card archive-empty"><h3>No archived schedules</h3><p>No deleted schedules match the selected deletion date, academic year, and semester.</p></div>
@else
    @php
        $archiveDateGroups = $archivePages->getCollection()
            ->groupBy('deletion_date')
            ->map(fn ($dateGroups) => $dateGroups
                ->groupBy('academic_year')
                ->map(fn ($yearGroups) => $yearGroups->groupBy('semester')));
    @endphp

    <div class="archive-date-groups">
        @foreach($archiveDateGroups as $deletedOn => $archivePeriods)
            <section class="archive-date-group" data-archive-date="{{ $deletedOn }}">
                <header class="card archive-date-header">
                    <div class="archive-date-heading">
                        <div class="archive-date-mark" aria-hidden="true">&#128197;</div>
                        <div>
                            <h3>Deleted on {{ \Illuminate\Support\Carbon::parse($deletedOn)->format('F j, Y') }}</h3>
                            <p>Schedules removed on this date</p>
                        </div>
                    </div>
                    <span class="archive-period-count">{{ $archivePeriods->flatten(2)->count() }} {{ str('archive')->plural($archivePeriods->flatten(2)->count()) }}</span>
                </header>

                <div class="archive-periods">
                @foreach($archivePeriods as $periodYear => $semesterGroups)
                    @foreach($semesterGroups as $periodSemester => $periodGroups)
                    <section class="archive-period" data-archive-period="{{ $deletedOn }}-{{ $periodYear }}-{{ $periodSemester }}">
                    <header class="card archive-period-header">
                        <div class="archive-period-heading">
                            <div class="archive-period-mark">AY</div>
                            <div><h3>Academic Year {{ $periodYear }}</h3><p>{{ $periodSemester }} Semester archived schedules</p></div>
                        </div>
                        <span class="archive-period-count">{{ $periodGroups->count() }} {{ str('section')->plural($periodGroups->count()) }}</span>
                    </header>

                    <div class="archive-list">
                        @foreach($periodGroups as $archiveGroup)
                            @php
                                $section = $sectionsById->get($archiveGroup->section_id);
                                $groupKey = "{$deletedOn}|{$periodYear}|{$periodSemester}|{$archiveGroup->section_id}";
                                $sectionSchedules = $archivedSchedulesByGroup->get($groupKey, collect());
                            @endphp
                            @continue(!$section)

                            <section class="card archive-section" data-archive-section="{{ $section->id }}-{{ $deletedOn }}-{{ $periodYear }}-{{ $periodSemester }}" data-section-card>
                                <header class="archive-section-header">
                                    <div class="archive-section-title">
                                        <div class="archive-section-mark">{{ strtoupper(substr($section->name, 0, 1)) }}</div>
                                        <div><h3>{{ $section->name }}</h3><p>{{ $section->year_level }} Year &middot; {{ $sectionSchedules->count() }} archived {{ str('entry')->plural($sectionSchedules->count()) }}</p></div>
                                    </div>
                                    <div class="archive-section-controls">
                                        <button class="button button-secondary mode-button" type="button" data-selection-mode="restore">Restore Schedule</button>
                                        <button
                                            class="button button-danger archive-action-trigger"
                                            type="button"
                                            data-kind="delete-section"
                                            data-url="{{ route('dean.archive.sections.destroy', ['section' => $section, 'academic_year' => $periodYear, 'semester' => $periodSemester, 'deleted_on' => $deletedOn]) }}"
                                            data-label="{{ $section->name }} — {{ $periodYear }} {{ $periodSemester }} Semester archive deleted on {{ \Illuminate\Support\Carbon::parse($deletedOn)->format('F j, Y') }}"
                                        >Delete Schedule</button>
                                        <button class="button button-secondary cancel-selection" type="button">Cancel Selection</button>
                                    </div>
                                </header>

                                <div class="selection-notice"><span data-selection-message></span><span>Select one row from the table below.</span></div>

                                <div class="table-wrap">
                                    <table class="archive-table">
                                        <thead><tr><th>Deleted</th><th>Time</th><th>Days</th><th>Subject Code</th><th>Subject Description</th><th>Room</th><th>Instructor</th><th class="selection-column">Select Entry</th></tr></thead>
                                        <tbody>
                                        @foreach($sectionSchedules as $schedule)
                                            <tr>
                                                <td class="archive-date">{{ $schedule->deleted_at?->format('M j, Y') }}<br>{{ $schedule->deleted_at?->format('g:i A') }}</td>
                                                <td>{{ date('g:i A', strtotime($schedule->start_time)) }} &ndash; {{ date('g:i A', strtotime($schedule->end_time)) }}</td>
                                                <td><strong>{{ $schedule->day }}</strong></td>
                                                <td><strong>{{ $schedule->subject?->code ?? 'Unavailable' }}</strong></td>
                                                <td>{{ $schedule->subject?->name ?? 'Unavailable' }}</td>
                                                <td>{{ $schedule->room?->name ?? ($schedule->room_id ? 'Unavailable' : 'TBA') }}</td>
                                                <td>{{ $schedule->instructor?->name ?? 'Unavailable' }}</td>
                                                <td class="selection-column"><button class="button archive-action-trigger restore-entry-action" type="button" data-kind="restore" data-url="{{ route('dean.archive.restore', $schedule->id) }}" data-label="{{ $section->name }} · {{ $schedule->subject?->code }}">Choose</button></td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endforeach
                    </div>
                    </section>
                    @endforeach
                @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <x-pagination :paginator="$archivePages" label="Archived schedule pages" />
@endif

<div id="archiveActionModal" class="archive-modal" hidden>
    <section class="archive-dialog" role="dialog" aria-modal="true" aria-labelledby="archiveActionTitle">
        <div id="archiveActionIcon" class="archive-dialog-icon" aria-hidden="true">↻</div>
        <h2 id="archiveActionTitle">Restore Schedule?</h2>
        <p id="archiveActionMessage"></p>
        <form id="archiveActionForm" method="POST">@csrf @method('PATCH')
            <div class="archive-dialog-actions"><button id="cancelArchiveAction" class="button button-secondary" type="button">Cancel</button><button id="confirmArchiveAction" class="button" type="submit">Restore</button></div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal=document.getElementById('archiveActionModal'),form=document.getElementById('archiveActionForm'),method=form.querySelector('input[name="_method"]'),title=document.getElementById('archiveActionTitle'),message=document.getElementById('archiveActionMessage'),icon=document.getElementById('archiveActionIcon'),confirm=document.getElementById('confirmArchiveAction'),cancel=document.getElementById('cancelArchiveAction');
    let trigger=null;
    const close=()=>{modal.hidden=true;document.body.classList.remove('modal-open');trigger?.focus();trigger=null};
    document.querySelectorAll('[data-section-card]').forEach(card=>{const selectionMessage=card.querySelector('[data-selection-message]');card.querySelector('[data-selection-mode]')?.addEventListener('click',()=>{card.dataset.mode='restore';selectionMessage.textContent='Which class entry do you want to restore?'});card.querySelector('.cancel-selection')?.addEventListener('click',()=>{delete card.dataset.mode;selectionMessage.textContent=''})});
    document.querySelectorAll('.archive-action-trigger').forEach(button=>button.addEventListener('click',()=>{trigger=button;const deleting=button.dataset.kind.startsWith('delete');form.action=button.dataset.url;method.value=deleting?'DELETE':'PATCH';title.textContent=deleting?'Delete Schedule Permanently?':'Restore Schedule?';message.textContent=deleting?`${button.dataset.label} will be permanently deleted and cannot be retrieved again.`:`Restore ${button.dataset.label} to the active timetable?`;icon.textContent=deleting?'!':'↻';confirm.textContent=deleting?'Delete Schedule':'Restore';confirm.classList.toggle('button-danger',deleting);modal.hidden=false;document.body.classList.add('modal-open');cancel.focus()}));
    cancel.addEventListener('click',close);modal.addEventListener('click',event=>{if(event.target===modal)close()});document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)close()});
})();
</script>
@endpush
