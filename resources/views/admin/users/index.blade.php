@extends('layouts.admin')

@section('title', 'User Accounts')
@section('page-title', 'User Account Management')

@push('styles')
<style>
    .filters {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 12px;
        margin-bottom: 20px;
    }

    .input {
        width: 100%;
        padding: 11px 13px;
        font-size: 14px;
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        outline: none;
    }

    .input:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, .13);
    }

    .table-wrapper {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    th {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
    }

    td {
        font-size: 14px;
    }

    .badge {
        display: inline-block;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 700;
        color: #1d4ed8;
        background: #eff6ff;
        border-radius: 20px;
        text-transform: capitalize;
    }

    .actions {
        display: flex;
        gap: 7px;
    }

    .page-header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        justify-content: flex-end;
    }

    .small-button {
        padding: 7px 10px;
        font-size: 12px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 20px;
    }

    .pagination-link {
        min-width: 38px;
        height: 38px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        padding: 0 11px;
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        transition: .2s;
    }

    .pagination-link:hover {
        color: #2563eb;
        border-color: #93c5fd;
        background: #eff6ff;
    }

    .pagination-link.active {
        color: white;
        background: #2563eb;
        border-color: #2563eb;
    }

    .pagination-link.disabled {
        color: #94a3b8;
        background: #f8fafc;
        cursor: not-allowed;
    }

    .pagination-arrow {
        width: 17px;
        height: 17px;
        display: block;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .delete-modal[hidden] {
        display: none;
    }

    .delete-modal {
        position: fixed;
        z-index: 1900;
        inset: 0;
        display: grid;
        place-items: center;
        padding: 20px;
        background: rgba(15, 23, 42, .58);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .delete-dialog {
        width: min(440px, 100%);
        padding: 30px;
        text-align: center;
        background: white;
        border-radius: 18px;
        box-shadow: 0 25px 65px rgba(15, 23, 42, .28);
    }

    .delete-icon {
        width: 58px;
        height: 58px;
        display: grid;
        place-items: center;
        margin: 0 auto 17px;
        color: #dc2626;
        background: #fee2e2;
        border-radius: 50%;
    }

    .delete-icon svg { width: 28px; height: 28px; }

    .delete-dialog h2 {
        margin-bottom: 9px;
        color: #172554;
    }

    .delete-dialog p {
        color: #64748b;
        line-height: 1.6;
    }

    .delete-account-name {
        font-weight: 700;
        color: #334155;
    }

    .delete-modal-actions {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 23px;
    }

    @media (max-width: 800px) {
        .filters {
            grid-template-columns: 1fr;
        }

        .page-header-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }

</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h2>User Accounts</h2>
        <p>Create and manage dean, instructor, and student accounts.</p>
    </div>

    <div class="page-header-actions">
        <button id="openUserImport" type="button" class="button button-secondary">Import Accounts</button>
        <a class="button button-secondary" href="{{ route('admin.users.import-template') }}">Download CSV Template</a>
        <button id="openUserCreate" type="button" class="button">＋ Create Account</button>
    </div>
</div>

<div class="card">

    <form method="GET"
          action="{{ route('admin.users.index', absolute: false) }}"
          class="filters"
          data-auto-filter>

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            class="input"
            placeholder="Search name, email, or course"
        >

        <select name="role" class="input">
            <option value="">All roles</option>

            @foreach ($roles as $role)
                <option value="{{ $role }}"
                    @selected(request('role') === $role)>
                    {{ ucfirst($role) }}
                </option>
            @endforeach
        </select>

        <select name="course" class="input">
            <option value="">All courses</option>

            @foreach ($courses as $course)
                <option value="{{ $course }}"
                    @selected(request('course') === $course)>
                    {{ $course }}
                </option>
            @endforeach
        </select>

    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Course</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>

                    <td>
                        <span class="badge">
                            {{ $user->role }}
                        </span>
                    </td>

                    <td>{{ $user->course ?? '—' }}</td>

                    <td>
                        {{ $user->created_at->format('M d, Y') }}
                    </td>

                    <td>
                        <div class="actions">
                            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['edit' => $user->id])) }}#userFormModal"
                               class="button button-secondary small-button">
                                Edit
                            </a>

                            <button
                                type="button"
                                class="button button-danger small-button delete-trigger"
                                data-delete-url="{{ route('admin.users.destroy', $user) }}"
                                data-delete-name="{{ $user->name }}"
                            >
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        No matching accounts found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$users" label="User account pages" />

</div>

