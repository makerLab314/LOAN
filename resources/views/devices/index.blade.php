@extends('layouts.app')

@section('title', 'Geräte')

@section('content')

    <!-- Breadcrumbs -->
    <nav id="breadcrumb-nav" class="flex text-sm mb-8" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="text-yellow-600 hover:underline flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-1">
                <path
                    d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                <path
                    d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
            </svg>
        </a>
        <span class="mx-2 text-yellow-600">/</span>
        <a href="{{ route('devices.index') }}" class="text-yellow-600 hover:underline">{{ __('Geräte') }}</a>
        <span id="current-category-breadcrumb"></span>
    </nav>

    <h1 class="text-2xl font-bold mb-4">{{ __('Geräte') }}</h1>
    <p class="block items-center text-sm mb-4">Eine Übersicht über alle Geräte. Klicke in der entsprechenden Spalte auf
        einen der Buttons, um ein <strong>Gerät zu verleihen oder vorzumerken</strong>.</p>
    <p class="block items-center text-sm mb-8">
        <a href="{{ route('devices.overview') }}" class="hover:underline text-yellow-600 flex items-center">
            Du möchtest zu den gebuchten Geräten? Folge mir!
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" height="18" width="18"
                class="ml-1">
                <path fill-rule="evenodd"
                    d="M19.902 4.098a3.75 3.75 0 0 0-5.304 0l-4.5 4.5a3.75 3.75 0 0 0 1.035 6.037.75.75 0 0 1-.646 1.353 5.25 5.25 0 0 1-1.449-8.45l4.5-4.5a5.25 5.25 0 1 1 7.424 7.424l-1.757 1.757a.75.75 0 1 1-1.06-1.06l1.757-1.757a3.75 3.75 0 0 0 0-5.304Zm-7.389 4.267a.75.75 0 0 1 1-.353 5.25 5.25 0 0 1 1.449 8.45l-4.5 4.5a5.25 5.25 0 1 1-7.424-7.424l1.757-1.757a.75.75 0 1 1 1.06 1.06l-1.757 1.757a3.75 3.75 0 1 0 5.304 5.304l4.5-4.5a3.75 3.75 0 0 0-1.035-6.037.75.75 0 0 1-.354-1Z"
                    clip-rule="evenodd" />
            </svg>
        </a>
    </p>
    @if (session('status'))
        <div id="alert" role="alert" class="mb-8 rounded-md border border-gray-300 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 text-green-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
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
        // Kategorien aus den vorhandenen Geräten sammeln
        $groupedDevices = $devices->groupBy('group');
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
        $availableGroups = $groupedDevices->keys()->all();
    @endphp

    <!-- GRAUER BLOCK mit Dropdown + Tabelle -->
    <div class="lg:container mx-auto flex items-center mb-8 p-4 pt-4 bg-gray-600 rounded">
        <div class="w-full">



            <div class="mb-4 flex flex-wrap justify-end gap-3">
                <!-- Gerät hinzufügen -->
                <a href="{{ route('devices.create') }}"
                    class="inline-flex items-center pl-3 pr-4 py-2 rounded-md bg-gray-600 text-white text-sm font-medium hover:bg-yellow-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="hidden sm:inline">Gerät hinzufügen</span>
                    <span class="sm:hidden">Neu</span>
                </a>

                <!-- Kategorien verwalten -->
                <a href="{{ route('categories.index') }}"
                    class="inline-flex items-center px-4 py-2 rounded-md bg-gray-600 text-white text-sm font-medium bg-gray-700 hover:bg-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 sm:mr-3">
                        <path fill-rule="evenodd"
                            d="M3 5.25a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.25Zm0 4.5A.75.75 0 0 1 3.75 9h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 9.75Zm0 4.5a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Zm0 4.5a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z"
                            clip-rule="evenodd" />
                    </svg>


                    <span class="hidden sm:inline">Kategorien verwalten</span>
                </a>
            </div>


            <!-- Toolbar: Dropdowns links, Suche rechts -->
            <div class="mb-4 flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2">
                <!-- LINKS: Geräte + Status -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <!-- Geräte-Dropdown -->
                    <div class="relative inline-block text-left">
                        <button id="categoryButton" type="button"
                            class="inline-flex w-full sm:w-56 justify-between items-center rounded-md bg-gray-500 px-4 py-2 text-sm text-white shadow-sm hover:bg-gray-400 focus:outline-none"
                            aria-haspopup="true" aria-expanded="false" onclick="toggleCategoryMenu()">
                            <span id="categoryButtonLabel">Geräte</span>
                            <svg class="h-5 w-5 ml-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="categoryMenu"
                            class="hidden absolute z-20 mt-2 w-full origin-top-left rounded-md bg-gray-500 shadow-lg ring-1 ring-black ring-opacity-5">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="categoryButton">
                                <a href="#"
                                    class="menu-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400 hover:text-white bg-gray-500"
                                    data-group="" data-name="Alle" role="menuitem">Alle</a>
                                @foreach ($availableGroups as $group)
                                    @php $label = $labels[$group] ?? $group; @endphp
                                    <a href="#"
                                        class="menu-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400 hover:text-white bg-gray-500"
                                        data-group="{{ $group }}" data-name="{{ $label }}"
                                        role="menuitem">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Status-Dropdown -->
                    <div class="relative inline-block text-left">
                        <button id="statusButton" type="button"
                            class="inline-flex w-full sm:w-40 justify-between items-center rounded-md bg-gray-500 px-4 py-2 text-sm text-gray-100 shadow-sm hover:bg-gray-400 focus:outline-none"
                            aria-haspopup="true" aria-expanded="false" onclick="toggleStatusMenu()">
                            <span id="statusButtonLabel">Status</span>
                            <svg class="h-5 w-5 ml-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="statusMenu"
                            class="hidden absolute z-20 mt-2 w-full sm:w-40 origin-top-left rounded-md bg-gray-500 shadow-lg ring-1 ring-black ring-opacity-5">
                            <div class="py-1" role="menu" aria-orientation="vertical"
                                aria-labelledby="statusButton">
                                <a href="#"
                                    class="status-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400"
                                    data-status="all">Alle</a>
                                <a href="#"
                                    class="status-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400"
                                    data-status="available">Verfügbar</a>
                                <a href="#"
                                    class="status-item block px-4 py-2 text-sm text-gray-100 hover:bg-gray-400"
                                    data-status="loaned">Verliehen</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RECHTS: Suche -->
                <div class="sm:ml-auto w-full sm:w-auto">
                    <div class="relative">
                        <input id="searchInput" type="text" placeholder="Suchen: Name oder Beschreibung…"
                            class="w-full sm:w-80 rounded-md bg-gray-700 placeholder-gray-400 text-gray-200 px-4 py-2 text-sm
                    border border-gray-700 focus:outline-none focus:ring-0 focus:ring-transparent focus:border-gray-700"
                            autocomplete="off" />
                        <button type="button" id="clearSearch"
                            class="absolute top-1/2 right-2 -translate-y-1/2 p-1 text-gray-200 hover:text-white hidden"
                            aria-label="Eingabe löschen">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M6.225 4.811a1 1 0 0 1 1.414 0L12 9.172l4.361-4.361a1 1 0 1 1 1.414 1.414L13.414 10.586l4.361 4.361a1 1 0 0 1-1.414 1.414L12 12l-4.361 4.361a1 1 0 0 1-1.414-1.414l4.361-4.361-4.361-4.361a1 1 0 0 1 0-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full bg-gray-700 text-white rounded-lg table-fixed text-left">
                    <thead>
                        <tr>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-24 font-medium text-sm">Bild</th>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-64 font-medium text-sm">Name & Label</th>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-32 font-medium text-sm">Beschreibung</th>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-32 font-medium text-sm"><span class="hidden lg:inline">Kategorie</span></th>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-24 font-medium text-sm">Anzahl</th>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-32 font-medium text-sm">Status</th>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-48 font-medium text-sm">Person / Zeitraum</th>
                            <th class="border-b-2 px-4 py-2 border-gray-500 w-48 font-medium text-sm"></th>
                        </tr>
                    </thead>
                    <tbody id="deviceTableBody">
                        @foreach ($devices as $device)
                            <tr class="device-row" data-group="{{ trim($device->category->name ?? $device->group) }}" 
                                data-available-qty="{{ $device->available_quantity }}" 
                                data-total-qty="{{ $device->total_quantity }}">
                                <td class="border-b px-4 py-2 border-gray-600">
                                    <img src="{{ $device->image ? Storage::url($device->image) : asset('img/filler.png') }}"
                                        alt="{{ $device->title }}"
                                        class="w-8 lg:w-16 h-8 lg:h-16 object-cover cursor-pointer rounded border-2 hover:border-gray-400"
                                        onclick="openImageModal('{{ $device->image ? Storage::url($device->image) : asset('img/filler.png') }}')">
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words">
                                    <a href="{{ route('devices.show', $device->id) }}"
                                        class="text-gray-300 hover:underline hover:text-white">{{ $device->title }}</a>
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words text-gray-300">
                                    {{ $device->description }}</td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm break-words text-gray-300">
                                    @switch($device->category->name ?? $device->group)
                                        @case('VRAR')
                                            VR-/AR-Brille
                                        @break
                                        @case('Videokonferenzsystem')
                                            Videokonf.
                                        @break
                                        @case('Microcontroller')
                                            Microcontr.
                                        @break
                                        @default
                                            {{ $device->category->name ?? $device->group }}
                                    @endswitch
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-sm text-gray-300">
                                    @if($device->total_quantity > 1)
                                        <span class="font-medium">{{ $device->available_quantity }}/{{ $device->total_quantity }}</span>
                                    @else
                                        1
                                    @endif
                                </td>
                                <td class="border-b px-4 py-2 border-gray-600 text-xs">
                                    @if($device->available_quantity > 0)
                                        <span class="text-white bg-green-600 rounded-full py-1 px-2 inline-flex">
                                            Verfügbar
                                        </span>
                                    @else
                                        <span class="text-white bg-yellow-600 rounded-full py-1 px-2 inline-flex">
                                            Verliehen
                                        </span>
                                    @endif
                                </td>
                                @if ($device->borrower_name)
                                    <td class="border-b px-4 py-2 border-gray-600 text-sm text-gray-300">
                                        {{ $device->borrower_name }} bis
                                        {{ \Carbon\Carbon::parse($device->loan_end_date)->format('d.m.Y') }}
                                        @if (!empty($device->loan_purpose))
                                            <div class="text-sm text-gray-400 mt-1">
                                                {{ $device->loan_purpose }}
                                            </div>
                                        @endif
                                    </td>
                                @else
                                    <td class="border-b px-4 py-2 border-gray-600 text-sm text-gray-300">
                                        Momentan verfügbar
                                    </td>
                                @endif
                                <td class="border-b px-4 py-2 border-gray-600 text-sm text-left">
                                    <div class="flex justify-end items-center flex-wrap gap-1">
                                        @if ($device->available_quantity > 0)
                                            <button onclick="openLoanModal({{ $device->id }}, {{ $device->available_quantity }}, {{ $device->total_quantity }})"
                                                class="mr-2 hover:bg-yellow-600 hover:text-white text-gray-200 font-bold py-2 px-4 rounded text-xs">
                                                <span class="hidden xl:inline">Verleihen</span>
                                                <span class="xl:hidden">V</span>
                                            </button>
                                        @endif
                                        <button type="button" onclick="openReservationModal({{ $device->id }})"
                                            class="inline-flex items-center px-4 py-2 rounded bg-gray-600 hover:bg-gray-800 text-white text-xs font-medium">
                                            Vormerken
                                        </button>
                                        <a href="{{ route('devices.edit', $device) }}"
                                            class="py-2 pl-2 lg:pl-4 pr-2 rounded text-gray-300 hover:text-white">
                                            <svg height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="currentColor" class="size-6">
                                                <path
                                                    d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
                                                <path
                                                    d="M5.25 5.25a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3V13.5a.75.75 0 0 0-1.5 0v5.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V8.25a1.5 1.5 0 0 1 1.5-1.5h5.25a.75.75 0 0 0 0-1.5H5.25Z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('devices.destroy', $device) }}" method="POST"
                                            class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="py-2 px-0 rounded text-gray-300 hover:text-white"
                                                onclick="return confirm('Sind Sie sicher, dass Sie dieses Gerät löschen möchten?')">
                                                <svg height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                    fill="currentColor" class="size-6">
                                                    <path fill-rule="evenodd"
                                                        d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z"
                                                        clip-rule="evenodd" />
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

            <!-- Mobile Card View -->
            <div class="md:hidden space-y-4" id="deviceCardContainer">
                @foreach ($devices as $device)
                    <div class="device-card bg-gray-700 rounded-lg p-4" data-group="{{ trim($device->category->name ?? $device->group) }}">
                        <div class="flex items-start gap-3">
                            <img src="{{ $device->image ? Storage::url($device->image) : asset('img/filler.png') }}"
                                alt="{{ $device->title }}"
                                class="w-16 h-16 object-cover rounded border-2 border-gray-600"
                                onclick="openImageModal('{{ $device->image ? Storage::url($device->image) : asset('img/filler.png') }}')">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('devices.show', $device->id) }}"
                                    class="text-white font-medium hover:underline block truncate">{{ $device->title }}</a>
                                <p class="text-gray-400 text-sm truncate">{{ $device->description }}</p>
                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                    @if($device->available_quantity > 0)
                                        <span class="text-white bg-green-600 rounded-full py-1 px-2 text-xs">Verfügbar</span>
                                    @else
                                        <span class="text-white bg-yellow-600 rounded-full py-1 px-2 text-xs">Verliehen</span>
                                    @endif
                                    @if($device->total_quantity > 1)
                                        <span class="text-gray-300 text-xs">{{ $device->available_quantity }}/{{ $device->total_quantity }} verfügbar</span>
                                    @endif
                                </div>
                                @if ($device->borrower_name)
                                    <p class="text-gray-400 text-sm mt-2">
                                        {{ $device->borrower_name }} bis {{ \Carbon\Carbon::parse($device->loan_end_date)->format('d.m.Y') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-600">
                            @if ($device->available_quantity > 0)
                                <button onclick="openLoanModal({{ $device->id }}, {{ $device->available_quantity }}, {{ $device->total_quantity }})"
                                    class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-3 rounded text-xs">
                                    Verleihen
                                </button>
                            @endif
                            <button type="button" onclick="openReservationModal({{ $device->id }})"
                                class="flex-1 bg-gray-600 hover:bg-gray-800 text-white py-2 px-3 rounded text-xs font-medium">
                                Vormerken
                            </button>
                            <a href="{{ route('devices.edit', $device) }}"
                                class="py-2 px-3 rounded bg-gray-600 hover:bg-gray-800 text-white text-xs">
                                Bearbeiten
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="imageModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex justify-center items-center z-10"
        onclick="closeImageModal()">
        <div class="bg-white p-1 rounded-lg relative" onclick="event.stopPropagation()">
            <span class="absolute top-2 right-2 p-2 cursor-pointer text-white" onclick="closeImageModal()">
                <svg height="36" width="36" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                    fill="currentColor" class="text-white">
                    <path fill-rule="evenodd"
                        d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                        clip-rule="evenodd" />
                </svg>
            </span>
            <img id="modalImage" src="" alt="Image" class="max-w-screen-md max-h-screen-md rounded-lg">
        </div>
    </div>

    <!-- Verleihen Modal -->
    <div id="loanModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full sm:flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6" id="modal-title">Gerät verleihen</h3>
                            <div class="mt-2">
                                <form id="loanForm" action="{{ route('devices.loan') }}" method="POST"
                                    class="w-full">
                                    @csrf
                                    <input type="hidden" name="device_id" id="device_id">
                                    
                                    <!-- Quantity field (shown only for multi-quantity devices) -->
                                    <div class="mb-4" id="loan_quantity_container" style="display: none;">
                                        <label for="loan_quantity" class="block text-gray-700 text-sm font-bold mb-2">
                                            Anzahl ausleihen:
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="loan_quantity" id="loan_quantity" min="1" value="1"
                                                class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-24 py-2 px-3 text-gray-700 leading-tight">
                                            <span class="text-gray-600 text-sm">von <span id="available_quantity_display">1</span> verfügbar</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Wähle die Anzahl der Geräte, die du ausleihen möchtest.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="borrower_name" class="block text-gray-700 text-sm font-bold mb-2">Name
                                            der Person:</label>
                                        <input type="text" name="borrower_name" id="borrower_name"
                                            class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                            required placeholder="An wen wird das Gerät verliehen?">
                                    </div>
                                    <div class="mb-4">
                                        <label for="loan_start_date"
                                            class="block text-gray-700 text-sm font-bold mb-2">Anfangsdatum:</label>
                                        <input type="date" name="loan_start_date" id="loan_start_date"
                                            class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                            required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="loan_end_date"
                                            class="block text-gray-700 text-sm font-bold mb-2">Enddatum:</label>
                                        <input type="date" name="loan_end_date" id="loan_end_date"
                                            class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                            required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="loan_purpose" class="block text-gray-700 text-sm font-bold mb-2">
                                            Person / Kontext (max. 255 Zeichen):
                                        </label>
                                        <textarea name="loan_purpose" id="loan_purpose" rows="3" maxlength="255"
                                            class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                            placeholder="In welchem Kontext wird das Gerät verliehen?"></textarea>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <strong>Beispiel:</strong> Blockseminar
                                            „Videos in der Lehre“.
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="submitLoanForm()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 text-base font-medium text-white hover:bg-black sm:ml-3 sm:w-auto sm:text-sm">Verleihen</button>
                    <button type="button" onclick="closeLoanModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-gray-100 text-base font-medium text-black hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Abbrechen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservieren Modal -->
    <div id="reservationModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="reservation-title"
        role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all
                sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6" id="reservation-title">
                                Gerät vormerken
                            </h3>

                            <div class="mt-2">
                                <form id="reservationForm" method="POST"
                                    data-action-template="{{ route('devices.reservations.store', ['device' => '__DEVICE_ID__']) }}">
                                    @csrf
                                    {{-- Falls du lieber mit hidden device_id arbeitest, kannst du das zusätzlich nutzen --}}
                                    <input type="hidden" name="device_id" id="reservation_device_id">

                                    <div class="mb-4">
                                        <label for="res_person" class="block text-gray-700 text-sm font-bold mb-2">
                                            Person (max. 255 Zeichen):
                                        </label>
                                        <input type="text" name="reserved_by_name" id="res_person" maxlength="255"
                                            class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                            placeholder="Für wen wird das Gerät vorgemerkt?">
                                    </div>

                                    <div class="mb-4">
                                        <label for="res_start_date"
                                            class="block text-gray-700 text-sm font-bold mb-2">Startdatum:</label>
                                        <input type="date" name="start_date" id="res_start_date"
                                            class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                            required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="res_end_date"
                                            class="block text-gray-700 text-sm font-bold mb-2">Enddatum:</label>
                                        <input type="date" name="end_date" id="res_end_date"
                                            class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                            required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="res_purpose" class="block text-gray-700 text-sm font-bold mb-2">
                                            Person / Kontext (max. 255 Zeichen):
                                        </label>
                                        <textarea name="purpose" id="res_purpose" rows="3" maxlength="255"
                                            class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                                            placeholder="In welchem Kontext wird das Gerät verliehen?"></textarea>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <strong>Beispiel:</strong> Blockseminar
                                            „Videos in der Lehre“.
                                        </p>
                                    </div>

                                    {{-- Optional: Hidden Zeitfelder (werden im Backend trotzdem hart auf 08:00/17:00 gesetzt) --}}
                                    <input type="hidden" name="start_time" value="08:00">
                                    <input type="hidden" name="end_time" value="17:00">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="submitReservationForm()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-800 text-base font-medium text-white hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Vormerken
                    </button>
                    <button type="button" onclick="closeReservationModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-gray-100 text-base font-medium text-black hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    </div>


    <style>
        .tab-button:focus {
            outline: none;
        }
    </style>

    <script>
        // --- bestehende Utilities ---
        function openImageModal(imageUrl) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        function openLoanModal(deviceId, availableQty = 1, totalQty = 1) {
            document.getElementById('device_id').value = deviceId;
            
            // Handle quantity display
            const qtyContainer = document.getElementById('loan_quantity_container');
            const qtyInput = document.getElementById('loan_quantity');
            const qtyDisplay = document.getElementById('available_quantity_display');
            
            if (totalQty > 1 && qtyContainer && qtyInput && qtyDisplay) {
                qtyContainer.style.display = 'block';
                qtyInput.max = availableQty;
                qtyInput.value = 1;
                qtyDisplay.textContent = availableQty;
            } else if (qtyContainer) {
                qtyContainer.style.display = 'none';
            }
            
            document.getElementById('loanModal').classList.remove('hidden');
        }

        function closeLoanModal() {
            document.getElementById('loanModal').classList.add('hidden');
        }

        function submitLoanForm() {
            document.getElementById('loanForm').submit();
        }

        function confirmReturn(deviceId, deviceTitle, deviceDescription) {
            const message =
                `Wurde ${deviceTitle} zurückgegeben und in den entsprechenden Aufbewahrungsort ${deviceDescription} zurückgeräumt?`;
            if (confirm(message)) {
                document.getElementById(`return-form-${deviceId}`).submit();
            }
        }

        // --- Helpers ---
        function getDeviceNameFromRow(row) {
            const link = row.querySelector('td:nth-child(2) a');
            const text = link ? link.textContent : row.querySelector('td:nth-child(2)')?.textContent || '';
            return text.trim();
        }

        function getDeviceDescriptionFromRow(row) {
            const cell = row.querySelector('td:nth-child(3)');
            return (cell?.textContent || '').trim();
        }

        // Zentraler Filter-State
        const currentFilters = {
            group: '', // '' = Alle
            groupName: 'Alle', // für Breadcrumb / Button-Label
            status: 'all', // 'all' | 'available' | 'loaned'
            query: '' // Suchbegriff
        };

        // Hauptfunktion: wendet alle Filter + Sortierung an
        function applyFilters() {
            const tableBody = document.getElementById('deviceTableBody');
            const rows = Array.from(document.querySelectorAll('.device-row'));
            const cards = Array.from(document.querySelectorAll('.device-card'));
            const table = document.querySelector('table');
            const message = document.getElementById('customMessage');

            // "Neu" Spezialfall wie gehabt (falls du ihn nutzt)
            if (currentFilters.group === 'Neu') {
                if (table) table.style.display = 'none';
                if (message) message.style.display = 'block';
                return;
            } else {
                if (table) table.style.display = '';
                if (message) message.style.display = 'none';
            }

            const q = currentFilters.query.toLocaleLowerCase('de');

            // 1) Filtern Desktop-Tabelle (Kategorie + Status + Suche)
            rows.forEach(row => {
                // Kategorie
                const inGroup = !currentFilters.group || row.dataset.group === currentFilters.group;

                // Status (wir lesen den farbigen Badge aus Spalte 6 nach Anzahl-Spalte)
                const isAvailable = row.querySelector('td:nth-child(6) span')?.classList.contains('bg-green-600');
                let inStatus = true;
                if (currentFilters.status === 'available') inStatus = !!isAvailable;
                if (currentFilters.status === 'loaned') inStatus = !isAvailable;

                // Suche (Name + Beschreibung)
                let inSearch = true;
                if (q) {
                    const name = getDeviceNameFromRow(row).toLocaleLowerCase('de');
                    const desc = getDeviceDescriptionFromRow(row).toLocaleLowerCase('de');
                    inSearch = name.includes(q) || desc.includes(q);
                }

                row.style.display = (inGroup && inStatus && inSearch) ? '' : 'none';
            });

            // 1b) Filtern Mobile-Karten
            cards.forEach(card => {
                const inGroup = !currentFilters.group || card.dataset.group === currentFilters.group;
                
                // Status from card badge
                const isAvailable = card.querySelector('.bg-green-600') !== null;
                let inStatus = true;
                if (currentFilters.status === 'available') inStatus = !!isAvailable;
                if (currentFilters.status === 'loaned') inStatus = !isAvailable;
                
                // Suche
                let inSearch = true;
                if (q) {
                    const name = (card.querySelector('a')?.textContent || '').toLocaleLowerCase('de');
                    const desc = (card.querySelector('p.text-gray-400')?.textContent || '').toLocaleLowerCase('de');
                    inSearch = name.includes(q) || desc.includes(q);
                }
                
                card.style.display = (inGroup && inStatus && inSearch) ? '' : 'none';
            });

            // 2) Sichtbare Zeilen sortieren (Name, de, numeric)
            const visibleRows = rows.filter(r => r.style.display !== 'none');
            visibleRows.sort((a, b) => {
                const aName = getDeviceNameFromRow(a).toLocaleLowerCase('de');
                const bName = getDeviceNameFromRow(b).toLocaleLowerCase('de');
                return aName.localeCompare(bName, 'de', {
                    sensitivity: 'base',
                    numeric: true
                });
            });

            // 3) Neu anordnen
            visibleRows.forEach(r => tableBody.appendChild(r));

            // 4) Breadcrumb aktualisieren
            const breadcrumb = document.getElementById('current-category-breadcrumb');
            if (breadcrumb) {
                const label = currentFilters.groupName || 'Alle';
                breadcrumb.innerHTML =
                    `<span class="mx-2 text-yellow-600">/</span><span class="text-gray-500">${label}</span>`;
            }
        }

        // ------ Events: Geräte-Dropdown ------
        function toggleCategoryMenu() {
            const menu = document.getElementById('categoryMenu');
            menu.classList.toggle('hidden');
            document.getElementById('categoryButton').setAttribute('aria-expanded', menu.classList.contains('hidden') ?
                'false' : 'true');
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
                currentFilters.group = item.dataset.group || '';
                currentFilters.groupName = item.dataset.name || 'Alle';
                document.getElementById('categoryButtonLabel').textContent =
                    (currentFilters.groupName === 'Alle') ? 'Geräte' : `${currentFilters.groupName}`;
                closeCategoryMenu();
                applyFilters();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeCategoryMenu();
        });

        // ------ Events: Status-Dropdown ------
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
            const statusButton = document.getElementById('statusButton');
            const statusMenu = document.getElementById('statusMenu');
            const item = e.target.closest('.status-item');

            if (!statusButton.contains(e.target) && !statusMenu.contains(e.target)) closeStatusMenu();

            if (item) {
                e.preventDefault();
                currentFilters.status = item.dataset.status; // 'all' | 'available' | 'loaned'
                document.getElementById('statusButtonLabel').textContent =
                    (currentFilters.status === 'all' ? 'Status' :
                        (currentFilters.status === 'available' ? 'Verfügbar' : 'Verliehen'));
                closeStatusMenu();
                applyFilters();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeStatusMenu();
        });

        // ------ Events: Live-Suche ------
        (function initLiveSearch() {
            const input = document.getElementById('searchInput');
            const clear = document.getElementById('clearSearch');
            if (!input) return;

            const onInput = () => {
                currentFilters.query = input.value || '';
                clear.classList.toggle('hidden', !currentFilters.query);
                applyFilters();
            };
            input.addEventListener('input', onInput);

            // Clear-Button
            if (clear) {
                clear.addEventListener('click', () => {
                    input.value = '';
                    currentFilters.query = '';
                    clear.classList.add('hidden');
                    input.focus();
                    applyFilters();
                });
            }
        })();

        // ------ Initial ------
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('categoryButtonLabel').textContent = 'Geräte';
            applyFilters(); // zeigt "Alle", Status "Alle", leere Suche
        });
    </script>

    <script>
        function openReservationModal(deviceId) {
            // Hidden device_id (falls genutzt)
            const hid = document.getElementById('reservation_device_id');
            if (hid) hid.value = deviceId;

            // Action-URL mit Platzhalter ersetzen
            const form = document.getElementById('reservationForm');
            const tpl = form.dataset.actionTemplate; // z.B. /devices/__DEVICE_ID__/reservations
            form.action = tpl.replace('__DEVICE_ID__', deviceId);

            // (Optional) Start/Ende defaulten (heute/morgen etc.)
            const start = document.getElementById('res_start_date');
            const end = document.getElementById('res_end_date');
            if (start && !start.value) {
                const today = new Date();
                start.value = today.toISOString().slice(0, 10);
            }
            if (end && !end.value) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                end.value = tomorrow.toISOString().slice(0, 10);
            }

            document.getElementById('reservationModal').classList.remove('hidden');
        }

        function closeReservationModal() {
            document.getElementById('reservationModal').classList.add('hidden');
        }

        function submitReservationForm() {
            document.getElementById('reservationForm').submit();
        }
    </script>


@endsection
