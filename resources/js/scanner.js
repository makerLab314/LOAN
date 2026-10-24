import { postJson } from './http';

/**
 * Kamera-Scanner für QR-Codes und Code-128-Barcodes.
 * Nutzt die native BarcodeDetector-API, sonst ZXing (wird nachgeladen).
 * Ohne Kamera bleibt die manuelle ID-Eingabe.
 */
// Statusfarben (für Tailwind sichtbar): text-muted text-ok text-warn text-danger
export function mountScanner() {
    const root = document.querySelector('[data-scanner]');
    if (!root) return;

    const video = root.querySelector('video');
    const startButton = root.querySelector('[data-scan-start]');
    const stopButton = root.querySelector('[data-scan-stop]');
    const status = root.querySelector('[data-scan-status]');
    const lookupUrl = root.dataset.lookup;

    let stream = null;
    let stopZxing = null;
    let busy = false;
    let lastCode = '';

    const setStatus = (text, tone = 'muted') => {
        status.textContent = text;
        status.className = `text-sm text-${tone}`;
    };

    const stop = () => {
        stopZxing?.();
        stopZxing = null;
        stream?.getTracks().forEach((t) => t.stop());
        stream = null;
        video.srcObject = null;
        root.classList.remove('scanning');
        startButton.hidden = false;
        stopButton.hidden = true;
    };

    const handle = async (code) => {
        code = (code || '').trim();
        if (!code || busy || code === lastCode) return;
        busy = true;
        lastCode = code;
        setStatus(`Erkannt: ${code} – suche…`);
        if (navigator.vibrate) navigator.vibrate(60);

        try {
            const { status: http, body } = await postJson(lookupUrl, { code });
            if (http === 200 && body.found) {
                setStatus(`${body.label} · ${body.title}`, 'ok');
                stop();
                window.location.href = body.url;
                return;
            }
            setStatus(body.message || 'Nicht gefunden.', 'danger');
        } catch (e) {
            setStatus('Fehler beim Nachschlagen.', 'danger');
        }

        // Nach kurzer Pause denselben Code wieder zulassen
        setTimeout(() => { busy = false; lastCode = ''; }, 2500);
    };

    const start = async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Dieser Browser erlaubt keinen Kamerazugriff (HTTPS nötig). Bitte die ID unten eingeben.', 'warn');
            return;
        }

        try {
            setStatus('Kamera wird gestartet…');
            startButton.hidden = true;
            stopButton.hidden = false;

            if ('BarcodeDetector' in window) {
                const formats = await window.BarcodeDetector.getSupportedFormats();
                const wanted = ['qr_code', 'code_128'].filter((f) => formats.includes(f));
                if (wanted.length) {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                    video.srcObject = stream;
                    await video.play();
                    root.classList.add('scanning');
                    setStatus('Code in den Rahmen halten…');
                    const detector = new window.BarcodeDetector({ formats: wanted });
                    const loop = async () => {
                        if (!stream) return;
                        try {
                            const codes = await detector.detect(video);
                            if (codes.length) await handle(codes[0].rawValue);
                        } catch (e) { /* Frame übersprungen */ }
                        if (stream) requestAnimationFrame(loop);
                    };
                    loop();
                    return;
                }
            }

            const { BrowserMultiFormatReader } = await import('@zxing/browser');
            const { DecodeHintType, BarcodeFormat } = await import('@zxing/library');
            const hints = new Map([[DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.QR_CODE, BarcodeFormat.CODE_128]]]);
            const reader = new BrowserMultiFormatReader(hints);
            root.classList.add('scanning');
            setStatus('Code in den Rahmen halten…');
            const controls = await reader.decodeFromConstraints(
                { video: { facingMode: 'environment' }, audio: false },
                video,
                (result) => { if (result) handle(result.getText()); },
            );
            stopZxing = () => controls.stop();
        } catch (e) {
            stop();
            setStatus(await explain(e), 'warn');
        }
    };

    // Ursache möglichst genau benennen: Website-Recht, Betriebssystem, Proxy-Header oder belegte Kamera
    const explain = async (e) => {
        const name = e?.name || 'Error';
        const policy = document.permissionsPolicy || document.featurePolicy;
        if (policy?.allowsFeature && !policy.allowsFeature('camera')) {
            return `Der Server verbietet der Seite die Kamera (Header „Permissions-Policy“, oft vom Reverse Proxy gesetzt). Bitte dort „camera=(self)“ erlauben. (${name})`;
        }
        if (name === 'NotAllowedError' || name === 'SecurityError') {
            let state = '';
            try { state = (await navigator.permissions.query({ name: 'camera' })).state; } catch (_) { /* nicht unterstützt */ }
            if (state === 'granted' || /system/i.test(e?.message || '')) {
                return `Die Website darf die Kamera nutzen, aber das Betriebssystem blockiert sie. Windows: Einstellungen → Datenschutz und Sicherheit → Kamera → „Desktop-Apps den Zugriff auf die Kamera erlauben“. macOS: Systemeinstellungen → Datenschutz → Kamera → Browser. Danach den Browser neu starten. (${name})`;
            }
            return `Kamerazugriff wurde verweigert. Bitte über das Schloss-Symbol in der Adressleiste erlauben und die Seite neu laden. (${name})`;
        }
        if (name === 'NotReadableError' || name === 'AbortError') {
            return `Die Kamera ist belegt, z. B. von Teams, Zoom oder einem anderen Tab. Bitte dort schließen und erneut versuchen. (${name})`;
        }
        if (name === 'NotFoundError' || name === 'OverconstrainedError') {
            return `Keine Kamera gefunden. Bitte die ID unten eingeben. (${name})`;
        }
        return `Kamera konnte nicht gestartet werden. Bitte die ID unten eingeben. (${name})`;
    };

    startButton.addEventListener('click', start);
    stopButton.addEventListener('click', () => { stop(); setStatus('Scanner gestoppt.'); });
    window.addEventListener('pagehide', stop);

    // Hand-Scanner (Tastatur-Emulation) tippen in das fokussierte ID-Feld und senden Enter → normales Formular
}
