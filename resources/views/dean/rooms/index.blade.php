@extends('layouts.dean')
@section('title','Rooms') @section('page-title','Rooms and Usage')
@push('styles')<style>.room-card{margin-bottom:18px}.room-head{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:14px}.room-head .actions{display:flex;flex-wrap:wrap;gap:8px}.usage{padding:10px 0;border-top:1px solid #e2e8f0;font-size:13px}.usage strong{display:inline-block;min-width:120px}.room-qr-modal[hidden]{display:none}.room-qr-modal{position:fixed;z-index:2100;inset:0;display:grid;place-items:center;padding:20px;background:rgba(24,9,39,.72);backdrop-filter:blur(5px)}.room-qr-dialog{width:min(500px,100%);max-height:calc(100vh - 40px);overflow-y:auto;padding:30px;text-align:center;background:white;border-top:5px solid var(--gold);border-radius:20px;box-shadow:0 30px 90px rgba(23,8,40,.35)}.room-qr-head{display:flex;justify-content:flex-end}.room-qr-close{width:36px;height:36px;display:grid;place-items:center;color:var(--muted);background:#f5f2f8;border:0;border-radius:9px;font-size:22px;cursor:pointer}.room-qr-label{margin-top:-12px;color:var(--gold-dark);font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase}.room-qr-dialog h2{margin:7px 0 4px;color:var(--primary)}.room-qr-department{color:var(--muted);font-size:13px}.room-qr-image-wrap{width:min(330px,100%);margin:22px auto 15px;padding:14px;background:white;border:1px solid var(--border);border-radius:16px}.room-qr-image{display:block;width:100%;aspect-ratio:1;object-fit:contain}.room-qr-payload{display:inline-block;padding:7px 10px;color:var(--primary);background:#f6f0fb;border-radius:8px;font:700 12px ui-monospace,monospace}.room-qr-status{min-height:20px;margin:10px 0;color:var(--muted);font-size:12px}.room-qr-status.error{color:var(--danger)}.room-qr-instruction{margin:0 auto 18px;max-width:340px;color:var(--muted);font-size:13px;line-height:1.55}.room-qr-actions{display:flex;justify-content:center;gap:10px}.room-qr-actions [aria-disabled=true]{opacity:.5;pointer-events:none}@media(max-width:700px){.room-head{align-items:flex-start;flex-direction:column}.room-head .actions{width:100%}.room-head .actions .button{flex:1}.room-qr-dialog{padding:22px}}@media print{body *{visibility:hidden!important}.room-qr-modal,.room-qr-modal *{visibility:visible!important}.room-qr-modal{position:absolute;inset:0;display:block;padding:0;background:white}.room-qr-dialog{width:100%;max-height:none;margin:0;padding:20mm;border:0;box-shadow:none}.room-qr-close,.room-qr-actions,.room-qr-status{display:none!important}.room-qr-image-wrap{width:95mm;border:0}.room-qr-payload{font-size:14px}}</style>@endpush
@section('content')
<div class="page-header"><div><h2>{{ $course }} Rooms</h2><p>Manage department rooms and view their scheduled usage.</p></div><a class="button" href="{{ route('dean.rooms.create') }}">Add Room</a></div>
@forelse($rooms as $room)<div class="card room-card"><div class="room-head"><div><h3>{{ $room->name }} <span class="badge">{{ $room->room_type }}</span></h3></div><div class="actions"><button type="button" class="button button-secondary room-qr-trigger" data-room-id="{{ $room->id }}" data-room-name="{{ $room->name }}" data-room-course="{{ $room->course }}">Generate QR Code</button><a class="button button-secondary" href="{{ route('dean.rooms.edit',$room) }}">Edit</a><button type="button" class="button button-danger delete-confirmation-trigger" data-delete-url="{{ route('dean.rooms.destroy',$room) }}" data-delete-name="{{ $room->name }}">Delete</button></div></div>
@forelse($room->schedules as $schedule)<div class="usage"><strong>{{ $schedule->day }}</strong> {{ date('g:i A',strtotime($schedule->start_time)) }}–{{ date('g:i A',strtotime($schedule->end_time)) }} · {{ $schedule->section?->name }} · {{ $schedule->subject?->code }}</div>@empty<p style="color:#64748b">No scheduled room usage.</p>@endforelse</div>@empty<div class="card">No rooms added yet.</div>@endforelse
<x-pagination :paginator="$rooms" label="Room pages" />

<div id="roomQrModal" class="room-qr-modal" hidden>
    <section class="room-qr-dialog" role="dialog" aria-modal="true" aria-labelledby="roomQrTitle">
        <div class="room-qr-head"><button id="closeRoomQr" class="room-qr-close" type="button" aria-label="Close QR code generator">×</button></div>
        <p class="room-qr-label">Scheduler Room QR Code</p>
        <h2 id="roomQrTitle">Room</h2>
        <p id="roomQrDepartment" class="room-qr-department"></p>
        <div class="room-qr-image-wrap"><img id="roomQrImage" class="room-qr-image" alt=""></div>
        <code id="roomQrPayload" class="room-qr-payload"></code>
        <p id="roomQrStatus" class="room-qr-status" aria-live="polite"></p>
        <p class="room-qr-instruction">Post this code outside the room. Instructors and students can scan it to view the room’s current and next scheduled use.</p>
        <div class="room-qr-actions"><a id="downloadRoomQr" class="button button-secondary" aria-disabled="true">Save PNG</a><button id="printRoomQr" class="button" type="button" disabled>Print QR Code</button></div>
    </section>
</div>

@include('dean.partials.delete-confirmation', [
    'title' => 'Delete Room?',
    'message' => 'This room will be permanently deleted. Rooms with existing class schedules cannot be deleted.',
    'confirmLabel' => 'Delete Room',
])
@endsection
@push('scripts')
    @vite('resources/js/room-qr-generator.js')
@endpush
