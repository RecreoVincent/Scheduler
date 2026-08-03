@push('styles')
<style>
    .delete-confirmation-modal[hidden] { display:none; }
    .delete-confirmation-modal { position:fixed; z-index:1900; inset:0; display:grid; place-items:center; padding:20px; background:rgba(15,23,42,.58); backdrop-filter:blur(3px); }
    .delete-confirmation-dialog { width:min(450px,100%); padding:30px; text-align:center; background:white; border-radius:18px; box-shadow:0 25px 65px rgba(15,23,42,.28); }
    .delete-confirmation-icon { width:58px; height:58px; display:grid; place-items:center; margin:0 auto 17px; font-size:27px; font-weight:700; color:#dc2626; background:#fee2e2; border-radius:50%; }
    .delete-confirmation-dialog h2 { margin-bottom:9px; color:var(--navy); }
    .delete-confirmation-dialog p { color:#64748b; line-height:1.6; }
    .delete-confirmation-name { margin-top:6px; font-weight:700; color:#334155 !important; }
    .delete-confirmation-actions { display:flex; justify-content:center; gap:10px; margin-top:23px; }
</style>
@endpush

<div id="deleteConfirmationModal" class="delete-confirmation-modal" hidden>
    <section class="delete-confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmationTitle" aria-describedby="deleteConfirmationMessage">
        <div class="delete-confirmation-icon" aria-hidden="true">!</div>
        <h2 id="deleteConfirmationTitle">{{ $title }}</h2>
        <p id="deleteConfirmationMessage">{{ $message }}</p>
        <p id="deleteConfirmationName" class="delete-confirmation-name"></p>

        <form id="deleteConfirmationForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="delete-confirmation-actions">
                <button type="button" id="cancelDeleteConfirmation" class="button button-secondary">Cancel</button>
                <button type="submit" id="confirmDeletion" class="button button-danger">{{ $confirmLabel }}</button>
            </div>
        </form>
    </section>
</div>

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('deleteConfirmationModal');
        const form = document.getElementById('deleteConfirmationForm');
        const itemName = document.getElementById('deleteConfirmationName');
        const title = document.getElementById('deleteConfirmationTitle');
        const message = document.getElementById('deleteConfirmationMessage');
        const cancelButton = document.getElementById('cancelDeleteConfirmation');
        const confirmButton = document.getElementById('confirmDeletion');
        const triggers = [...document.querySelectorAll('.delete-confirmation-trigger')];
        const defaultTitle = title.textContent;
        const defaultMessage = message.textContent;
        const defaultConfirmLabel = confirmButton.textContent;
        let activeTrigger = null;

        function openModal(trigger) {
            activeTrigger = trigger;
            form.action = trigger.dataset.deleteUrl;
            itemName.textContent = trigger.dataset.deleteName;
            title.textContent = trigger.dataset.deleteTitle || defaultTitle;
            message.textContent = trigger.dataset.deleteMessage || defaultMessage;
            confirmButton.textContent = trigger.dataset.deleteConfirmLabel || defaultConfirmLabel;
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
        form.addEventListener('submit', () => {
            confirmButton.disabled = true;
            confirmButton.textContent = 'Deleting...';
        });
    })();
</script>
@endpush
