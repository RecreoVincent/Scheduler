@extends('layouts.dean')

@section('title', 'Dashboard')
@section('page-title', $course.' Dean Dashboard')

@push('styles')
<style>
    .welcome { margin-bottom:22px; padding:28px; background:linear-gradient(120deg,#f0e2fa,#fff9e8); border:1px solid #ddc7ec; border-radius:16px; }
    .welcome h2 { margin-bottom:7px; color:var(--navy); }
    .stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:15px; margin-bottom:22px; }
    .stat { width:100%; padding:20px; font:inherit; text-align:left; cursor:pointer; background:white; border:1px solid var(--border); border-radius:14px; transition:.2s; }
    .stat:hover,.stat:focus-visible,.stat.active { transform:translateY(-3px); border-color:#3b82f6; box-shadow:0 12px 25px rgba(37,99,235,.12); outline:none; }
    .stat span { font-size:12px; color:#64748b; }
    .stat strong { display:block; margin-top:8px; font-size:28px; color:var(--navy); }
    .schedule-row { display:grid; grid-template-columns:1fr 1.4fr 1fr 1fr; gap:10px; padding:12px 0; border-bottom:1px solid #e2e8f0; font-size:13px; }
    .analytics-modal[hidden] { display:none; }
    .analytics-modal { position:fixed; z-index:1500; inset:0; display:grid; place-items:center; padding:24px; background:rgba(15,23,42,.58); backdrop-filter:blur(3px); }
    .analytics-dialog { width:min(900px,100%); max-height:calc(100vh - 48px); padding:26px; overflow-y:auto; background:white; border-radius:18px; box-shadow:0 25px 65px rgba(15,23,42,.28); }
    .chart-header,.chart-actions,.chart-layout,.legend-item { display:flex; align-items:center; }
    .chart-header { justify-content:space-between; gap:18px; margin-bottom:24px; }
    .chart-header h2 { margin-bottom:5px; color:var(--navy); } .chart-header p { color:var(--muted); }
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
    @media(max-width:850px){ .stats{grid-template-columns:repeat(2,1fr)} .chart-header,.chart-layout{align-items:stretch;flex-direction:column}.chart-circle{margin:auto}.schedule-row{grid-template-columns:1fr 1fr} }
    @media(max-width:520px){ .stats{grid-template-columns:1fr}.chart-actions{align-items:stretch;flex-direction:column}.chart-toolbar{flex-wrap:wrap}.bar-chart{overflow-x:auto}.analytics-modal{padding:12px} }
</style>
@endpush

@section('content')
<div class="welcome">
    <h2>Welcome, {{ auth()->user()->name }}!</h2>
    <p>Manage the {{ $course }} department's people, academic data, rooms, and class schedules.</p>
</div>

<div class="stats">
    @foreach (['Instructors' => 'instructors', 'Students' => 'students', 'Subjects' => 'subjects', 'Sections' => 'sections', 'Rooms' => 'rooms'] as $label => $key)
        <button type="button" class="stat" data-stat="{{ $key }}" data-label="{{ $label }}">
            <span>{{ $label }}</span>
            <strong>{{ $statistics[$key] }}</strong>
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
