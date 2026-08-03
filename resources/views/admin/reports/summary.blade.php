@extends('layouts.admin')

@section('title', 'Account Summary')
@section('page-title', 'Account Summary')

@section('content')
<div class="page-header">
    <div><h2>Account Summary</h2><p>Overview of managed academic accounts.</p></div>
    <a class="button button-secondary" href="{{ route('admin.reports.index') }}">Back to Reports</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
    @foreach (['Deans' => $summary['deans'], 'Instructors' => $summary['instructors'], 'Students' => $summary['students']] as $label => $count)
        <div class="card"><p style="color:#64748b;">{{ $label }}</p><p style="font-size:32px;font-weight:700;margin-top:8px;">{{ $count }}</p></div>
    @endforeach
</div>
@endsection
