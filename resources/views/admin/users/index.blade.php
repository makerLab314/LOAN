@extends('layouts.app')
@section('title', __('Nutzer'))

@section('content')
    <x-page-header :title="__('Nutzer')">
        <x-slot:subtitle>{{ __('Rechte je Rolle legst du unter Einstellungen → Rechte fest.') }}</x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><x-icon name="plus"/>{{ __('Neuer Nutzer') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th class="hidden md:table-cell">{{ __('E-Mail') }}</th>
                <th>{{ __('Rolle') }}</th>
                <th class="hidden lg:table-cell">{{ __('Zuletzt aktiv') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>
                        <span class="font-medium">{{ $user->name }}</span>
                        @if ($user->is(auth()->user()))<span class="badge badge-muted ml-1">{{ __('du') }}</span>@endif
                        <span class="block text-xs text-muted md:hidden">{{ $user->email }}</span>
                    </td>
                    <td class="hidden text-muted md:table-cell">{{ $user->email }}</td>
                    <td><span class="badge {{ $user->isAdmin() ? 'badge-accent' : 'badge-muted' }}">{{ config('loan.roles.'.$user->role, $user->role) }}</span></td>
                    <td class="hidden text-muted lg:table-cell">
                        {{ isset($lastSeen[$user->id]) ? \Illuminate\Support\Carbon::createFromTimestamp($lastSeen[$user->id])->diffForHumans() : '—' }}
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-sm" aria-label="{{ __('Bearbeiten') }}"><x-icon name="edit"/></a>
                        @can('delete', $user)
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" data-confirm="{{ __(':name löschen?', ['name' => $user->name]) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger-ghost btn-sm" aria-label="{{ __('Löschen') }}"><x-icon name="trash"/></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
