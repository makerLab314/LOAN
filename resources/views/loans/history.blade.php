@extends('layouts.app')
@section('title', __('Abgeschlossene Ausleihen'))

@section('content')
    <x-page-header :title="__('Abgeschlossene Ausleihen')" :back="route('loans.index')"/>

    <form method="GET" class="card mb-4 flex gap-2 p-3">
        <input type="search" name="q" value="{{ $q }}" class="input flex-1" placeholder="{{ __('Person, Gerät oder ID…') }}">
        <button class="btn btn-secondary"><x-icon name="search"/>{{ __('Suchen') }}</button>
    </form>

    <div class="card overflow-x-auto">
        @if ($loans->isEmpty())
            <x-empty icon="history" :title="__('Keine Einträge')"/>
        @else
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('Gerät') }}</th>
                    <th>{{ __('Person') }}</th>
                    <th class="hidden md:table-cell">{{ __('Zeitraum') }}</th>
                    <th>{{ __('Zurück am') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($loans as $loan)
                    <tr>
                        <td>
                            <span class="font-medium">{{ $loan->device?->title }}</span>
                            <span class="block text-xs text-muted">{{ $loan->device?->inventory_number }} · {{ $loan->quantity }}×</span>
                        </td>
                        <td>{{ $loan->borrower_name }}
                            @if ($loan->return_issues)
                                <span class="badge {{ $loan->issues_resolved_at ? 'badge-muted' : 'badge-warn' }}">{{ __('Zubehör') }}</span>
                            @endif
                        </td>
                        <td class="hidden text-muted md:table-cell">{{ $loan->loan_start_date->format('d.m.Y') }} – {{ $loan->loan_end_date->format('d.m.Y') }}</td>
                        <td>
                            {{ $loan->returned_at?->format('d.m.Y H:i') }}
                            <span class="block text-xs text-muted">{{ $loan->returnedBy?->name }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
