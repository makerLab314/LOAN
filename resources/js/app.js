/*
 * LOAN – Frontend
 * Alpine läuft im CSP-Build (kein eval) – alle Logik steckt in Alpine.data-Komponenten.
 * Daten aus dem Server werden ausschließlich per textContent/x-text ausgegeben.
 */
import Alpine from '@alpinejs/csp';
import { postJson } from './http';
import { searchPalette } from './search';
import { loanForm, returnForm, accessoryEditor, unitPicker } from './forms';
import { deviceFilter } from './filters';

window.Alpine = Alpine;

/* ---------------------------------------------------------------------- *
 * App-Rahmen: Drawer & Tastaturkürzel
 * ---------------------------------------------------------------------- */
Alpine.data('shell', () => ({
    drawer: false,
    openDrawer() { this.drawer = true; },
    closeDrawer() { this.drawer = false; },
    onKey(event) {
        const tag = (event.target.tagName || '').toLowerCase();
        const typing = ['input', 'textarea', 'select'].includes(tag) || event.target.isContentEditable;
        if ((event.key === 'k' && (event.ctrlKey || event.metaKey)) || (event.key === '/' && !typing)) {
            event.preventDefault();
            openSearch();
        }
        if (event.key === 'Escape') this.drawer = false;
    },
}));

Alpine.data('dropdown', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
}));

Alpine.data('toast', () => ({
    visible: true,
    init() {
        const timeout = parseInt(this.$root.dataset.timeout || '5000', 10);
        setTimeout(() => { this.visible = false; }, timeout);
    },
    hide() { this.visible = false; },
}));

/* ---------------------------------------------------------------------- *
 * Farbschema
 * ---------------------------------------------------------------------- */
function applyTheme(pref) {
    const dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.theme = pref;
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    applyTheme(document.documentElement.dataset.theme || 'system');
});

Alpine.data('themeToggle', () => ({
    open: false,
    pref: document.documentElement.dataset.theme || 'system',
    get label() {
        return { light: 'Hell', dark: 'Dunkel', system: 'System' }[this.pref];
    },
    init() {
        if (!this.$root.dataset.url) {
            try { this.pref = localStorage.getItem('theme') || this.pref; } catch (e) { /* privat */ }
        }
    },
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
    set(event) {
        this.pref = event.currentTarget.dataset.value;
        this.open = false;
        applyTheme(this.pref);
        try { localStorage.setItem('theme', this.pref); } catch (e) { /* privat */ }
        if (this.$root.dataset.url) postJson(this.$root.dataset.url, { theme: this.pref }).catch(() => {});
    },
}));

Alpine.data('searchPalette', searchPalette);
Alpine.data('loanForm', loanForm);
Alpine.data('returnForm', returnForm);
Alpine.data('accessoryEditor', accessoryEditor);
Alpine.data('unitPicker', unitPicker);
Alpine.data('deviceFilter', deviceFilter);

Alpine.data('tabs', () => ({
    tab: '',
    init() { this.tab = this.$root.dataset.initial || ''; },
    select(event) { this.tab = event.currentTarget.dataset.tab; },
    is(name) { return this.tab === name; },
}));

/* ---------------------------------------------------------------------- *
 * Globale Helfer ohne Alpine (data-Attribute)
 * ---------------------------------------------------------------------- */
function openSearch() {
    const dialog = document.getElementById('search-dialog');
    if (dialog && !dialog.open) {
        dialog.showModal();
        dialog.querySelector('input')?.focus();
    }
}

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-search-open]');
    if (opener) {
        event.preventDefault();
        openSearch();
        return;
    }

    // <button data-dialog="id"> öffnet <dialog id="id">
    const dialogButton = event.target.closest('[data-dialog]');
    if (dialogButton) {
        const dialog = document.getElementById(dialogButton.dataset.dialog);
        if (dialog) {
            event.preventDefault();
            dialog.showModal();
            dialog.dispatchEvent(new CustomEvent('opened', { detail: { ...dialogButton.dataset } }));
            dialog.querySelector('[autofocus]')?.focus();
        }
        return;
    }

    if (event.target.closest('[data-dialog-close]')) {
        event.target.closest('dialog')?.close();
        return;
    }

    // Klick auf den Hintergrund schließt Dialoge
    if (event.target.tagName === 'DIALOG' && event.target.open) {
        const rect = event.target.getBoundingClientRect();
        const inside = event.clientX >= rect.left && event.clientX <= rect.right
            && event.clientY >= rect.top && event.clientY <= rect.bottom;
        if (!inside) event.target.close();
    }
});

// <form data-confirm="Wirklich?"> – Sicherheitsabfrage vor dem Absenden
document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = form.dataset.confirm || event.submitter?.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
    }
    // Doppeltes Absenden verhindern
    if (form.dataset.submitting) {
        event.preventDefault();
        return;
    }
    form.dataset.submitting = '1';
    setTimeout(() => { delete form.dataset.submitting; }, 4000);
});

// <select data-autosubmit> / <input data-autosubmit>
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-autosubmit]')) event.target.form?.requestSubmit();
});

// Seitenspezifische Module nur bei Bedarf laden
if (document.querySelector('[data-calendar]')) import('./calendar').then((m) => m.mountCalendar());
if (document.querySelector('[data-scanner]')) import('./scanner').then((m) => m.mountScanner());
if (document.querySelector('[data-label-picker]')) import('./labels').then((m) => m.mountLabelPicker());

// Dialoge mit Validierungsfehlern nach dem Neuladen wieder öffnen
document.querySelectorAll('dialog[data-open-on-load]').forEach((d) => d.showModal());


Alpine.start();
