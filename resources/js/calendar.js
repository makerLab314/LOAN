import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import deLocale from '@fullcalendar/core/locales/de';

const KINDS = ['ev-room', 'ev-reservation', 'ev-loan', 'ev-overdue'];

export function mountCalendar() {
    const el = document.querySelector('[data-calendar]');
    if (!el) return;

    const filters = document.querySelector('[data-calendar-filters]');
    const detail = document.getElementById('event-dialog');
    const mobile = window.matchMedia('(max-width: 767px)').matches;

    const selected = () => {
        const params = new URLSearchParams();
        filters?.querySelectorAll('input[name="types[]"]:checked').forEach((i) => params.append('types[]', i.value));
        const room = filters?.querySelector('select[name="room"]')?.value;
        if (room) params.set('room', room);
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
            detail.classList.remove(...KINDS);
            detail.classList.add(...info.event.classNames.filter((c) => KINDS.includes(c)));
            detail.querySelector('[data-field=kind]').textContent = props.kind || '';
            detail.querySelector('[data-field=title]').textContent = info.event.title;
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
