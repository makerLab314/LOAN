@extends('layouts.app')

@section('title', 'Räumeübersicht')

@section('content')
<!-- Breadcrumbs -->
<nav class="flex text-sm mb-8" aria-label="Breadcrumb">
    <a href="{{ url('/') }}" class="text-yellow-600 hover:underline flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-1">
            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
        </svg>
    </a>
    <span class="mx-2 text-yellow-600">/</span>
    <span class="text-gray-500">Räume</span>
</nav>

<h1 class="text-2xl font-bold mb-4">Räume</h1>
<p class="block items-center text-sm mb-2">
    Eine Übersicht über alle Räume. Möchtest du einen <span class="font-semibold">Raum reservieren?</span>
    Klicke in der entsprechenden Spalte auf den Button
    <span class="ml-1 bg-blackshadow-md bg-gray-100 text-gray-800 font-bold py-2 px-4 rounded">Reservieren</span>
</p>
<p class="mb-4 block items-center text-sm">
    <strong>Hinweis: </strong>Beschreibe den Zweck des Raums genau.
</p>
<p class="mb-8 flex items-center text-sm">
    <a href="{{ route('rooms.create') }}" class="hover:underline text-yellow-600 flex items-center">
        Du möchtest dem System einen Raum hinzufügen? Folge mir!
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" height="18" width="18" class="ml-1">
            <path fill-rule="evenodd" d="M19.902 4.098a3.75 3.75 0 0 0-5.304 0l-4.5 4.5a3.75 3.75 0 0 0 1.035 6.037.75.75 0 0 1-.646 1.353 5.25 5.25 0 0 1-1.449-8.45l4.5-4.5a5.25 5.25 0 1 1 7.424 7.424l-1.757 1.757a.75.75 0 1 1-1.06-1.06l1.757-1.757a3.75 3.75 0 0 0 0-5.304Zm-7.389 4.267a.75.75 0 0 1 1-.353 5.25 5.25 0 0 1 1.449 8.45l-4.5 4.5a5.25 5.25 0 1 1-7.424-7.424l1.757-1.757a.75.75 0 1 1 1.06 1.06l-1.757 1.757a3.75 3.75 0 1 0 5.304 5.304l4.5-4.5a.75.75 0 0 1-.354-1Z" clip-rule="evenodd" />
        </svg>
    </a>
</p>

@if(session('status'))
    <div id="alert" role="alert" class="mb-8 rounded-md border border-gray-300 bg-white p-4 shadow-sm">
        <div class="flex items-start gap-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <div class="flex-1">
                <strong class="font-medium text-gray-900"> Änderungen erfolgreich </strong>
                <p class="mt-0.5 text-sm text-gray-700">{{ session('status') }}</p>
            </div>
            <button onclick="document.getElementById('alert').style.display = 'none';"
                    class="-m-3 rounded-full p-1.5 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700"
                    type="button" aria-label="Dismiss alert">
                <span class="sr-only">Schließen</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif

@php
    // Einfache Liste verfügbarer Standorte für das Dropdown
    $locations = $rooms->pluck('location')->filter()->unique()->sort()->values();
@endphp

