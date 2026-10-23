@extends('layouts.app')
@section('title', __('Einstellungen'))

@php
    $labels = [
        'general' => [__('Allgemein'), 'settings'],
        'loans' => [__('Ausleihe'), 'loans'],
        'rooms' => [__('Räume'), 'door'],
        'permissions' => [__('Rechte'), 'shield'],
        'security' => [__('Sicherheit'), 'key'],
        'labels' => [__('Etiketten'), 'qr'],
        'legal' => [__('Rechtliches'), 'info'],
        'backups' => [__('Backups'), 'database'],
    ];
    $v = fn (string $key) => old(str_replace('.', '__', $key), $values[$key] ?? null);
    $f = fn (string $key) => str_replace('.', '__', $key);
@endphp

@section('content')
    <x-page-header :title="__('Einstellungen')"/>

    <div class="grid gap-6 lg:grid-cols-[14rem_1fr]">
        <nav class="flex gap-1 overflow-x-auto lg:flex-col" aria-label="{{ __('Bereiche') }}">
            @foreach ($sections as $s)
                <a href="{{ route('admin.settings', $s) }}" @class(['nav-link shrink-0', 'active' => $section === $s])>
                    <x-icon :name="$labels[$s][1]"/>{{ $labels[$s][0] }}
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.settings.update', $section) }}" enctype="multipart/form-data" class="card">
            @csrf @method('PUT')
            <div class="card-header"><h2 class="card-title">{{ $labels[$section][0] }}</h2></div>
            <div class="card-body space-y-5">

                @if ($section === 'general')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field :label="__('Name der Anwendung')" :name="$f('general.app_name')">
                            <input name="{{ $f('general.app_name') }}" class="input" value="{{ $v('general.app_name') }}" required maxlength="60">
                        </x-field>
                        <x-field :label="__('Untertitel')" :name="$f('general.tagline')">
                            <input name="{{ $f('general.tagline') }}" class="input" value="{{ $v('general.tagline') }}" maxlength="120">
                        </x-field>
                        <x-field :label="__('Akzentfarbe')" :name="$f('general.accent_color')">
                            <div class="flex items-center gap-2">
                                <input type="color" name="{{ $f('general.accent_color') }}" value="{{ $v('general.accent_color') }}" class="h-10 w-14 cursor-pointer rounded-lg border border-line bg-surface p-1">
                                <span class="text-sm text-muted">{{ __('Buttons, Links, Hervorhebungen') }}</span>
                            </div>
                        </x-field>
                        <x-field :label="__('Standardsprache')" :name="$f('general.default_locale')">
                            <select name="{{ $f('general.default_locale') }}" class="input">
                                <option value="de" @selected($v('general.default_locale') === 'de')>Deutsch</option>
                                <option value="en" @selected($v('general.default_locale') === 'en')>English</option>
                            </select>
                        </x-field>
                        <x-field :label="__('Zeitzone')" :name="$f('general.timezone')">
                            <select name="{{ $f('general.timezone') }}" class="input">
                                @foreach (['Europe/Berlin', 'Europe/Vienna', 'Europe/Zurich', 'Europe/Luxembourg', 'UTC'] as $tz)
                                    <option value="{{ $tz }}" @selected($v('general.timezone') === $tz)>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </x-field>
                    </div>
                    <div class="grid gap-4 border-t border-line pt-5 sm:grid-cols-2">
                        <x-field :label="__('Logo')" name="logo" :hint="__('PNG/JPG/WebP, quadratisch wirkt am besten.')">
                            @if ($branding->logoUrl())
                                <div class="mb-2 flex items-center gap-3">
                                    <img src="{{ $branding->logoUrl() }}" alt="" class="size-12 rounded-lg border border-line object-contain">
                                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_logo" value="1" class="checkbox">{{ __('entfernen') }}</label>
                                </div>
                            @endif
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-surface-2 file:px-3 file:py-2 file:text-fg">
                        </x-field>
                        <x-field :label="__('Favicon')" name="favicon" :hint="__('PNG, mind. 64×64 px.')">
                            @if ($values['general.favicon_path'] ?? null)
                                <label class="mb-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_favicon" value="1" class="checkbox">{{ __('Favicon entfernen') }}</label>
                            @endif
                            <input type="file" name="favicon" accept="image/png" class="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-surface-2 file:px-3 file:py-2 file:text-fg">
                        </x-field>
                    </div>
                @endif

                @if ($section === 'loans')
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-field :label="__('Standard-Ausleihdauer (Tage)')" :name="$f('loans.default_days')">
                            <input type="number" name="{{ $f('loans.default_days') }}" class="input" min="1" max="365" value="{{ $v('loans.default_days') }}">
                        </x-field>
                        <x-field :label="__('Maximale Dauer (Tage)')" :name="$f('loans.max_days')" :hint="__('0 = unbegrenzt')">
                            <input type="number" name="{{ $f('loans.max_days') }}" class="input" min="0" max="3650" value="{{ $v('loans.max_days') }}">
                        </x-field>
                        <x-field :label="__('Vormerkung ausgeben ab (Std. vorher)')" :name="$f('loans.convert_lead_hours')">
                            <input type="number" name="{{ $f('loans.convert_lead_hours') }}" class="input" min="0" max="720" value="{{ $v('loans.convert_lead_hours') }}">
                        </x-field>
                    </div>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="{{ $f('loans.accessories_check_required') }}" value="1" class="checkbox mt-0.5" @checked($v('loans.accessories_check_required'))>
                        <span><span class="block text-sm font-medium">{{ __('Zubehör-Checkliste bei Rückgabe verpflichtend') }}</span>
                            <span class="hint">{{ __('Fehlendes Zubehör wird vermerkt und bleibt als Hinweis sichtbar, bis es erledigt ist.') }}</span></span>
                    </label>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="{{ $f('loans.units_required') }}" value="1" class="checkbox mt-0.5" @checked($v('loans.units_required'))>
                        <span><span class="block text-sm font-medium">{{ __('Exemplar-Nummern bei Ausleihe verpflichtend') }}</span>
                            <span class="hint">{{ __('Bei Geräten mit mehreren Exemplaren muss angegeben werden, welche (-1, -2 …) ausgegeben werden.') }}</span></span>
                    </label>
                @endif

                @if ($section === 'rooms')
                    <x-field :label="__('Maximale Buchungsdauer (Stunden)')" :name="$f('rooms.max_hours')" :hint="__('0 = unbegrenzt')" class="max-w-xs">
                        <input type="number" name="{{ $f('rooms.max_hours') }}" class="input" min="0" max="8760" value="{{ $v('rooms.max_hours') }}">
                    </x-field>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="{{ $f('rooms.allow_past') }}" value="1" class="checkbox mt-0.5" @checked($v('rooms.allow_past'))>
                        <span><span class="block text-sm font-medium">{{ __('Buchungen & Vormerkungen in der Vergangenheit erlauben') }}</span>
                            <span class="hint">{{ __('Nützlich zum Nachtragen.') }}</span></span>
                    </label>
                @endif

                @if ($section === 'permissions')
                    <p class="text-sm text-muted">{{ __('Administration hat immer alle Rechte. Nutzerverwaltung und Einstellungen bleiben Admins vorbehalten.') }}</p>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>{{ __('Recht') }}</th>
                                @foreach ($roles as $role => $roleLabel)
                                    <th class="text-center">{{ $roleLabel }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach (collect($permissions)->groupBy('group', true) as $group => $items)
                                <tr><td colspan="{{ count($roles) + 1 }}" class="bg-surface-2/60 text-xs font-semibold tracking-wide text-muted uppercase">{{ __($group) }}</td></tr>
                                @foreach ($items as $key => $permission)
                                    <tr>
                                        <td>{{ __($permission['label']) }}</td>
                                        @foreach ($roles as $role => $roleLabel)
                                            <td class="text-center">
                                                @if ($role === 'administration')
                                                    <x-icon name="check" class="mx-auto size-4 text-ok"/>
                                                @elseif (! empty($permission['admin_only']))
                                                    <span class="text-muted">—</span>
                                                @else
                                                    <input type="checkbox" name="permissions[{{ $role }}][]" value="{{ $key }}" class="checkbox"
                                                           @checked(in_array($key, (array) ($values['permissions.'.$role] ?? []), true))
                                                           aria-label="{{ $roleLabel }}: {{ __($permission['label']) }}">
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($section === 'security')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field :label="__('Mindestlänge Passwort')" :name="$f('security.password_min')">
                            <input type="number" name="{{ $f('security.password_min') }}" class="input" min="8" max="64" value="{{ $v('security.password_min') }}">
                        </x-field>
                        <x-field :label="__('Automatische Abmeldung nach (Minuten Inaktivität)')" :name="$f('security.session_lifetime')">
                            <input type="number" name="{{ $f('security.session_lifetime') }}" class="input" min="5" max="43200" value="{{ $v('security.session_lifetime') }}">
                        </x-field>
                    </div>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="{{ $f('security.password_uncompromised') }}" value="1" class="checkbox mt-0.5" @checked($v('security.password_uncompromised'))>
                        <span><span class="block text-sm font-medium">{{ __('Bekannte geleakte Passwörter ablehnen') }}</span>
                            <span class="hint">{{ __('Prüft über die „Have I Been Pwned“-API (k-Anonymität – das Passwort verlässt den Server nicht). Benötigt Internetzugang.') }}</span></span>
                    </label>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="{{ $f('security.allow_self_delete') }}" value="1" class="checkbox mt-0.5" @checked($v('security.allow_self_delete'))>
                        <span><span class="block text-sm font-medium">{{ __('Nutzer dürfen ihr Konto selbst löschen') }}</span></span>
                    </label>
                @endif

                @if ($section === 'labels')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field :label="__('Standardformat')" :name="$f('labels.format')">
                            <select name="{{ $f('labels.format') }}" class="input">
                                @foreach ($formats as $key => $format)
                                    <option value="{{ $key }}" @selected($v('labels.format') === $key)>{{ $format['label'] }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field :label="__('Standard-Code')" :name="$f('labels.code_type')">
                            <select name="{{ $f('labels.code_type') }}" class="input">
                                @foreach (['qr' => __('QR-Code'), 'barcode' => __('Barcode (Code 128)'), 'both' => __('QR + Barcode'), 'text' => __('Nur Text')] as $key => $label)
                                    <option value="{{ $key }}" @selected($v('labels.code_type') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-field>
                    </div>
                    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="{{ $f('labels.show_title') }}" value="1" class="checkbox" @checked($v('labels.show_title'))>{{ __('Gerätename aufdrucken') }}</label>
                    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="{{ $f('labels.show_app_name') }}" value="1" class="checkbox" @checked($v('labels.show_app_name'))>{{ __('Name der Anwendung aufdrucken') }}</label>
                @endif

                @if ($section === 'legal')
                    <p class="text-sm text-muted">{{ __('Markdown wird unterstützt (# Überschrift, **fett**, Listen, Links). HTML wird entfernt. Leere Seiten werden nicht verlinkt.') }}</p>
                    <x-field :label="__('Impressum')" :name="$f('legal.imprint')">
                        <textarea name="{{ $f('legal.imprint') }}" rows="10" class="input font-mono text-xs">{{ $v('legal.imprint') }}</textarea>
                    </x-field>
                    <x-field :label="__('Datenschutzerklärung')" :name="$f('legal.privacy')">
                        <textarea name="{{ $f('legal.privacy') }}" rows="14" class="input font-mono text-xs">{{ $v('legal.privacy') }}</textarea>
                    </x-field>
                @endif

                @if ($section === 'backups')
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="{{ $f('backups.enabled') }}" value="1" class="checkbox mt-0.5" @checked($v('backups.enabled'))>
                        <span><span class="block text-sm font-medium">{{ __('Tägliches Backup (02:30 Uhr)') }}</span>
                            <span class="hint">{{ __('Datenbank und hochgeladene Bilder; im Docker-Setup unter ./data/backups.') }}</span></span>
                    </label>
                    <x-field :label="__('Aufbewahrung (Tage)')" :name="$f('backups.keep_days')" class="max-w-xs">
                        <input type="number" name="{{ $f('backups.keep_days') }}" class="input" min="1" max="365" value="{{ $v('backups.keep_days') }}">
                    </x-field>
                    <div class="rounded-lg border border-line p-4 text-sm">
                        <p>
                            {{ __('Letzter Lauf:') }}
                            <strong>{{ ($values['backups.last_run'] ?? null) ? \Illuminate\Support\Carbon::parse($values['backups.last_run'])->format('d.m.Y H:i') : '—' }}</strong>
                            @if (($values['backups.last_status'] ?? null) === 'ok')
                                <span class="badge badge-ok ml-1">OK</span>
                            @elseif ($values['backups.last_status'] ?? null)
                                <span class="badge badge-danger ml-1">{{ $values['backups.last_status'] }}</span>
                            @endif
                        </p>
                        @if ($backups)
                            <ul class="mt-3 max-h-56 space-y-1 overflow-y-auto font-mono text-xs text-muted">
                                @foreach ($backups as $file)
                                    <li>{{ $file['name'] }} · {{ number_format($file['size'] / 1048576, 2, ',', '.') }} MB</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
            <div class="flex items-center justify-between gap-2 border-t border-line px-4 py-3 sm:px-5">
                <div>
                    @if ($section === 'backups')
                        <button form="run-backup" class="btn btn-secondary"><x-icon name="database"/>{{ __('Jetzt sichern') }}</button>
                    @endif
                </div>
                <button class="btn btn-primary"><x-icon name="check"/>{{ __('Speichern') }}</button>
            </div>
        </form>
    </div>

    @if ($section === 'backups')
        <form id="run-backup" method="POST" action="{{ route('admin.settings.backup') }}">@csrf</form>
    @endif
@endsection
