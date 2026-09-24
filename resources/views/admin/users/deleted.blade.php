@extends('layouts.admin')

@section('title', 'Deleted Accounts')
@section('page-title', 'Deleted Accounts')

@push('styles')
<style>
    .deleted-accounts-description { margin-bottom:18px; }
    .table-wrapper { overflow-x:auto; }
    .deleted-accounts-table { min-width:820px; }
    .restore-form, .force-delete-form { display:inline-flex; }
    .deleted-accounts-actions { display:flex; gap:8px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>Recently Deleted Accounts</h2>
        <p>Review and restore accounts that were removed from the system.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="button button-secondary">Back to User Accounts</a>
</div>

<section class="card">
    <p class="deleted-accounts-description">The ten most recently deleted accounts are shown on each page.</p>

    <x-pagination :paginator="$deletedUsers" label="Deleted account pages" mode="summary" />
    <div class="table-wrapper">
        <table class="deleted-accounts-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Course</th>
                    <th>Deleted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deletedUsers as $deletedUser)
                    <tr>
                        <td>{{ $deletedUser->name }}</td>
                        <td>{{ $deletedUser->email }}</td>
                        <td><span class="badge"><x-role-label :role="$deletedUser->role" /></span></td>
                        <td>{{ $deletedUser->course ?? '—' }}</td>
                        <td>{{ $deletedUser->deleted_at->format('M d, Y g:i A') }}</td>
                        <td>
                            <div class="deleted-accounts-actions">
                                <form class="restore-form" method="POST" action="{{ route('admin.users.restore', $deletedUser->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="button button-secondary">Restore</button>
                                </form>
                                <form class="force-delete-form" method="POST" action="{{ route('admin.users.force-delete', $deletedUser->id) }}" onsubmit="return confirm('Permanently delete {{ addslashes($deletedUser->name) }}? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button button-danger">Delete Permanently</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No recently deleted accounts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$deletedUsers" label="Deleted account pages" mode="navigation" />

</section>
@endsection