<div id="deleteConfirmationModal" class="delete-modal" hidden>
    <section class="delete-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle" aria-describedby="deleteModalDescription">
        <div class="delete-icon"><x-icon name="warning" /></div>
        <h2 id="deleteModalTitle">Delete Account?</h2>
        <p id="deleteModalDescription">
            You are about to permanently delete
            <span id="deleteAccountName" class="delete-account-name"></span>.
            This action cannot be undone.
        </p>

        <form id="deleteAccountForm" method="POST">
            @csrf
            @method('DELETE')

            <div class="delete-modal-actions">
                <button type="button" id="cancelDelete" class="button button-secondary">Cancel</button>
                <button type="submit" id="confirmDelete" class="button button-danger">Delete Account</button>
            </div>
        </form>
    </section>
</div>

<div id="userFormModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="userFormTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="userFormTitle">{{ $editingUser ? 'Edit Account' : 'Create Account' }}</h2>
                <p>{{ $editingUser ? "Update {$editingUser->name}'s account information." : 'Add a dean, instructor, or student account.' }}</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-user-form aria-label="Close account form">&times;</button>
        </header>

        <form id="userForm" method="POST" action="{{ $editingUser ? route('admin.users.update', $editingUser) : route('admin.users.store') }}">
            @csrf
            @if($editingUser) @method('PUT') @endif
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field">
                    <label for="user_first_name">First Name</label>
                    <input id="user_first_name" class="input" name="first_name" value="{{ old('first_name', $editingUser?->first_name) }}" required>
                    @error('first_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_middle_name">Middle Name</label>
                    <input id="user_middle_name" class="input" name="middle_name" value="{{ old('middle_name', $editingUser?->middle_name) }}">
                    @error('middle_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_last_name">Last Name</label>
                    <input id="user_last_name" class="input" name="last_name" value="{{ old('last_name', $editingUser?->last_name) }}" required>
                    @error('last_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_suffix">Suffix</label>
                    <input id="user_suffix" class="input" name="suffix" value="{{ old('suffix', $editingUser?->suffix) }}" placeholder="Jr., Sr., III">
                    @error('suffix')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_email">Email Address</label>
                    <input id="user_email" type="email" class="input" name="email" value="{{ old('email', $editingUser?->email) }}" required>
                    @error('email')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_role">Role</label>
                    <select id="user_role" class="input" name="role" required>
                        <option value="">Select role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', $editingUser?->role) === $role)>{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                    @error('role')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_course">Course</label>
                    <select id="user_course" class="input" name="course" required>
                        <option value="">Select course</option>
                        @foreach($courses as $course)
                            <option value="{{ $course }}" @selected(old('course', $editingUser?->course) === $course)>{{ $course }}</option>
                        @endforeach
                    </select>
                    @error('course')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_employment_type">Instructor Employment Type</label>
                    <select id="user_employment_type" class="input" name="employment_type">
                        <option value="">Not applicable</option>
                        <option value="full_time" @selected(old('employment_type', $editingUser?->employment_type) === 'full_time')>Full time (30 units maximum)</option>
                        <option value="industry_part_time" @selected(old('employment_type', $editingUser?->employment_type) === 'industry_part_time')>Industry Part-Time (15 units maximum)</option>
                        <option value="flexible_part_time" @selected(in_array(old('employment_type', $editingUser?->employment_type), ['flexible_part_time', 'part_time'], true))>Flexible Part-Time (15 units maximum)</option>
                    </select>
                    @error('employment_type')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_outside_work_end_time">Industry Outside-Work End Time</label>
                    <input id="user_outside_work_end_time" type="time" class="input" name="outside_work_end_time" value="{{ old('outside_work_end_time', $editingUser?->outside_work_end_time ? substr($editingUser->outside_work_end_time, 0, 5) : '17:00') }}">
                    <small style="display:block;margin-top:5px;color:var(--muted)">Used only for Industry Part-Time instructors.</small>
                    @error('outside_work_end_time')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_year_level">Student Year Level</label>
                    <select id="user_year_level" class="input" name="year_level">
                        <option value="">Not applicable</option>
                        @for($level = 1; $level <= 4; $level++)
                            <option value="{{ $level }}" @selected(old('year_level', $editingUser?->year_level) == $level)>Year {{ $level }}</option>
                        @endfor
                    </select>
                    @error('year_level')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_academic_section_id">Student Section</label>
                    <select id="user_academic_section_id" class="input" name="academic_section_id">
                        <option value="">Not assigned yet</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" @selected(old('academic_section_id', $editingUser?->academic_section_id) == $section->id)>{{ $section->course }} · Year {{ $section->year_level }} · {{ $section->name }} · {{ $section->academic_year }}</option>
                        @endforeach
                    </select>
                    @error('academic_section_id')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_account_status">Account Status</label>
                    <select id="user_account_status" class="input" name="account_status" required>
                        <option value="active" @selected(old('account_status', $editingUser?->account_status ?? 'active') === 'active')>Active</option>
                        <option value="pending" @selected(old('account_status', $editingUser?->account_status) === 'pending')>Pending</option>
                    </select>
                    @error('account_status')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_password">{{ $editingUser ? 'New Password' : 'Password' }}</label>
                    <input id="user_password" type="password" class="input" name="password" autocomplete="new-password" placeholder="{{ $editingUser ? 'Leave blank to keep current password' : '' }}" @if(!$editingUser) required @endif>
                    @error('password')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="user_password_confirmation">Confirm {{ $editingUser ? 'New ' : '' }}Password</label>
                    <input id="user_password_confirmation" type="password" class="input" name="password_confirmation" autocomplete="new-password" @if(!$editingUser) required @endif>
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-user-form>Cancel</button>
                <button class="button" type="submit">{{ $editingUser ? 'Save Changes' : 'Create Account' }}</button>
            </footer>
        </form>
    </section>
</div>

<div id="userImportModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="userImportTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="userImportTitle">Import User Accounts</h2>
                <p>Bulk-create dean, instructor, and student accounts from a CSV file.</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-user-import aria-label="Close user account import">&times;</button>
        </header>

        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field full">
                    <label for="user_import_csv_file">CSV file</label>
                    <input id="user_import_csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
                    @error('csv_file')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <p style="margin:0;color:var(--muted);font-size:12px;line-height:1.6">
                        Required columns for every row: <strong>first_name</strong>, <strong>last_name</strong>, <strong>email</strong>, <strong>role</strong> (dean, instructor, or student), and <strong>course</strong>.
                        Instructor rows also require employment_type (full_time, industry_part_time, or flexible_part_time); outside_work_end_time is required for industry part-time instructors (HH:MM).
                        Student rows require year_level and student_id; section is optional but must match the course and year level. The student ID must already be in the Student Roster.
                        Optional columns: middle_name, suffix, account_status (active or pending), and password. A temporary password is generated when password is blank.
                        <a href="{{ route('admin.users.import-template') }}">Download a CSV template</a>.
                    </p>
                    @if(session('user_import_error_note'))<p style="margin:12px 0 0;color:var(--danger);font-size:12px;line-height:1.55">{{ session('user_import_error_note') }}</p>@endif
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-user-import>Cancel</button>
                <button class="button" type="submit">Import Accounts</button>
            </footer>
        </form>
    </section>
</div>

@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('deleteConfirmationModal');
        const form = document.getElementById('deleteAccountForm');
        const accountName = document.getElementById('deleteAccountName');
        const cancelButton = document.getElementById('cancelDelete');
        const confirmButton = document.getElementById('confirmDelete');
        const triggers = [...document.querySelectorAll('.delete-trigger')];
        let activeTrigger = null;

        function openDeleteModal(trigger) {
            activeTrigger = trigger;
            form.action = trigger.dataset.deleteUrl;
            accountName.textContent = trigger.dataset.deleteName;
            modal.hidden = false;
            document.body.classList.add('modal-open');
            cancelButton.focus();
        }

        function closeDeleteModal() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            form.removeAttribute('action');
            activeTrigger?.focus();
        }

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => openDeleteModal(trigger));
        });

        cancelButton.addEventListener('click', closeDeleteModal);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeDeleteModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeDeleteModal();
            }
        });

        form.addEventListener('submit', () => {
            confirmButton.disabled = true;
            confirmButton.textContent = 'Deleting...';
        });
    })();

    (() => {
        const modal = document.getElementById('userFormModal');
        const openButton = document.getElementById('openUserCreate');
        const closeButtons = [...modal.querySelectorAll('[data-close-user-form]')];
        const firstInput = document.getElementById('user_first_name');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            @if($editingUser)
                window.location = '{{ route('admin.users.index', request()->except('edit')) }}';
                return;
            @endif
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            openButton.focus();
        }

        openButton.addEventListener('click', openModal);
        closeButtons.forEach(button => button.addEventListener('click', closeModal));
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

        @if($errors->hasAny(['first_name', 'last_name', 'middle_name', 'suffix', 'email', 'role', 'course', 'year_level', 'academic_section_id', 'employment_type', 'outside_work_end_time', 'account_status', 'password']) || $editingUser)
            openModal();
        @endif
    })();
</script>
@endpush

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('userImportModal');
        const openButton = document.getElementById('openUserImport');
        const closeButtons = [...modal.querySelectorAll('[data-close-user-import]')];
        const fileInput = document.getElementById('user_import_csv_file');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => fileInput.focus(), 0);
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

        @if($errors->has('csv_file') || session('user_import_error_note'))
            openModal();
        @endif
    })();
</script>
@endpush
