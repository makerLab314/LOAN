<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageStore;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const SECTIONS = ['general', 'loans', 'rooms', 'permissions', 'security', 'labels', 'legal', 'backups'];

    public function __construct(private Settings $settings) {}

    public function edit(Request $request, string $section = 'general'): View
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        return view('admin.settings', [
            'section' => $section,
            'sections' => self::SECTIONS,
            'values' => $this->settings->all(),
            'permissions' => config('loan.permissions'),
            'roles' => config('loan.roles'),
            'formats' => config('loan.label_formats'),
            'backups' => $section === 'backups' ? $this->backupFiles() : [],
        ]);
    }

    public function update(Request $request, ImageStore $images, string $section): RedirectResponse
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $values = match ($section) {
            'general' => $this->general($request, $images),
            'loans' => $this->validateSection($request, [
                'loans.default_days' => ['required', 'integer', 'min:1', 'max:365'],
                'loans.max_days' => ['required', 'integer', 'min:0', 'max:3650'],
                'loans.convert_lead_hours' => ['required', 'integer', 'min:0', 'max:720'],
                'loans.accessories_check_required' => ['boolean'],
                'loans.units_required' => ['boolean'],
            ], ['loans.accessories_check_required', 'loans.units_required']),
            'rooms' => $this->validateSection($request, [
                'rooms.allow_past' => ['boolean'],
                'rooms.max_hours' => ['required', 'integer', 'min:0', 'max:8760'],
            ], ['rooms.allow_past']),
            'permissions' => $this->permissions($request),
            'security' => $this->validateSection($request, [
                'security.password_min' => ['required', 'integer', 'min:8', 'max:64'],
                'security.password_uncompromised' => ['boolean'],
                'security.allow_self_delete' => ['boolean'],
                'security.session_lifetime' => ['required', 'integer', 'min:5', 'max:43200'],
            ], ['security.password_uncompromised', 'security.allow_self_delete']),
            'labels' => $this->validateSection($request, [
                'labels.format' => ['required', Rule::in(array_keys(config('loan.label_formats')))],
                'labels.code_type' => ['required', Rule::in(['qr', 'barcode', 'both', 'text'])],
                'labels.show_title' => ['boolean'],
                'labels.show_app_name' => ['boolean'],
                'labels.note' => ['nullable', 'string', 'max:40'],
            ], ['labels.show_title', 'labels.show_app_name']),
            'legal' => $this->validateSection($request, [
                'legal.imprint' => ['nullable', 'string', 'max:50000'],
                'legal.privacy' => ['nullable', 'string', 'max:100000'],
            ]),
            'backups' => $this->validateSection($request, [
                'backups.enabled' => ['boolean'],
                'backups.keep_days' => ['required', 'integer', 'min:1', 'max:365'],
            ], ['backups.enabled']),
        };

        $this->settings->set($values);
        Audit::log('settings.updated', null, ['section' => $section, 'keys' => array_keys($values)]);

        return redirect()->route('admin.settings', $section)->with('success', __('Einstellungen gespeichert.'));
    }

    public function runBackup(): RedirectResponse
    {
        $exit = Artisan::call('loan:backup');

        return back()->with($exit === 0 ? 'success' : 'error', $exit === 0
            ? __('Backup erstellt.')
            : __('Backup fehlgeschlagen – siehe Protokoll.'));
    }

    /**
     * Formularfelder heißen wie der Einstellungsschlüssel, nur mit "__" statt "."
     * (Punkte würden als verschachtelte Arrays interpretiert).
     *
     * @param  array<string, list<mixed>>  $rules  Schlüssel → Regeln
     * @param  list<string>  $booleans  Checkboxen (fehlen im Request, wenn nicht angehakt)
     * @return array<string, mixed>
     */
    private function validateSection(Request $request, array $rules, array $booleans = []): array
    {
        $data = $fieldRules = [];
        foreach ($rules as $key => $rule) {
            $field = str_replace('.', '__', $key);
            $fieldRules[$field] = $rule;
            $data[$field] = in_array($key, $booleans, true) ? $request->boolean($field) : $request->input($field);
        }

        $validated = validator($data, $fieldRules)->validate();

        $values = [];
        foreach ($rules as $key => $rule) {
            $value = $validated[str_replace('.', '__', $key)] ?? null;
            $values[$key] = match (true) {
                in_array($key, $booleans, true) => (bool) $value,
                in_array('integer', $rule, true) => (int) $value,
                default => $value,
            };
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function general(Request $request, ImageStore $images): array
    {
        $values = $this->validateSection($request, [
            'general.app_name' => ['required', 'string', 'max:60'],
            'general.tagline' => ['nullable', 'string', 'max:120'],
            'general.accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'general.default_locale' => ['required', Rule::in(['de', 'en'])],
            'general.timezone' => ['required', 'timezone:all'],
        ]);

        $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'favicon' => ['nullable', 'image', 'mimes:png', 'max:1024'],
        ]);

        foreach (['logo' => 600, 'favicon' => 256] as $field => $size) {
            $key = 'general.'.$field.'_path';
            if ($request->hasFile($field)) {
                $this->deleteFile($this->settings->get($key));
                $values[$key] = $images->storePng($request->file($field), 'branding', $size);
            } elseif ($request->boolean('remove_'.$field)) {
                $this->deleteFile($this->settings->get($key));
                $values[$key] = null;
            }
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function permissions(Request $request): array
    {
        $values = [];
        foreach (array_keys(config('loan.roles')) as $role) {
            if ($role === 'administration') {
                continue;
            }
            $values['permissions.'.$role] = array_values(array_unique(array_filter(
                (array) Arr::get($request->input('permissions', []), $role, []),
                fn ($p) => Settings::assignable($role, $p),
            )));
        }

        return $values;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return list<array{name: string, size: int, time: int}>
     */
    private function backupFiles(): array
    {
        $dir = (string) config('loan.backup_path');
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (glob($dir.'/loan-*') ?: [] as $file) {
            $files[] = ['name' => basename($file), 'size' => (int) filesize($file), 'time' => (int) filemtime($file)];
        }
        usort($files, fn ($a, $b) => $b['time'] <=> $a['time']);

        return $files;
    }
}
