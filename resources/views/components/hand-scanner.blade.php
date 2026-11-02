{{-- Schalter „Hand-Scanner“ für ein ID-Feld mit data-scan-focus (Logik in handscanner.js, gemerkt je Gerät).
     An: Das Feld hat immer den Fokus, ein USB-/Bluetooth-Scanner tippt direkt hinein.
     Aus: Kein Autofokus – auf Tablets öffnet sich die Bildschirmtastatur erst beim Antippen. --}}
<label {{ $attributes->merge(['class' => 'switch']) }} title="{{ __('An, wenn ein Hand-Scanner angeschlossen ist: Das ID-Feld ist dann immer bereit. Aus: Die Bildschirmtastatur erscheint erst beim Antippen.') }}">
    <input type="checkbox" data-hand-scanner>
    <span class="switch-track"></span>
    {{ __('Hand-Scanner') }}
</label>
