@extends('layouts.dean')

@section('title', 'Sections')
@section('page-title', 'Sections')

@section('content')
<div class="page-header">
    <div><h2>{{ $course }} Sections</h2><p>Organize sections by year level and academic period.</p></div>
    <div class="actions">
        <button id="openSectionImport" class="button button-secondary" type="button">Import Sections</button>
        <a class="button button-secondary" href="{{ route('dean.sections.import-template') }}">Download CSV Template</a>
        <button id="openSectionCreate" class="button" type="button">Add Section</button>
    </div>
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

    <div style="display:flex;justify-content:flex-end;margin:14px 0">
        <button type="button" class="button button-danger delete-confirmation-trigger"
            data-delete-url="{{ route('dean.sections.destroy-all') }}"
            data-delete-name="All {{ $course }} sections"
            data-delete-title="Delete All Sections?"
            data-delete-message="This permanently removes every {{ $course }} section and every schedule linked to those sections. This cannot be undone."
            data-delete-confirm-label="Delete All Sections">Delete All Sections</button>
    </div>

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
                            <a class="button button-secondary" href="{{ route('dean.sections.index', array_merge(request()->query(), ['edit' => $section->id])) }}#sectionCreateModal">Edit</a>
                            <button type="button" class="button button-danger delete-confirmation-trigger" data-delete-url="{{ route('dean.sections.destroy', $section) }}" data-delete-name="{{ $section->name }}">Delete</button>
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

@push('portal-profile-overlay')
<div id="sectionCreateModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="sectionCreateTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="sectionCreateTitle">{{ $editingSection ? 'Edit Section' : 'Add Section' }}</h2>
                <p>{{ $editingSection ? "Update this {$course} section." : "Create a new {$course} section for the selected year level and academic year." }}</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-section-create aria-label="Close section form">&times;</button>
        </header>

        <form id="sectionCreateForm" method="POST" action="{{ $editingSection ? route('dean.sections.update', $editingSection) : route('dean.sections.store') }}">
            @csrf
            @if($editingSection) @method('PUT') @endif
            <input type="hidden" name="section_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field">
                    <label for="section_name">Section name</label>
                    <input id="section_name" class="input" name="name" value="{{ old('name', $editingSection?->name) }}" placeholder="Year 1-A" required>
                    @error('name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="section_year_level">Year level</label>
                    <select id="section_year_level" class="input" name="year_level" required>
                        @for($i=1;$i<=4;$i++)
                            <option value="{{ $i }}" @selected((int) old('year_level', $editingSection?->year_level ?? 1) === $i)>Year {{ $i }}</option>
                        @endfor
                    </select>
                    @error('year_level')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <label for="section_academic_year">Academic year</label>
                    <input id="section_academic_year" class="input" name="academic_year" value="{{ old('academic_year', $editingSection?->academic_year) }}" placeholder="2026-2027" inputmode="numeric" required>
                    @error('academic_year')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-section-create>Cancel</button>
                <button class="button" type="submit">{{ $editingSection ? 'Save Changes' : 'Add Section' }}</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('portal-profile-overlay')
<div id="sectionImportModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="sectionImportTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="sectionImportTitle">Import Sections</h2>
                <p>Bulk-create {{ $course }} sections from a CSV file.</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-section-import aria-label="Close section import">&times;</button>
        </header>

        <form method="POST" action="{{ route('dean.sections.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="section_import_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field full">
                    <label for="section_csv_file">CSV file</label>
                    <input id="section_csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
                    @error('csv_file')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <p style="margin:0;color:var(--muted);font-size:12px;line-height:1.6">
                        Required columns: <strong>name</strong>, <strong>year_level</strong> (1–4), <strong>academic_year</strong> (format YYYY-YYYY).
                        <a href="{{ route('dean.sections.import-template') }}">Download a CSV template</a>.
                    </p>
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-section-import>Cancel</button>
                <button class="button" type="submit">Import Sections</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@include('dean.partials.delete-confirmation', [
    'title' => 'Delete Section?',
    'message' => 'This section and all of its existing class schedules will be permanently deleted.',
    'confirmLabel' => 'Delete Section',
])
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('sectionCreateModal');
        const openButton = document.getElementById('openSectionCreate');
        const closeButtons = [...modal.querySelectorAll('[data-close-section-create]')];
        const firstInput = document.getElementById('section_name');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            @if($editingSection)
                window.location = '{{ route('dean.sections.index', request()->except('edit')) }}';
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

        @if($errors->hasAny(['name', 'year_level', 'academic_year']) || $editingSection)
            openModal();
        @endif
    })();

    (() => {
        const modal = document.getElementById('sectionImportModal');
        const openButton = document.getElementById('openSectionImport');
        const closeButtons = [...modal.querySelectorAll('[data-close-section-import]')];
        const firstInput = document.getElementById('section_csv_file');

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
