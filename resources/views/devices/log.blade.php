@extends('layouts.app')

@section('title', 'Geräte-Historie')

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
    <a href="{{ route('devices.index') }}" class="text-yellow-600 hover:underline">{{ __('Geräte') }}</a>
    <span class="mx-2 text-yellow-600">/</span>
    <span class="text-gray-500">{{ __('Historie') }}</span>
</nav>

<h1 class="text-2xl font-bold mb-4">Geräte-Historie</h1>
<p class="block items-center text-sm mb-8">
    Eine Übersicht aller Ausleih- und Rückgabeaktionen.
</p>

<div class="lg:container mx-auto flex items-center mb-8 p-4 pt-4 bg-gray-600 rounded">
    <div class="w-full">
        <div class="bg-black text-white p-4 rounded max-h-[600px] overflow-y-auto">
            @forelse ($histories as $history)
                <p class="text-sm mb-1">
                    <span class="text-gray-400">{{ \Carbon\Carbon::parse($history->created_at)->format('d.m.Y H:i') }}: </span>
                    @if($history->device)
                        <a href="{{ route('devices.show', $history->device) }}" class="text-blue-300 hover:underline">
                            {{ $history->device->title }}
                        </a>
                        -
                    @endif
                    <span class="text-green-400">
                        {{ $history->action === 'loaned' ? 'Geliehen von' : 'Zurückgegeben von' }}
                    </span>
                    <span class="text-yellow-400">{{ $history->user_name }}</span>
                    (eingetragen durch <span class="text-blue-400">{{ $history->action_by }}</span>)
                </p>
            @empty
                <p class="text-gray-400">Keine Historie vorhanden.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
