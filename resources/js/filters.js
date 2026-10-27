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
            // Filter stehen in der URL – so landet „Zurück“ wieder bei denselben Ergebnissen
            const params = new URLSearchParams(window.location.search);
            this.q = params.get('q') || '';
            this.category = params.get('category') || this.$root.dataset.category || '';
            this.status = params.get('status') || '';
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
            this.remember();
        },

        remember() {
            const params = new URLSearchParams(window.location.search);
            const set = (key, value) => (value ? params.set(key, value) : params.delete(key));
            set('q', this.q.trim());
            set('category', this.category);
            set('status', this.status);
            const query = params.toString();
            const url = window.location.pathname + (query ? `?${query}` : '');
            window.history.replaceState(window.history.state, '', url);
            try { sessionStorage.setItem('list.devices', url); } catch (e) { /* privat */ }
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
