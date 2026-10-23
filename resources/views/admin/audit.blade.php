@extends('layouts.app')
@section('title', __('Protokoll'))

@section('content')
    <x-page-header :title="__('Protokoll')">
        <x-slot:subtitle>{{ __('Alle Änderungen und Anmeldungen – wer, was, wann.') }}</x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('admin.audit.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download"/>{{ __('CSV-Export') }}</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-4 grid gap-3 p-3 sm:grid-cols-2 lg:grid-cols-5">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input lg:col-span-2" placeholder="{{ __('Suche in Objekt, Person, Details…') }}">
        <select name="action" class="input" data-autosubmit>
            <option value="">{{ __('Alle Aktionen') }}</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ (new \App\Models\AuditLog(['action' => $action]))->description() }}</option>
            @endforeach
        </select>
        <select name="user" class="input" data-autosubmit>
            <option value="">{{ __('Alle Nutzer') }}</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected((string) ($filters['user'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input" aria-label="{{ __('Von') }}">
            <button class="btn btn-secondary btn-icon" aria-label="{{ __('Filtern') }}"><x-icon name="filter"/></button>
        </div>
    </form>

    <div class="card overflow-x-auto">
        @if ($logs->isEmpty())
            <x-empty icon="shield" :title="__('Keine Einträge')"/>
        @else
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('Zeit') }}</th>
                    <th>{{ __('Aktion') }}</th>
                    <th>{{ __('Objekt') }}</th>
                    <th class="hidden lg:table-cell">{{ __('Details') }}</th>
                    <th class="hidden md:table-cell">{{ __('Von') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="text-xs whitespace-nowrap text-muted tabular-nums">{{ $log->created_at?->format('d.m.Y H:i:s') }}</td>
                        <td>
                            <span @class(['badge', 'badge-danger' => str_ends_with($log->action, 'failed') || str_ends_with($log->action, 'deleted'), 'badge-muted' => ! (str_ends_with($log->action, 'failed') || str_ends_with($log->action, 'deleted'))])>{{ $log->description() }}</span>
                        </td>
                        <td class="max-w-64 truncate">{{ $log->subject_label }}</td>
                        <td class="hidden max-w-80 text-xs text-muted lg:table-cell">
                            @foreach ((array) $log->properties as $key => $value)
                                <span class="mr-2 inline-block"><span class="font-medium">{{ $key }}:</span> {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (is_bool($value) ? ($value ? 'ja' : 'nein') : $value) }}</span>
                            @endforeach
                        </td>
                        <td class="hidden md:table-cell">
                            {{ $log->user_name ?? '—' }}
                            <span class="block text-xs text-muted">{{ $log->ip_address }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
