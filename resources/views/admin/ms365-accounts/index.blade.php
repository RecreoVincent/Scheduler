@extends('layouts.admin')

@section('title', 'MS365 Accounts')
@section('page-title', 'MS365 Student Accounts')

@push('styles')
<style>
    .ms365-stats {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:14px;
        margin-bottom:20px;
    }

    .ms365-stat-card {
        display:flex;
        align-items:center;
        gap:14px;
        min-width:0;
        padding:18px 20px;
        background:rgba(255,255,255,.78);
        border:1px solid rgba(69,6,147,.2);
        border-radius:16px;
        box-shadow:0 8px 22px rgba(40,8,77,.08);
    }

    .ms365-stat-icon {
        width:42px;
        height:42px;
        display:grid;
        place-items:center;
        flex:0 0 42px;
        color:var(--stat-color);
        background:color-mix(in srgb,var(--stat-color) 13%,white);
        border-radius:12px;
    }

    .ms365-stat-icon svg { width:21px;height:21px; }
    .ms365-stat-body { min-width:0; }
    .ms365-stat-body span { display:block;margin-bottom:3px;color:#55465f;font-size:12px;font-weight:750; }
    .ms365-stat-body strong { display:block;color:#24152f;font-size:25px;line-height:1.1; }
    .ms365-table-wrap { overflow:hidden; }
    .ms365-table { width:100%; table-layout:fixed; }
    .ms365-table th, .ms365-table td { padding:16px 18px; white-space:normal; overflow-wrap:anywhere; }

    @media(max-width:800px) {
        .ms365-stats { grid-template-columns:1fr; }
        .ms365-table-wrap { overflow-x:auto; }
        .ms365-table { min-width:780px; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>MS365 Student Account Registry</h2>
        <p>Manage the college-issued Microsoft 365 student email registry.</p>
    </div>
</div>

<div class="ms365-stats" aria-label="MS365 account summary">
    <article class="ms365-stat-card" style="--stat-color:#450693">
        <span class="ms365-stat-icon"><x-icon name="at-sign" /></span>
        <div class="ms365-stat-body"><span>Total imported</span><strong>{{ number_format($statistics['total']) }}</strong></div>
    </article>
    <article class="ms365-stat-card" style="--stat-color:#15803d">
        <span class="ms365-stat-icon"><x-icon name="check" /></span>
        <div class="ms365-stat-body"><span>Eligible</span><strong>{{ number_format($statistics['eligible']) }}</strong></div>
    </article>
    <article class="ms365-stat-card" style="--stat-color:#b42318">
        <span class="ms365-stat-icon"><x-icon name="warning" /></span>
        <div class="ms365-stat-body"><span>Blocked</span><strong>{{ number_format($statistics['blocked']) }}</strong></div>
    </article>
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
    </form>
</div>

<div class="card">
    <form class="filters" method="GET" style="grid-template-columns:1fr auto">
        <input class="input" type="search" name="search" value="{{ $search }}" placeholder="Search student number, name, or MS365 email">
        <button class="button" type="submit">Search</button>
    </form>
    <div class="table-wrap ms365-table-wrap">
        <table class="ms365-table">
            <colgroup>
                <col style="width:16.6667%">
                <col style="width:16.6667%">
                <col style="width:16.6667%">
                <col style="width:16.6667%">
                <col style="width:16.6666%">
                <col style="width:16.6666%">
            </colgroup>
            <thead><tr><th>Name</th><th>Student Number</th><th>MS365 email</th><th>License</th><th>Status</th><th>Last imported</th></tr></thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td><strong>{{ $account->display_name ?: '—' }}</strong></td>
                        <td>{{ $account->student_number ?: '—' }}</td>
                        <td>{{ $account->email }}</td>
                        <td>{{ $account->license ?: 'Not listed' }}</td>
                        <td>
                            @if($account->soft_deleted_at)<span class="badge" style="color:#b42318">Soft deleted</span>
                            @elseif($account->is_blocked)<span class="badge" style="color:#b42318">Blocked</span>
                            @else<span class="badge">Eligible</span>
                            @endif
                        </td>
                        <td>{{ $account->last_imported_at?->format('M d, Y g:i A') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No MS365 accounts have been imported.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$accounts" label="MS365 account pages" />
</div>
@endsection
