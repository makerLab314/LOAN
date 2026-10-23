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
