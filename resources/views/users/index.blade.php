@extends('layouts.app')

@section('title', 'Konten')

@section('content')
<div class="flex justify-between items-center">
    <h1 class="text-2xl font-bold mb-4">Kontenübersicht</h1>
    <p class="mb-2">
        <span  id="animated-icon" class="flex items-center gap-1 rounded-full bg-red-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-red-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6">
                <path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 0 0-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 0 0-2.282.819l-.922 1.597a1.875 1.875 0 0 0 .432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 0 0 0 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 0 0-.432 2.385l.922 1.597a1.875 1.875 0 0 0 2.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 0 0 2.28-.819l.923-1.597a1.875 1.875 0 0 0-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 0 0 0-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 0 0-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 0 0-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 0 0-1.85-1.567h-1.843ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" clip-rule="evenodd" />
            </svg>                        
            Administration
        </span>
    </p>
</div>
<p class="mb-8 flex items-center text-sm">Eine Übersicht über alle Konten.
    <a href="{{ route('users.create') }}" class="hover:underline text-yellow-600 flex items-center pl-1">
        Du möchtest neue Nutzende hinzufügen? Folge mir!
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" height="18" width="18" class="ml-1">
            <path fill-rule="evenodd" d="M19.902 4.098a3.75 3.75 0 0 0-5.304 0l-4.5 4.5a3.75 3.75 0 0 0 1.035 6.037.75.75 0 0 1-.646 1.353 5.25 5.25 0 0 1-1.449-8.45l4.5-4.5a5.25 5.25 0 1 1 7.424 7.424l-1.757 1.757a.75.75 0 1 1-1.06-1.06l1.757-1.757a3.75 3.75 0 0 0 0-5.304Zm-7.389 4.267a.75.75 0 0 1 1-.353 5.25 5.25 0 0 1 1.449 8.45l-4.5 4.5a5.25 5.25 0 1 1-7.424-7.424l1.757-1.757a.75.75 0 1 1 1.06 1.06l-1.757 1.757a3.75 3.75 0 1 0 5.304 5.304l4.5-4.5a3.75 3.75 0 0 0-1.035-6.037.75.75 0 0 1-.354-1Z" clip-rule="evenodd" />
        </svg>
    </a>
</p>
@if(session('success'))
    <div class="bg-green-400 text-white p-4 font-semibold mb-4 rounded">
        {{ session('success') }}
    </div>
@endif
<table class="w-full bg-gray-700 text-white rounded-lg table-fixed text-left">
    <thead>
        <tr>
            <th class="border-b-2 px-4 py-2 border-gray-500 w-1/4 font-medium text-sm">Name</th>
            <th class="border-b-2 px-4 py-2 border-gray-500 w-1/4 font-medium text-sm">E-Mail-Adresse</th>
            <th class="border-b-2 px-4 py-2 border-gray-500 w-1/8 font-medium text-sm">Rolle</th>
            <th class="border-b-2 px-4 py-2 border-gray-500 w-1/4 font-medium text-sm">Verifikationsdatum</th>
            <th class="border-b-2 px-4 py-2 border-gray-500 w-1/8 font-medium text-sm"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
            <tr>
                <td class="border-b px-4 py-2 border-gray-500 text-sm break-words">{{ $user->name }}</td>
                <td class="border-b px-4 py-2 border-gray-500 text-sm break-words">{{ $user->email }}</td>
                <td class="border-b px-4 py-2 border-gray-500 text-sm break-words">{{ $user->role }}</td>
                <td class="border-b px-4 py-2 border-gray-500 text-sm break-words">{{ $user->email_verified_at ? $user->email_verified_at->format('d.m.Y H:i') : 'Nicht verifiziert' }}</td>
                <td class="border-b px-4 py-2 border-gray-500 text-sm break-words">
                    <a href="{{ route('users.edit', $user->id) }}" class="shadow-md bg-gray-700 hover:bg-gray-900 text-white font-semibold py-1 ny-1 px-4 rounded text-xs">Bearbeiten</a>
                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-300 hover:text-white ml-2" onclick="return confirm('Sind Sie sicher, dass Sie diesen Account löschen möchten?')">
                            <svg height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
    <!-- JavaScript zum Starten der Animation nach Seitenladevorgang -->
    <script>
        window.addEventListener('load', () => {
            const icon = document.querySelector('#animated-icon svg');
            icon.classList.add('rotate-animation');
            
            setTimeout(() => {
                icon.classList.remove('rotate-animation');
            }, 1000); // Entfernt die Animation nach 1 Sekunde
        });
    </script>
    
    <!-- Tailwind CSS Klasse für die Animation -->
    <style>
        .rotate-animation {
            animation: rotateOnce 1s linear forwards;
        }
    
        @keyframes rotateOnce {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
@endsection