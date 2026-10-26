/**
 * Kleine DOM-Helfer für die Scan-Seiten. Texte aus dem Server immer über
 * textContent – nie als HTML.
 */
export function el(tag, className = '', text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = String(text);
    return node;
}

export function hidden(name, value) {
    const input = el('input');
    input.type = 'hidden';
    input.name = name;
    input.value = String(value);
    return input;
}

export function icon(name) {
    const template = document.querySelector(`template[data-icon="${name}"]`);
    return template ? template.content.cloneNode(true) : document.createTextNode('×');
}

/** Übersetzungen aus data-i18n, Platzhalter wie in Laravel (":name") */
export function translator(root) {
    let strings = {};
    try { strings = JSON.parse(root.dataset.i18n || '{}'); } catch (e) { /* leer */ }

    return (key, vars = {}) => (strings[key] || key).replace(/:(\w+)/g, (match, name) => (name in vars ? String(vars[name]) : match));
}

export function parseJson(value, fallback) {
    try { return JSON.parse(value || ''); } catch (e) { return fallback; }
}

/** Rückmeldung in einer Statuszeile; Farben: text-muted text-ok text-warn text-danger */
export function setTone(node, text, tone = 'muted', base = 'hint') {
    if (!node) return;
    node.textContent = text;
    node.className = `${base} text-${tone}`;
}
