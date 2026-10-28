/**
 * Etiketten: Geräte auswählen (alle / nach Kategorie / Suche).
 */
export function mountLabelPicker() {
    const root = document.querySelector('[data-label-picker]');
    if (!root) return;

    const boxes = () => [...root.querySelectorAll('input[name="devices[]"]')];
    const counter = root.querySelector('[data-selected-count]');
    const search = root.querySelector('[data-label-search]');
    const category = root.querySelector('[data-label-category]');

    const update = () => {
        counter.textContent = String(boxes().filter((b) => b.checked).length);
    };

    const visible = () => boxes().filter((b) => !b.closest('[data-item]').hidden);

    const filter = () => {
        const q = (search.value || '').trim().toLocaleLowerCase('de');
        const cat = category.value;
        boxes().forEach((b) => {
            const row = b.closest('[data-item]');
            row.hidden = !((!q || row.dataset.search.includes(q)) && (!cat || row.dataset.category === cat));
        });
    };

    root.addEventListener('change', update);
    search.addEventListener('input', filter);
    category.addEventListener('change', filter);
    root.querySelector('[data-select-visible]').addEventListener('click', () => {
        visible().forEach((b) => { b.checked = true; });
        update();
    });
    root.querySelector('[data-select-none]').addEventListener('click', () => {
        boxes().forEach((b) => { b.checked = false; });
        update();
    });

    update();
    mountLabelOptions();
}

const STORAGE_KEY = 'loan.labelOptions';

/**
 * Optionen: eigenes Format ein-/ausblenden und die letzte Wahl im Browser merken
 * (Vorgaben aus den Einstellungen gelten nur, solange nichts gespeichert ist).
 */
function mountLabelOptions() {
    const root = document.querySelector('[data-label-options]');
    if (!root) return;

    const format = root.querySelector('[data-label-format]');
    const custom = root.querySelector('[data-label-custom]');
    const sheet = root.querySelector('[data-label-custom-sheet]');
    const grid = root.querySelector('[data-label-custom-grid]');
    const fields = () => [...root.querySelectorAll('input[name], select[name]')];

    const toggle = () => {
        custom.hidden = format.value !== 'custom';
        grid.hidden = !sheet.checked;
    };

    const save = () => {
        const values = {};
        fields().forEach((el) => {
            if (el.type === 'radio') {
                if (el.checked) values[el.name] = el.value;
            } else if (el.type === 'checkbox') {
                values[el.name] = el.checked;
            } else {
                values[el.name] = el.value;
            }
        });
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(values)); } catch { /* privat/gesperrt */ }
    };

    const restore = () => {
        let values = null;
        try { values = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null'); } catch { /* ignorieren */ }
        if (!values) return;
        fields().forEach((el) => {
            if (!(el.name in values)) return;
            if (el.type === 'radio') el.checked = values[el.name] === el.value;
            else if (el.type === 'checkbox') el.checked = Boolean(values[el.name]);
            // Nur gültige Auswahl übernehmen (Format könnte entfernt worden sein)
            else if (el.tagName !== 'SELECT' || [...el.options].some((o) => o.value === values[el.name])) el.value = values[el.name];
        });
    };

    // Nach einem Validierungsfehler zeigt der Server die eingegebenen Werte – dann nichts überschreiben
    if (format.value !== 'custom') restore();
    toggle();

    root.addEventListener('change', () => { toggle(); save(); });
    root.addEventListener('input', save);
    root.querySelector('[data-label-reset]').addEventListener('click', () => {
        try { localStorage.removeItem(STORAGE_KEY); } catch { /* ignorieren */ }
        window.location.reload();
    });
}
