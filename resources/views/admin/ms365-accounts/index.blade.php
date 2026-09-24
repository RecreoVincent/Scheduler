@extends('layouts.admin')

@section('title', 'MS365 Accounts')
@section('page-title', 'MS365 Student Accounts')

@push('styles')
<style>
    .ms365-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
    .ms365-stat-card { display:flex; align-items:center; gap:14px; min-width:0; padding:18px 20px; background:rgba(255,255,255,.78); border:1px solid rgba(69,6,147,.2); border-radius:16px; box-shadow:0 8px 22px rgba(40,8,77,.08); }
    .ms365-stat-icon { width:42px; height:42px; display:grid; place-items:center; flex:0 0 42px; color:var(--stat-color); background:color-mix(in srgb,var(--stat-color) 13%,white); border-radius:12px; }
    .ms365-stat-icon svg { width:21px;height:21px; }
    .ms365-stat-body { min-width:0; }
    .ms365-stat-body span { display:block;margin-bottom:3px;color:#55465f;font-size:12px;font-weight:750; }
    .ms365-stat-body strong { display:block;color:#24152f;font-size:25px;line-height:1.1; }
    .ms365-table-wrap { overflow:hidden; }
    .ms365-table { width:100%; table-layout:fixed; }
    .ms365-table th, .ms365-table td { padding:16px 14px; white-space:normal; overflow-wrap:anywhere; }
    .ms365-page-actions, .ms365-actions { display:flex; align-items:center; gap:7px; }
    .ms365-actions { flex-wrap:nowrap; white-space:nowrap; }
    .ms365-actions .button { min-width:0; flex:0 0 auto; padding:8px 11px; font-size:12px; }
    @media(max-width:900px) { .ms365-stats { grid-template-columns:1fr; } .ms365-table-wrap { overflow-x:auto; } .ms365-table { min-width:980px; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>MS365 Student Account Registry</h2>
        <p>Manage the college-issued Microsoft 365 student email registry.</p>
    </div>
    <div class="ms365-page-actions">
        <button type="button" class="button button-danger ms365-delete-trigger" data-delete-all="true" data-delete-url="{{ route('admin.ms365-accounts.destroy-all') }}">Delete All</button>
        <button id="openMs365Create" class="button" type="button">+ Add MS365 Record</button>
    </div>
</div>

<div class="ms365-stats" aria-label="MS365 account summary">
    <article class="ms365-stat-card" style="--stat-color:#450693"><span class="ms365-stat-icon"><x-icon name="at-sign" /></span><div class="ms365-stat-body"><span>Total records</span><strong>{{ number_format($statistics['total']) }}</strong></div></article>
    <article class="ms365-stat-card" style="--stat-color:#15803d"><span class="ms365-stat-icon"><x-icon name="check" /></span><div class="ms365-stat-body"><span>Eligible</span><strong>{{ number_format($statistics['eligible']) }}</strong></div></article>
    <article class="ms365-stat-card" style="--stat-color:#b42318"><span class="ms365-stat-icon"><x-icon name="warning" /></span><div class="ms365-stat-body"><span>Blocked</span><strong>{{ number_format($statistics['blocked']) }}</strong></div></article>
</div>

<div class="card" style="margin-bottom:20px">
    <h3 style="margin-bottom:7px">Import Microsoft 365 CSV</h3>
    <p style="margin-bottom:15px">Upload the Users CSV exported from Microsoft 365. Existing email records will be updated.</p>
    <form method="POST" action="{{ route('admin.ms365-accounts.import') }}" enctype="multipart/form-data" style="display:flex;align-items:end;gap:12px;flex-wrap:wrap">
        @csrf
        <div style="flex:1;min-width:260px">
            <label for="csv_file">CSV file</label>
            <input id="csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
            @error('csv_file')<span class="error">{{ $message }}</span>@enderror
        </div>
        <button class="button" type="submit">Import CSV</button>
        <a class="button button-secondary" href="{{ route('admin.ms365-accounts.import-template') }}">Download CSV Template</a>
    </form>
</div>

<div class="card">
    <form class="filters" method="GET" style="grid-template-columns:1fr auto">
        <input class="input" type="search" name="search" value="{{ $search }}" placeholder="Search student number, name, or MS365 email">
        <button class="button" type="submit">Search</button>
    </form>
    <x-pagination :paginator="$accounts" label="MS365 account pages" mode="summary" />
    <div class="table-wrap ms365-table-wrap">
        <table class="ms365-table">
            <colgroup>
                <col style="width:15%"><col style="width:12%"><col style="width:20%"><col style="width:16%"><col style="width:11%"><col style="width:13%"><col style="width:13%">
            </colgroup>
            <thead><tr><th>Name</th><th>Student Number</th><th>MS365 Email</th><th>License</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td><strong>{{ $account->display_name ?: '—' }}</strong></td>
                        <td>{{ $account->student_number ?: '—' }}</td>
                        <td>{{ $account->email }}</td>
                        <td>{{ $account->license ?: 'Not listed' }}</td>
                        <td>@if($account->soft_deleted_at)<span class="badge" style="color:#b42318">Soft deleted</span>@elseif($account->is_blocked)<span class="badge" style="color:#b42318">Blocked</span>@else<span class="badge">Eligible</span>@endif</td>
                        <td>{{ ($account->last_imported_at ?? $account->updated_at)?->format('M d, Y g:i A') ?: '—' }}</td>
                        <td><div class="ms365-actions">
                            <a class="button button-secondary" href="{{ route('admin.ms365-accounts.index', array_merge(request()->query(), ['edit' => $account->id])) }}#ms365FormModal">Edit</a>
                            <button type="button" class="button button-danger ms365-delete-trigger" data-delete-url="{{ route('admin.ms365-accounts.destroy', $account) }}" data-account-email="{{ $account->email }}">Delete</button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No MS365 accounts have been imported.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$accounts" label="MS365 account pages" mode="navigation" />
</div>

@php($selectedStatus = old('status', $editingAccount?->soft_deleted_at ? 'soft_deleted' : ($editingAccount?->is_blocked ? 'blocked' : 'eligible')))
<div id="ms365FormModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="ms365FormTitle">
        <header class="admin-profile-header">
            <div><h2 id="ms365FormTitle">{{ $editingAccount ? 'Edit MS365 Record' : 'Add MS365 Record' }}</h2><p>{{ $editingAccount ? 'Update this local MS365 registry record.' : 'Create a local MS365 registry record.' }}</p></div>
            <button class="admin-profile-close" type="button" data-close-ms365-form aria-label="Close MS365 form">&times;</button>
        </header>
        <form id="ms365Form" method="POST" action="{{ $editingAccount ? route('admin.ms365-accounts.update', $editingAccount) : route('admin.ms365-accounts.store') }}">
            @csrf
            @if($editingAccount) @method('PATCH') @endif
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field"><label for="ms365_display_name">Display Name</label><input id="ms365_display_name" class="input" name="display_name" value="{{ old('display_name', $editingAccount?->display_name) }}" required>@error('display_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="ms365_student_number">Student Number</label><input id="ms365_student_number" class="input" name="student_number" value="{{ old('student_number', $editingAccount?->student_number) }}">@error('student_number')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field full"><label for="ms365_email">MS365 Email</label><input id="ms365_email" type="email" class="input" name="email" value="{{ old('email', $editingAccount?->email) }}" required>@error('email')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="ms365_first_name">First Name</label><input id="ms365_first_name" class="input" name="first_name" value="{{ old('first_name', $editingAccount?->first_name) }}">@error('first_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="ms365_last_name">Last Name</label><input id="ms365_last_name" class="input" name="last_name" value="{{ old('last_name', $editingAccount?->last_name) }}">@error('last_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="ms365_license">License</label><input id="ms365_license" class="input" name="license" value="{{ old('license', $editingAccount?->license) }}" placeholder="Example: Office 365 A1 for students">@error('license')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="ms365_status">Account Status</label><select id="ms365_status" class="input" name="status" required><option value="eligible" @selected($selectedStatus === 'eligible')>Eligible</option><option value="blocked" @selected($selectedStatus === 'blocked')>Blocked</option><option value="soft_deleted" @selected($selectedStatus === 'soft_deleted')>Soft deleted</option></select>@error('status')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
            </div>
            <footer class="admin-profile-actions"><button class="button button-secondary" type="button" data-close-ms365-form>Cancel</button><button class="button" type="submit">{{ $editingAccount ? 'Save Changes' : 'Create Record' }}</button></footer>
        </form>
    </section>
</div>

<div id="ms365DeleteModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="ms365DeleteTitle">
        <header class="admin-profile-header"><div><h2 id="ms365DeleteTitle">Remove MS365 Registry Record?</h2><p id="ms365DeleteSingleMessage">Remove <strong id="ms365DeleteEmail"></strong> from this local registry. The Microsoft 365 account itself will not be deleted.</p><p id="ms365DeleteAllMessage" hidden>Remove all {{ $statistics['total'] }} local MS365 registry records. The Microsoft 365 accounts themselves will not be deleted.</p></div></header>
        <form id="ms365DeleteForm" method="POST">@csrf @method('DELETE')<footer class="admin-profile-actions"><button class="button button-secondary" type="button" id="cancelMs365Delete">Cancel</button><button class="button button-danger" type="submit" id="confirmMs365Delete">Remove Record</button></footer></form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('ms365FormModal');
        const openButton = document.getElementById('openMs365Create');
        const closeButtons = [...modal.querySelectorAll('[data-close-ms365-form]')];
        const firstInput = document.getElementById('ms365_display_name');
        const open = () => { modal.hidden = false; document.body.classList.add('modal-open'); window.setTimeout(() => firstInput.focus(), 0); };
        const close = () => {
            @if($editingAccount) window.location = '{{ route('admin.ms365-accounts.index', request()->except('edit')) }}'; return; @endif
            modal.hidden = true; document.body.classList.remove('modal-open'); openButton.focus();
        };
        openButton.addEventListener('click', open);
        closeButtons.forEach(button => button.addEventListener('click', close));
        modal.addEventListener('click', event => { if (event.target === modal) close(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });
        @if($errors->hasAny(['email', 'student_number', 'display_name', 'first_name', 'last_name', 'license', 'status']) || $editingAccount) open(); @endif
    })();

    (() => {
        const modal = document.getElementById('ms365DeleteModal');
        const form = document.getElementById('ms365DeleteForm');
        const title = document.getElementById('ms365DeleteTitle');
        const email = document.getElementById('ms365DeleteEmail');
        const singleMessage = document.getElementById('ms365DeleteSingleMessage');
        const allMessage = document.getElementById('ms365DeleteAllMessage');
        const cancel = document.getElementById('cancelMs365Delete');
        const confirm = document.getElementById('confirmMs365Delete');
        let trigger;
        const close = () => { modal.hidden = true; document.body.classList.remove('modal-open'); form.removeAttribute('action'); trigger?.focus(); };
        document.querySelectorAll('.ms365-delete-trigger').forEach(button => button.addEventListener('click', () => {
            trigger = button;
            const deletingAll = button.dataset.deleteAll === 'true';
            form.action = button.dataset.deleteUrl;
            title.textContent = deletingAll ? 'Remove All MS365 Registry Records?' : 'Remove MS365 Registry Record?';
            singleMessage.hidden = deletingAll;
            allMessage.hidden = !deletingAll;
            email.textContent = button.dataset.accountEmail;
            confirm.textContent = deletingAll ? 'Remove All Records' : 'Remove Record';
            modal.hidden = false; document.body.classList.add('modal-open'); cancel.focus();
        }));
        cancel.addEventListener('click', close);
        modal.addEventListener('click', event => { if (event.target === modal) close(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });
        form.addEventListener('submit', () => { confirm.disabled = true; confirm.textContent = 'Removing...'; });
    })();
</script>
@endpush
