import QRCode from 'qrcode';

const modal = document.getElementById('roomQrModal');

if (modal) {
    const dialog = modal.querySelector('.admin-profile-dialog');
    const closeButton = document.getElementById('closeRoomQr');
    const image = document.getElementById('roomQrImage');
    const title = document.getElementById('roomQrTitle');
    const department = document.getElementById('roomQrDepartment');
    const payloadLabel = document.getElementById('roomQrPayload');
    const status = document.getElementById('roomQrStatus');
    const downloadButton = document.getElementById('downloadRoomQr');
    const printButton = document.getElementById('printRoomQr');
    let activeTrigger = null;

    const safeFileName = (value) => String(value)
        .trim()
        .replace(/[^a-z0-9]+/gi, '-')
        .replace(/^-|-$/g, '')
        .toLowerCase() || 'room';

    function openModal(trigger) {
        activeTrigger = trigger;
        const roomId = trigger.dataset.roomId;
        const roomName = trigger.dataset.roomName;
        const roomCourse = trigger.dataset.roomCourse;
        const payload = `ROOM:${roomId}`;

        title.textContent = roomName;
        department.textContent = `${roomCourse} Department`;
        payloadLabel.textContent = payload;
        image.removeAttribute('src');
        image.alt = `QR code for ${roomName}`;
        status.textContent = 'Generating QR code…';
        downloadButton.removeAttribute('href');
        downloadButton.setAttribute('aria-disabled', 'true');
        printButton.disabled = true;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        closeButton.focus();

        QRCode.toDataURL(payload, {
            errorCorrectionLevel: 'H',
            margin: 3,
            width: 480,
            color: { dark: '#111827', light: '#ffffff' },
        }).then((dataUrl) => {
            image.src = dataUrl;
            downloadButton.href = dataUrl;
            downloadButton.download = `${safeFileName(roomCourse)}-${safeFileName(roomName)}-qr.png`;
            downloadButton.removeAttribute('aria-disabled');
            printButton.disabled = false;
            status.textContent = 'Ready to scan, save, or print.';
        }).catch(() => {
            status.textContent = 'The QR code could not be generated. Please refresh the page and try again.';
            status.classList.add('error');
        });
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        status.classList.remove('error');
        activeTrigger?.focus();
        activeTrigger = null;
    }

    document.querySelectorAll('.room-qr-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger));
    });

    closeButton.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    dialog.addEventListener('click', (event) => event.stopPropagation());
    downloadButton.addEventListener('click', (event) => {
        if (! downloadButton.hasAttribute('href')) {
            event.preventDefault();
        }
    });
    printButton.addEventListener('click', () => window.print());
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ! modal.hidden) {
            closeModal();
        }
    });
}
