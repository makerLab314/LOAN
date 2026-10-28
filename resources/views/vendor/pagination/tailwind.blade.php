{{-- Ersetzt Laravels Standard-Pagination (deren Farben passen nicht zum Theme) --}}
@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="{{ __('Seitennavigation') }}">
        <p class="text-xs text-muted tabular-nums">
            {{ __(':from–:to von :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>

        <div class="pager-group">
            @if ($paginator->onFirstPage())
                <span class="pager-item" aria-disabled="true" aria-label="{{ __('Zurück') }}"><x-icon name="chevron-left"/></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pager-item" aria-label="{{ __('Zurück') }}"><x-icon name="chevron-left"/></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-item pager-gap hidden sm:grid" aria-hidden="true">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-item active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pager-item hidden sm:grid" aria-label="{{ __('Seite :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pager-item" aria-label="{{ __('Weiter') }}"><x-icon name="chevron-right"/></a>
            @else
                <span class="pager-item" aria-disabled="true" aria-label="{{ __('Weiter') }}"><x-icon name="chevron-right"/></span>
            @endif
        </div>
    </nav>
@endif
