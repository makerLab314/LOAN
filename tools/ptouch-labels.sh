#!/usr/bin/env bash
# Etiketten-PDF aus LOAN auf einem Brother P-touch (z. B. PT-P700) drucken –
# ohne CUPS-Treiber, direkt über USB mit ptouch-print.
#
#   tools/ptouch-labels.sh --install           Starter „Etiketten drucken“ anlegen (App-Übersicht,
#                                              Schreibtisch, „Öffnen mit“ für PDFs)
#   tools/ptouch-labels.sh                     neueste Etiketten*.pdf aus „Downloads“ drucken,
#                                              danach nach „Downloads/Etiketten gedruckt“ verschieben
#   tools/ptouch-labels.sh datei.pdf           diese Datei drucken
#
#   --streifen   ein Streifen mit Schnittmarken statt Schnitt nach jedem Etikett
#   --png        nur Vorschau nach streifen.png (im Ordner des PDFs)
#   -y           ohne Rückfrage drucken
#
# Ohne Terminal (Doppelklick) laufen Rückfrage, Fortschritt und Fehler über Dialoge (zenity).
# Benötigt: ptouch-print, pdftoppm (poppler-utils), magick (ImageMagick).
set -euo pipefail

script=$(readlink -f "$0")
pdf="" mode=cut confirm=1 install=0
for arg in "$@"; do
    case $arg in
        --install) install=1 ;;
        --streifen) mode=strip ;;
        --png) mode=png ;;
        -y) confirm=0 ;;
        -*) echo "Unbekannte Option: $arg" >&2; exit 1 ;;
        *) pdf=$arg ;;
    esac
done

# Dialoge nur ohne Terminal und mit grafischer Sitzung
gui=0
if [ ! -t 1 ] && command -v zenity >/dev/null && [ -n "${DISPLAY:-}${WAYLAND_DISPLAY:-}" ]; then
    gui=1
fi
title="Etiketten drucken"

say() {
    if [ $gui = 1 ]; then zenity --info --title="$title" --width=360 --text="$1" 2>/dev/null || true; else echo "$1"; fi
}
fail() {
    if [ $gui = 1 ]; then zenity --error --title="$title" --width=400 --text="$1" 2>/dev/null || true; else echo "$1" >&2; fi
    exit 1
}
ask() {
    if [ $gui = 1 ]; then
        zenity --question --title="$title" --width=360 --ok-label=Drucken --cancel-label=Abbrechen --text="$1" 2>/dev/null
    else
        read -r -p "$1 [J/n] " answer
        [[ ! $answer =~ ^[nN] ]]
    fi
}

downloads=$(xdg-user-dir DOWNLOAD 2>/dev/null || echo "$HOME/Downloads")

# --- Starter anlegen -------------------------------------------------------------
if [ $install = 1 ]; then
    chmod +x "$script"
    entry="[Desktop Entry]
Type=Application
Name=Etiketten drucken
Comment=Etiketten aus LOAN auf dem P-touch drucken
Exec=\"$script\" %f
Icon=printer
Terminal=false
MimeType=application/pdf;
Categories=Office;"
    apps="$HOME/.local/share/applications"
    mkdir -p "$apps"
    printf '%s\n' "$entry" > "$apps/loan-etiketten.desktop"
    update-desktop-database "$apps" 2>/dev/null || true
    desktop=$(xdg-user-dir DESKTOP 2>/dev/null || true)
    if [ -n "$desktop" ] && [ "$desktop" != "$HOME" ] && [ -d "$desktop" ]; then
        printf '%s\n' "$entry" > "$desktop/Etiketten drucken.desktop"
        chmod +x "$desktop/Etiketten drucken.desktop"
        gio set "$desktop/Etiketten drucken.desktop" metadata::trusted true 2>/dev/null || true
    fi
    echo "„Etiketten drucken“ ist eingerichtet: in der App-Übersicht (an das Dock heften), auf dem"
    echo "Schreibtisch und bei PDFs unter „Öffnen mit“. Gedruckt wird die neueste Etiketten*.pdf aus $downloads."
    exit 0
fi

for cmd in ptouch-print pdftoppm magick; do
    command -v "$cmd" >/dev/null || fail "$cmd fehlt – siehe README von LOAN, Abschnitt „Labels and scanning“."
done

# --- PDF finden ------------------------------------------------------------------
from_downloads=0
if [ -z "$pdf" ]; then
    # Neueste Etiketten*.pdf, auch „Etiketten (1).pdf“ vom erneuten Speichern
    pdf=$(find "$downloads" -maxdepth 1 -type f -iname 'Etiketten*.pdf' -printf '%T@\t%p\n' 2>/dev/null | sort -n | tail -n1 | cut -f2-)
    [ -n "$pdf" ] || fail "Keine Etiketten.pdf in $downloads gefunden.

In LOAN: Etiketten → Format „TZe …“ → Druckansicht → Drucken → „Als PDF speichern“."
    from_downloads=1
fi
[ -f "$pdf" ] || fail "Datei nicht gefunden: $pdf"

