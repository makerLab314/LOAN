@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto">
    <h1 class="text-xl font-semibold mb-6">Kategorie erstellen</h1>

    <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input name="name" value="{{ old('name') }}" class="w-full border rounded p-2" required />
            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Beschreibung (optional)</label>
            <textarea name="description" class="w-full border rounded p-2" rows="3">{{ old('description') }}</textarea>
        </div>
        <div class="flex gap-2">
            <button class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Speichern</button>
            <a href="{{ route('categories.index') }}" class="px-3 py-2 rounded border">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
