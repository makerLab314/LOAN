@extends('layouts.app')

@section('title', 'Gerät vormerken')

@section('content')
<nav id="breadcrumb-nav" class="flex text-sm mb-8" aria-label="Breadcrumb">
    <!-- optional: Breadcrumbs -->
</nav>

<div class="container mx-auto">

    @if($errors->any())
        <div class="bg-red-500 text-white p-4 rounded mb-4">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1 class="text-2xl font-bold mb-4">{{ $device->name }} vormerken</h1>

    <form action="{{ route('devices.reservations.store', $device) }}" method="POST" class="w-full">
        @csrf

        <div class="mb-4">
            <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Startdatum:</label>
            <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                   class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight" required>
        </div>

        <div class="mb-4">
            <label for="start_time" class="block text-gray-700 text-sm font-bold mb-2">Startzeit:</label>
            <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}"
                   class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight" required>
        </div>

        <div class="mb-4">
            <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Enddatum:</label>
            <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                   class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight" required>
        </div>

        <div class="mb-4">
            <label for="end_time" class="block text-gray-700 text-sm font-bold mb-2">Endzeit:</label>
            <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}"
                   class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight" required>
        </div>

        <div class="mb-4">
            <label for="purpose" class="block text-gray-700 text-sm font-bold mb-2">Zweck (max. 255 Zeichen):</label>
            <textarea name="purpose" id="purpose" rows="3" maxlength="255"
                      class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                      placeholder="In welchem Kontext wird das Gerät verliehen?">{{ old('purpose') }}</textarea>
            <p class="text-xs text-gray-500 mt-1"><strong>Beispiel:</strong> Profin. Meier benötigt den Arduino für Ihr Blockseminar "Interaktives Lernen mit Arduino".</p>
        </div>

        <div class="mb-4">
            <button type="submit" class="bg-gray-600 hover:bg-gray-800 border-gray-300 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Vormerken
            </button>
        </div>
    </form>
</div>
@endsection
