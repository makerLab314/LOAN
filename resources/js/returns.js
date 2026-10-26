import { postJson } from './http';
import { createScanner } from './scanner';
import { el, hidden, icon, translator, parseJson, setTone } from './dom';

/**
 * Rückgabe per Scan: jedes Etikett ordnet sich seiner Ausleihe zu, danach
 * wird alles auf einmal zurückgebucht. Am Kiosk prüft der Server, dass nur
 * Gescanntes zurückgegeben wird.
 */
export function mountReturns() {
    const root = document.querySelector('[data-return]');
    if (!root) return;

    const t = translator(root);
    const list = root.querySelector('[data-cart-list]');
    const empty = root.querySelector('[data-cart-empty]');
    const choice = root.querySelector('[data-choice]');
    const counter = root.querySelector('[data-cart-count]');
    const submit = root.querySelector('[data-return-submit]');
    const submitLabel = root.querySelector('[data-submit-label]');
    const feedback = root.querySelector('[data-return-feedback]');
    const codeForm = root.querySelector('[data-code-form]');
    const codeInput = codeForm.querySelector('input');

    /** @type {{loan: object, device: object, units: Set<number>, quantity: number, checks: Object<string, boolean>}[]} */
    const lines = [];
    /** Mehrere passende Ausleihen ohne Exemplar-Nummern: Auswahl, wer zurückgibt */
    let pending = null;
    let scanner = null;

    const notify = (text, tone = 'muted') => {
        setTone(feedback, text, tone);
        scanner?.setStatus(text, tone);
    };

    const unitLabel = (loan, unit) => loan.unit_labels?.[unit] || `-${unit}`;
    const completes = (line) => (line.loan.units.length
        ? line.units.size >= line.loan.outstanding
        : line.quantity >= line.loan.outstanding);

    const lineFor = (loan, device) => {
        let line = lines.find((l) => l.loan.id === loan.id);
        if (!line) {
            line = { loan, device, units: new Set(), quantity: loan.outstanding, checks: {} };
            lines.push(line);
        }
        return line;
    };

    const onResult = (body) => {
        const { device, unit, label, loans } = body;

        if (unit) {
            const owner = loans.find((l) => l.units.includes(unit));
            if (owner) {
                const line = lineFor(owner, device);
                if (line.units.has(unit)) return notify(t('already', { label }), 'warn');
                line.units.add(unit);
                render();
                return notify(t('added', { label }), 'ok');
            }
        }

        // Ausleihen ohne Exemplar-Nummern: das Geräte-Etikett (oder irgendein Exemplar) genügt
        const plain = loans.filter((l) => l.units.length === 0);
        if (!loans.length || (!plain.length && unit)) return notify(t('notLoaned', { label }), 'warn');
        if (!plain.length) return notify(t('scanUnit', { title: device.title }), 'warn');

        if (plain.length === 1) {
            if (lines.some((l) => l.loan.id === plain[0].id)) return notify(t('already', { label }), 'warn');
            lineFor(plain[0], device);
            render();
            return notify(t('added', { label }), 'ok');
        }

        pending = { device, loans: plain };
        render();
        return notify(t('choose', { title: device.title }), 'warn');
    };

    const lookup = async (code) => {
        try {
            const { status, body } = await postJson(root.dataset.lookup, { code });
            if (status === 200 && body.found) {
                onResult(body);
            } else {
                notify(body.message || t('notFound'), 'danger');
            }
        } catch (e) {
            notify(t('error'), 'danger');
        }
    };

    const loanInfo = (loan, device) => {
        const box = el('div', 'min-w-0');
        const head = el('p', 'flex flex-wrap items-center gap-2');
        head.append(el('span', 'font-medium', device.title), el('span', 'text-muted', `· ${loan.borrower}`));
        const meta = el('p', 'text-sm text-muted', t('until', { date: loan.until }));
        if (loan.overdue) meta.append(' ', el('span', 'badge badge-danger', t('overdue')));
        box.append(head, meta);
        return box;
    };

    const renderChoice = () => {
        choice.replaceChildren();
        if (!pending) return;
        const box = el('div', 'space-y-2 border-b border-line bg-accent-soft px-4 py-3 sm:px-5');
        box.append(el('p', 'text-sm font-medium', t('chooseTitle', { title: pending.device.title })));
        const options = el('div', 'grid gap-2 sm:grid-cols-2');
        pending.loans.forEach((loan) => {
            // Das Gerät steht schon in der Überschrift – hier zählt, wer es hat
            const option = el('button', 'card flex items-center justify-between gap-2 p-3 text-left text-sm transition hover:border-accent disabled:opacity-50');
            option.type = 'button';
            const info = el('span', 'min-w-0');
            const meta = el('span', 'block text-xs text-muted', t('until', { date: loan.until }));
            if (loan.overdue) meta.append(' ', el('span', 'badge badge-danger', t('overdue')));
            info.append(el('span', 'block truncate font-medium', loan.borrower), meta);
            option.append(info, el('span', 'badge badge-muted', `${loan.outstanding}×`));
            option.disabled = lines.some((l) => l.loan.id === loan.id);
            option.addEventListener('click', () => {
                lineFor(loan, pending.device);
                pending = null;
                render();
            });
            options.append(option);
        });
        box.append(options);
        choice.append(box);
    };

    const renderLine = (line, index) => {
        const { loan, device } = line;
        const item = el('li', 'flex gap-3 px-4 py-3 sm:px-5');
        const body = el('div', 'min-w-0 flex-1 space-y-2');
        body.append(loanInfo(loan, device));
        item.append(hidden(`returns[${index}][loan_id]`, loan.id), body);

        if (loan.units.length) {
            const chips = el('div', 'flex flex-wrap items-center gap-1');
            loan.units.forEach((unit) => {
                const on = line.units.has(unit);
                chips.append(el('span', on ? 'id-chip border-accent bg-accent text-accent-fg' : 'id-chip text-muted line-through opacity-60', unitLabel(loan, unit)));
                if (on) item.append(hidden(`returns[${index}][units][]`, unit));
            });
            body.append(chips, el('p', 'text-xs text-muted', t('scanned', { n: line.units.size, m: loan.outstanding })
                + (completes(line) ? '' : ` ${t('missingUnits')}`)));
        } else if (loan.outstanding > 1) {
            const label = el('label', 'flex items-center gap-2 text-sm text-muted');
            const quantity = el('input', 'input w-20 py-1');
            quantity.type = 'number';
            quantity.name = `returns[${index}][quantity]`;
            quantity.min = '1';
            quantity.max = String(loan.outstanding);
            quantity.value = String(line.quantity);
            quantity.addEventListener('change', () => {
                line.quantity = Math.min(Math.max(1, parseInt(quantity.value, 10) || 1), loan.outstanding);
                render();
            });
            label.append(quantity, t('of', { n: loan.outstanding }));
            body.append(label);
        } else {
            item.append(hidden(`returns[${index}][quantity]`, 1));
        }

        // Zubehör-Checkliste nur, wenn diese Rückgabe die Ausleihe abschließt
        if (completes(line) && loan.accessories.length) {
            const set = el('fieldset', 'rounded-lg border border-line p-3');
            set.append(el('legend', 'px-1 text-sm font-medium', t('accessories')), hidden(`returns[${index}][accessories_present]`, 1));
            loan.accessories.forEach((a) => {
                const row = el('label', 'flex items-center gap-3 py-0.5 text-sm');
                const box = el('input', 'checkbox');
                box.type = 'checkbox';
                box.name = `returns[${index}][accessories][${a.key}]`;
                box.value = '1';
                box.checked = Boolean(line.checks[a.key]);
                box.addEventListener('change', () => { line.checks[a.key] = box.checked; });
                row.append(hidden(`returns[${index}][accessories][${a.key}]`, 0), box, el('span', 'flex-1', a.name));
                if (a.quantity > 1) row.append(el('span', 'badge badge-muted', `${a.quantity}×`));
                set.append(row);
            });
            set.append(el('p', 'hint mt-2', t('accessoriesHint')));
            body.append(set);
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
        renderChoice();
        list.replaceChildren(...lines.map(renderLine));
        // Ausleihe mit Exemplaren, von denen keines mehr gescannt ist, nicht mitschicken
        const ready = lines.filter((l) => !l.loan.units.length || l.units.size > 0);
        empty.hidden = lines.length > 0 || pending !== null;
        counter.textContent = String(lines.length);
        submit.disabled = ready.length === 0 || ready.length !== lines.length;
        submitLabel.textContent = lines.length > 1 ? t('submitMany', { n: lines.length }) : t('submit');
    };

    codeForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const code = codeInput.value.trim();
        if (!code) return;
        codeInput.value = '';
        await lookup(code);
        codeInput.focus();
    });

    parseJson(root.dataset.initial, []).forEach((entry) => {
        const line = lineFor(entry.loan, entry.device);
        (entry.units || []).forEach((u) => line.units.add(u));
        if (entry.quantity) line.quantity = Math.min(Math.max(1, entry.quantity), entry.loan.outstanding);
    });
    render();

    scanner = createScanner(root.querySelector('[data-scanner]'), (code) => lookup(code));
}
