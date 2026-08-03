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
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 24px;
    }

    .stat-card {
        position: relative;
        width: 100%;
        padding: 22px;
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

    .stat-card span {
        font-size: 14px;
        color: #64748b;
    }

    .stat-card strong {
        display: block;
        margin-top: 10px;
        font-size: 31px;
        color: #172554;
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
        inset: 0;
        display: grid;
        place-items: center;
        padding: 24px;
        background: rgba(15, 23, 42, .58);
        backdrop-filter: blur(3px);
    }

    .chart-panel {
        width: min(900px, 100%);
        max-height: calc(100vh - 48px);
        overflow-y: auto;
        box-shadow: 0 24px 65px rgba(15, 23, 42, .25);
    }

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
        gap: 8px;
        padding: 5px;
        background: #eff6ff;
        border-radius: 11px;
    }

    .chart-type-button {
        padding: 8px 13px;
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
        background: transparent;
        border: 0;
        border-radius: 8px;
    }

    .chart-type-button.active {
        color: white;
        background: #2563eb;
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
        gap: 18px;
        width: 100%;
        height: 280px;
        padding: 30px 10px 0;
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
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
    }

    .bar-fill {
        width: min(70px, 75%);
        min-height: 3px;
        border-radius: 9px 9px 0 0;
        transition: height .35s ease;
    }

    .bar-label {
        margin-top: 9px;
        font-size: 12px;
        color: #64748b;
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
        grid-template-columns: 1.4fr .8fr;
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

    .quick-link {
        display: block;
        margin-bottom: 11px;
        padding: 14px;
        font-size: 14px;
        font-weight: 600;
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 11px;
    }

    .quick-link:hover {
        background: #dbeafe;
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
    <button type="button" class="stat-card" data-stat="total_users">
        <span>Total Accounts</span>
        <strong>{{ $statistics['total_users'] }}</strong>
    </button>

    <button type="button" class="stat-card" data-stat="total_deans">
        <span>Deans</span>
        <strong>{{ $statistics['total_deans'] }}</strong>
    </button>

    <button type="button" class="stat-card" data-stat="total_instructors">
        <span>Instructors</span>
        <strong>{{ $statistics['total_instructors'] }}</strong>
    </button>

    <button type="button" class="stat-card" data-stat="total_students">
        <span>Students</span>
        <strong>{{ $statistics['total_students'] }}</strong>
    </button>
</div>

<div id="analyticsModal" class="chart-modal" hidden>
<section class="card chart-panel" role="dialog" aria-modal="true" aria-labelledby="chartTitle">
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

    <div class="card">
        <h3 class="card-title">Quick Actions</h3>

        <a href="{{ route('admin.users.create') }}"
           class="quick-link">
            ＋ Create New Account
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="quick-link">
            ♙ Manage User Accounts
        </a>

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
