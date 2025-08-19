@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Kategorien</h1>
        <a href="{{ route('categories.create') }}" class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Neue Kategorie</a>
    </div>

    @if(session('success'))
        <div class="mb-4 text-green-700 bg-green-100 border border-green-200 rounded p-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 text-red-700 bg-red-100 border border-red-200 rounded p-3">{{ session('error') }}</div>
    @endif

    <div class="bg-white shadow rounded">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Name</th>
                    <th class="p-3">Beschreibung</th>
                    <th class="p-3 w-40">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr class="border-b">
                        <td class="p-3">{{ $cat->name }}</td>
                        <td class="p-3 text-gray-600">{{ $cat->description }}</td>
                        <td class="p-3">
                            <a href="{{ route('categories.edit', $cat) }}" class="text-blue-600 hover:underline mr-3">Bearbeiten</a>
                            <form action="{{ route('categories.destroy', $cat) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Kategorie wirklich löschen?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-3 text-gray-600" colspan="3">Noch keine Kategorien.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
@endsection
