@extends('layouts.dean')

@section('title', 'Dashboard')
@section('page-title', $course.' Dean Dashboard')

@push('styles')
@include('layouts.partials.dashboard-analytics-card-styles')
<style>
    .welcome { margin-bottom:22px; padding:28px; background:linear-gradient(120deg,#f0e2fa,#fff9e8); border:1px solid #ddc7ec; border-radius:16px; }
    .welcome h2 { margin-bottom:7px; color:var(--navy); }
    .schedule-row { display:grid; grid-template-columns:1fr 1.4fr 1fr 1fr; gap:10px; padding:12px 0; border-bottom:1px solid #e2e8f0; font-size:13px; }
    .analytics-modal[hidden] { display:none; }
    .analytics-modal { position:fixed; z-index:1000; top:68px; right:0; bottom:0; left:220px; display:grid; place-items:center; padding:24px; background:rgba(15,23,42,.58); backdrop-filter:blur(3px); }
    .analytics-dialog { width:min(1100px,100%); max-height:calc(100vh - 132px); padding:26px; overflow-y:auto; color:#24152f; background:rgba(239,226,248,.68); border:1px solid rgba(69,6,147,.32); border-radius:18px; box-shadow:0 24px 65px rgba(15,23,42,.25); backdrop-filter:blur(8px); }
    .chart-header,.chart-actions,.chart-layout,.legend-item { display:flex; align-items:center; }
    .chart-header { justify-content:space-between; gap:18px; margin-bottom:24px; }
    .chart-header h2 { margin-bottom:5px; color:#2d045f !important; } .chart-header p { color:#4b3d55 !important; }
    .chart-actions { gap:12px; } .chart-toolbar { display:flex; gap:6px; padding:5px; background:#f3e9fa; border-radius:10px; }
    .chart-type { padding:8px 12px; font-size:12px; font-weight:700; color:#475569; cursor:pointer; background:transparent; border:0; border-radius:7px; }
    .chart-type.active { color:white; background:var(--primary); }
    .chart-close { width:38px; height:38px; font-size:24px; color:#64748b; cursor:pointer; background:#f8fafc; border:1px solid #e2e8f0; border-radius:9px; }
    .chart-layout { justify-content:center; gap:38px; min-height:280px; }
    .bar-chart { width:100%; height:280px; display:flex; align-items:flex-end; justify-content:space-around; gap:12px; padding:28px 8px 0; border-bottom:1px solid #cbd5e1; }
    .bar-column { height:100%; flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; }
    .bar-value { margin-bottom:6px; font-size:12px; font-weight:700; } .bar-fill { width:min(62px,75%); min-height:3px; border-radius:8px 8px 0 0; }
    .bar-label { margin-top:8px; font-size:10px; color:#64748b; text-align:center; }
    .chart-circle { position:relative; flex:0 0 230px; width:230px; height:230px; border-radius:50%; }
    .chart-circle.doughnut::after { position:absolute; inset:57px; content:''; background:white; border-radius:50%; box-shadow:0 0 0 1px #e2e8f0; }
    .legend { min-width:220px; display:grid; gap:10px; } .legend-item { justify-content:space-between; gap:20px; font-size:13px; }
    .legend-label { display:flex; align-items:center; gap:8px; } .legend-color { width:11px; height:11px; border-radius:3px; }
    .chart-empty { color:#64748b; }
    @media(max-width:950px){ .analytics-modal{left:0} }
    @media(max-width:850px){ .chart-header,.chart-layout{align-items:stretch;flex-direction:column}.chart-circle{margin:auto}.schedule-row{grid-template-columns:1fr 1fr} }
    @media(max-width:520px){ .chart-actions{align-items:stretch;flex-direction:column}.chart-toolbar{flex-wrap:wrap}.bar-chart{overflow-x:auto}.analytics-modal{padding:12px} }
</style>
@endpush

@section('content')
<div class="welcome">
    <h2>Welcome, {{ auth()->user()->name }}!</h2>
    <p>Manage the {{ $course }} department's people, academic data, rooms, and class schedules.</p>
</div>

@php
    $deanCards = [
        ['label'=>'Instructors','key'=>'instructors','color'=>'#3b82f6'], ['label'=>'Students','key'=>'students','color'=>'#8b5cf6'],
        ['label'=>'Subjects','key'=>'subjects','color'=>'#14b8a6'], ['label'=>'Sections','key'=>'sections','color'=>'#f59e0b'], ['label'=>'Rooms','key'=>'rooms','color'=>'#ef4444'],
    ];
    $deanMaximum = max(1, ...array_values($statistics));
@endphp
<div class="stats portal-analytics-grid">
    @foreach ($deanCards as $card)
        @php($percentage = (int) round(($statistics[$card['key']] / $deanMaximum) * 100))
        <button type="button" class="stat portal-analytics-card" data-stat="{{ $card['key'] }}" data-label="{{ $card['label'] }}" style="--analytics-accent:{{ $card['color'] }};--analytics-progress:{{ $percentage }}%">
            <span class="portal-analytics-header"><span class="portal-analytics-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19V8M10 19V4M16 19v-7M22 19H2"/></svg></span><span class="portal-analytics-title">{{ $card['label'] }}</span></span>
            <span class="portal-analytics-metric"><span class="portal-analytics-label">Department total</span><strong class="portal-analytics-value">{{ $statistics[$card['key']] }}</strong></span>
            <span class="portal-analytics-progress"><span class="portal-analytics-progress-fill"></span></span>
            <span class="portal-analytics-footer">{{ $percentage }}% relative to highest metric</span>
        </button>
    @endforeach
</div>

<div class="card">
    <div class="page-header">
        <div><h2>Recent Schedules</h2><p>Latest generated class assignments.</p></div>
        <a class="button" href="{{ route('dean.schedules.create') }}">Create Schedule</a>
    </div>
    @forelse ($recentSchedules as $schedule)
        <div class="schedule-row"><strong>{{ $schedule->section?->name }}</strong><span>{{ $schedule->subject?->code }} · {{ $schedule->subject?->name }}</span><span>{{ $schedule->day }} {{ date('g:i A', strtotime($schedule->start_time)) }}</span><span>{{ $schedule->room?->name ?? 'TBA' }}</span></div>
    @empty
        <p>No schedules generated yet.</p>
    @endforelse
</div>

<div id="deanAnalyticsModal" class="analytics-modal" hidden>
    <section class="analytics-dialog" role="dialog" aria-modal="true" aria-labelledby="deanChartTitle">
        <div class="chart-header">
            <div><h2 id="deanChartTitle">Department Analytics</h2><p>Compare current {{ $course }} department records.</p></div>
            <div class="chart-actions">
                <div class="chart-toolbar" role="group" aria-label="Chart type">
                    <button type="button" class="chart-type active" data-chart-type="bar">Bar</button>
                    <button type="button" class="chart-type" data-chart-type="pie">Pie</button>
                    <button type="button" class="chart-type" data-chart-type="doughnut">Doughnut</button>
                </div>
                <button type="button" id="closeDeanAnalytics" class="chart-close" aria-label="Close analytics">&times;</button>
            </div>
        </div>
        <div id="deanChartCanvas" class="chart-layout"></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const analytics = {{ Illuminate\Support\Js::from($analytics) }};
        const modal = document.getElementById('deanAnalyticsModal');
        const canvas = document.getElementById('deanChartCanvas');
        const title = document.getElementById('deanChartTitle');
        const closeButton = document.getElementById('closeDeanAnalytics');
        const cards = [...document.querySelectorAll('.stat[data-stat]')];
        const typeButtons = [...document.querySelectorAll('.chart-type')];
        let chartType = 'bar';
        let selectedKey = null;
        let lastTrigger = null;

        const colors = ['#450693','#e8b84a','#7022b8','#9b6ac2','#c89b2f','#6d3a96','#b899ca','#805000','#7c7285'];

        function activeData() {
            return Object.entries(analytics[selectedKey] ?? {}).map(([label, value], index) => ({
                key: label.toLowerCase().replaceAll(' ', '_'),
                label,
                value,
                color: label === 'Unassigned' || label === 'Unspecified' ? '#64748b' : colors[index % colors.length],
            }));
        }

        const escapeHtml = value => String(value).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
        const legend = items => `<div class="legend">${items.map(item => `<div class="legend-item"><span class="legend-label"><i class="legend-color" style="background:${item.color}"></i>${escapeHtml(item.label)}</span><strong>${item.value}</strong></div>`).join('')}</div>`;

        function renderBar() {
            const items = activeData();
            if (!items.length) { canvas.innerHTML = '<p class="chart-empty">No analytics are available for this category yet.</p>'; return; }
            const maximum = Math.max(1, ...items.map(item => item.value));
            canvas.innerHTML = `<div class="bar-chart" role="img" aria-label="Department analytics bar chart">${items.map(item => {
                const height = item.value === 0 ? 1 : Math.max(8, item.value / maximum * 210);
                return `<div class="bar-column"><span class="bar-value">${item.value}</span><div class="bar-fill" style="height:${height}px;background:${item.color}"></div><span class="bar-label">${escapeHtml(item.label)}</span></div>`;
            }).join('')}</div>`;
        }

        function renderCircle() {
            const items = activeData();
            const total = items.reduce((sum,item) => sum + item.value, 0);
            if (!total) { canvas.innerHTML = '<p class="chart-empty">No department analytics are available yet.</p>'; return; }
            let current = 0;
            const segments = items.map(item => { const start=current; current += item.value/total*100; return `${item.color} ${start}% ${current}%`; }).join(',');
            canvas.innerHTML = `<div class="chart-circle ${chartType === 'doughnut' ? 'doughnut' : ''}" style="background:conic-gradient(${segments})" role="img" aria-label="Department analytics ${chartType} chart"></div>${legend(items)}`;
        }

        function render() { chartType === 'bar' ? renderBar() : renderCircle(); }
        function closeModal() { modal.hidden=true; document.body.classList.remove('modal-open'); cards.forEach(card=>card.classList.remove('active')); lastTrigger?.focus(); }

        cards.forEach(card => card.addEventListener('click', () => {
            selectedKey=card.dataset.stat; lastTrigger=card;
            cards.forEach(item=>item.classList.toggle('active',item===card));
            title.textContent=`${card.dataset.label} Analytics`;
            modal.hidden=false; document.body.classList.add('modal-open'); render(); closeButton.focus();
        }));
        typeButtons.forEach(button => button.addEventListener('click', () => { chartType=button.dataset.chartType; typeButtons.forEach(item=>item.classList.toggle('active',item===button)); render(); }));
        closeButton.addEventListener('click',closeModal);
        modal.addEventListener('click',event=>{if(event.target===modal)closeModal()});
        document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)closeModal()});
    })();
</script>
@endpush
