import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import deLocale from '@fullcalendar/core/locales/de';

/** Farbklassen eines Termins (Art und Saal) – auch für den Detail-Dialog */
const isColorClass = (c) => c.startsWith('ev-') || c.startsWith('room-c');
const COLOR_CLASSES = ['ev-room', 'ev-reservation', 'ev-loan', 'ev-overdue', ...Array.from({ length: 6 }, (_, i) => `room-c${i}`)];

export function mountCalendar() {
    const el = document.querySelector('[data-calendar]');
    if (!el) return;

    const filters = document.querySelector('[data-calendar-filters]');
    const detail = document.getElementById('event-dialog');
    const mobile = window.matchMedia('(max-width: 767px)').matches;

    const selected = () => {
        const params = new URLSearchParams();
        filters?.querySelectorAll('input[name="types[]"]:checked').forEach((i) => params.append('types[]', i.value));
        // Alle Säle an = kein Filter; keiner an = keine Raumbuchungen
        const rooms = [...(filters?.querySelectorAll('input[name="rooms[]"]') || [])];
        const on = rooms.filter((i) => i.checked);
        if (on.length < rooms.length) (on.length ? on : [{ value: '0' }]).forEach((i) => params.append('rooms[]', i.value));
        return params;
    };

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
        locale: document.documentElement.lang.startsWith('de') ? deLocale : 'en',
        initialView: mobile ? 'listWeek' : 'dayGridMonth',
        headerToolbar: mobile
            ? { left: 'prev,next', center: 'title', right: 'today' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        footerToolbar: mobile ? { center: 'listWeek,dayGridMonth,timeGridDay' } : false,
        height: 'auto',
        firstDay: 1,
        nowIndicator: true,
        dayMaxEvents: 3,
        eventDisplay: 'block',
        expandRows: true,
        stickyHeaderDates: true,
        weekNumbers: !mobile,
        slotMinTime: '07:00:00',
        slotMaxTime: '21:00:00',
        dayHeaderContent: dayHeader,
        eventContent: eventContent,
        // Gleichzeitige Termine nebeneinander statt schräg übereinander
        slotEventOverlap: false,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        events(info, success, failure) {
            const params = selected();
            params.set('start', info.startStr);
            params.set('end', info.endStr);
            fetch(`${el.dataset.events}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
                .then(success)
                .catch(failure);
        },
        eventClick(info) {
            info.jsEvent.preventDefault();
            if (!detail) return;
            const props = info.event.extendedProps;
            // Farbe der Art (ev-room, ev-loan …) für Streifen und Markierung im Dialog
            detail.classList.remove(...COLOR_CLASSES);
            detail.classList.add(...info.event.classNames.filter(isColorClass));
            // Raumbuchung: Saal als Markierung, Anlass als Überschrift
            detail.querySelector('[data-field=kind]').textContent = props.room || props.kind || '';
            detail.querySelector('[data-field=title]').textContent = props.headline || info.event.title;
            detail.querySelector('[data-field=detail]').textContent = props.detail || '';
            detail.querySelector('[data-field=time]').textContent = formatRange(info.event);
            const link = detail.querySelector('[data-field=link]');
            if (props.url) {
                link.href = props.url;
                link.hidden = false;
            } else {
                link.hidden = true;
            }
            detail.showModal();
        },
        eventDidMount(info) {
            // Tooltip mit allen Angaben (gekürzte Titel in kleinen Zellen)
            const { kind, detail: text } = info.event.extendedProps;
            info.el.title = [kind, info.event.title, text].filter(Boolean).join(' · ');
        },
    });

    calendar.render();
    filters?.addEventListener('change', () => calendar.refetchEvents());
}

const node = (tag, className, text) => {
    const el = document.createElement(tag);
    el.className = className;
    if (text) el.textContent = text;
    return el;
};

const timeFormat = () => new Intl.DateTimeFormat(document.documentElement.lang || 'de', { hour: '2-digit', minute: '2-digit' });

/**
 * Termin-Inhalt: Zeit und Saal klein, Anlass als Titel, Ansprechperson darunter (Woche/Tag).
 * Daten nur per textContent – nie als HTML.
 */
function eventContent(arg) {
    if (arg.view.type.startsWith('list')) return true;

    const { room, headline, contact } = arg.event.extendedProps;
    const title = headline || arg.event.title;
    const time = arg.event.allDay || !arg.event.start ? '' : timeFormat().format(arg.event.start);

    if (arg.view.type.startsWith('dayGrid')) {
        // Monat: eine Zeile, die Uhrzeit wird nie abgeschnitten
        const line = node('div', 'ev-line');
        if (time) line.append(node('span', 'ev-time', time));
        line.append(node('span', 'ev-title', room ? `${room} · ${title}` : title));
        return { domNodes: [line] };
    }

    const end = arg.event.end && !arg.event.allDay ? timeFormat().format(arg.event.end) : '';
    const meta = [time && end ? `${time}–${end}` : time, room].filter(Boolean).join(' · ');
    const nodes = [];
    if (meta) nodes.push(node('div', 'ev-meta', meta));
    nodes.push(node('div', 'ev-title', title));
    if (contact) nodes.push(node('div', 'ev-sub', contact));
    return { domNodes: nodes };
}

/** Woche/Tag: Wochentag klein, Datum groß (heute hervorgehoben) */
function dayHeader(arg) {
    if (!arg.view.type.startsWith('timeGrid')) return true;
    const lang = document.documentElement.lang || 'de';
    const wrap = node('div', `cal-head${arg.isToday ? ' is-today' : ''}`);
    wrap.append(
        node('span', 'cal-head-day', new Intl.DateTimeFormat(lang, { weekday: 'short' }).format(arg.date).replace('.', '')),
        node('span', 'cal-head-date', String(arg.date.getDate())),
    );
    return { domNodes: [wrap] };
}

function formatRange(event) {
    const opts = event.allDay
        ? { day: '2-digit', month: '2-digit', year: 'numeric' }
        : { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' };
    const fmt = new Intl.DateTimeFormat(document.documentElement.lang || 'de', opts);
    const start = event.start ? fmt.format(event.start) : '';
    let end = event.end;
    if (end && event.allDay) end = new Date(end.getTime() - 86400000);
    return end ? `${start} – ${fmt.format(end)}` : start;
}
