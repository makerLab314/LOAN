/**
 * Client-seitiger Filter für Listen (Geräte).
 * Einträge tragen data-item, data-search, data-category und data-status.
 * Gezählt werden nur Einträge mit data-count (Kachel- und Listenansicht
 * enthalten dieselben Geräte).
 */
export function deviceFilter() {
    return {
        q: '',
        category: '',
        status: '',
        view: 'grid',
        shown: 0,
        total: 0,

        init() {
            this.category = this.$root.dataset.category || '';
            try { this.view = localStorage.getItem('devices.view') || 'grid'; } catch (e) { /* privat */ }
            this.$watch('q', () => this.apply());
            this.$watch('category', () => this.apply());
            this.$watch('status', () => this.apply());
            this.$watch('view', (v) => { try { localStorage.setItem('devices.view', v); } catch (e) { /* privat */ } });
            this.apply();
        },

        apply() {
            const q = this.q.trim().toLocaleLowerCase('de');
            let shown = 0;
            let total = 0;
            this.$root.querySelectorAll('[data-item]').forEach((el) => {
                const ok = (!q || (el.dataset.search || '').includes(q))
                    && (!this.category || el.dataset.category === this.category)
                    && (!this.status || (el.dataset.status || '').split(' ').includes(this.status));
                el.hidden = !ok;
                if (el.hasAttribute('data-count')) {
                    total++;
                    if (ok) shown++;
                }
            });
            this.shown = shown;
            this.total = total;
        },

        setView(event) { this.view = event.currentTarget.dataset.view; },
        get isGrid() { return this.view === 'grid'; },
        get isList() { return this.view === 'list'; },
        get filtered() { return this.shown !== this.total; },
        reset() {
            this.q = '';
            this.category = '';
            this.status = '';
        },
    };
}
