{{-- Globale Suche / ID-Eingabe (Strg+K, "/") – Ergebnisse werden per textContent gerendert --}}
<dialog x-data="searchPalette" data-url="{{ route('search') }}" id="search-dialog"
        class="modal mt-[10vh] mb-auto max-w-xl" @close="reset" aria-label="{{ __('Suche') }}">
    <div class="flex items-center gap-3 border-b border-line px-4">
        <x-icon name="search" class="size-5 text-muted"/>
        <input x-ref="input" x-model="query" @input.debounce.200ms="search" @keydown="onKey" type="search"
               class="h-14 flex-1 bg-transparent text-base outline-none placeholder:text-muted"
               placeholder="{{ Gate::allows('devices.view') ? __('Gerät, Inventar-ID, Person, Raum…') : __('Raum, Anlass oder Person…') }}" autocomplete="off" spellcheck="false">
        <button type="button" class="kbd" @click="closeDialog">Esc</button>
    </div>
    <div class="max-h-[60vh] overflow-y-auto p-2">
        <template x-if="loading">
            <p class="px-3 py-6 text-center text-sm text-muted">{{ __('Suche…') }}</p>
        </template>
        <template x-if="showEmpty">
            <p class="px-3 py-6 text-center text-sm text-muted">{{ __('Keine Treffer.') }}</p>
        </template>
        <template x-if="showHint">
            <div class="px-3 py-6 text-center text-sm text-muted">
                @can('devices.view')
                    <p>{{ __('Tippe einen Gerätenamen oder eine Inventar-ID wie') }} <span class="id-chip">KAB-0042-3</span></p>
                @else
                    <p>{{ __('Tippe einen Raum, einen Anlass oder eine Ansprechperson.') }}</p>
                @endcan
                <p class="mt-2">{{ __('Navigieren mit') }} <span class="kbd">↑</span> <span class="kbd">↓</span>, {{ __('öffnen mit') }} <span class="kbd">Enter</span></p>
            </div>
        </template>
        <template x-for="(item, index) in results" :key="item.url + index">
            <div>
                <p x-show="item.firstOfGroup" class="px-3 pt-3 pb-1 text-[11px] font-semibold tracking-wider text-muted uppercase" x-text="item.group"></p>
                <a :href="item.url" @mouseenter="setActive(index)"
                   :class="index === active ? 'bg-accent-soft text-accent' : 'text-fg'"
                   class="flex items-center gap-3 rounded-lg px-3 py-2">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium" x-text="item.title"></span>
                        <span class="block truncate text-xs text-muted" x-text="item.subtitle"></span>
                    </span>
                    <x-icon name="chevron-right" class="size-4 opacity-50"/>
                </a>
            </div>
        </template>
    </div>
</dialog>
