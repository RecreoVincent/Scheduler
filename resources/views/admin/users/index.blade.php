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
        backdrop-filter: blur(3px);
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
        font-size: 27px;
        font-weight: 700;
        color: #dc2626;
        background: #fee2e2;
        border-radius: 50%;
    }

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
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h2>User Accounts</h2>
        <p>Create and manage dean, instructor, and student accounts.</p>
    </div>

    <a href="{{ route('admin.users.create') }}" class="button">
        ＋ Create Account
    </a>
</div>

<div class="card">

    <form method="GET"
          action="{{ route('admin.users.index') }}"
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
                            <a href="{{ route('admin.users.edit', $user) }}"
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
        <div class="delete-icon" aria-hidden="true">!</div>
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
</script>
@endpush
