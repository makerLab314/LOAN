<!-- resources/views/categories/edit.blade.php -->
@extends('layouts.app')

@section('title', 'Kategorie bearbeiten')

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
    <a href="{{ route('categories.index') }}" class="text-yellow-600 hover:underline">Kategorien</a>
    <span class="mx-2 text-yellow-600">/</span>
    <span class="text-gray-500"><strong>{{ old('name', $category->name) }}</strong> bearbeiten</span>
</nav>

<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">Kategorie <em>{{ old('name', $category->name) }}</em> bearbeiten</h1>

    @if ($errors->any())
        <div class="bg-red-500 text-white p-2 mb-4 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 rounded-md border border-gray-300 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 12.75 6.75 10.5a.75.75 0 1 0-1.06 1.06l3 3a.75.75 0 0 0 1.06 0l7.5-7.5a.75.75 0 1 0-1.06-1.06L9 12.75Z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <strong class="font-medium text-gray-900">Änderungen gespeichert</strong>
                    <p class="mt-0.5 text-sm text-gray-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('categories.update', $category) }}" method="POST" class="w-full">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $category->name) }}"
                class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight"
                required
            >
            @error('name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Beschreibung und Details (optional)</label>
            <textarea
                id="description"
                name="description"
                rows="3"
                class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight"
            >{{ old('description', $category->description) }}</textarea>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-gray-600 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded focus:outline-none">
                Kategorie aktualisieren
            </button>
        </div>
    </form>
</div>
@endsection
