import { getJson } from './http';

/**
 * Ausleih-Dialog: Exemplar-Auswahl bestimmt die Menge automatisch.
 */
export function loanForm() {
    return {
        units: 0,
        quantity: 1,
        init() {
            this.quantity = parseInt(this.$root.dataset.quantity || '1', 10);
            this.count();
        },
        count() {
            this.units = this.$root.querySelectorAll('input[name="units[]"]:checked').length;
            if (this.units > 0) this.quantity = this.units;
        },
        get usesUnits() { return this.units > 0; },
    };
}

/**
 * Rückgabe-Dialog: komplett, Teilmenge oder bestimmte Exemplare.
 */
export function returnForm() {
    return {
        mode: 'all',
        outstanding: 1,
        quantity: 1,
        units: 0,
        init() {
            this.mode = this.$root.dataset.mode || 'all';
            this.outstanding = parseInt(this.$root.dataset.outstanding || '1', 10);
            this.count();
            this.$root.closest('dialog')?.addEventListener('opened', () => { this.mode = 'all'; });
        },
        count() {
            this.quantity = parseInt(this.$root.querySelector('input[name="quantity"]')?.value || '0', 10) || 0;
            this.units = this.$root.querySelectorAll('input[name="units[]"]:checked').length;
        },
        get isAll() { return this.mode === 'all'; },
        get isQuantity() { return this.mode === 'quantity'; },
        get isUnits() { return this.mode === 'units'; },
        /** Schließt diese Rückgabe die Ausleihe ab? Dann gehört die Zubehör-Checkliste dazu. */
        get completes() {
            return this.isAll
                || (this.isQuantity && this.quantity >= this.outstanding)
                || (this.isUnits && this.units >= this.outstanding);
        },
    };
}

/**
 * Zubehör-Liste im Geräteformular (Zeilen hinzufügen/entfernen).
 */
export function accessoryEditor() {
    return {
        rows: [],
        init() {
            try {
                this.rows = JSON.parse(this.$root.dataset.rows || '[]');
            } catch (e) {
                this.rows = [];
            }
            if (this.rows.length === 0) this.rows = [];
        },
        add() {
            this.rows.push({ name: '', quantity: 1 });
            this.$nextTick(() => {
                const inputs = this.$root.querySelectorAll('input[data-accessory-name]');
                inputs[inputs.length - 1]?.focus();
            });
        },
        remove(index) { this.rows.splice(index, 1); },
        nameField(index) { return `accessories[${index}][name]`; },
        quantityField(index) { return `accessories[${index}][quantity]`; },
    };
}

/**
 * Exemplar-Auswahl ohne Ausleihe (z. B. Rückgabe bestimmter Exemplare).
 */
export function unitPicker() {
    return {
        selected: 0,
        count() {
            this.selected = this.$root.querySelectorAll('input[type=checkbox]:checked').length;
        },
    };
}

const pad = (n) => String(n).padStart(2, '0');
const DURATIONS = ['45', '60', '90', '120'];

/**
 * Raumbuchung: Dauer-Presets, Ende wandert mit dem Beginn mit, Live-Prüfung auf Überschneidungen
 * und eine Tagesleiste aller Säle.
 */
