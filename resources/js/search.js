import { getJson } from './http';

/**
 * Globale Suche (Strg+K). Ergebnisse kommen als JSON und werden nur mit
 * x-text ausgegeben – kein HTML aus Daten.
 */
export function searchPalette() {
    return {
        query: '',
        results: [],
        active: 0,
        loading: false,
        searched: false,
        controller: null,

        get showEmpty() { return this.searched && !this.loading && this.results.length === 0; },
        get showHint() { return !this.searched && !this.loading; },

        async search() {
            const q = this.query.trim();
            if (q === '') {
                this.controller?.abort();
                this.loading = false;
                this.results = [];
                this.searched = false;
                return;
            }

            // Ältere, noch laufende Suche abbrechen – sonst kann ihre Antwort später
            // eintreffen und die Ergebnisse der neueren Eingabe überschreiben
            this.controller?.abort();
            const controller = new AbortController();
            this.controller = controller;
            this.loading = true;

            try {
                const url = `${this.$root.dataset.url}?q=${encodeURIComponent(q)}`;
                const data = await getJson(url, { signal: controller.signal });
                let previousGroup = null;
                this.results = (data.results || []).map((item) => {
                    const firstOfGroup = item.group !== previousGroup;
                    previousGroup = item.group;
                    return { ...item, firstOfGroup };
                });
                this.active = 0;
                this.searched = true;
            } catch (e) {
                if (e.name === 'AbortError') return; // abgelöst durch neuere Suche
                this.results = [];
            } finally {
                if (this.controller === controller) this.loading = false;
            }
        },

        setActive(index) { this.active = index; },

        onKey(event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.active = Math.min(this.active + 1, this.results.length - 1);
                this.scrollToActive();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.active = Math.max(this.active - 1, 0);
                this.scrollToActive();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                const item = this.results[this.active];
                if (item) {
                    window.location.href = item.url;
                } else if (this.query.trim() !== '') {
                    // Unbekannte ID direkt nachschlagen
                    window.location.href = `/d/${encodeURIComponent(this.query.trim())}`;
                }
            }
        },

        scrollToActive() {
            this.$nextTick(() => {
                this.$root.querySelectorAll('a[href]')[this.active]?.scrollIntoView({ block: 'nearest' });
            });
        },

        closeDialog() { this.$root.close(); },

        reset() {
            this.query = '';
            this.results = [];
            this.searched = false;
            this.active = 0;
        },
    };
}
