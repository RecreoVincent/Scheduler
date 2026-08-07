@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Admin Dashboard')

@push('styles')
<style>
    .welcome-card {
        margin-bottom: 24px;
        padding: 30px;
        background:
            linear-gradient(120deg, rgba(219, 234, 254, .9), rgba(255, 255, 255, .95));
        border: 1px solid #bfdbfe;
        border-radius: 19px;
    }

    .welcome-card h2 {
        margin-bottom: 8px;
        font-size: 27px;
        color: #172554;
    }

    .welcome-card p {
        max-width: 680px;
        line-height: 1.7;
        color: #64748b;
    }

    .statistics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }

    .stat-card {
        --card-accent: #4f46e5;
        position: relative;
        width: 100%;
        min-height: 205px;
        padding: 16px;
        overflow: hidden;
        font: inherit;
        text-align: left;
        cursor: pointer;
        background: white;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .stat-card:hover,
    .stat-card:focus-visible,
    .stat-card.active {
        transform: translateY(-3px);
        border-color: #3b82f6;
        box-shadow: 0 12px 25px rgba(37, 99, 235, .12);
        outline: none;
    }

    .stat-card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(99, 102, 241, .18);
    }

    .stat-card-icon {
        display: grid;
        flex: 0 0 28px;
        width: 28px;
        height: 28px;
        place-items: center;
        color: var(--card-accent);
        background: color-mix(in srgb, var(--card-accent) 12%, white);
        border-radius: 9px;
    }

    .stat-card-icon svg {
        width: 17px;
        height: 17px;
        stroke: currentColor;
    }

    .stat-card-title {
        font-size: 16px;
        font-weight: 800;
        color: #221538;
    }

    .stat-card-metrics {
        display: grid;
        gap: 7px;
        padding: 11px 0 9px;
    }

    .stat-metric {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: #40364f;
    }

    .stat-metric-label {
        font-size: 13px;
        font-weight: 600;
    }

    .stat-metric-value {
        display: inline-grid;
        grid-template-columns: minmax(24px, auto) 17px;
        align-items: center;
        justify-content: end;
        gap: 7px;
        min-width: 55px;
        font-size: 14px;
        font-weight: 800;
        color: #1e1231;
        text-align: right;
    }

    .status-dot {
        display: inline-block;
        flex: 0 0 17px;
        width: 17px;
        height: 17px;
        border-radius: 999px;
        box-shadow: inset 0 0 0 6px rgba(255, 255, 255, .75);
    }

    .status-dot.active { background: #22c55e; }
    .status-dot.pending { background: #f59e0b; }

    .stat-progress {
        display: block;
        width: 100%;
        height: 6px;
        max-height: 6px;
        overflow: hidden;
        background: rgba(148, 163, 184, .24);
        border-radius: 999px;
    }

    .stat-progress-bar {
        display: block;
        width: var(--progress);
        height: 6px;
        max-height: 6px;
        background: var(--card-accent);
        border-radius: inherit;
        transition: width .35s ease;
    }

    .stat-card-footer {
        display: block;
        width: 100%;
        margin-top: 9px;
        padding: 8px 10px;
        font-size: 11px;
        font-weight: 700;
        color: #554a65;
        background: rgba(248, 250, 252, .72);
        border-radius: 10px;
        white-space: nowrap;
    }

    body.modal-open {
        overflow: hidden;
    }

    .chart-modal[hidden] {
        display: none;
    }

    .chart-modal {
        position: fixed;
        z-index: 1000;
        top: 68px;
        right: 0;
        bottom: 0;
        left: 220px;
        display: grid;
        place-items: center;
        padding: 24px;
        background: rgba(15, 23, 42, .58);
        backdrop-filter: blur(3px);
    }

    .chart-panel {
        width: min(1100px, 100%);
        max-height: calc(100vh - 132px);
        padding: 26px;
        overflow-y: auto;
        color: #24152f;
        background-color: rgba(239, 226, 248, .68) !important;
        border-color: rgba(69, 6, 147, .32) !important;
        border-radius: 18px;
        backdrop-filter: blur(8px);
        box-shadow: 0 24px 65px rgba(15, 23, 42, .25);
    }

    .chart-panel .chart-header h3 { margin-bottom: 5px; font-size: 24px; color: #2d045f !important; }
    .chart-panel .chart-header p { color: #4b3d55 !important; }

    .chart-header,
    .chart-toolbar,
    .chart-layout,
    .chart-legend-item {
        display: flex;
        align-items: center;
    }

    .chart-header {
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 24px;
    }

    .chart-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chart-close {
        display: grid;
        width: 38px;
        height: 38px;
        place-items: center;
        flex: 0 0 auto;
        font-size: 24px;
        line-height: 1;
        color: #64748b;
        cursor: pointer;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
    }

    .chart-close:hover,
    .chart-close:focus-visible {
        color: #1e3a8a;
        background: #eff6ff;
        outline: 2px solid #93c5fd;
    }

    .chart-header p {
        margin-top: 5px;
        color: #64748b;
    }

    .chart-toolbar {
        gap: 6px;
        padding: 5px;
        background: #f3e9fa;
        border-radius: 10px;
    }

    .chart-type-button {
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
        background: transparent;
        border: 0;
        border-radius: 7px;
    }

    .chart-type-button.active {
        color: white;
        background: var(--primary);
    }

    .chart-layout {
        justify-content: center;
        gap: 38px;
        min-height: 280px;
    }

    .bar-chart {
        display: flex;
        align-items: flex-end;
        justify-content: space-around;
        gap: 12px;
        width: 100%;
        height: 280px;
        padding: 28px 8px 0;
        border-bottom: 1px solid #cbd5e1;
    }

    .bar-column {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        height: 100%;
    }

    .bar-value {
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }

    .bar-fill {
        width: min(62px, 75%);
        min-height: 3px;
        border-radius: 8px 8px 0 0;
        transition: height .35s ease;
    }

    .bar-label {
        margin-top: 8px;
        font-size: 10px;
        color: #64748b;
        text-align: center;
    }

    .chart-circle {
        position: relative;
        flex: 0 0 230px;
        width: 230px;
        height: 230px;
        border-radius: 50%;
    }

    .chart-circle.doughnut::after {
        position: absolute;
        inset: 57px;
        content: '';
        background: white;
        border-radius: 50%;
        box-shadow: 0 0 0 1px #e2e8f0;
    }

    .chart-legend {
        display: grid;
        gap: 12px;
        min-width: 190px;
    }

    .chart-legend-item {
        justify-content: space-between;
        gap: 24px;
        font-size: 14px;
        color: #475569;
    }

    .legend-label {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .legend-color {
        width: 11px;
        height: 11px;
        border-radius: 3px;
    }

    .chart-empty {
        color: #64748b;
        text-align: center;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .card-title {
        margin-bottom: 18px;
        font-size: 18px;
        color: #172554;
    }

    .recent-user {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 13px 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .recent-user:last-child {
        border-bottom: none;
    }

    .recent-user strong {
        display: block;
        margin-bottom: 3px;
        font-size: 14px;
    }

    .recent-user span {
        font-size: 12px;
        color: #64748b;
    }

    .role-badge {
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 700;
        color: #1d4ed8;
        background: #eff6ff;
        border-radius: 20px;
        text-transform: capitalize;
    }

    @media (max-width: 1050px) {
        .statistics {
            grid-template-columns: repeat(2, 1fr);
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .chart-header,
        .chart-layout {
            align-items: stretch;
            flex-direction: column;
        }

        .chart-actions {
            justify-content: space-between;
        }

        .chart-circle {
            flex-basis: 230px;
            margin: auto;
        }
    }

    @media (max-width: 950px) {
        .chart-modal {
            left: 0;
        }
    }

    @media (max-width: 550px) {
        .statistics {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

<div class="welcome-card">
    <h2>Welcome, {{ auth()->user()->name }}!</h2>

    <p>
        Monitor Scheduler accounts and manage system users from your
        administration portal.
    </p>
</div>

<div class="statistics">
    @php
        $analyticsCards = [
            ['stat' => 'total_users', 'summary' => 'all', 'title' => 'Account Status', 'total_label' => 'Total Accounts', 'accent' => '#3b82f6', 'icon' => 'users'],
            ['stat' => 'total_deans', 'summary' => 'dean', 'title' => 'Dean Status', 'total_label' => 'Total Deans', 'accent' => '#8b5cf6', 'icon' => 'cap'],
            ['stat' => 'total_instructors', 'summary' => 'instructor', 'title' => 'Instructor Status', 'total_label' => 'Total Instructors', 'accent' => '#14b8a6', 'icon' => 'board'],
            ['stat' => 'total_students', 'summary' => 'student', 'title' => 'Student Status', 'total_label' => 'Total Students', 'accent' => '#f59e0b', 'icon' => 'book'],
        ];
    @endphp

    @foreach ($analyticsCards as $card)
        @php($summary = $accountStatusAnalytics[$card['summary']])
        <button type="button" class="stat-card" data-stat="{{ $card['stat'] }}" style="--card-accent: {{ $card['accent'] }}; --progress: {{ $summary['active_percentage'] }}%;">
            <span class="stat-card-header">
                <span class="stat-card-icon" aria-hidden="true">
                    @if ($card['icon'] === 'users')
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    @elseif ($card['icon'] === 'cap')
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="m2 10 10-5 10 5-10 5Z"/><path d="M6 12v5c3 2.5 9 2.5 12 0v-5M22 10v6"/></svg>
                    @elseif ($card['icon'] === 'board')
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M3 3h18v12H3zM8 21l4-6 4 6M7 8h4M7 11h7"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M4 5.5v14A2.5 2.5 0 0 0 6.5 22H20"/></svg>
                    @endif
                </span>
                <span class="stat-card-title">{{ $card['title'] }}</span>
            </span>

            <span class="stat-card-metrics">
                <span class="stat-metric"><span class="stat-metric-label">{{ $card['total_label'] }}</span><span class="stat-metric-value">{{ $summary['total'] }}</span></span>
                <span class="stat-metric"><span class="stat-metric-label">Active</span><span class="stat-metric-value">{{ $summary['active'] }} <span class="status-dot active" aria-hidden="true"></span></span></span>
                <span class="stat-metric"><span class="stat-metric-label">Pending</span><span class="stat-metric-value">{{ $summary['pending'] }} <span class="status-dot pending" aria-hidden="true"></span></span></span>
            </span>

            <span class="stat-progress" aria-label="{{ $summary['active_percentage'] }} percent active">
                <span class="stat-progress-bar"></span>
            </span>
            <span class="stat-card-footer">{{ $summary['active_percentage'] }}% active</span>
        </button>
    @endforeach
</div>

<div id="analyticsModal" class="chart-modal" hidden>
<section class="card chart-panel" role="dialog" aria-modal="true" aria-labelledby="chartTitle" style="background-color:#d9bfea !important;background-image:var(--portal-panel-image) !important;background-position:center !important;background-repeat:no-repeat !important;background-size:cover !important;border:1px solid rgba(69,6,147,.16) !important;">
    <div class="chart-header">
        <div>
            <h3 id="chartTitle" class="card-title" style="margin-bottom:0;">Account Analytics</h3>
            <p>Compare the current account distribution.</p>
        </div>

        <div class="chart-actions">
            <div class="chart-toolbar" role="group" aria-label="Chart type">
                <button type="button" class="chart-type-button active" data-chart-type="bar">Bar</button>
                <button type="button" class="chart-type-button" data-chart-type="pie">Pie</button>
                <button type="button" class="chart-type-button" data-chart-type="doughnut">Doughnut</button>
            </div>

            <button type="button" id="closeAnalyticsModal" class="chart-close" aria-label="Close analytics">&times;</button>
        </div>
    </div>

    <div id="chartCanvas" class="chart-layout"></div>
</section>
</div>

<div class="dashboard-grid">

    <div class="card">
        <h3 class="card-title">Recently Created Accounts</h3>

        @forelse ($recentUsers as $user)
            <div class="recent-user">
                <div>
                    <strong>{{ $user->name }}</strong>

                    <span>
                        {{ $user->email }} ·
                        {{ $user->course ?? 'No course' }}
                    </span>
                </div>

                <span class="role-badge">
                    {{ $user->role }}
                </span>
            </div>
        @empty
            <p>No accounts have been created.</p>
        @endforelse
    </div>

</div>

@endsection

@push('scripts')
<script>
    (() => {
        const statistics = {{ Illuminate\Support\Js::from($statistics) }};
        const analyticsByRole = {{ Illuminate\Support\Js::from($analyticsByRole) }};
        const modal = document.getElementById('analyticsModal');
        const closeButton = document.getElementById('closeAnalyticsModal');
        const canvas = document.getElementById('chartCanvas');
        const title = document.getElementById('chartTitle');
        const statCards = [...document.querySelectorAll('.stat-card[data-stat]')];
        const typeButtons = [...document.querySelectorAll('.chart-type-button')];
        let chartType = 'bar';
        let selectedStat = 'total_users';
        let lastTrigger = null;

        const chartData = [
            { key: 'total_users', label: 'Total Accounts', value: statistics.total_users, color: '#2563eb' },
            { key: 'total_deans', label: 'Deans', value: statistics.total_deans, color: '#8b5cf6' },
            { key: 'total_instructors', label: 'Instructors', value: statistics.total_instructors, color: '#f59e0b' },
            { key: 'total_students', label: 'Students', value: statistics.total_students, color: '#10b981' },
        ];

        const otherAccounts = Math.max(0, statistics.total_users
            - statistics.total_deans
            - statistics.total_instructors
            - statistics.total_students);

        const distributionData = [
            ...chartData.slice(1),
            { key: 'other', label: 'Admin / Other', value: otherAccounts, color: '#64748b' },
        ];

        const departmentColors = {
            BSIT: '#dc2626',
            BSBA: '#16a34a',
            BSHM: '#f97316',
            BSED: '#2563eb',
            BEED: '#38bdf8',
            Unassigned: '#64748b',
        };
        const roleByStatistic = {
            total_deans: 'dean',
            total_instructors: 'instructor',
            total_students: 'student',
        };

        function activeData() {
            const role = roleByStatistic[selectedStat];

            if (!role) {
                return distributionData;
            }

            return Object.entries(analyticsByRole[role]).map(([department, value]) => ({
                key: department.toLowerCase(),
                label: department,
                value,
                color: departmentColors[department] ?? departmentColors.Unassigned,
            }));
        }

        const escapeHtml = (value) => String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        function legend(items) {
            return `<div class="chart-legend">${items.map((item) => `
                <div class="chart-legend-item">
                    <span class="legend-label"><i class="legend-color" style="background:${item.color}"></i>${escapeHtml(item.label)}</span>
                    <strong>${item.value}</strong>
                </div>`).join('')}</div>`;
        }

        function renderBarChart() {
            const items = activeData();
            const maximum = Math.max(1, ...items.map((item) => item.value));

            canvas.innerHTML = `<div class="bar-chart" role="img" aria-label="Accounts by department bar chart">
                ${items.map((item) => {
                    const height = item.value === 0 ? 1 : Math.max(8, (item.value / maximum) * 210);

                    return `<div class="bar-column">
                        <span class="bar-value">${item.value}</span>
                        <div class="bar-fill" style="height:${height}px;background:${item.color}"></div>
                        <span class="bar-label">${escapeHtml(item.label)}</span>
                    </div>`;
                }).join('')}
            </div>`;
        }

        function renderCircularChart() {
            const items = activeData();
            const total = items.reduce((sum, item) => sum + item.value, 0);

            if (total === 0) {
                canvas.innerHTML = '<p class="chart-empty">No account data is available yet.</p>';
                return;
            }

            let current = 0;
            const segments = items.map((item) => {
                const start = current;
                current += (item.value / total) * 100;
                return `${item.color} ${start}% ${current}%`;
            }).join(', ');

            canvas.innerHTML = `
                <div class="chart-circle ${chartType === 'doughnut' ? 'doughnut' : ''}"
                     style="background:conic-gradient(${segments})"
                     role="img" aria-label="Account distribution ${chartType} chart"></div>
                ${legend(items)}`;
        }

        function renderChart() {
            if (chartType === 'bar') {
                renderBarChart();
                return;
            }

            renderCircularChart();
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            statCards.forEach((item) => item.classList.remove('active'));
            lastTrigger?.focus();
        }

        statCards.forEach((card) => card.addEventListener('click', () => {
            selectedStat = card.dataset.stat;
            const selected = chartData.find((item) => item.key === selectedStat);
            lastTrigger = card;

            statCards.forEach((item) => item.classList.toggle('active', item === card));
            title.textContent = `${selected.label} Analytics`;
            modal.hidden = false;
            document.body.classList.add('modal-open');
            renderChart();
            closeButton.focus();
        }));

        typeButtons.forEach((button) => button.addEventListener('click', () => {
            chartType = button.dataset.chartType;
            typeButtons.forEach((item) => item.classList.toggle('active', item === button));
            renderChart();
        }));

        closeButton.addEventListener('click', closeModal);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
</script>
@endpush
