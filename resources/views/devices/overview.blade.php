@extends('layouts.app')

@section('title', 'Gebuchte Geräte')

@section('content')

    <!-- Breadcrumbs -->
    <nav id="breadcrumb-nav" class="flex text-sm mb-8" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="text-yellow-600 hover:underline flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-1">
                <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
            </svg>
        </a>
        <span class="mx-2 text-yellow-600">/</span>
        <span class="text-gray-500">{{ __('Gebuchte Geräte') }}</span>
        <span id="current-category-breadcrumb"></span>
    </nav>

    @if (session('status'))
        <div id="alert" role="alert" class="mb-8 rounded-md border border-gray-300 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 text-green-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <div class="flex-1">
                    <strong class="font-medium text-gray-900">Änderungen erfolgreich</strong>
                    <p class="mt-0.5 text-sm text-gray-700">{{ session('status') }}</p>
                </div>

                <button onclick="document.getElementById('alert').style.display = 'none';"
                        class="-m-3 rounded-full p-1.5 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700"
                        type="button" aria-label="Dismiss alert">
                    <span class="sr-only">Schließen</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <h1 class="text-2xl font-bold mb-4">Gebuchte Geräte</h1>
    <p class="block items-center text-sm mb-2">
        Eine Übersicht aller aktuell <strong>verliehenen</strong> und <strong>vorgemerkten</strong> Geräte.
        Störniere ein Vormerkung, bevor du ein Gerät verleihst. Sollte ein Gerät in einwandfreiem Zustand zurück gebracht werden, musst du es annehmen, um es erneut zu verleihen.
    </p>
    <p class="mb-4 block items-center text-sm">
        <strong>Hinweis:</strong> Wenn das heutige Datum das Enddatum eines verliehenen Geräts überschreitet, kontaktiere
        bitte die Person, an die das Gerät verliehen wurde.
    </p>
    <p class="block items-center text-sm mb-8">
        <a href="{{ route('devices.index') }}" class="hover:underline text-yellow-600 flex items-center">
            Du möchtest zur Geräteübersicht? Folge mir!
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" height="18" width="18" class="ml-1">
                <path fill-rule="evenodd" d="M19.902 4.098a3.75 3.75 0 0 0-5.304 0l-4.5 4.5a3.75 3.75 0 0 0 1.035 6.037.75.75 0 0 1-.646 1.353 5.25 5.25 0 0 1-1.449-8.45l4.5-4.5a5.25 5.25 0 1 1 7.424 7.424l-1.757 1.757a.75.75 0 1 1-1.06-1.06l1.757-1.757a3.75 3.75 0 0 0 0-5.304Zm-7.389 4.267a.75.75 0 0 1 1-.353 5.25 5.25 0 0 1 1.449 8.45l-4.5 4.5a5.25 5.25 0 1 1-7.424-7.424l1.757-1.757a.75.75 0 1 1 1.06 1.06l-1.757 1.757a3.75 3.75 0 1 0 5.304 5.304l4.5-4.5a3.75 3.75 0 0 0-1.035-6.037.75.75 0 0 1-.354-1Z" clip-rule="evenodd" />
            </svg>
        </a>
    </p>

    @php
        // Labels für Kategorien
        $labels = [
            'VRAR' => 'VR/AR',
            'Videokonferenzsystem' => 'Videokonferenzsysteme',
            'Microcontroller' => 'Microcontroller',
            'Stativ' => 'Stative',
            'Kamera' => 'Kameras',
            'Mikrofon' => 'Mikrofone',
            'Koffer' => 'Koffer',
            'Laptop' => 'Computer/ Laptops',
            'Tablet' => 'Tablets',
            'Sonstiges' => 'Sonstiges',
        ];

        // Alle Kategorien aus geliehenen Geräten + reservierten Geräten
        $loanedGroups = $devices->where('status', 'loaned')->pluck('group');
        $reservedGroups = collect($reservations)->map(fn($r) => optional($r->device)->group);
        $allGroups = $loanedGroups->merge($reservedGroups)->filter()->unique()->sort()->values();
    @endphp

    <!-- Filter & Tabelle (eine Tabelle für beide Typen) -->
    <div class="lg:container mx-auto flex items-center mb-8 p-4 pt-4 bg-gray-600 rounded">
        <div class="w-full">
            <!-- Toolbar -->
            <div class="mb-4 flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <!-- Kategorie-Dropdown -->
                    <div class="relative inline-block text-left">
                        <button id="categoryButton" type="button"
                                class="inline-flex w-full sm:w-56 justify-between items-center rounded-md bg-gray-500 px-4 py-2 text-sm text-white shadow-sm hover:bg-gray-400 focus:outline-none"
                                aria-haspopup="true" aria-expanded="false" onclick="toggleCategoryMenu()">
                            <span id="categoryButtonLabel">Kategorie</span>
                            <svg class="h-5 w-5 ml-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="categoryMenu"
                             class="hidden absolute z-20 mt-2 w-full origin-top-left rounded-md bg-gray-500 shadow-lg ring-1 ring-black ring-opacity-5">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="categoryButton">
                                <a href="#" class="menu-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400 hover:text-white bg-gray-500"
                                   data-group="" data-name="Alle" role="menuitem">Alle</a>
                                @foreach ($allGroups as $group)
                                    @php $label = $labels[$group] ?? $group; @endphp
                                    <a href="#" class="menu-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400 hover:text-white bg-gray-500"
                                       data-group="{{ $group }}" data-name="{{ $label }}" role="menuitem">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Status-Dropdown -->
                    <div class="relative inline-block text-left">
                        <button id="typeButton" type="button"
                                class="inline-flex w-full sm:w-44 justify-between items-center rounded-md bg-gray-500 px-4 py-2 text-sm text-gray-100 shadow-sm hover:bg-gray-400 focus:outline-none"
                                aria-haspopup="true" aria-expanded="false" onclick="toggleTypeMenu()">
                            <span id="typeButtonLabel">Status</span>
                            <svg class="h-5 w-5 ml-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="typeMenu"
                             class="hidden absolute z-20 mt-2 w-full sm:w-44 origin-top-left rounded-md bg-gray-500 shadow-lg ring-1 ring-black ring-opacity-5">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="typeButton">
                                <a href="#" class="type-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400" data-type="all">Alle</a>
                                <a href="#" class="type-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400" data-type="loaned">Verliehen</a>
                                <a href="#" class="type-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400" data-type="reserved">Vorgemerkt</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suche -->
                <div class="sm:ml-auto w-full sm:w-auto">
                    <div class="relative">
                        <input id="searchInput" type="text" placeholder="Suchen: Name oder Beschreibung…"
                               class="w-full sm:w-80 rounded-md bg-gray-700 placeholder-gray-400 text-gray-200 px-4 py-2 text-sm
                               border border-gray-700 focus:outline-none focus:ring-0 focus:ring-transparent focus:border-gray-700"
                               autocomplete="off" />
                        <button type="button" id="clearSearch"
                                class="absolute top-1/2 right-2 -translate-y-1/2 p-1 text-gray-200 hover:text-white hidden"
                                aria-label="Eingabe löschen">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M6.225 4.811a1 1 0 0 1 1.414 0L12 9.172l4.361-4.361a1 1 0 1 1 1.414 1.414L13.414 10.586l4.361 4.361a1 1 0 0 1-1.414 1.414L12 12l-4.361 4.361a1 1 0 0 1-1.414-1.414l4.361-4.361-4.361-4.361a1 1 0 0 1 0-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div id="customMessage" style="display:none;" class="text-white text-sm ml-1 my-2">
                <h3 class="text-lg font-bold mb-2">Hinweis</h3>
                Wende dich an die Administration, wenn eine weitere Kategorie hinzugefügt werden soll.
            </div>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full bg-gray-700 text-white rounded-lg table-fixed text-left">
                    <thead>
                        <tr>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-24 font-medium text-sm">Bild</th>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-64 font-medium text-sm">Name & Label</th>
                        <th class="border-b-2 px-4 py-2 border-gray-500 w-32 font-medium text-sm">Beschreibung</th>
                        <th class="border-b-2 px-4 py-2 border-gray-500 w-40 font-medium text-sm"><span class="hidden lg:inline">Kategorie</span></th>
                        <th class="border-b-2 px-4 py-2 border-gray-500 w-32 font-medium text-sm">Status</th>
                        <th class="border-b-2 px-4 py-2 border-gray-500 w-90 font-medium text-sm">Person / Zeitraum</th>
                        <th class="border-b-2 px-4 py-2 border-gray-500 w-64 font-medium text-sm"></th>
                    </tr>
                </thead>
                <tbody id="mixTableBody">
                    {{-- Ausgeliehene Geräte --}}
                    @foreach ($devices as $device)
                        @if ($device->status == 'loaned')
                            <tr class="mix-row text-gray-300" data-type="loaned" data-group="{{ $device->category->name ?? $device->group }}">
                                <td class="border-b px-4 py-2 border-gray-600">
                                    <img src="{{ $device->image ? Storage::url($device->image) : asset('img/filler.png') }}"
                                         alt="{{ $device->title }}"
                                         class="w-16 h-16 object-cover cursor-pointer rounded border-2 hover:border-gray-400"
                                         onclick="openImageModal('{{ $device->image ? Storage::url($device->image) : asset('img/filler.png') }}')">
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                                    <a href="{{ route('devices.show', $device->id) }}"
                                       class="text-gray-300 hover:underline hover:text-white">{{ $device->title }}</a>
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                                    {{ $device->description }}
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                                    @switch($device->category->name ?? $device->group)
                                        @case('VRAR') VR-/AR-Brille @break
                                        @case('Videokonferenzsystem') Videokonf. @break
                                        @case('Microcontroller') Microcontr. @break
                                        @default {{ $device->category->name ?? $device->group }}
                                    @endswitch
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-xs">
                                    <span class="text-white bg-yellow-600 rounded-full py-1 px-2 inline-flex">Verliehen</span>
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm text-gray-300">
                                    {{ $device->borrower_name }}
                                    @if (!empty($device->loan_end_date))
                                        bis {{ \Carbon\Carbon::parse($device->loan_end_date)->format('d.m.Y') }}
                                    @endif
                                    @if (!empty($device->loan_purpose))
                                        <div class="text-sm text-gray-400 mt-1">
                                            {{ $device->loan_purpose }}
                                        </div>
                                    @endif
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm text-right">
                                    <form action="{{ route('devices.return') }}" method="POST" class="inline-block"
                                          onsubmit="return confirm('Wurde {{ $device->title }} vollständig und korrekt angenommen?');">
                                        @csrf
                                        <input type="hidden" name="device_id" value="{{ $device->id }}">
                                        <button type="submit"
                                                class="bg-gray-200 hover:bg-white text-black font-bold py-2 px-4 rounded text-xs"
                                                title="Gerät annehmnen">
                                            Annehmen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    {{-- Vorgemerkte Geräte --}}
                    @forelse($reservations as $res)
                        @php $dev = $res->device; @endphp
                        @if ($dev)
                            <tr class="mix-row text-gray-300" data-type="reserved" data-group="{{ $dev->group }}">
                                <td class="border-b px-4 py-2 border-gray-600">
                                    <img src="{{ $dev->image ? Storage::url($dev->image) : asset('img/filler.png') }}"
                                         alt="{{ $dev->title }}"
                                         class="w-16 h-16 object-cover cursor-pointer rounded border-2 hover:border-gray-400"
                                         onclick="openImageModal('{{ $dev->image ? Storage::url($dev->image) : asset('img/filler.png') }}')">
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                                    <a href="{{ route('devices.show', $dev->id) }}"
                                       class="text-gray-300 hover:underline hover:text-white">{{ $dev->title }}</a>
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                                    {{ $dev->description }}
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                                    @switch($dev->group)
                                        @case('VRAR') VR-/AR-Brille @break
                                        @case('Videokonferenzsystem') Videokonf. @break
                                        @case('Microcontroller') Microcontr. @break
                                        @default {{ $dev->group }}
                                    @endswitch
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-xs">
                                    @if ($res->status === 'approved')
                                        <span class="text-white bg-purple-600 rounded-full py-1 px-2 inline-flex">Vorgemerkt</span>
                                    @elseif($res->status === 'pending')
                                        <span class="text-white bg-purple-600 rounded-full py-1 px-2 inline-flex">Vorgemerkt</span>
                                    @else
                                        <span class="text-white bg-purple-500 rounded-full py-1 px-2 inline-flex">{{ ucfirst($res->status) }}</span>
                                    @endif
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm text-gray-400 ">
                                    <div class="text-sm text-gray-300 mb-1">
                                        {{ $res->reserved_by_name ?? optional($res->user)->name ?? 'Unbekannt' }}:
                                        {{ $res->start_at->timezone(config('app.timezone'))->format('d.m.Y') }}
                                        bis
                                        {{ $res->end_at->timezone(config('app.timezone'))->format('d.m.Y') }}
                                    </div>
                                    {{ $res->purpose ?? 'Keine Beschreibung vorhanden' }}
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm text-right">
                                    <div class="flex justify-end items-center">
                                        <!-- Verleihen mit Prefill aus der Vormerkung -->
                                        <button
                                            type="button"
                                            class="mr-2 hover:bg-yellow-600 hover:text-white text-gray-200 font-bold py-2 px-4 rounded text-xs js-loan-prefill"
                                            title="Aus Vormerkung verleihen"
                                            data-device-id="{{ $dev->id }}"
                                            data-borrower="{{ e($res->reserved_by_name ?? optional($res->user)->name) }}"
                                            data-start="{{ $res->start_at->timezone(config('app.timezone'))->format('Y-m-d') }}"
                                            data-end="{{ $res->end_at->timezone(config('app.timezone'))->format('Y-m-d') }}"
                                            data-purpose="{{ e($res->purpose) }}"
                                        >
                                            <span class="hidden xl:inline">Verleihen</span>
                                            <span class="xl:hidden">V</span>
                                        </button>

                                        @if ($res->status === 'pending' && $res->user_id === auth()->id())
                                            <form action="{{ route('devices.reservations.destroy', $res) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Willst du die Vormerkung wirklich widerrufen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center px-4 py-2 rounded bg-gray-600 hover:bg-gray-800 text-white text-xs font-medium"
                                                        title="Vormerkung widerrufen">
                                                    Stornieren
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        {{-- nichts --}}
                    @endforelse
                </tbody>
            </table>
            </div>
            
            <!-- Mobile Card View -->
            <div class="md:hidden space-y-4" id="mixCardContainer">
                {{-- Ausgeliehene Geräte --}}
                @foreach ($devices as $device)
                    @if ($device->status == 'loaned')
                        <div class="mix-card bg-gray-700 rounded-lg p-4" data-type="loaned" data-group="{{ $device->category->name ?? $device->group }}">
                            <div class="flex items-start gap-3">
                                <img src="{{ $device->image ? Storage::url($device->image) : asset('img/filler.png') }}"
                                    alt="{{ $device->title }}"
                                    class="w-16 h-16 object-cover rounded border-2 border-gray-600">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('devices.show', $device->id) }}"
                                        class="text-white font-medium hover:underline block truncate">{{ $device->title }}</a>
                                    <p class="text-gray-400 text-sm truncate">{{ $device->description }}</p>
                                    <div class="flex items-center gap-2 mt-2 flex-wrap">
                                        <span class="text-white bg-yellow-600 rounded-full py-1 px-2 text-xs">Verliehen</span>
                                    </div>
                                    <p class="text-gray-400 text-sm mt-2">
                                        {{ $device->borrower_name }}
                                        @if (!empty($device->loan_end_date))
                                            bis {{ \Carbon\Carbon::parse($device->loan_end_date)->format('d.m.Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-600">
                                <form action="{{ route('devices.return') }}" method="POST" class="flex-1"
                                    onsubmit="return confirm('Wurde {{ $device->title }} vollständig und korrekt angenommen?');">
                                    @csrf
                                    <input type="hidden" name="device_id" value="{{ $device->id }}">
                                    <button type="submit"
                                        class="w-full bg-gray-200 hover:bg-white text-black font-bold py-2 px-4 rounded text-xs">
                                        Annehmen
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Vorgemerkte Geräte --}}
                @forelse($reservations as $res)
                    @php $dev = $res->device; @endphp
                    @if ($dev)
                        <div class="mix-card bg-gray-700 rounded-lg p-4" data-type="reserved" data-group="{{ $dev->group }}">
                            <div class="flex items-start gap-3">
                                <img src="{{ $dev->image ? Storage::url($dev->image) : asset('img/filler.png') }}"
                                    alt="{{ $dev->title }}"
                                    class="w-16 h-16 object-cover rounded border-2 border-gray-600">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('devices.show', $dev->id) }}"
                                        class="text-white font-medium hover:underline block truncate">{{ $dev->title }}</a>
                                    <p class="text-gray-400 text-sm truncate">{{ $dev->description }}</p>
                                    <div class="flex items-center gap-2 mt-2 flex-wrap">
                                        <span class="text-white bg-purple-600 rounded-full py-1 px-2 text-xs">Vorgemerkt</span>
                                    </div>
                                    <p class="text-gray-400 text-sm mt-2">
                                        {{ $res->reserved_by_name ?? optional($res->user)->name ?? 'Unbekannt' }}:
                                        {{ $res->start_at->timezone(config('app.timezone'))->format('d.m.Y') }} bis
                                        {{ $res->end_at->timezone(config('app.timezone'))->format('d.m.Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-600">
                                <button type="button"
                                    class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-3 rounded text-xs js-loan-prefill"
                                    data-device-id="{{ $dev->id }}"
                                    data-borrower="{{ e($res->reserved_by_name ?? optional($res->user)->name) }}"
                                    data-start="{{ $res->start_at->timezone(config('app.timezone'))->format('Y-m-d') }}"
                                    data-end="{{ $res->end_at->timezone(config('app.timezone'))->format('Y-m-d') }}"
                                    data-purpose="{{ e($res->purpose) }}">
                                    Verleihen
                                </button>
                                @if ($res->status === 'pending' && $res->user_id === auth()->id())
                                    <form action="{{ route('devices.reservations.destroy', $res) }}" method="POST" class="flex-1"
                                        onsubmit="return confirm('Willst du die Vormerkung wirklich widerrufen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="w-full bg-gray-600 hover:bg-gray-800 text-white py-2 px-3 rounded text-xs font-medium">
                                            Stornieren
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty
                @endforelse
            </div>
        </div>
    </div>

    <!-- Verleihen Modal (Overview, identisch zum Index + Breitenfix) -->
    <div id="loanModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="loan-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full sm:flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6" id="loan-modal-title">Vormerkung übernehmen und Gerät verleihen</h3>

                            <div class="mt-2">
                                <form id="loanForm" action="{{ route('devices.loan') }}" method="POST" class="w-full">
                                    @csrf
                                    <input type="hidden" name="device_id" id="device_id">

                                    <div class="mb-4">
                                        <label for="borrower_name" class="block text-gray-700 text-sm font-bold mb-2">Name der Person:</label>
                                        <input type="text" name="borrower_name" id="borrower_name" required
                                               class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                               placeholder="An wen wird das Gerät verliehen?">
                                    </div>

                                    <div class="mb-4">
                                        <label for="loan_start_date" class="block text-gray-700 text-sm font-bold mb-2">Anfangsdatum:</label>
                                        <input type="date" name="loan_start_date" id="loan_start_date" required
                                               class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
                                    </div>

                                    <div class="mb-4">
                                        <label for="loan_end_date" class="block text-gray-700 text-sm font-bold mb-2">Enddatum:</label>
                                        <input type="date" name="loan_end_date" id="loan_end_date" required
                                               class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
                                    </div>

                                    <div class="mb-4">
                                        <label for="loan_purpose" class="block text-gray-700 text-sm font-bold mb-2">
                                            Person / Kontext (max. 255 Zeichen):
                                        </label>
                                        <textarea name="loan_purpose" id="loan_purpose" rows="3" maxlength="255"
                                                  class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                                  placeholder="In welchem Kontext wird das Gerät verliehen?"></textarea>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <strong>Beispiel:</strong> Blockseminar „Video in der Lehre“.
                                        </p>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="bg-white px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="submitLoanForm()"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 text-base font-medium text-white hover:bg-black sm:ml-3 sm:w-auto sm:text-sm">
                        Verleihen
                    </button>
                    <button type="button" onclick="closeLoanModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-gray-100 text-base font-medium text-black hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex justify-center items-center z-10" onclick="closeImageModal()">
        <div class="bg-white p-1 rounded-lg relative" onclick="event.stopPropagation()">
            <span class="absolute top-2 right-2 p-2 cursor-pointer text-white" onclick="closeImageModal()">
                <svg height="36" width="36" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="text-white">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                </svg>
            </span>
            <img id="modalImage" src="" alt="Image" class="max-w-screen-md max-h-screen-md rounded-lg">
        </div>
    </div>

    <style>
        .tab-button:focus { outline: none; }
    </style>

    <script>
        /** ------- Modal ------- **/
        function openImageModal(imageUrl) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('imageModal').classList.remove('hidden');
        }
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        /** ------- Helpers ------- **/
        function getNameFromRow(row) {
            const link = row.querySelector('td:nth-child(2) a');
            const text = link ? link.textContent : row.querySelector('td:nth-child(2)')?.textContent || '';
            return text.trim();
        }
        function getDescFromRow(row) {
            const cell = row.querySelector('td:nth-child(3)');
            return (cell?.textContent || '').trim();
        }

        /** ------- Filter-State ------- **/
        const filters = { group: '', groupName: 'Alle', type: 'all', query: '' };

        /** ------- Apply Filters + Sort ------- **/
        function applyFilters() {
            const rows = Array.from(document.querySelectorAll('.mix-row'));
            const cards = Array.from(document.querySelectorAll('.mix-card'));
            const body = document.getElementById('mixTableBody');
            const q = (filters.query || '').toLocaleLowerCase('de');

            // Filter desktop table rows
            rows.forEach(row => {
                const rowGroup = row.dataset.group || '';
                const rowType = row.dataset.type || '';

                const inGroup = !filters.group || rowGroup === filters.group;
                const inType = (filters.type === 'all') || (rowType === filters.type);

                let inSearch = true;
                if (q) {
                    const name = getNameFromRow(row).toLocaleLowerCase('de');
                    const desc = getDescFromRow(row).toLocaleLowerCase('de');
                    inSearch = name.includes(q) || desc.includes(q);
                }

                row.style.display = (inGroup && inType && inSearch) ? '' : 'none';
            });
            
            // Filter mobile cards
            cards.forEach(card => {
                const cardGroup = card.dataset.group || '';
                const cardType = card.dataset.type || '';
                
                const inGroup = !filters.group || cardGroup === filters.group;
                const inType = (filters.type === 'all') || (cardType === filters.type);
                
                let inSearch = true;
                if (q) {
                    const name = (card.querySelector('a')?.textContent || '').toLocaleLowerCase('de');
                    const desc = (card.querySelector('p.text-gray-400')?.textContent || '').toLocaleLowerCase('de');
                    inSearch = name.includes(q) || desc.includes(q);
                }
                
                card.style.display = (inGroup && inType && inSearch) ? '' : 'none';
            });

            const visible = rows.filter(r => r.style.display !== 'none');
            visible.sort((a, b) => {
                const an = getNameFromRow(a).toLocaleLowerCase('de');
                const bn = getNameFromRow(b).toLocaleLowerCase('de');
                return an.localeCompare(bn, 'de', { sensitivity: 'base', numeric: true });
            });
            visible.forEach(r => body.appendChild(r));

            const breadcrumb = document.getElementById('current-category-breadcrumb');
            if (breadcrumb) {
                const label = filters.groupName || 'Alle';
                const typeLabel = filters.type === 'all' ? 'Alle' : (filters.type === 'loaned' ? 'Verliehen' : 'Vorgemerkt');
                breadcrumb.innerHTML = `<span class="mx-2 text-yellow-600">/</span><span class="text-gray-500">${label} / ${typeLabel}</span>`;
            }
        }

        /** ------- Dropdowns ------- **/
        function toggleCategoryMenu() {
            const menu = document.getElementById('categoryMenu');
            menu.classList.toggle('hidden');
            document.getElementById('categoryButton').setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
        }
        function closeCategoryMenu() {
            const menu = document.getElementById('categoryMenu');
            if (!menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                document.getElementById('categoryButton').setAttribute('aria-expanded', 'false');
            }
        }
        document.addEventListener('click', (e) => {
            const button = document.getElementById('categoryButton');
            const menu = document.getElementById('categoryMenu');
            const item = e.target.closest('.menu-item');

            if (!button.contains(e.target) && !menu.contains(e.target)) closeCategoryMenu();

            if (item) {
                e.preventDefault();
                filters.group = item.dataset.group || '';
                filters.groupName = item.dataset.name || 'Alle';
                document.getElementById('categoryButtonLabel').textContent = (filters.groupName === 'Alle') ? 'Kategorie' : filters.groupName;
                closeCategoryMenu();
                applyFilters();
            }
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCategoryMenu(); });

        function toggleTypeMenu() {
            const menu = document.getElementById('typeMenu');
            menu.classList.toggle('hidden');
            document.getElementById('typeButton').setAttribute('aria-expanded', !menu.classList.contains('hidden'));
        }
        function closeTypeMenu() {
            const menu = document.getElementById('typeMenu');
            if (!menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                document.getElementById('typeButton').setAttribute('aria-expanded', 'false');
            }
        }
        document.addEventListener('click', (e) => {
            const typeButton = document.getElementById('typeButton');
            const typeMenu = document.getElementById('typeMenu');
            const item = e.target.closest('.type-item');

            if (!typeButton.contains(e.target) && !typeMenu.contains(e.target)) closeTypeMenu();

            if (item) {
                e.preventDefault();
                filters.type = item.dataset.type; // 'all' | 'loaned' | 'reserved'
                document.getElementById('typeButtonLabel').textContent =
                    (filters.type === 'all' ? 'Status' : (filters.type === 'loaned' ? 'Verliehen' : 'Vorgemerkt'));
                closeTypeMenu();
                applyFilters();
            }
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeTypeMenu(); });

        /** ------- Suche ------- **/
        (function initLiveSearch() {
            const input = document.getElementById('searchInput');
            const clear = document.getElementById('clearSearch');
            if (!input) return;

            const onInput = () => {
                filters.query = input.value || '';
                clear.classList.toggle('hidden', !filters.query);
                applyFilters();
            };
            input.addEventListener('input', onInput);

            if (clear) {
                clear.addEventListener('click', () => {
                    input.value = '';
                    filters.query = '';
                    clear.classList.add('hidden');
                    input.focus();
                    applyFilters();
                });
            }
        })();

        /** ------- Init ------- **/
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('categoryButtonLabel').textContent = 'Kategorie';
            document.getElementById('typeButtonLabel').textContent = 'Status';
            applyFilters();
        });
    </script>

    <!-- Verleih-Modal: Öffnen + Prefill aus data-* -->
    <script>
        function openLoanModal(deviceId, defaults = {}) {
            document.getElementById('device_id').value = deviceId;

            const $borrower = document.getElementById('borrower_name');
            const $start    = document.getElementById('loan_start_date');
            const $end      = document.getElementById('loan_end_date');
            const $purpose  = document.getElementById('loan_purpose');

            if ($borrower) $borrower.value = defaults.borrower_name ?? '';
            if ($start)    $start.value    = defaults.start_date ?? '';
            if ($end)      $end.value      = defaults.end_date ?? '';
            if ($purpose)  $purpose.value  = defaults.purpose ?? '';

            document.getElementById('loanModal').classList.remove('hidden');
        }
        function closeLoanModal() {
            document.getElementById('loanModal').classList.add('hidden');
        }
        function submitLoanForm() {
            document.getElementById('loanForm').submit();
        }

        // Delegation: Klick auf Button mit .js-loan-prefill
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-loan-prefill');
            if (!btn) return;

            e.preventDefault();
            openLoanModal(btn.dataset.deviceId, {
                borrower_name: btn.dataset.borrower || '',
                start_date:    btn.dataset.start    || '',
                end_date:      btn.dataset.end      || '',
                purpose:       btn.dataset.purpose  || ''
            });
        });
    </script>
@endsection
