@extends('layouts.app')

@section('title', 'Nutzer bearbeiten')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold mb-4">Konto bearbeiten</h1>
        <p class="mb-2">
            <span class="flex items-center gap-1 rounded-full bg-red-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>                  
                Administration
            </span>
        </p>
    </div>
    <p class="mb-8 flex items-center text-sm">Bearbeite die angegebenen Informationen des/der Nutzenenden.
        <a href="{{ route('users.index') }}" class="hover:underline text-yellow-600 flex items-center pl-1">
            Du möchtest zur Übersicht über alle Nutzenden? Folge mir!
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" height="18" width="18" class="ml-1">
                <path fill-rule="evenodd" d="M19.902 4.098a3.75 3.75 0 0 0-5.304 0l-4.5 4.5a3.75 3.75 0 0 0 1.035 6.037.75.75 0 0 1-.646 1.353 5.25 5.25 0 0 1-1.449-8.45l4.5-4.5a5.25 5.25 0 1 1 7.424 7.424l-1.757 1.757a.75.75 0 1 1-1.06-1.06l1.757-1.757a3.75 3.75 0 0 0 0-5.304Zm-7.389 4.267a.75.75 0 0 1 1-.353 5.25 5.25 0 0 1 1.449 8.45l-4.5 4.5a5.25 5.25 0 1 1-7.424-7.424l1.757-1.757a.75.75 0 1 1 1.06 1.06l-1.757 1.757a3.75 3.75 0 1 0 5.304 5.304l4.5-4.5a3.75 3.75 0 0 0-1.035-6.037.75.75 0 0 1-.354-1Z" clip-rule="evenodd" />
            </svg>
        </a>
    </p>
    @if ($errors->any())
        <div class="bg-red-500 text-white p-2 mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.update', $user->id) }}" class="w-full">
        @csrf
        @method('PATCH')

        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
            <input type="email" name="email" id="email" class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight" value="{{ old('email', $user->email) }}" required>
        </div>

        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Passwort (Optional):</label>
            <input type="password" name="password" id="password" class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
            <p class="text-gray-600 text-xs italic mt-1">Lasse das Feld leer, wenn du das Passwort nicht ändern möchtest.</p>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">Passwort bestätigen:</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
        </div>

        <div class="mb-4">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Rolle:</label>
            <select name="role" id="role" class="bg-gray-50 focus:ring-gray-500 focus:border-gray-500 border-gray-300 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight" required>
                <option value="administration" {{ $user->role == 'administration' ? 'selected' : '' }}>Administration</option>
                <option value="moderation" {{ $user->role == 'moderation' ? 'selected' : '' }}>Moderation</option>
            </select>
        </div>

        <div class="mb-4">
            <button type="submit" class="bg-gray-600 hover:bg-gray-800 border-gray-300 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Nutzer bearbeiten</button>
        </div>
    </form>
</div>
@endsection
