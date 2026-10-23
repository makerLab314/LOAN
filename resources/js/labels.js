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
}
