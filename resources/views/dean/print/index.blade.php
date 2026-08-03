@extends('layouts.dean')
@section('title','Print Reports') @section('page-title','Printable Reports')
@push('styles')
<style>
    .report-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
    .report-card{min-height:230px;display:flex;flex-direction:column;align-items:flex-start;padding:22px;background:linear-gradient(145deg,rgba(255,255,255,.82),rgba(255,255,255,.64));border:1px solid rgba(255,255,255,.8);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
    .report-card:hover{transform:translateY(-4px);border-color:rgba(69,6,147,.28);box-shadow:0 20px 42px rgba(40,8,68,.14)}
    .report-symbol{width:46px;height:46px;display:grid;place-items:center;margin-bottom:20px;color:#fff;background:#450693;border:1px solid rgba(255,255,255,.65);border-radius:13px;box-shadow:0 10px 24px rgba(69,6,147,.18);font-size:13px;font-weight:850;letter-spacing:.3px}
    .report-card h3{margin:0 0 8px;color:var(--navy);font-size:17px;line-height:1.3}
    .report-card p{margin:0 0 22px;color:var(--muted);font-size:12px;line-height:1.6}
    .report-card .button{width:100%;margin-top:auto}
    .report-card .button span{margin-left:auto;font-size:16px}
    @media(max-width:900px){.report-grid{grid-template-columns:1fr}.report-card{min-height:190px}}
</style>
@endpush
@section('content')
<div class="page-header"><div><h2>{{ $course }} Reports</h2><p>Select a report to open its official print-ready document.</p></div></div>
<div class="report-grid">
@foreach([
    'teaching-loads'=>['TL','Summary of Teaching Loads','Review department teaching assignments and load totals.'],
    'instructor-workload'=>['FL','Individual Faculty Load Sheet','Open the detailed teaching load for every department instructor.'],
    'class-schedules'=>['CS','Class Schedules','View complete class schedules organized into separate section sheets.']
] as $type=>$details)
    <article class="card report-card">
        <span class="report-symbol" aria-hidden="true">{{ $details[0] }}</span>
        <h3>{{ $details[1] }}</h3>
        <p>{{ $details[2] }}</p>
        <a class="button" target="_blank" href="{{ route('dean.print.report',$type) }}">Open Print View <span aria-hidden="true">&rarr;</span></a>
    </article>
@endforeach
</div>
@endsection
