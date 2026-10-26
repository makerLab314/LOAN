import { postJson } from './http';
import { createScanner } from './scanner';
import { el, hidden, icon, translator, parseJson, setTone } from './dom';

/**
 * Ausleihe per Scan: Geräte und Exemplare sammeln, dann mit einem Namen und
 * Zeitraum auf einmal ausleihen. Der Server prüft alles noch einmal.
 */
export function mountCheckout() {
    const root = document.querySelector('[data-checkout]');
    if (!root) return;

    const t = translator(root);
    const catalog = new Map(parseJson(root.dataset.devices, []).map((d) => [d.id, d]));
    const unitsRequired = root.dataset.unitsRequired === '1';

    const list = root.querySelector('[data-cart-list]');
    const empty = root.querySelector('[data-cart-empty]');
    const counter = root.querySelector('[data-cart-count]');
    const submit = root.querySelector('[data-checkout-submit]');
    const submitLabel = root.querySelector('[data-submit-label]');
    const confirm = root.querySelector('[data-accessories-confirm]');
    const feedback = root.querySelector('[data-checkout-feedback]');
    const codeForm = root.querySelector('[data-code-form]');
    const codeInput = codeForm.querySelector('input');
    const pickerForm = root.querySelector('[data-picker-form]');
    const picker = pickerForm.querySelector('select');

    /** @type {{id: number, units: Set<number>, quantity: number}[]} */
    const lines = [];
    let scanner = null;

    const notify = (text, tone = 'muted') => {
        setTone(feedback, text, tone);
        scanner?.setStatus(text, tone);
    };

    const needsUnits = (device) => unitsRequired && device.total > 1;
    const amount = (line) => line.units.size || line.quantity;

    const add = (id, unit) => {
        const device = catalog.get(id);
        if (!device) {
            notify(t('notFound'), 'danger');
            return;
        }
        let line = lines.find((l) => l.id === id);

        if (unit) {
            const label = `${device.label}-${unit}`;
            if (device.busy.includes(unit)) return notify(t('busy', { label }), 'danger');
            if (line?.units.has(unit)) return notify(t('already', { label }), 'warn');
            if (line && line.units.size >= device.available) return notify(t('maxed', { title: device.title }), 'warn');
            if (!line) {
                line = { id, units: new Set(), quantity: 1 };
                lines.push(line);
            }
            line.units.add(unit);
            render();
            return notify(t('added', { label }), 'ok');
        }

        if (device.available < 1) return notify(t('noneFree', { title: device.title }), 'danger');
        if (line && (line.units.size || needsUnits(device))) return notify(t('needUnit', { title: device.title }), 'warn');
        if (line) {
            if (line.quantity >= device.available) return notify(t('maxed', { title: device.title }), 'warn');
            line.quantity += 1;
        } else {
            lines.push({ id, units: new Set(), quantity: 1 });
        }
        render();
        if (needsUnits(device)) return notify(t('needUnit', { title: device.title }), 'warn');
        return notify(t('added', { label: device.title }), 'ok');
    };

    const lookup = async (code) => {
        try {
            const { status, body } = await postJson(root.dataset.lookup, { code });
            if (status === 200 && body.found) {
                add(body.device_id, body.unit);
            } else {
                notify(body.message || t('notFound'), 'danger');
            }
        } catch (e) {
            notify(t('error'), 'danger');
        }
    };

    const summary = () => {
        const total = lines.reduce((sum, line) => sum + amount(line), 0);
        counter.textContent = String(total);
        empty.hidden = lines.length > 0;
        submit.disabled = lines.length === 0;
        submitLabel.textContent = total === 0 ? t('submit') : (total === 1 ? t('submitOne') : t('submitMany', { n: total }));
        confirm.hidden = !lines.some((line) => catalog.get(line.id)?.accessories.length);
    };

    const renderLine = (line, index) => {
        const device = catalog.get(line.id);
        const item = el('li', 'flex gap-3 px-4 py-3 sm:px-5');
        const body = el('div', 'min-w-0 flex-1 space-y-2');
        const head = el('p', 'flex flex-wrap items-center gap-2');
        head.append(el('span', 'font-medium', device.title), el('span', 'id-chip', device.label));
        body.append(head);
        item.append(hidden(`items[${index}][device_id]`, device.id), body);

        if (device.total > 1) {
            const row = el('div', 'flex flex-wrap items-center gap-3');
            const label = el('label', 'flex items-center gap-2 text-sm text-muted', t('quantity'));
            const quantity = el('input', 'input w-20 py-1');
            quantity.type = 'number';
            quantity.min = '1';
            quantity.max = String(Math.max(1, device.available));
            quantity.value = String(amount(line));
            quantity.disabled = line.units.size > 0;
            if (!line.units.size) quantity.name = `items[${index}][quantity]`;
            quantity.addEventListener('change', () => {
                const value = Math.min(Math.max(1, parseInt(quantity.value, 10) || 1), Math.max(1, device.available));
                line.quantity = value;
                quantity.value = String(value);
                summary();
            });
            label.append(quantity);
            row.append(label, el('span', 'text-xs text-muted', t('free', { n: device.available })));
            body.append(row);

            if (device.total <= 60) {
                const chips = el('div', 'flex flex-wrap gap-1');
                for (let n = 1; n <= device.total; n += 1) {
                    const busy = device.busy.includes(n);
                    const on = line.units.has(n);
                    const chip = el('button', `min-w-10 rounded-md border px-1.5 py-1 font-mono text-xs transition ${busy
                        ? 'cursor-not-allowed border-dashed border-line bg-surface-2 text-muted line-through'
                        : (on ? 'border-accent bg-accent text-accent-fg' : 'border-line bg-surface hover:border-accent')}`, `-${n}`);
                    chip.type = 'button';
                    chip.disabled = busy;
                    chip.title = `${device.label}-${n}`;
                    chip.setAttribute('aria-pressed', String(on));
                    chip.addEventListener('click', () => {
                        if (on) {
                            line.units.delete(n);
                        } else if (line.units.size >= device.available) {
                            return notify(t('maxed', { title: device.title }), 'warn');
                        } else {
                            line.units.add(n);
                        }
                        return render();
                    });
                    chips.append(chip);
                }
                body.append(el('p', 'text-xs text-muted', t('unitsHint')), chips);
            }

            [...line.units].sort((a, b) => a - b).forEach((n) => item.append(hidden(`items[${index}][units][]`, n)));
        } else {
            item.append(hidden(`items[${index}][quantity]`, 1));
        }

        if (device.accessories.length) {
            body.append(el('p', 'text-xs text-muted', `${t('accessories')} ${device.accessories.join(', ')}`));
        }

        const remove = el('button', 'btn btn-ghost btn-icon -mr-2 self-start');
        remove.type = 'button';
        remove.title = t('remove');
        remove.setAttribute('aria-label', t('remove'));
        remove.append(icon('x'));
        remove.addEventListener('click', () => {
            lines.splice(lines.indexOf(line), 1);
            render();
        });
        item.append(remove);

        return item;
    };

    const render = () => {
        list.replaceChildren(...lines.map(renderLine));
        summary();
    };

    codeForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const code = codeInput.value.trim();
        if (!code) return;
        codeInput.value = '';
        await lookup(code);
        codeInput.focus();
    });

    pickerForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const id = Number(picker.value);
        if (id) add(id, null);
        picker.value = '';
    });

    // Nach einem Validierungsfehler: vorige Auswahl
    parseJson(root.dataset.initial, []).forEach((entry) => {
        if (!catalog.has(entry.id) || lines.some((l) => l.id === entry.id)) return;
        lines.push({ id: entry.id, units: new Set(entry.units || []), quantity: entry.quantity || 1 });
    });
    render();

    scanner = createScanner(root.querySelector('[data-scanner]'), (code) => lookup(code));
}
