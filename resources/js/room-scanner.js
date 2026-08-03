import jsQR from 'jsqr';

const scanner = document.querySelector('[data-room-scanner]');

if (scanner) {
    const video = document.getElementById('scannerVideo');
    const placeholder = document.getElementById('cameraPlaceholder');
    const frame = document.getElementById('scanFrame');
    const startButton = document.getElementById('startScanner');
    const stopButton = document.getElementById('stopScanner');
    const roomSelect = document.getElementById('manualRoom');
    const checkButton = document.getElementById('checkRoom');
    const panel = document.getElementById('roomStatus');
    const feedback = document.getElementById('scannerFeedback');
    const endpoint = scanner.dataset.statusEndpoint;
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });

    let stream = null;
    let detector = null;
    let scanning = false;
    let scanPending = false;
    let animationFrame = null;
    let lastScanAt = 0;

    const escapeHtml = (value) => {
        const node = document.createElement('div');
        node.textContent = String(value ?? '');

        return node.innerHTML;
    };

    const normalize = (value) => String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[·|]/g, ' ')
        .replace(/\s+/g, ' ');

    function roomId(value) {
        const text = String(value ?? '').trim();

        if (! text) {
            return null;
        }

        try {
            const payload = JSON.parse(text);
            const id = payload.room_id ?? payload.roomId ?? payload.id;

            if (/^\d+$/.test(String(id ?? ''))) {
                return String(id);
            }
        } catch (_) {
            // A normal room QR value is not JSON.
        }

        const tagged = text.match(/^(?:ROOM|ROOM_ID)\s*[:#=-]\s*(\d+)$/i);
        if (tagged) {
            return tagged[1];
        }

        try {
            const url = new URL(text, window.location.origin);
            const match = url.pathname.match(/(?:scanner\/rooms|rooms?|room)\/(\d+)(?:\/|$)/i);

            if (match) {
                return match[1];
            }
        } catch (_) {
            // The QR value is not a URL, so continue with text matching.
        }

        if (/^\d+$/.test(text)) {
            return text;
        }

        const roomText = normalize(text.replace(/^ROOM\s*[:#=-]\s*/i, ''));
        const matchingOption = [...roomSelect.options].find((option) => {
            if (! option.value) {
                return false;
            }

            const roomName = normalize(option.dataset.roomName);
            const courseAndRoom = normalize(`${option.dataset.roomCourse} ${option.dataset.roomName}`);

            return roomText === roomName || roomText === courseAndRoom || roomText === normalize(option.textContent);
        });

        return matchingOption?.value ?? null;
    }

    function details(title, item) {
        return `<div class="usage-details"><strong>${title}</strong><p>${escapeHtml(item.subject || 'Subject unavailable')}</p><p>Section: ${escapeHtml(item.section || 'Unassigned')}</p><p>Instructor: ${escapeHtml(item.instructor || 'Unassigned')}</p><p>Time: ${escapeHtml(item.time)}</p></div>`;
    }

    function setFeedback(message, type = '') {
        feedback.textContent = message;
        feedback.className = `scanner-feedback ${type}`.trim();
    }

    function stopCamera({ resetMessage = true } = {}) {
        scanning = false;
        scanPending = false;

        if (animationFrame !== null) {
            cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }

        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        video.pause();
        video.srcObject = null;
        video.hidden = true;
        placeholder.hidden = false;
        frame.hidden = true;
        startButton.disabled = false;
        stopButton.disabled = true;

        if (resetMessage) {
            placeholder.textContent = 'Select “Start Camera” and point your device at a room QR code.';
            setFeedback('Camera is off.');
        }
    }

    async function loadStatus(id) {
        panel.innerHTML = '<h3 style="color:var(--navy);margin-bottom:10px">Checking room…</h3>';

        try {
            const url = endpoint.replace('__ROOM__', encodeURIComponent(id));
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (! response.ok) {
                throw new Error('Room not found');
            }

            const data = await response.json();
            const current = data.current;
            const next = data.next;

            panel.innerHTML = `<h3 style="color:var(--navy)">${escapeHtml(data.room.name)}</h3><p class="scanner-message">${escapeHtml(data.room.course)} Department · Checked ${escapeHtml(data.checked_at)}</p><span class="room-state ${data.in_use ? 'occupied' : 'available'}">${data.in_use ? 'Currently In Use' : 'Currently Available'}</span>${current ? details('Current Class', current) : '<p class="scanner-message">No class is using this room right now.</p>'}${next ? details('Next Class Today', next) : '<p class="scanner-message" style="margin-top:12px">No later class is scheduled today.</p>'}`;
            stopCamera({ resetMessage: false });
            placeholder.textContent = 'Room QR code scanned successfully.';
            setFeedback('Room detected successfully.', 'success');
        } catch (_) {
            panel.innerHTML = '<h3 style="color:#b91c1c;margin-bottom:10px">Unable to check room</h3><p class="scanner-message">The QR code does not match a room in the system.</p>';
            setFeedback('No matching room was found. Try scanning again or select the room manually.', 'error');
        }
    }

    async function decodedValue() {
        if (video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA || ! video.videoWidth || ! context) {
            return null;
        }

        if (detector) {
            try {
                const codes = await detector.detect(video);
                if (codes.length) {
                    return codes[0].rawValue;
                }
            } catch (_) {
                detector = null;
            }
        }

        const scale = Math.min(1, 1280 / video.videoWidth);
        canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
        canvas.height = Math.max(1, Math.round(video.videoHeight * scale));
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const image = context.getImageData(0, 0, canvas.width, canvas.height);
        const result = jsQR(image.data, image.width, image.height, { inversionAttempts: 'attemptBoth' });

        return result?.data ?? null;
    }

    async function scan(timestamp = 0) {
        if (! scanning) {
            return;
        }

        if (! scanPending && timestamp - lastScanAt >= 140) {
            scanPending = true;
            lastScanAt = timestamp;

            try {
                const value = await decodedValue();

                if (value) {
                    const id = roomId(value);

                    if (id) {
                        setFeedback('QR code detected. Checking room…');
                        await loadStatus(id);
                        return;
                    }

                    setFeedback('QR code detected, but it is not linked to a room.', 'error');
                }
            } catch (_) {
                setFeedback('The camera image could not be read. Keep the QR code steady and try again.', 'error');
            } finally {
                scanPending = false;
            }
        }

        if (scanning) {
            animationFrame = requestAnimationFrame(scan);
        }
    }

    async function startCamera() {
        if (! navigator.mediaDevices?.getUserMedia) {
            placeholder.textContent = 'Camera access requires HTTPS or localhost. Open this system through a secure address, or use manual room lookup.';
            setFeedback('Camera access is not supported on this address.', 'error');
            return;
        }

        startButton.disabled = true;
        setFeedback('Requesting camera permission…');

        try {
            if ('BarcodeDetector' in window) {
                try {
                    detector = new BarcodeDetector({ formats: ['qr_code'] });
                } catch (_) {
                    detector = null;
                }
            }

            stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            });

            video.srcObject = stream;
            video.muted = true;
            video.hidden = false;
            placeholder.hidden = true;
            frame.hidden = false;
            await video.play();

            scanning = true;
            stopButton.disabled = false;
            setFeedback('Camera active. Hold a room QR code inside the frame.', 'success');
            animationFrame = requestAnimationFrame(scan);
        } catch (error) {
            stopCamera({ resetMessage: false });

            if (error?.name === 'NotAllowedError') {
                placeholder.textContent = 'Camera permission was denied. Allow camera access in your browser settings, then try again.';
            } else if (error?.name === 'NotFoundError') {
                placeholder.textContent = 'No camera was found on this device. Use manual room lookup below.';
            } else {
                placeholder.textContent = 'The camera could not start. Use HTTPS or localhost and confirm that no other application is using the camera.';
            }

            setFeedback(placeholder.textContent, 'error');
        }
    }

    startButton.addEventListener('click', startCamera);
    stopButton.addEventListener('click', () => stopCamera());
    checkButton.addEventListener('click', () => {
        if (roomSelect.value) {
            loadStatus(roomSelect.value);
        } else {
            setFeedback('Choose a room before selecting Check.', 'error');
            roomSelect.focus();
        }
    });
    window.addEventListener('pagehide', () => stopCamera());
}
