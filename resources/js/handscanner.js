/**
 * Hand-Scanner (USB/Bluetooth) tippen wie eine Tastatur in das fokussierte Feld.
 * Den Fokus von selbst ins ID-Feld zu setzen, lohnt sich nur mit Scanner – auf
 * Touch-Geräten öffnet er sonst sofort die Bildschirmtastatur. Daher ein Schalter,
 * gemerkt je Gerät; ohne Wahl an bei Maus/Trackpad, aus bei Touch.
 */
const KEY = 'handScanner';

export function handScannerOn() {
    try {
        const stored = localStorage.getItem(KEY);
        if (stored !== null) return stored === '1';
    } catch (e) { /* privat */ }
    return window.matchMedia('(pointer: fine)').matches;
}

export function mountHandScanner() {
    const field = document.querySelector('[data-scan-focus]');
    if (!field) return;

    let on = handScannerOn();
    const focus = () => field.focus({ preventScroll: true });

    document.querySelectorAll('[data-hand-scanner]').forEach((toggle) => {
        toggle.checked = on;
        toggle.addEventListener('change', () => {
            on = toggle.checked;
            try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) { /* privat */ }
            if (on) focus();
        });
    });

    if (on) focus();

    // Tippt der Scanner, während gerade nichts Eingabefähiges fokussiert ist (z. B. nach
    // einem Tipp auf ein Exemplar), landet die Eingabe trotzdem im ID-Feld.
    document.addEventListener('keydown', (event) => {
        if (!on || event.key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey) return;
        if (event.target.closest?.('input, textarea, select, [contenteditable], dialog')) return;
        focus();
    }, true);
}
