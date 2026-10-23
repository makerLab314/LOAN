<div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
    <span>{{ $branding->name() }}</span>
    @if (trim((string) $settings->get('legal.imprint')) !== '')
        <a href="{{ route('legal.imprint') }}" class="hover:text-fg">{{ __('Impressum') }}</a>
    @endif
    @if (trim((string) $settings->get('legal.privacy')) !== '')
        <a href="{{ route('legal.privacy') }}" class="hover:text-fg">{{ __('Datenschutz') }}</a>
    @endif
    <span>{{ __('basiert auf') }} <a href="https://github.com/dusanvin/LOAN" class="hover:text-fg" rel="noopener" target="_blank">LOAN</a> · MIT</span>
</div>
