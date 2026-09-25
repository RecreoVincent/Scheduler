@extends('layouts.dean')
@section('title','Rooms') @section('page-title','Rooms and Usage')
@push('styles')
<style>
    .room-list-card { padding:28px; }
    .room-card { margin-bottom:18px; }
    .room-head { display:flex; justify-content:space-between; align-items:center; gap:18px; margin-bottom:14px; }
    .room-head .actions { display:flex; flex-wrap:wrap; gap:8px; }
    .room-usage-table-wrap { overflow:hidden; border:1px solid rgba(69,6,147,.14); border-radius:10px; }
    .room-usage-table { width:100%; table-layout:fixed; }
    .room-usage-table th, .room-usage-table td { padding:16px 18px; white-space:normal; overflow-wrap:anywhere; }
    .room-usage-table td:first-child { font-weight:800; }
    .room-empty { padding:14px; color:#64748b; text-align:center; }
    .room-qr-image-wrap { width:min(330px,100%); margin:22px auto 15px; padding:14px; background:white; border:1px solid var(--border); border-radius:16px; }
    .room-qr-image { display:block; width:100%; aspect-ratio:1; object-fit:contain; }
    .room-qr-payload { display:inline-block; padding:7px 10px; color:var(--primary); background:#f6f0fb; border-radius:8px; font:700 12px ui-monospace,monospace; }
    .room-qr-status { min-height:20px; margin:10px 0; color:var(--muted); font-size:12px; }
    .room-qr-status.error { color:var(--danger); }
    .room-qr-instruction { margin:0 auto 18px; max-width:340px; color:var(--muted); font-size:13px; line-height:1.55; }
    .room-qr-actions { display:flex; justify-content:center; gap:10px; }
    .room-qr-actions [aria-disabled=true] { opacity:.5; pointer-events:none; }
    @media(max-width:700px) {
        .room-head { align-items:flex-start; flex-direction:column; }
        .room-head .actions { width:100%; }
        .room-head .actions .button { flex:1; }
        .room-usage-table-wrap { overflow-x:auto; }
        .room-usage-table { min-width:720px; }
    }
    @media print {
        body * { visibility:hidden !important; }
        #roomQrModal, #roomQrModal * { visibility:visible !important; }
        #roomQrModal { position:absolute; inset:0; display:block; padding:0; background:white; }
        #roomQrModal .admin-profile-dialog { width:100%; max-height:none; margin:0; padding:20mm; background:white; border:0; box-shadow:none; }
        #closeRoomQr, #roomQrModal .admin-profile-actions, #roomQrStatus { display:none !important; }
        .room-qr-image-wrap { width:95mm; border:0; }
        .room-qr-payload { font-size:14px; }
    }
</style>
@endpush
@section('content')
<div class="page-header"><div><h2>{{ $course }} Rooms</h2><p>Manage department rooms and view their scheduled usage.</p></div><div class="actions"><button id="openRoomImport" class="button button-secondary" type="button">Import Rooms</button><a class="button button-secondary" href="{{ route('dean.rooms.import-template') }}">Download CSV Template</a><button id="openRoomCreate" class="button" type="button">Add Room</button></div></div>

<div class="card">
    <form class="filters" style="grid-template-columns:1fr 1fr;" method="GET" data-auto-filter>
        <input class="input" name="search" value="{{ request('search') }}" placeholder="Search room name">
        <select class="input" name="room_type">
            <option value="">All room types</option>
            @foreach($roomTypes as $type)
                <option value="{{ $type }}" @selected(request('room_type') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </form>
</div>

<div style="display:flex;justify-content:flex-end;margin:14px 0">
    <button type="button" class="button button-danger delete-confirmation-trigger"
        data-delete-url="{{ route('dean.rooms.destroy-all') }}"
        data-delete-name="All {{ $course }} rooms"
        data-delete-title="Delete All Rooms?"
        data-delete-message="This permanently removes every {{ $course }} room. Existing schedules will remain, but their room assignment will become TBA. This cannot be undone."
        data-delete-confirm-label="Delete All Rooms">Delete All Rooms</button>
</div>

<section class="card room-list-card">
<x-pagination :paginator="$rooms" label="Room pages" mode="summary" />
@forelse($rooms as $room)
<div class="card room-card">
    <div class="room-head">
        <div><h3>{{ $room->name }} <span class="badge">{{ $room->room_type }}</span></h3></div>
        <div class="actions">
            <button type="button" class="button button-secondary room-qr-trigger" data-room-id="{{ $room->id }}" data-room-name="{{ $room->name }}" data-room-course="{{ $room->course }}">Generate QR Code</button>
            <a class="button button-secondary" href="{{ route('dean.rooms.index', array_merge(request()->query(), ['edit' => $room->id])) }}#roomCreateModal">Edit</a>
            <button type="button" class="button button-danger delete-confirmation-trigger" data-delete-url="{{ route('dean.rooms.destroy',$room) }}" data-delete-name="{{ $room->name }}">Delete</button>
        </div>
    </div>
    @if($room->schedules->isNotEmpty())
        <div class="room-usage-table-wrap">
            <table class="room-usage-table">
                <colgroup>
                    <col style="width:20%">
                    <col style="width:20%">
                    <col style="width:20%">
                    <col style="width:20%">
                    <col style="width:20%">
                </colgroup>
                <thead><tr><th>Days</th><th>Time</th><th>Section</th><th>Subject Code</th><th>Subject Name</th></tr></thead>
                <tbody>
                    @foreach($room->schedules as $schedule)
                        <tr>
                            <td>{{ $schedule->day }}</td>
                            <td>{{ date('g:i A', strtotime($schedule->start_time)) }}–{{ date('g:i A', strtotime($schedule->end_time)) }}</td>
                            <td>{{ $schedule->section?->name ?? '—' }}</td>
                            <td>{{ $schedule->subject?->code ?? '—' }}</td>
                            <td>{{ $schedule->subject?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="room-empty">No scheduled room usage.</p>
    @endif
</div>
@empty
<div class="card">{{ request()->hasAny(['search', 'room_type']) ? 'No rooms match the current filters.' : 'No rooms added yet.' }}</div>
@endforelse
<x-pagination :paginator="$rooms" label="Room pages" mode="navigation" />
</section>
@push('portal-profile-overlay')
<div id="roomQrModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="roomQrTitle">
        <header class="admin-profile-header">
            <div><h2 id="roomQrTitle">Room</h2><p id="roomQrDepartment">Scheduler Room QR Code</p></div>
            <button id="closeRoomQr" class="admin-profile-close" type="button" aria-label="Close QR code generator">&times;</button>
        </header>
        <div style="text-align:center">
        <div class="room-qr-image-wrap"><img id="roomQrImage" class="room-qr-image" alt=""></div>
        <code id="roomQrPayload" class="room-qr-payload"></code>
        <p id="roomQrStatus" class="room-qr-status" aria-live="polite"></p>
        <p class="room-qr-instruction">Post this code outside the room. Instructors and students can scan it to view the room’s current and next scheduled use.</p>
        </div>
        <footer class="admin-profile-actions"><a id="downloadRoomQr" class="button button-secondary" aria-disabled="true">Save PNG</a><button id="printRoomQr" class="button" type="button" disabled>Print QR Code</button></footer>
    </section>
</div>
@endpush

@include('dean.partials.delete-confirmation', [
    'title' => 'Delete Room?',
    'message' => 'This room will be permanently deleted. Its active and archived schedules will be kept, but their room assignment will become TBA.',
    'confirmLabel' => 'Delete Room',
])
@endsection

@push('portal-profile-overlay')
<div id="roomCreateModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="roomCreateTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="roomCreateTitle">{{ $editingRoom ? 'Edit Room' : 'Add Room' }}</h2>
                <p>{{ $editingRoom ? "Update this {$course} room." : "Create a new room for the {$course} department." }}</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-room-create aria-label="Close room form">&times;</button>
        </header>

        <form id="roomCreateForm" method="POST" action="{{ $editingRoom ? route('dean.rooms.update', $editingRoom) : route('dean.rooms.store') }}">
            @csrf
            @if($editingRoom) @method('PUT') @endif
            <input type="hidden" name="room_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field">
                    <label for="room_name">Room name</label>
                    <input id="room_name" class="input" name="name" value="{{ old('name', $editingRoom?->name) }}" placeholder="ITE 101" required>
                    @error('name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="room_type">Room type</label>
                    <select id="room_type" class="input" name="room_type" required>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type }}" @selected(old('room_type', $editingRoom?->room_type ?? 'Lecture') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('room_type')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-room-create>Cancel</button>
                <button class="button" type="submit">{{ $editingRoom ? 'Save Changes' : 'Add Room' }}</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('portal-profile-overlay')
<div id="roomImportModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="roomImportTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="roomImportTitle">Import Rooms</h2>
                <p>Bulk-create {{ $course }} rooms from a CSV file.</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-room-import aria-label="Close room import">&times;</button>
        </header>

        <form method="POST" action="{{ route('dean.rooms.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="room_import_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field full">
                    <label for="room_csv_file">CSV file</label>
                    <input id="room_csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
                    @error('csv_file')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <p style="margin:0;color:var(--muted);font-size:12px;line-height:1.6">
                        Required columns: <strong>name</strong>, <strong>room_type</strong> ({{ implode(', ', $roomTypes) }}).
                        <a href="{{ route('dean.rooms.import-template') }}">Download a CSV template</a>.
                    </p>
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-room-import>Cancel</button>
                <button class="button" type="submit">Import Rooms</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('roomCreateModal');
        const openButton = document.getElementById('openRoomCreate');
        const closeButtons = [...modal.querySelectorAll('[data-close-room-create]')];
        const firstInput = document.getElementById('room_name');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            @if($editingRoom)
                window.location = '{{ route('dean.rooms.index', request()->except('edit')) }}';
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

        @if($errors->hasAny(['name', 'room_type']) || $editingRoom)
            openModal();
        @endif
    })();

    (() => {
        const modal = document.getElementById('roomImportModal');
        const openButton = document.getElementById('openRoomImport');
        const closeButtons = [...modal.querySelectorAll('[data-close-room-import]')];
        const firstInput = document.getElementById('room_csv_file');

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
@vite('resources/js/room-qr-generator.js')
@endpush