export function bookingForm() {
    return {
        problem: '',
        conflicts: [],
        free: [],
        rows: [],
        ticks: [],
        dayLabel: '',
        roomName: '',
        checked: false,
        hasSelection: false,
        selectionStyle: '',
        activePreset: '',
        duration: 0,
        timer: null,
        request: null,
        init() {
            this.duration = this.currentDuration();
            this.markPreset();
            this.check();
        },
        get hasConflict() { return this.conflicts.length > 0; },
        get isFree() { return this.checked && !this.problem && !this.hasConflict; },
        isPreset(value) { return this.activePreset === value; },

        form() { return this.$root.querySelector('form'); },
        field(name) { return this.form().querySelector(`[name="${name}"]`); },
        read(prefix) {
            const date = this.field(`${prefix}_date`).value;
            const time = this.field(`${prefix}_time`).value;
            return date && time ? new Date(`${date}T${time}`) : null;
        },
        write(prefix, value) {
            this.field(`${prefix}_date`).value = `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`;
            this.field(`${prefix}_time`).value = `${pad(value.getHours())}:${pad(value.getMinutes())}`;
        },
        currentDuration() {
            const start = this.read('start');
            const end = this.read('end');
            return start && end ? Math.round((end - start) / 60000) : 0;
        },
        markPreset() {
            const start = this.read('start');
            const end = this.read('end');
            if (start && end && start.getHours() === 0 && start.getMinutes() === 0 && end.getHours() === 23 && end.getMinutes() === 59) {
                this.activePreset = 'day';
                return;
            }
            this.activePreset = DURATIONS.find((m) => Number(m) === this.duration) || '';
        },

        /** Beginn geändert: Dauer beibehalten */
        startChanged() {
            const start = this.read('start');
            if (start && this.duration > 0) this.write('end', new Date(start.getTime() + this.duration * 60000));
            this.markPreset();
            this.schedule();
        },
        endChanged() {
            this.duration = this.currentDuration();
            this.markPreset();
            this.schedule();
        },
        preset(event) {
            const value = event.currentTarget.dataset.preset;
            const start = this.read('start');
            if (!start) return;
            if (value === 'day') {
                start.setHours(0, 0, 0, 0);
                this.write('start', start);
                const end = new Date(start);
                end.setHours(23, 59, 0, 0);
                this.write('end', end);
            } else {
                // Von „ganztägig“ zurück auf eine Dauer: nicht um Mitternacht beginnen
                if (this.activePreset === 'day') {
                    start.setHours(8, 0, 0, 0);
                    this.write('start', start);
                }
                this.write('end', new Date(start.getTime() + Number(value) * 60000));
            }
            this.duration = this.currentDuration();
            this.markPreset();
            this.check();
        },
        switchRoom(event) {
            const radio = this.form().querySelector(`input[name="room_id"][value="${event.currentTarget.dataset.room}"]`);
            if (!radio) return;
            radio.checked = true;
            radio.focus();
            this.check();
        },

        schedule() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.check(), 250);
        },
        async check() {
            const room = this.form().querySelector('input[name="room_id"]:checked');
            if (!room) return;
            this.roomName = room.dataset.name || '';

            const params = new URLSearchParams({ room_id: room.value });
            ['start_date', 'start_time', 'end_date', 'end_time'].forEach((name) => params.set(name, this.field(name).value));
            if (this.$root.dataset.ignore) params.set('ignore', this.$root.dataset.ignore);

            this.request?.abort();
            this.request = new AbortController();
            try {
                const data = await getJson(`${this.$root.dataset.availability}?${params}`, { signal: this.request.signal });
                this.apply(data, Number(room.value));
            } catch (e) {
                if (e.name === 'AbortError') return;
                // Unvollständige Eingabe oder Netzfehler: der Server prüft beim Absenden ohnehin
                this.checked = false;
                this.conflicts = [];
                this.problem = '';
            }
        },
        apply(data, roomId) {
            this.checked = true;
            this.problem = data.problem || '';
            this.conflicts = data.conflicts.map((c) => ({
                ...c,
                label: [c.purpose, c.name].filter(Boolean).join(' · '),
            }));
            this.free = data.free;
            this.dayLabel = data.day.label;

            // Sichtbares Fenster: 7–21 Uhr, bei Bedarf erweitert
            const selection = data.day.selection;
            const all = data.day.rooms.flatMap((r) => r.bookings);
            if (selection) all.push(selection);
            const from = Math.min(7 * 60, ...all.map((b) => Math.floor(b.from / 60) * 60));
            const to = Math.max(21 * 60, ...all.map((b) => Math.ceil(b.to / 60) * 60));
            const span = Math.max(to - from, 60);
            const place = (a, b) => `left:${((a - from) / span) * 100}%;width:${Math.max(((b - a) / span) * 100, 0.8)}%`;

            this.ticks = [];
            const step = span > 16 * 60 ? 240 : 120;
            for (let m = Math.ceil(from / step) * step; m <= to; m += step) {
                this.ticks.push({ label: pad(Math.min(m / 60, 24)), style: `left:${((m - from) / span) * 100}%` });
            }

            this.rows = data.day.rooms.map((r) => ({
                id: r.id,
                name: r.name,
                selected: r.id === roomId,
                bars: r.bookings.map((b) => ({
                    style: place(b.from, b.to),
                    time: b.time,
                    label: b.title,
                    title: `${b.time} · ${b.title}`,
                })),
            }));
            this.hasSelection = Boolean(selection && selection.to > selection.from);
            this.selectionStyle = this.hasSelection ? place(selection.from, selection.to) : '';
        },
    };
}