<!-- GRAUER BLOCK: Toolbar + Tabelle -->
<div class="container mx-auto flex items-center mb-8 p-4 pt-4 bg-gray-600 rounded-tr rounded-b">
    <div class="w-full">

        <!-- Toolbar: Standort + Status links, Suche rechts -->
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <!-- LINKS -->
            <div class="flex items-center gap-2">
                <!-- Standort-Dropdown -->
                <div class="relative inline-block text-left">
                    <button id="locationButton"
                            type="button"
                            class="inline-flex w-96 justify-between items-center rounded-md bg-gray-500 px-4 py-2 text-sm text-white shadow-sm hover:bg-gray-400 focus:outline-none"
                            aria-haspopup="true" aria-expanded="false"
                            onclick="toggleLocationMenu()">
                        <span id="locationButtonLabel">Standort</span>
                        <svg class="h-5 w-5 ml-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="locationMenu"
                         class="hidden absolute z-20 mt-2 w-full origin-top-left rounded-md bg-gray-500 shadow-lg ring-1 ring-black ring-opacity-5">
                        <div class="py-1" role="menu" aria-orientation="vertical">
                            <a href="#" class="location-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400"
                               data-location="">Alle</a>
                            @foreach($locations as $loc)
                                <a href="#" class="location-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400"
                                   data-location="{{ $loc }}">{{ $loc }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Status-Dropdown -->
                <div class="relative inline-block text-left">
                    <button id="statusButton"
                            type="button"
                            class="inline-flex w-40 justify-between items-center rounded-md bg-gray-500 px-4 py-2 text-sm text-gray-100 shadow-sm hover:bg-gray-400 focus:outline-none"
                            aria-haspopup="true" aria-expanded="false"
                            onclick="toggleStatusMenu()">
                        <span id="statusButtonLabel">Status</span>
                        <svg class="h-5 w-5 ml-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="statusMenu"
                         class="hidden absolute z-20 mt-2 w-40 origin-top-left rounded-md bg-gray-500 shadow-lg ring-1 ring-black ring-opacity-5">
                        <div class="py-1" role="menu" aria-orientation="vertical">
                            <a href="#" class="status-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400"
                               data-status="all">Alle</a>
                            <a href="#" class="status-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400"
                               data-status="available">Verfügbar</a>
                            <a href="#" class="status-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400"
                               data-status="reserved">Reserviert</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECHTS: Suche -->
            <div class="ml-auto">
                <div class="relative">
                    <input id="searchInput" type="text"
                           placeholder="Suchen: Name, Standort oder Beschreibung…"
                           class="w-80 rounded-md bg-gray-700 placeholder-gray-400 text-gray-200 px-4 py-2 text-sm
                                  border border-gray-700 focus:outline-none focus:ring-0 focus:ring-transparent focus:border-gray-700"
                           autocomplete="off" />
                    <button type="button" id="clearSearch"
                            class="absolute top-1/2 right-2 -translate-y-1/2 p-1 text-gray-200 hover:text-white hidden"
                            aria-label="Eingabe löschen">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M6.225 4.811a1 1 0 0 1 1.414 0L12 9.172l4.361-4.361a1 1 0 1 1 1.414 1.414L13.414 10.586l4.361 4.361a1 1 0 0 1-1.414 1.414L12 12l-4.361 4.361a1 1 0 0 1-1.414-1.414l4.361-4.361-4.361-4.361a1 1 0 0 1 0-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabelle -->
        <table class="w-full bg-gray-700 text-white rounded-lg table-fixed text-left">
            <thead>
            <tr>
                <th class="border-b-2 px-4 py-2 border-gray-500 w-1/12 font-medium text-sm">Name</th>
                <th class="border-b-2 px-4 py-2 border-gray-500 w-3/12 font-medium text-sm">Standort</th>
                <th class="border-b-2 px-4 py-2 border-gray-500 w-5/12 font-medium text-sm">Beschreibung</th>
                <th class="border-b-2 px-4 py-2 border-gray-500 w-3/12 font-medium text-sm"></th>
            </tr>
            </thead>
            <tbody id="roomTableBody">
            @foreach($rooms as $room)
                <tr class="room-row"
                    data-location="{{ $room->location }}"
                    data-status="{{ $room->status ?? 'available' }}"> {{-- TODO: hier ggf. 'reserved' setzen, wenn bereits belegt --}}
                    <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                        <span class="text-gray-300">{{ $room->name }}</span>
                    </td>
                    <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                        <span class="text-gray-300 text-sm">{{ $room->location }}</span>
                    </td>
                    <td class="border-b px-4 py-2 border-gray-600 break-words text-gray-300 text-sm">{{ $room->description }}</td>
                    <td class="border-b px-4 py-2 border-gray-600 text-sm text-right">
                        <div class="flex justify-end items-center space-x-2">
                            <a href="{{ route('rooms.reserve', $room) }}" class="shadow-md bg-gray-100 hover:bg-white text-gray-800 font-bold py-2 px-4 rounded">
                                Reservieren
                            </a>
                            <a href="{{ route('rooms.edit', $room) }}" class="py-2 pl-6 pr-2 rounded text-white">
                                <svg height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
                                    <path d="M5.25 5.25a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3V13.5a.75.75 0 0 0-1.5 0v5.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V8.25a1.5 1.5 0 0 1 1.5-1.5h5.25a.75.75 0 0 0 0-1.5H5.25Z" />
                                </svg>
                            </a>
                            <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="py-2 px-0 rounded text-white" onclick="return confirm('Sind Sie sicher, dass Sie diesen Raum löschen möchten?')">
                                    <svg height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                        <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
/* optional: Fokus-Ring der Suche komplett aus */
#searchInput:focus { --tw-ring-color: transparent; box-shadow: none; }
</style>

<script>
// ---------- Helpers ----------
function getRoomNameFromRow(row) {
    return (row.querySelector('td:nth-child(1)')?.textContent || '').trim();
}
function getRoomLocationFromRow(row) {
    return (row.querySelector('td:nth-child(2)')?.textContent || '').trim();
}
function getRoomDescriptionFromRow(row) {
    return (row.querySelector('td:nth-child(3)')?.textContent || '').trim();
}

// Zentraler Filter-State
const roomFilters = {
    location: '',        // '' = Alle
    status: 'all',       // 'all' | 'available' | 'reserved'
    query: ''            // Suchbegriff
};

// Hauptfunktion: wendet alle Filter + Sortierung an
function applyRoomFilters() {
    const tbody = document.getElementById('roomTableBody');
    const rows = Array.from(document.querySelectorAll('.room-row'));
    const q = roomFilters.query.toLocaleLowerCase('de');

    rows.forEach(row => {
        // Standort
        const loc = (row.dataset.location || '').toLocaleLowerCase('de').trim();
        const inLocation = !roomFilters.location || loc === roomFilters.location.toLocaleLowerCase('de').trim();

        // Status (aus data-status, Standard 'available' wenn leer)
        const status = (row.dataset.status || 'available').toLowerCase();
        let inStatus = true;
        if (roomFilters.status === 'available') inStatus = status === 'available';
        if (roomFilters.status === 'reserved')  inStatus = status === 'reserved';

        // Suche: Name/Standort/Beschreibung
        let inSearch = true;
        if (q) {
            const name = getRoomNameFromRow(row).toLocaleLowerCase('de');
            const locText = getRoomLocationFromRow(row).toLocaleLowerCase('de');
            const desc = getRoomDescriptionFromRow(row).toLocaleLowerCase('de');
            inSearch = name.includes(q) || locText.includes(q) || desc.includes(q);
        }

        row.style.display = (inLocation && inStatus && inSearch) ? '' : 'none';
    });

    // sichtbare Zeilen alphabetisch nach Name sortieren
    const visibleRows = rows.filter(r => r.style.display !== 'none');
    visibleRows.sort((a, b) => {
        const aName = getRoomNameFromRow(a).toLocaleLowerCase('de');
        const bName = getRoomNameFromRow(b).toLocaleLowerCase('de');
        return aName.localeCompare(bName, 'de', { sensitivity: 'base', numeric: true });
    });
    visibleRows.forEach(r => tbody.appendChild(r));
}

// ---------- Standort-Dropdown ----------
function toggleLocationMenu() {
    const menu = document.getElementById('locationMenu');
    menu.classList.toggle('hidden');
    document.getElementById('locationButton').setAttribute('aria-expanded', !menu.classList.contains('hidden'));
}
function closeLocationMenu() {
    const menu = document.getElementById('locationMenu');
    if (!menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
        document.getElementById('locationButton').setAttribute('aria-expanded', 'false');
    }
}
document.addEventListener('click', (e) => {
    const btn = document.getElementById('locationButton');
    const menu = document.getElementById('locationMenu');
    const item = e.target.closest('.location-item');
    if (!btn.contains(e.target) && !menu.contains(e.target)) closeLocationMenu();

    if (item) {
        e.preventDefault();
        const val = item.dataset.location || '';
        roomFilters.location = val;
        document.getElementById('locationButtonLabel').textContent = `${val || 'Alle'}`;
        closeLocationMenu();
        applyRoomFilters();
    }
});
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLocationMenu(); });

