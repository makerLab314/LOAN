@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto">
    <h1 class="text-xl font-semibold mb-6">Kategorie bearbeiten</h1>

    <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input name="name" value="{{ old('name', $category->name) }}" class="w-full border rounded p-2" required />
            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Beschreibung (optional)</label>
            <textarea name="description" class="w-full border rounded p-2" rows="3">{{ old('description', $category->description) }}</textarea>
        </div>
        <div class="flex gap-2">
            <button class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Aktualisieren</button>
            <a href="{{ route('categories.index') }}" class="px-3 py-2 rounded border">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
