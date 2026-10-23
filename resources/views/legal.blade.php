@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('title', $title)

@section('content')
    <article class="card card-body mx-auto max-w-3xl">
        <h1 class="mb-4 text-2xl font-semibold">{{ $title }}</h1>
        @if ($html)
            {{-- Markdown aus den Einstellungen; HTML-Eingaben und unsichere Links werden beim Rendern entfernt --}}
            <div class="prose-legal text-sm">{!! $html !!}</div>
        @else
            <p class="text-sm text-muted">{{ __('Noch kein Text hinterlegt.') }}</p>
            @can('settings.manage')
                <a href="{{ route('admin.settings', 'legal') }}" class="btn btn-secondary btn-sm mt-4">{{ __('Jetzt hinterlegen') }}</a>
            @endcan
        @endif
    </article>
@endsection
