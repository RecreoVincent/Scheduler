@extends('layouts.dean')

@section('title', 'Sections')
@section('page-title', 'Sections')

@push('styles')
<style>
    .section-delete-modal[hidden] { display:none; }
    .section-delete-modal { position:fixed; z-index:1900; inset:0; display:grid; place-items:center; padding:20px; background:rgba(15,23,42,.58); backdrop-filter:blur(3px); }
    .section-delete-dialog { width:min(450px,100%); padding:30px; text-align:center; background:white; border-radius:18px; box-shadow:0 25px 65px rgba(15,23,42,.28); }
    .section-delete-icon { width:58px; height:58px; display:grid; place-items:center; margin:0 auto 17px; font-size:27px; font-weight:700; color:#dc2626; background:#fee2e2; border-radius:50%; }
    .section-delete-dialog h2 { margin-bottom:9px; color:var(--navy); }
    .section-delete-dialog p { color:#64748b; line-height:1.6; }
    .section-delete-name { margin-top:6px; font-weight:700; color:#334155; }
    .section-delete-actions { display:flex; justify-content:center; gap:10px; margin-top:23px; }
    .section-pagination { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:6px; margin-top:20px; }
    .section-page-link { min-width:38px; height:38px; display:inline-flex; justify-content:center; align-items:center; padding:0 11px; font-size:13px; font-weight:700; color:#475569; background:white; border:1px solid #cbd5e1; border-radius:9px; transition:.2s; }
    .section-page-link:hover { color:var(--primary); background:#f4ebfa; border-color:#cba9e1; }
    .section-page-link.active { color:white; background:var(--primary); border-color:var(--primary); }
    .section-page-link.disabled { color:#94a3b8; background:#f8fafc; cursor:not-allowed; }
    .section-page-arrow { width:17px; height:17px; display:block; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div><h2>{{ $course }} Sections</h2><p>Organize sections by year level and academic period.</p></div>
    <a class="button" href="{{ route('dean.sections.create') }}">Add Section</a>
</div>

<div class="card">
    <form class="filters" style="grid-template-columns:1fr 1fr;" method="GET" data-auto-filter>
        <select class="input" name="year_level">
            <option value="">All year levels</option>
            @for ($i = 1; $i <= 4; $i++)
                <option value="{{ $i }}" @selected(request('year_level') == $i)>Year {{ $i }}</option>
            @endfor
        </select>
        <input class="input" name="academic_year" value="{{ request('academic_year') }}" placeholder="2026-2027">
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Section</th><th>Year</th><th>Academic Year</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse ($sections as $section)
                <tr>
                    <td>{{ $section->name }}</td>
                    <td>Year {{ $section->year_level }}</td>
                    <td>{{ $section->academic_year }}</td>
                    <td>
                        <div class="actions">
                            <a class="button button-secondary" href="{{ route('dean.sections.edit', $section) }}">Edit</a>
                            <button type="button" class="button button-danger section-delete-trigger" data-delete-url="{{ route('dean.sections.destroy', $section) }}" data-section-name="{{ $section->name }}">Delete</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No sections found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$sections" label="Section pages" />
</div>

<div id="sectionDeleteModal" class="section-delete-modal" hidden>
    <section class="section-delete-dialog" role="dialog" aria-modal="true" aria-labelledby="sectionDeleteTitle" aria-describedby="sectionDeleteMessage">
        <div class="section-delete-icon" aria-hidden="true">!</div>
        <h2 id="sectionDeleteTitle">Delete Section?</h2>
        <p id="sectionDeleteMessage">This section and all of its existing class schedules will be permanently deleted.</p>
        <p id="sectionDeleteName" class="section-delete-name"></p>

        <form id="sectionDeleteForm" method="POST">
            @csrf @method('DELETE')
            <div class="section-delete-actions">
                <button type="button" id="cancelSectionDelete" class="button button-secondary">Cancel</button>
                <button type="submit" id="confirmSectionDelete" class="button button-danger">Delete Section</button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('sectionDeleteModal');
        const form = document.getElementById('sectionDeleteForm');
        const sectionName = document.getElementById('sectionDeleteName');
        const cancelButton = document.getElementById('cancelSectionDelete');
        const confirmButton = document.getElementById('confirmSectionDelete');
        const triggers = [...document.querySelectorAll('.section-delete-trigger')];
        let activeTrigger = null;

        function openModal(trigger) {
            activeTrigger = trigger;
            form.action = trigger.dataset.deleteUrl;
            sectionName.textContent = trigger.dataset.sectionName;
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

        triggers.forEach(trigger => trigger.addEventListener('click', () => openModal(trigger)));
        cancelButton.addEventListener('click', closeModal);
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
        form.addEventListener('submit', () => { confirmButton.disabled = true; confirmButton.textContent = 'Deleting...'; });
    })();
</script>
@endpush