// ---------- Status-Dropdown ----------
function toggleStatusMenu() {
    const menu = document.getElementById('statusMenu');
    menu.classList.toggle('hidden');
    document.getElementById('statusButton').setAttribute('aria-expanded', !menu.classList.contains('hidden'));
}
function closeStatusMenu() {
    const menu = document.getElementById('statusMenu');
    if (!menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
        document.getElementById('statusButton').setAttribute('aria-expanded', 'false');
    }
}
document.addEventListener('click', (e) => {
    const btn = document.getElementById('statusButton');
    const menu = document.getElementById('statusMenu');
    const item = e.target.closest('.status-item');
    if (!btn.contains(e.target) && !menu.contains(e.target)) closeStatusMenu();

    if (item) {
        e.preventDefault();
        roomFilters.status = item.dataset.status; // all | available | reserved
        document.getElementById('statusButtonLabel').textContent =
            roomFilters.status === 'all' ? 'Status' :
            (roomFilters.status === 'available' ? 'Verfügbar' : 'Reserviert');
        closeStatusMenu();
        applyRoomFilters();
    }
});
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeStatusMenu(); });

// ---------- Live-Suche ----------
(function initRoomSearch() {
    const input = document.getElementById('searchInput');
    const clear = document.getElementById('clearSearch');
    if (!input) return;

    const onInput = () => {
        roomFilters.query = input.value || '';
        clear.classList.toggle('hidden', !roomFilters.query);
        applyRoomFilters();
    };
    input.addEventListener('input', onInput);

    if (clear) {
        clear.addEventListener('click', () => {
            input.value = '';
            roomFilters.query = '';
            clear.classList.add('hidden');
            input.focus();
            applyRoomFilters();
        });
    }
})();

// ---------- Init ----------
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('locationButtonLabel').textContent = 'Standort';
    document.getElementById('statusButtonLabel').textContent = 'Status';
    applyRoomFilters();
});
</script>
@endsection
