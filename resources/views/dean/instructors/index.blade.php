@extends('layouts.dean')

@section('title', 'Instructors')
@section('page-title', 'Instructor List')

@push('styles')
<style>
    .pending-card { margin-bottom: 22px; border-color: #fde68a; }
    .pending-heading { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
    .pending-heading h2 { color:#92400e; }
    .pending-count { padding:5px 10px; font-size:11px; font-weight:700; color:#92400e; background:#fef3c7; border-radius:20px; }
    .active-card h2 { margin-bottom:16px; color:var(--navy); }
    .account-confirmation[hidden] { display:none; }
    .account-confirmation { position:fixed; z-index:1900; inset:0; display:grid; place-items:center; padding:20px; background:rgba(15,23,42,.58); backdrop-filter:blur(3px); }
    .confirmation-dialog { width:min(450px,100%); padding:30px; text-align:center; background:white; border-radius:18px; box-shadow:0 25px 65px rgba(15,23,42,.28); }
    .confirmation-icon { width:58px; height:58px; display:grid; place-items:center; margin:0 auto 17px; font-size:26px; font-weight:700; color:var(--gold-dark); background:var(--gold-soft); border-radius:50%; }
    .confirmation-dialog h2 { margin-bottom:9px; color:var(--navy); }
    .confirmation-dialog p { color:#64748b; line-height:1.6; }
    .confirmation-name { font-weight:700; color:#334155; }
    .confirmation-actions { display:flex; justify-content:center; gap:10px; margin-top:23px; }
    .pagination-controls { display:flex; justify-content:space-between; align-items:center; gap:15px; margin-top:20px; }
    .pagination-button { min-width:100px; padding:9px 13px; font-size:13px; font-weight:700; color:var(--primary); text-align:center; background:#f4ebfa; border:1px solid #dcc6eb; border-radius:9px; }
    .pagination-button:hover { background:#eadbf4; }
    .pagination-button.disabled { color:#94a3b8; cursor:not-allowed; background:#f8fafc; border-color:#e2e8f0; }
    .pagination-status { font-size:13px; color:#64748b; }
</style>
@endpush

@section('content')
<div class="card pending-card">
    <div class="pending-heading">
        <div>
            <h2>Pending Instructor Registrations</h2>
            <p style="margin-top:5px;color:#64748b;font-size:13px;">Approve only verified applicants from the {{ $course }} department.</p>
        </div>
        <span class="pending-count">{{ $pendingInstructors->count() }} Pending</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Employment</th><th>Registered</th><th>Decision</th></tr></thead>
            <tbody>
            @forelse ($pendingInstructors as $instructor)
                <tr>
                    <td>{{ $instructor->name }}</td>
                    <td>{{ $instructor->email }}</td>
                    <td>{{ str($instructor->employment_type ?? 'Unspecified')->replace('_', ' ')->title() }}</td>
                    <td>{{ $instructor->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="actions">
                            <button type="button" class="button account-action" data-action="approve" data-url="{{ route('dean.instructors.approve', $instructor) }}" data-name="{{ $instructor->name }}">Approve</button>
                            <button type="button" class="button button-danger account-action" data-action="decline" data-url="{{ route('dean.instructors.destroy', $instructor) }}" data-name="{{ $instructor->name }}">Decline</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No pending instructor registrations.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="page-header" style="margin-top:28px;">
    <div>
        <h2>{{ $course }} Instructor Accounts</h2>
        <p>Filter and sort approved instructor accounts.</p>
    </div>
</div>

<form class="filters" style="grid-template-columns:2fr 1fr 1fr;" method="GET" data-auto-filter>
    <input class="input" name="search" value="{{ request('search') }}" placeholder="Search active instructor">
    <select class="input" name="employment_type">
        <option value="">All employment types</option>
        <option value="full_time" @selected(request('employment_type') === 'full_time')>Full time</option>
        <option value="industry_part_time" @selected(request('employment_type') === 'industry_part_time')>Industry Part-Time</option>
        <option value="flexible_part_time" @selected(request('employment_type') === 'flexible_part_time')>Flexible Part-Time</option>
    </select>
    <select class="input" name="sort">
        <option value="name" @selected(request('sort', 'name') === 'name')>Name A–Z</option>
        <option value="newest" @selected(request('sort') === 'newest')>Newest first</option>
        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
        <option value="employment" @selected(request('sort') === 'employment')>Employment type</option>
    </select>
</form>

<div class="card active-card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Employment</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse ($instructors as $instructor)
                <tr>
                    <td>{{ $instructor->name }}</td>
                    <td>{{ $instructor->email }}</td>
                    <td>{{ str($instructor->employment_type ?? 'Unspecified')->replace('_', ' ')->title() }}</td>
                    <td><span class="badge">Active</span></td>
                    <td>
                        <button type="button" class="button button-danger account-action" data-action="delete" data-url="{{ route('dean.instructors.destroy', $instructor) }}" data-name="{{ $instructor->name }}">Delete</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No active instructors found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$instructors" label="Instructor account pages" />
</div>

<div id="accountConfirmationModal" class="account-confirmation" hidden>
    <section class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmationTitle" aria-describedby="confirmationMessage">
        <div id="confirmationIcon" class="confirmation-icon" aria-hidden="true">?</div>
        <h2 id="confirmationTitle">Confirm Account Action</h2>
        <p id="confirmationMessage"></p>
        <p class="confirmation-name" id="confirmationAccountName"></p>

        <form id="accountActionForm" method="POST">
            @csrf
            <input type="hidden" name="_method" id="accountActionMethod">
            <div class="confirmation-actions">
                <button type="button" id="cancelAccountAction" class="button button-secondary">Cancel</button>
                <button type="submit" id="confirmAccountAction" class="button">Confirm</button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('accountConfirmationModal');
        const form = document.getElementById('accountActionForm');
        const method = document.getElementById('accountActionMethod');
        const title = document.getElementById('confirmationTitle');
        const message = document.getElementById('confirmationMessage');
        const accountName = document.getElementById('confirmationAccountName');
        const icon = document.getElementById('confirmationIcon');
        const cancelButton = document.getElementById('cancelAccountAction');
        const confirmButton = document.getElementById('confirmAccountAction');
        const actions = [...document.querySelectorAll('.account-action')];
        let activeTrigger = null;

        const content = {
            approve: { title:'Approve Instructor Account?', message:'This instructor will be activated and allowed to sign in.', label:'Approve Account', method:'PATCH', icon:'✓' },
            decline: { title:'Decline Registration?', message:'This pending registration will be permanently removed.', label:'Decline Account', method:'DELETE', icon:'!' },
            delete: { title:'Delete Instructor Account?', message:'This account and its assigned schedules will be permanently removed.', label:'Delete Account', method:'DELETE', icon:'!' },
        };

        function openModal(trigger) {
            activeTrigger = trigger;
            const action = trigger.dataset.action;
            const details = content[action];
            form.action = trigger.dataset.url;
            method.value = details.method;
            title.textContent = details.title;
            message.textContent = details.message;
            accountName.textContent = trigger.dataset.name;
            icon.textContent = details.icon;
            confirmButton.textContent = details.label;
            confirmButton.classList.toggle('button-danger', action !== 'approve');
            modal.hidden = false;
            document.body.classList.add('modal-open');
            cancelButton.focus();
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            form.removeAttribute('action');
            activeTrigger?.focus();
        }

        actions.forEach(button => button.addEventListener('click', () => openModal(button)));
        cancelButton.addEventListener('click', closeModal);
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
        form.addEventListener('submit', () => { confirmButton.disabled = true; confirmButton.textContent = 'Processing...'; });
    })();
</script>
@endpush
