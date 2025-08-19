@extends('layouts.app')

@section('title', 'Kategorien')

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
    <span class="text-yellow-600 mr-1"><a href="{{ route('devices.index') }}">Geräte</a></span>
    <span class="mx-1 text-yellow-600">/</span>
    <span id="current-category-breadcrumb" class="mx-1 text-gray-500">Kategorien</span>
</nav>

<h1 class="text-2xl font-bold mb-4">Gerätekategorien</h1>
<p class="block items-center text-sm mb-2">Eine Übersicht über alle (Geräte-)kategorien. Füge eine neue Kategorie hinzu. Bearbeite oder lösche bereits vorhandene Kategorien.</p>
<p class="mb-4 block items-center text-sm">
    <strong>Hinweis:</strong> Eine Kategoire kann erst gelöscht werden, wenn kein Gerät mehr Teil dieser Kategorie ist.
</p>

<div class="lg:container mx-auto mb-8 p-4 bg-gray-600 rounded text-white">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold"> </h1>
        <a href="{{ route('categories.create') }}"
           class="inline-flex items-center px-4 py-2 rounded-md bg-gray-600 text-white text-sm font-medium hover:bg-yellow-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z" clip-rule="evenodd"/>
            </svg>
            Kategorie hinzufügen
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md border border-green-400 bg-green-100 p-3 text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-md border border-red-400 bg-red-100 p-3 text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg shadow bg-gray-700">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-gray-500">
                    <th class="p-3 font-medium text-sm w-1/2">Name</th>
                    <th class="p-3 font-medium text-sm w-1/2">Beschreibung</th>
                    <th class="p-3 font-medium text-sm text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr class="border-b border-gray-600 hover:bg-gray-600/50">
                        <td class="p-3">{{ $cat->name }}</td>
                        <td class="p-3 text-gray-300">{{ $cat->description ?: 'Keine Beschreibung verfügbar' }}</td>
                        <td class="p-3 text-right">
                            <a href="{{ route('categories.edit', $cat) }}"
                               class="inline-block mr-2 text-gray-300 hover:text-white">
                                <!-- Stift -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 
                                             1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 
                                             8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 
                                             2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 
                                             5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
                                    <path d="M5.25 5.25a3 3 0 0 0-3 
                                             3v10.5a3 3 0 0 0 3 3h10.5a3 3 
                                             0 0 0 3-3V13.5a.75.75 0 0 
                                             0-1.5 0v5.25a1.5 1.5 0 0 
                                             1-1.5 1.5H5.25a1.5 1.5 0 0 
                                             1-1.5-1.5V8.25a1.5 1.5 0 
                                             0 1 1.5-1.5h5.25a.75.75 0 
                                             0 0 0-1.5H5.25Z" />
                                </svg>
                            </a>
                            <form action="{{ route('categories.destroy', $cat) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Kategorie wirklich löschen?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-block text-gray-300 hover:text-red-500">
                                    <!-- Mülleimer -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 
                                                 48.816 0 0 1 3.878.512.75.75 
                                                 0 1 1-.256 1.478l-.209-.035-1.005 
                                                 13.07a3 3 0 0 1-2.991 
                                                 2.77H8.084a3 3 0 0 
                                                 1-2.991-2.77L4.087 
                                                 6.66l-.209.035a.75.75 
                                                 0 0 1-.256-1.478A48.567 
                                                 48.567 0 0 1 7.5 
                                                 4.705v-.227c0-1.564 
                                                 1.213-2.9 2.816-2.951a52.662 
                                                 52.662 0 0 1 3.369 
                                                 0c1.603.051 2.815 
                                                 1.387 2.815 2.951Zm-6.136-1.452a51.196 
                                                 51.196 0 0 1 3.273 
                                                 0C14.39 3.05 15 3.684 15 
                                                 4.478v.113a49.488 49.488 
                                                 0 0 0-6 0v-.113c0-.794.609-1.428 
                                                 1.364-1.452Zm-.355 
                                                 5.945a.75.75 0 1 0-1.5.058l.347 
                                                 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 
                                                 0 1 0-1.498-.058l-.347 
                                                 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="p-3 text-gray-300">Noch keine Kategorien.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
@endsection
