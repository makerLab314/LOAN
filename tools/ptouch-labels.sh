#!/usr/bin/env bash
# Etiketten-PDF aus LOAN auf einem Brother P-touch (z. B. PT-P700) drucken –
# ohne CUPS-Treiber, direkt über USB mit ptouch-print.
#
#   tools/ptouch-labels.sh etiketten.pdf             drucken, nach jedem Etikett abschneiden
#   tools/ptouch-labels.sh etiketten.pdf --streifen  ein Streifen mit Schnittmarken, ein Schnitt am Ende
#   tools/ptouch-labels.sh etiketten.pdf --png       nur Vorschau nach streifen.png
#
# Beim Abschneiden läuft jedes Etikett als eigener Auftrag im Kettendruck (--chain)
# mit Schnitt davor (--precut): Der Vorlauf von ~25 mm fällt nur einmal an.
#
# Das PDF entsteht in der Druckansicht von LOAN (Format „TZe … mm“) über
# „Als PDF speichern“. Benötigt: ptouch-print, pdftoppm (poppler-utils), magick (ImageMagick).
set -euo pipefail

pdf=${1:-}
mode=${2:-cut}
if [ ! -f "$pdf" ] || ! [[ $mode =~ ^(cut|--streifen|--png)$ ]]; then
    echo "Aufruf: $0 etiketten.pdf [--streifen | --png]" >&2
    exit 1
fi
for cmd in ptouch-print pdftoppm magick; do
    if ! command -v "$cmd" >/dev/null; then
        echo "$cmd fehlt – siehe README, Abschnitt „Labels and scanning“" >&2
        exit 1
    fi
done

# Druckbare Höhe des eingelegten Bands in Pixeln (24 mm → 128 px, 12 mm → 76 px bei 180 dpi).
# Die letzte px-Angabe gilt dem Band, die erste dem Druckkopf; LC_ALL=C gegen übersetzte Ausgaben.
px=$(LC_ALL=C ptouch-print --info 2>/dev/null | grep -oE '[0-9]+ ?px' | tail -n1 | grep -oE '[0-9]+' || true)
if [ -z "$px" ]; then
    echo "Drucker nicht gefunden: per USB angeschlossen, eingeschaltet, Editor-Lite-LED aus?" >&2
    echo "Ohne udev-Regel fehlt der Zugriff auf USB – siehe README, Schritt 3." >&2
    exit 1
fi

tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

# 180 dpi = Auflösung des Druckkopfs, ohne Kantenglättung (sonst graue Pixel im QR-Code)
pdftoppm -r 180 -gray -aa no -aaVector no -png "$pdf" "$tmp/seite"

labels=()
for page in "$tmp"/seite-*.png; do
    height=$(magick identify -format '%h' "$page")
    if [ $((height * 6 / 10)) -gt "$px" ]; then
        echo "Das Band im Drucker ist schmaler als das Format im PDF – passendes TZe-Format wählen." >&2
        exit 1
    fi
    [ "$height" -lt "$px" ] && echo "Hinweis: Das Band ist breiter als das Format im PDF." >&2

    # Mitte des Bands ausschneiden (dort liegt der Inhalt), zwei Farben mit Palette (verlangt ptouch-print)
    out="$tmp/etikett-$(basename "$page")"
    magick "$page" -gravity center -crop "x${px}+0+0" +repage -threshold 50% -type Palette -colors 2 "PNG8:$out"
    labels+=("$out")
done

# Ein Streifen: Etiketten aneinander, Schnittmarken dazwischen
strip=()
for label in "${labels[@]}"; do
    [ ${#strip[@]} -gt 0 ] && strip+=(--cutmark)
    strip+=(--image "$label")
done

case $mode in
    --png)
        ptouch-print "${strip[@]}" --writepng streifen.png
        echo "Vorschau: streifen.png"
        ;;
    --streifen)
        echo "Drucke ${#labels[@]} Etikett(en) als Streifen …"
        ptouch-print "${strip[@]}"
        ;;
    cut)
        # Kettendruck: Jedes Etikett mit Schnitt davor, erst das letzte wird ausgegeben und abgeschnitten.
        # --timeout: auf den vorherigen Auftrag warten, solange der Drucker noch druckt.
        last=$((${#labels[@]} - 1))
        for i in "${!labels[@]}"; do
            echo "Etikett $((i + 1)) von ${#labels[@]} …"
            chain=(--chain)
            [ "$i" -eq "$last" ] && chain=()
            ptouch-print --timeout 30 --precut "${chain[@]}" --image "${labels[$i]}"
        done
        ;;
esac
