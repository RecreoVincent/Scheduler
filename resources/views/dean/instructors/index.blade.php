@extends('layouts.dean')

@section('title', 'Instructors')
@section('page-title', 'Instructor List')

@push('styles')
<style>
    .active-card h2 { margin-bottom:16px; color:var(--navy); }
    .account-confirmation[hidden] { display:none; }
    .account-confirmation { position:fixed; z-index:1900; inset:0; display:grid; place-items:center; padding:20px; background:rgba(15,23,42,.58); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); }
    .confirmation-dialog { width:min(450px,100%); padding:30px; text-align:center; background:white; border-radius:18px; box-shadow:0 25px 65px rgba(15,23,42,.28); }
    .confirmation-icon { width:58px; height:58px; display:grid; place-items:center; margin:0 auto 17px; color:var(--gold-dark); background:var(--gold-soft); border-radius:50%; }
    .confirmation-icon svg { width:27px; height:27px; }
    .confirmation-dialog h2 { margin-bottom:9px; color:var(--navy); }
    .confirmation-dialog p { color:#64748b; line-height:1.6; }
    .confirmation-name { font-weight:700; color:#334155; }
    .confirmation-actions { display:flex; justify-content:center; gap:10px; margin-top:23px; }
    .pagination-controls { display:flex; justify-content:space-between; align-items:center; gap:15px; margin-top:20px; }
    .pagination-button { min-width:100px; padding:9px 13px; font-size:13px; font-weight:700; color:var(--primary); text-align:center; background:#f4ebfa; border:1px solid #dcc6eb; border-radius:9px; }
    .pagination-button:hover { background:#eadbf4; }
    .pagination-button.disabled { color:#94a3b8; cursor:not-allowed; background:#f8fafc; border-color:#e2e8f0; }
    .pagination-status { font-size:13px; color:#64748b; }
    .input:disabled { color:#8b8092; background:rgba(243,238,246,.75); cursor:not-allowed; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $course }} Instructor Accounts</h2>
        <p>Filter and sort approved instructor accounts.</p>
    </div>
    <div class="actions">
        <button id="openInstructorImport" class="button button-secondary" type="button">Import Instructors</button>
        <a class="button button-secondary" href="{{ route('dean.instructors.import-template') }}">Download CSV Template</a>
        <button id="openInstructorCreate" class="button" type="button">Add Instructor</button>
    </div>
</div>

<form class="filters" style="grid-template-columns:2fr 1fr 1fr;" method="GET" data-auto-filter>
    <input class="input" name="search" value="{{ request('search') }}" placeholder="Search active instructor">
    <select class="input" name="employment_type">
        <option value="">All employment types</option>
        <option value="full_time" @selected(request('employment_type') === 'full_time')>Full time</option>
        <option value="flexible_part_time" @selected(request('employment_type') === 'flexible_part_time')>Flexible Part-Time</option>
        <option value="industry_part_time" @selected(request('employment_type') === 'industry_part_time')>Industry Part-Time</option>
    </select>
    <select class="input" name="sort">
        <option value="name" @selected(request('sort', 'name') === 'name')>Name A–Z</option>
        <option value="newest" @selected(request('sort') === 'newest')>Newest first</option>
        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
        <option value="employment" @selected(request('sort') === 'employment')>Employment type</option>
    </select>
</form>

<div style="display:flex;justify-content:flex-end;margin:14px 0">
    <button
        type="button"
        class="button button-danger account-action"
        data-action="deleteAll"
        data-url="{{ route('dean.instructors.destroy-all') }}"
        data-name="{{ $instructorAccountCount }} {{ str('instructor account')->plural($instructorAccountCount) }} in {{ $course }}"
        @disabled($instructorAccountCount === 0)
    >Delete All Accounts</button>
</div>

<div class="card active-card">
    <x-pagination :paginator="$instructors" label="Instructor account pages" mode="summary" />
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
                        <div class="actions">
                            <a class="button button-secondary" href="{{ route('dean.instructors.index', array_merge(request()->query(), ['edit' => $instructor->id])) }}#instructorCreateModal">Edit</a>
                            <button type="button" class="button button-danger account-action" data-action="delete" data-url="{{ route('dean.instructors.destroy', $instructor) }}" data-name="{{ $instructor->name }}">Delete</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No active instructors found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$instructors" label="Instructor account pages" mode="navigation" />
</div>

<div id="accountConfirmationModal" class="account-confirmation" hidden>
    <section class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmationTitle" aria-describedby="confirmationMessage">
        <div id="confirmationIcon" class="confirmation-icon"><x-icon name="question" /></div>
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

@push('portal-profile-overlay')
<div id="instructorCreateModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="instructorCreateTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="instructorCreateTitle">{{ $editingInstructor ? 'Edit Instructor' : 'Add Instructor' }}</h2>
                <p>{{ $editingInstructor ? "Update this instructor's profile for the {$course} department." : "Manually create an active instructor account for the {$course} department." }}</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-instructor-create aria-label="Close instructor form">&times;</button>
        </header>

        <form id="instructorCreateForm" method="POST" action="{{ $editingInstructor ? route('dean.instructors.update', $editingInstructor) : route('dean.instructors.store') }}">
            @csrf
            @if($editingInstructor) @method('PATCH') @endif
            <input type="hidden" name="instructor_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field">
                    <label for="modal_first_name">First Name</label>
                    <input id="modal_first_name" class="input" name="first_name" value="{{ old('first_name', $editingInstructor?->first_name) }}" required>
                    @error('first_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_last_name">Last Name</label>
                    <input id="modal_last_name" class="input" name="last_name" value="{{ old('last_name', $editingInstructor?->last_name) }}" required>
                    @error('last_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_middle_name">Middle Name</label>
                    <input id="modal_middle_name" class="input" name="middle_name" value="{{ old('middle_name', $editingInstructor?->middle_name) }}">
                    @error('middle_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_suffix">Suffix</label>
                    <input id="modal_suffix" class="input" name="suffix" value="{{ old('suffix', $editingInstructor?->suffix) }}" placeholder="Jr., III, etc.">
                    @error('suffix')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_email">Email</label>
                    <input id="modal_email" type="email" class="input" name="email" value="{{ old('email', $editingInstructor?->email) }}" required>
                    @error('email')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_employment_type">Employment Type</label>
                    <select id="modal_employment_type" class="input" name="employment_type" required>
                        @foreach(['full_time' => 'Full time', 'flexible_part_time' => 'Flexible Part-Time', 'industry_part_time' => 'Industry Part-Time'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_type', $editingInstructor?->employment_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('employment_type')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <label for="modal_outside_work_end_time">Outside Work End Time</label>
                    <input id="modal_outside_work_end_time" type="time" class="input" name="outside_work_end_time" value="{{ old('outside_work_end_time', $editingInstructor?->outside_work_end_time) }}">
                    <small style="display:block;margin-top:5px;color:var(--muted)">Required only for Industry Part-Time instructors.</small>
                    @error('outside_work_end_time')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                @if (! $editingInstructor)
                    <div class="admin-profile-field">
                        <label for="modal_password">Password</label>
                        <input id="modal_password" type="password" class="input" name="password" required>
                        @error('password')<span class="admin-profile-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="admin-profile-field">
                        <label for="modal_password_confirmation">Confirm Password</label>
                        <input id="modal_password_confirmation" type="password" class="input" name="password_confirmation" required>
                    </div>
                @endif
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-instructor-create>Cancel</button>
                <button class="button" type="submit">{{ $editingInstructor ? 'Save Changes' : 'Add Instructor' }}</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('instructorCreateModal');
        const openButton = document.getElementById('openInstructorCreate');
        const closeButtons = [...modal.querySelectorAll('[data-close-instructor-create]')];
        const firstInput = document.getElementById('modal_first_name');
        const employmentType = document.getElementById('modal_employment_type');
        const outsideWorkTime = document.getElementById('modal_outside_work_end_time');

        function syncOutsideWorkField() {
            const isIndustryPartTime = employmentType.value === 'industry_part_time';
            outsideWorkTime.disabled = !isIndustryPartTime;
            outsideWorkTime.required = isIndustryPartTime;
            if (!isIndustryPartTime) outsideWorkTime.value = '';
        }

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            @if($editingInstructor)
                window.location = '{{ route('dean.instructors.index', request()->except('edit')) }}';
                return;
            @endif
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            openButton.focus();
        }

        employmentType.addEventListener('change', syncOutsideWorkField);
        syncOutsideWorkField();

        openButton.addEventListener('click', openModal);
        closeButtons.forEach(button => button.addEventListener('click', closeModal));
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

        @if($errors->hasAny(['first_name', 'last_name', 'middle_name', 'suffix', 'email', 'employment_type', 'outside_work_end_time', 'password']) || $editingInstructor)
            openModal();
        @endif
    })();
</script>
@endpush

@push('portal-profile-overlay')
<div id="instructorImportModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="instructorImportTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="instructorImportTitle">Import Instructors</h2>
                <p>Bulk-create active {{ $course }} instructor accounts from a CSV file.</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-instructor-import aria-label="Close instructor import">&times;</button>
        </header>

        <form method="POST" action="{{ route('dean.instructors.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="instructor_import_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field full">
                    <label for="csv_file">CSV file</label>
                    <input id="csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
                    @error('csv_file')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <p style="margin:0;color:var(--muted);font-size:12px;line-height:1.6">
                        Required columns: <strong>first_name</strong>, <strong>last_name</strong>, <strong>email</strong>, <strong>employment_type</strong> (full_time, industry_part_time, or flexible_part_time).
                        Optional columns: middle_name, suffix, outside_work_end_time (required only for industry_part_time, format HH:MM), password (a temporary one is generated if left blank).
                        <a href="{{ route('dean.instructors.import-template') }}">Download a CSV template</a>.
                    </p>
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-instructor-import>Cancel</button>
                <button class="button" type="submit">Import Instructors</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('instructorImportModal');
        const openButton = document.getElementById('openInstructorImport');
        const closeButtons = [...modal.querySelectorAll('[data-close-instructor-import]')];
        const firstInput = document.getElementById('csv_file');

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
        closeButtons.forEach(button => button.addEventListener('click', closeModal));
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

        @if($errors->has('csv_file'))
            openModal();
        @endif
    })();
</script>
@endpush

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

        const iconSvg = {
            check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4M12 17h.01"/></svg>',
        };

        const content = {
            approve: { title:'Approve Instructor Account?', message:'This instructor will be activated and allowed to sign in.', label:'Approve Account', method:'PATCH', icon:'check' },
            decline: { title:'Decline Registration?', message:'This pending registration will be permanently removed.', label:'Decline Account', method:'DELETE', icon:'warning' },
            delete: { title:'Delete Instructor Account?', message:'This account and its assigned schedules will be permanently removed.', label:'Delete Account', method:'DELETE', icon:'warning' },
            deleteAll: { title:'Delete All Instructor Accounts?', message:'This permanently removes every instructor account below, along with their assigned schedules and subject assignments. This cannot be undone.', label:'Delete All Accounts', method:'DELETE', icon:'warning' },
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
            icon.innerHTML = iconSvg[details.icon];
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
