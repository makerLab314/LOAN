@extends('layouts.app')
@use('App\Support\Names')
@section('title', __('Ausleihen'))

@section('content')
<div x-data="tabs" data-initial="loans">
    <x-page-header :title="__('Ausleihen & Vormerkungen')">
        <x-slot:actions>
            @unless (auth()->user()->isKiosk())
                <a href="{{ route('loans.history') }}" class="btn btn-secondary"><x-icon name="history"/><span class="hidden sm:inline">{{ __('Abgeschlossene') }}</span></a>
            @endunless
            @can('loans.return')
                <a href="{{ route('loans.return-scan') }}" class="btn btn-secondary"><x-icon name="undo"/>{{ __('Zurückgeben') }}</a>
            @endcan
            @can('loans.create')
                <a href="{{ route('loans.checkout') }}" class="btn btn-primary"><x-icon name="scan"/>{{ __('Ausleihen') }}</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex gap-1 overflow-x-auto rounded-xl border border-line bg-surface p-1">
        <button type="button" class="btn flex-1" :class="is('loans') ? 'bg-accent-soft text-accent' : 'text-muted'" data-tab="loans" @click="select">
            {{ __('Verliehen') }} <span class="badge badge-muted">{{ $loans->count() }}</span>
        </button>
        <button type="button" class="btn flex-1" :class="is('reservations') ? 'bg-accent-soft text-accent' : 'text-muted'" data-tab="reservations" @click="select">
            {{ __('Vormerkungen') }} <span class="badge badge-muted">{{ $reservations->count() }}</span>
        </button>
        @if ($issues->isNotEmpty())
            <button type="button" class="btn flex-1" :class="is('issues') ? 'bg-accent-soft text-accent' : 'text-warn'" data-tab="issues" @click="select">
                {{ __('Zubehör fehlt') }} <span class="badge badge-warn">{{ $issues->count() }}</span>
            </button>
        @endif
    </div>

    {{-- Aktive Ausleihen --}}
    <section x-show="is('loans')" class="card overflow-hidden">
        @if ($loans->isEmpty())
            <x-empty icon="check" :title="__('Aktuell ist nichts verliehen')"/>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('Gerät') }}</th>
                        <th>{{ __('Person') }}</th>
                        <th class="hidden md:table-cell">{{ __('Zeitraum') }}</th>
                        <th>{{ __('Rückgabe') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($loans as $loan)
                        <tr>
                            <td>
                                <a href="{{ route('devices.show', $loan->device_id) }}" class="font-medium hover:text-accent">{{ $loan->device?->title }}</a>
                                <p class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-muted">
                                    <span class="id-chip">{{ $loan->device?->inventory_number }}</span>
                                    @if ($loan->outstanding_quantity > 1 || $loan->units->isNotEmpty())
                                        {{ $loan->outstanding_quantity }}×
                                    @endif
                                    @foreach ($loan->units->whereNull('returned_at')->take(6) as $unit)
                                        <span class="id-chip">-{{ $unit->unit_no }}</span>
                                    @endforeach
                                </p>
                            </td>
                            <td>
                                {{ Names::shown($loan->borrower_name) }}
                                @if ($loan->loan_purpose)<p class="max-w-56 truncate text-xs text-muted">{{ $loan->loan_purpose }}</p>@endif
                            </td>
                            <td class="hidden text-muted md:table-cell">{{ $loan->loan_start_date->format('d.m.') }} – {{ $loan->loan_end_date->format('d.m.Y') }}</td>
                            <td>
                                @if ($loan->isOverdue())
                                    <span class="badge badge-danger">{{ __('überfällig seit :date', ['date' => $loan->loan_end_date->format('d.m.')]) }}</span>
                                @elseif ($loan->isDueToday())
                                    <span class="badge badge-info">{{ __('heute') }}</span>
                                @else
                                    <span class="text-sm">{{ $loan->loan_end_date->format('d.m.Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Vormerkungen --}}
    <section x-show="is('reservations')" x-cloak class="card overflow-hidden" id="reservations">
        @if ($reservations->isEmpty())
            <x-empty icon="bookmark" :title="__('Keine Vormerkungen')"/>
        @else
            <ul class="divide-y divide-line">
                @foreach ($reservations as $r)
                    <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:px-5">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('devices.show', $r->device_id) }}" class="font-medium hover:text-accent">{{ $r->quantity }}× {{ $r->device?->title }}</a>
                            <p class="text-sm text-muted">{{ Names::shown($r->displayName()) }} · {{ $r->start_at->format('d.m.Y H:i') }} – {{ $r->end_at->format('d.m.Y H:i') }}</p>
                            @if ($r->purpose)<p class="text-xs text-muted">{{ $r->purpose }}</p>@endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($r->end_at->isPast())
                                <span class="badge badge-warn">{{ __('abgelaufen') }}</span>
                            @elseif ($r->start_at->isPast())
                                <span class="badge badge-info">{{ __('läuft') }}</span>
                            @endif
                            @can('convert', $r)
                                <a href="{{ route('devices.show', $r->device_id) }}#reservations" class="btn btn-secondary btn-sm"><x-icon name="convert"/>{{ __('Ausgeben') }}</a>
                            @endcan
                            @can('cancel', $r)
                                <form method="POST" action="{{ route('reservations.destroy', $r) }}" data-confirm="{{ __('Vormerkung widerrufen?') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger-ghost btn-sm">{{ __('Widerrufen') }}</button>
                                </form>
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Fehlendes Zubehör --}}
    @if ($issues->isNotEmpty())
        <section x-show="is('issues')" x-cloak class="card overflow-hidden" id="issues">
            <ul class="divide-y divide-line">
                @foreach ($issues as $loan)
                    <li class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:px-5">
                        <div class="min-w-0 flex-1 text-sm">
                            <a href="{{ route('devices.show', $loan->device_id) }}" class="font-medium hover:text-accent">{{ $loan->device?->title }}</a>
                            · {{ Names::shown($loan->borrower_name) }}
                            <p class="text-muted">{{ collect($loan->return_issues)->map(fn ($i) => $i['missing'].'× '.$i['name'])->join(', ') }}</p>
                        </div>
                        @can('loans.resolve')
                            <form method="POST" action="{{ route('loans.resolve', $loan) }}" class="flex gap-2">
                                @csrf
                                <input type="hidden" name="redirect" value="{{ '/'.request()->path() }}">
                                <input name="note" class="input py-1.5" placeholder="{{ __('Notiz (optional)') }}" maxlength="1000">
                                <button class="btn btn-secondary btn-sm"><x-icon name="check"/>{{ __('Erledigt') }}</button>
                            </form>
                        @endcan
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