# --- Drucker und Band ------------------------------------------------------------
# Druckbare Höhe des eingelegten Bands in Pixeln (24 mm → 128 px, 12 mm → 76 px bei 180 dpi).
# Die letzte px-Angabe gilt dem Band, die erste dem Druckkopf; LC_ALL=C gegen übersetzte Ausgaben.
if [ $mode = png ]; then
    info=$(LC_ALL=C ptouch-print --info 2>/dev/null || true)
else
    info=$(LC_ALL=C ptouch-print --info 2>/dev/null) || fail "Drucker nicht gefunden.

Ist er per USB angeschlossen und eingeschaltet, und ist die grüne Editor-Lite-LED aus?
Ohne udev-Regel fehlt der Zugriff auf USB – siehe README, Schritt 3."
fi
px=$(grep -oE '[0-9]+ ?px' <<<"$info" | tail -n1 | grep -oE '[0-9]+' || true)
tape=$(grep -oE 'media width = [0-9]+' <<<"$info" | grep -oE '[0-9]+' || true)
px=${px:-128}

# --- Seiten in Druckbilder umwandeln ------------------------------------------------
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

# Viermal so fein rendern und auf die 180 dpi des Druckkopfs herunterrechnen: Jeder Druckpunkt
# wird schwarz, wenn er zu mindestens 45 % bedeckt ist. So bleiben dünne Striche kleiner Schrift
# erhalten, statt wie beim direkten Rendern ohne Kantenglättung wegzubrechen.
pdftoppm -r 720 -gray -png "$pdf" "$tmp/seite"

labels=()
for page in "$tmp"/seite-*.png; do
    small="$tmp/klein-$(basename "$page")"
    magick "$page" -filter Box -resize 25% "$small"
    height=$(magick identify -format '%h' "$small")
    if [ $((height * 6 / 10)) -gt "$px" ]; then
        fail "Das Band im Drucker${tape:+ ($tape mm)} ist schmaler als das Format im PDF.
In LOAN das passende TZe-Format wählen."
    fi

    # Mitte des Bands ausschneiden (dort liegt der Inhalt), zwei Farben mit Palette (verlangt ptouch-print)
    out="$tmp/etikett-$(basename "$page")"
    magick "$small" -gravity center -crop "x${px}+0+0" +repage -threshold 55% -type Palette -colors 2 "PNG8:$out"
    labels+=("$out")
done
count=${#labels[@]}

# Ein Streifen: Etiketten aneinander, Schnittmarken dazwischen
strip=()
for label in "${labels[@]}"; do
    [ ${#strip[@]} -gt 0 ] && strip+=(--cutmark)
    strip+=(--image "$label")
done

if [ $mode = png ]; then
    preview="$(dirname "$pdf")/streifen.png"
    ptouch-print --force-tape-width "$px" "${strip[@]}" --writepng "$preview" >/dev/null 2>&1 \
        || ptouch-print "${strip[@]}" --writepng "$preview" >/dev/null
    say "Vorschau gespeichert: $preview"
    exit 0
fi

if [ $confirm = 1 ]; then
    ask "$count Etikett(en) aus „$(basename "$pdf")“${tape:+ auf $tape-mm-Band} drucken?" || exit 0
fi

# --- Drucken -----------------------------------------------------------------------
log="$tmp/fehler.log"

# Kettendruck: Jedes Etikett mit Schnitt davor, erst das letzte wird ausgegeben und abgeschnitten
# (der Vorlauf von ~25 mm fällt nur einmal an). Gibt Fortschritt im Format von zenity --progress aus.
print_all() {
    if [ $mode = strip ]; then
        echo "# $count Etikett(en) als Streifen …"
        ptouch-print "${strip[@]}" >/dev/null 2>>"$log" || return 1
    else
        local i chain
        for i in "${!labels[@]}"; do
            echo "$((i * 100 / count))"
            echo "# Etikett $((i + 1)) von $count …"
            chain=(--chain)
            [ "$i" -eq $((count - 1)) ] && chain=()
            # --timeout: auf den vorherigen Auftrag warten, solange der Drucker noch druckt
            ptouch-print --timeout 30 --precut "${chain[@]}" --image "${labels[$i]}" >/dev/null 2>>"$log" || return 1
        done
    fi
    echo 100
}

if [ $gui = 1 ]; then
    if ! print_all | zenity --progress --title="$title" --width=360 --auto-close --no-cancel 2>/dev/null; then
        fail "Drucken fehlgeschlagen.

$(tail -n 5 "$log" 2>/dev/null)"
    fi
else
    print_all | sed -n 's/^# //p' || fail "Drucken fehlgeschlagen: $(tail -n 5 "$log" 2>/dev/null)"
fi

# Gedruckte Datei aus dem Weg räumen, damit der nächste Doppelklick nicht dieselben Etiketten druckt
if [ $from_downloads = 1 ]; then
    done_dir="$downloads/Etiketten gedruckt"
    mkdir -p "$done_dir"
    mv "$pdf" "$done_dir/Etiketten $(date '+%Y-%m-%d %H-%M-%S').pdf"
fi
[ $gui = 1 ] && command -v notify-send >/dev/null && notify-send "$title" "$count Etikett(en) gedruckt." || true
echo "$count Etikett(en) gedruckt."
