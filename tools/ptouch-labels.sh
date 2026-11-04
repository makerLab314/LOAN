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
# Vor dem Drucken wird das PDF geprüft (Seiten in Bandbreite statt A4?) und mit dem eingelegten
# Band verglichen. Ohne Terminal (Doppelklick) laufen Rückfrage mit Vorschau, Fortschritt und
# Fehler über Dialoge (zenity), im Terminal mit Fortschrittsbalken.
# Benötigt: ptouch-print, pdftoppm/pdfinfo/pdftotext (poppler-utils), magick (ImageMagick).
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
gui=0 tty=0
if [ ! -t 1 ] && command -v zenity >/dev/null && [ -n "${DISPLAY:-}${WAYLAND_DISPLAY:-}" ]; then
    gui=1
fi
[ -t 1 ] && tty=1
title="Etiketten drucken"

# Farben im Terminal
if [ $tty = 1 ]; then
    bold=$'\033[1m' dim=$'\033[2m' red=$'\033[1;31m' green=$'\033[1;32m' yellow=$'\033[33m' reset=$'\033[0m'
else
    bold="" dim="" red="" green="" yellow="" reset=""
fi

# --- Dialoge -----------------------------------------------------------------------
# Texte sind Pango-Markup (zenity). Im Terminal werden die Tags entfernt.
esc() { local s=${1//&/&amp;}; s=${s//</&lt;}; printf '%s' "${s//>/&gt;}"; }
plain() {
    sed -E -e 's#<big><b>([^<]*)</b></big>#'"$bold"'\1'"$reset"'#g' -e 's/<[^>]+>//g' \
        -e 's/&lt;/</g; s/&gt;/>/g; s/&amp;/\&/g' <<<"$1"
}
say() {
    if [ $gui = 1 ]; then zenity --info --title="$title" --width=400 --text="$1" 2>/dev/null || true; else plain "$1"; fi
}
fail() {
    if [ $gui = 1 ]; then
        zenity --error --title="$title" --width=440 --text="<big><b>Nicht gedruckt</b></big>

$1" 2>/dev/null || true
    else
        printf '%s✖ %s%s\n' "$red" "$(plain "$1")" "$reset" >&2
    fi
    exit 1
}
plural() { if [ "$1" = 1 ]; then echo "1 Etikett"; else echo "$1 Etiketten"; fi; }
wird() { if [ "$1" = 1 ]; then echo wird; else echo werden; fi; }

# --- Fortschritt ---------------------------------------------------------------------
# Die Arbeitsschritte melden „pct N“, „text …“ und „spin“ (Animation, solange ein Befehl läuft).
# Daraus wird ein zenity-Fortschrittsdialog oder im Terminal ein Balken.
emit() { printf '%s\n' "$*" 2>/dev/null || true; }
progress_ui() {
    local line pct=0 text="" frame=0 frames=(⠋ ⠙ ⠹ ⠸ ⠼ ⠴ ⠦ ⠧ ⠇ ⠏)
    if [ $gui = 1 ]; then
        while IFS= read -r line; do
            case $line in
                pct\ *) echo "${line#pct }" ;;
                text\ *) text=$(esc "${line#text }"); echo "# $text" ;;
                spin) frame=$(((frame + 1) % 10)); echo "# ${frames[$frame]}  $text" ;;
            esac
        done | zenity --progress --title="$title" --text="$1" --width=460 --percentage=0 \
            --auto-close --no-cancel --time-remaining 2>/dev/null || true
        return 0
    fi
    local width=26 fill bar rest max
    max=$(($(tput cols 2>/dev/null || echo 80) - width - 12))
    [ $tty = 1 ] && plain "$1"
    while IFS= read -r line; do
        case $line in
            pct\ *) pct=${line#pct } ;;
            text\ *) text=${line#text }; [ $tty = 1 ] || echo "$text" ;;
            spin) frame=$(((frame + 1) % 10)) ;;
        esac
        if [ $tty = 1 ]; then
            fill=$((pct * width / 100))
            printf -v bar '%*s' "$fill" ''
            printf -v rest '%*s' $((width - fill)) ''
            printf '\r\033[K%s %s%s%s%s %3d %%  %s' "${frames[$frame]}" "$green" "${bar// /█}" "$dim" "${rest// /░}$reset" "$pct" "${text:0:$max}"
        fi
    done
    [ $tty = 1 ] && printf '\r\033[K'
    return 0
}

# Solange der Befehl (PID) läuft: Animation, Balken wandert geschätzt von $2 bis knapp vor $3
spin() {
    local pid=$1 from=$2 to=$3 est=$(($4 > 0 ? $4 : 1)) k=0
    while kill -0 "$pid" 2>/dev/null; do
        k=$((k + 1))
        emit "pct $((from + (to - from) * (k < est ? k : est) * 9 / (est * 10)))"
        emit spin
        sleep 0.1
    done
    wait "$pid"
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

for cmd in ptouch-print pdftoppm pdfinfo pdftotext magick; do
    command -v "$cmd" >/dev/null || fail "<tt>$cmd</tt> fehlt – siehe README von LOAN, Abschnitt „Labels and scanning“."
done

# --- PDF finden ------------------------------------------------------------------
from_downloads=0
if [ -z "$pdf" ]; then
    # Neueste Etiketten*.pdf, auch „Etiketten (1).pdf“ vom erneuten Speichern
    pdf=$(find "$downloads" -maxdepth 1 -type f -iname 'Etiketten*.pdf' -printf '%T@\t%p\n' 2>/dev/null | sort -n | tail -n1 | cut -f2-)
    [ -n "$pdf" ] || fail "Keine <b>Etiketten.pdf</b> in $(esc "$downloads") gefunden.

In LOAN: Etiketten → Format „TZe …“ → Druckansicht → Drucken → „Als PDF speichern“."
    from_downloads=1
fi
[ -f "$pdf" ] || fail "Datei nicht gefunden: $(esc "$pdf")"
name=$(esc "$(basename "$pdf")")

# --- PDF prüfen ------------------------------------------------------------------
# Jede Seite ist ein Etikett: Höhe = Bandbreite, Breite = Länge. Speichert der Browser in A4 oder
# skaliert er die Seite, stimmt nichts mehr – das fällt hier auf, bevor Band verbraucht wird.
how_to_save="Im Druckdialog des Browsers: Ziel „Als PDF speichern“, Papierformat bzw. Seitengröße
unverändert lassen, Ränder „Keine“, Skalierung „Standard“ (100 %)."

pages=$(LC_ALL=C pdfinfo "$pdf" 2>/dev/null | awk '/^Pages:/ { print $2 }') || true
[ -n "$pages" ] && [ "$pages" -gt 0 ] || fail "$name lässt sich nicht als PDF lesen."
sizes=$(LC_ALL=C pdfinfo -f 1 -l "$pages" "$pdf" 2>/dev/null \
    | LC_ALL=C awk '/^Page +[0-9]+ size:/ { printf "%.1f %.1f\n", $4 * 25.4 / 72, $6 * 25.4 / 72 }')
read -r check a b c d < <(LC_ALL=C awk '
    { w = $1; h = $2; n++
      if (n == 1) h1 = h
      if (w > 120 && h > 120 && !big) { big = 1; bw = w; bh = h }
      if (h - h1 > 0.6 || h1 - h > 0.6) mixed = 1
      if (w > maxw) maxw = w
      if (n == 1 || w < minw) minw = w
      total += w }
    END {
      if (!n) { print "empty"; exit }
      if (big) { printf "sheet %.0f %.0f\n", bw, bh; exit }
      if (mixed) { print "mixed"; exit }
      split("9 12 18 24", t, " "); tape = 0
      for (i in t) if (h1 - t[i] < 0.6 && t[i] - h1 < 0.6) tape = t[i]
      if (!tape) { printf "height %.1f\n", h1; exit }
      if (maxw > 520) { printf "long %.0f\n", maxw; exit }
      printf "ok %d %.0f %.0f %.0f\n", tape, total, minw, maxw }' <<<"$sizes")

case $check in
    ok) pdf_tape=$a total_mm=$b min_mm=$c max_mm=$d ;;
    sheet)
        case "$a×$b" in
            210×297 | 297×210) paper="A4" ;;
            216×279 | 279×216) paper="US-Letter" ;;
            *) paper="$a × $b mm" ;;
        esac
        fail "$name hat Seiten im Format <b>$paper</b> statt in Etikettengröße – der Browser hat das
Papierformat beim Speichern überschrieben.

$how_to_save" ;;
    mixed) fail "Die Seiten in $name sind unterschiedlich hoch. Pro Druck nur ein TZe-Format verwenden." ;;
    height) fail "Die Seiten in $name sind <b>${a/./,} mm</b> hoch, passend wären 9, 12, 18 oder 24 mm.

In LOAN ein TZe-Format wählen und beim Speichern Hoch-/Querformat nicht umstellen.
$how_to_save" ;;
    long) fail "Eine Seite in $name ist $a mm lang – zu lang für ein Etikett. Ist das ein Etiketten-PDF aus LOAN?" ;;
    *) fail "$name enthält keine Seiten." ;;
esac
count=$pages
mapfile -t lens < <(awk '{ printf "%.0f\n", $1 }' <<<"$sizes")

# Druckbare Höhe je Bandbreite in Pixeln bei 180 dpi (wie ptouch-print sie meldet)
declare -A tape_px=([9]=52 [12]=76 [18]=120 [24]=128)
cpx=${tape_px[$pdf_tape]}

# --- Drucker und Band ------------------------------------------------------------
de_type() {
    case $1 in
        "Laminated tape") echo "laminiertes Band" ;;
        "Non-laminated tape") echo "nicht laminiertes Band" ;;
        "Flexible ID tape" | "Flexi tape") echo "Flexi-Band" ;;
        "Fabric tape") echo "Textilband" ;;
        "Heat-shrink tube") echo "Schrumpfschlauch" ;;
        "Satin tape") echo "Satinband" ;;
        *) echo "$1" ;;
    esac
}
de_color() {
    case ${1,,} in
        *white*) echo weiß ;; *clear*) echo transparent ;; *black*) echo schwarz ;; *red*) echo rot ;;
        *blue*) echo blau ;; *yellow*) echo gelb ;; *green*) echo grün ;; *gold*) echo gold ;;
        *silver*) echo silber ;; *orange*) echo orange ;; *pink*) echo pink ;; *gray*) echo grau ;;
        *) echo "$1" ;;
    esac
}

warnings=() band="" model="" px=$cpx
if [ $mode != png ]; then
    info=$(LC_ALL=C ptouch-print --info 2>/dev/null) || fail "Drucker nicht gefunden.

Ist er per USB angeschlossen und eingeschaltet, und ist die grüne Editor-Lite-LED aus?
Ohne udev-Regel fehlt der Zugriff auf USB – siehe README, Schritt 3."
    # Die letzte px-Angabe gilt dem Band, die erste dem Druckkopf
    px=$(grep -oE '[0-9]+ ?px' <<<"$info" | tail -n1 | grep -oE '[0-9]+' || true)
    tape=$(grep -oE 'media width = [0-9]+' <<<"$info" | grep -oE '[0-9]+' || true)
    ptype=$(sed -nE 's/^media type = [^(]*\((.*)\)$/\1/p' <<<"$info")
    tcolor=$(sed -nE 's/^tape color = [^(]*\((.*)\)$/\1/p' <<<"$info")
    xcolor=$(sed -nE 's/^text color = [^(]*\((.*)\)$/\1/p' <<<"$info")
    err=$(sed -nE 's/^error = (0x)?([0-9a-fA-F]+).*/\2/p' <<<"$info")
    model=$({ echo "$info"; LC_ALL=C ptouch-print --list-connected 2>/dev/null || true; } | grep -oE '\b(PT|QL)-[A-Z0-9]+' | head -n1 || true)
    px=${px:-$cpx}

    case $ptype in
        "No media") tape=0 ;;
        Incompatible* | Cleaning | Stencil) fail "Das eingelegte Band ($(esc "$ptype")) kann nicht bedruckt werden." ;;
        "Heat-shrink tube") warnings+=("Eingelegt ist Schrumpfschlauch – die TZe-Formate von LOAN sind für Band gedacht.") ;;
    esac
    [ "${tape:-}" != 0 ] || fail "Kein Band eingelegt."
    if [ -n "$tape" ] && [ "$tape" -lt "$pdf_tape" ]; then
        fail "Das PDF ist für <b>$pdf_tape-mm-Band</b>, im Drucker liegt <b>$tape mm</b>.

Entweder $pdf_tape-mm-Band einlegen oder in LOAN das Format „TZe $tape mm“ wählen."
    fi
    if [ -n "$tape" ] && [ "$tape" -gt "$pdf_tape" ]; then
        warnings+=("Im Drucker liegt $tape-mm-Band, das PDF ist für $pdf_tape mm: Die Etiketten werden klein in der Bandmitte gedruckt.")
    fi
    # Fehlerbits laut Brother-Raster-Referenz (Statusbyte 8 = untere, Byte 9 = obere Hälfte)
    if [ -n "$err" ] && [ $((16#$err)) -ne 0 ]; then
        e=$((16#$err)) what=()
        (((e & 0x0001) != 0)) && what+=("kein Band")
        (((e & 0x0004) != 0)) && what+=("Schneidemesser klemmt")
        (((e & 0x0008) != 0)) && what+=("Akku schwach")
        (((e & 0x0100) != 0)) && what+=("falsches Band")
        (((e & 0x1000) != 0)) && what+=("Abdeckung offen")
        (((e & 0x2000) != 0)) && what+=("überhitzt")
        msg=$(IFS=,; echo "${what[*]:-unbekannter Fehler}")
        warnings+=("Der Drucker meldet: ${msg//,/, } (Code 0x$err).")
    fi
    if [ -n "$tape" ]; then
        band="$tape mm"
        [ -n "$ptype" ] && band+=" $(de_type "$ptype")"
        [ -n "$tcolor" ] && band+=", $(de_color "$tcolor")"
        [ -n "$xcolor" ] && band+=", Schrift $(de_color "$xcolor")"
    fi
fi

# --- Seiten in Druckbilder umwandeln ------------------------------------------------
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
log="$tmp/fehler.log"

# Viermal so fein rendern und auf die 180 dpi des Druckkopfs herunterrechnen: Jeder Druckpunkt
# wird schwarz, wenn er zu mindestens 45 % bedeckt ist. So bleiben dünne Striche kleiner Schrift
# erhalten, statt wie beim direkten Rendern ohne Kantenglättung wegzubrechen. Dann die Mitte des
# Bands ausschneiden (dort liegt der Inhalt), zwei Farben mit Palette (verlangt ptouch-print).
# Der Text jeder Seite (ID, Gerätename) dient nur der Anzeige.
render_all() {
    trap '' PIPE # Dialog zu? Trotzdem weitermachen.
    local i
    for ((i = 1; i <= count; i++)); do
        emit "pct $(((i - 1) * 100 / count))"
        emit "text Etikett $i von $count wird aufbereitet …"
        pdftoppm -f "$i" -l "$i" -r 720 -gray -png -singlefile "$pdf" "$tmp/seite-$i" 2>>"$log" || return 1
        magick "$tmp/seite-$i.png" -filter Box -resize 25% -gravity center -crop "x${cpx}+0+0" +repage \
            -threshold 55% -type Palette -colors 2 "PNG8:$tmp/etikett-$i.png" 2>>"$log" || return 1
        rm -f "$tmp/seite-$i.png"
        pdftotext -f "$i" -l "$i" -enc UTF-8 "$pdf" - 2>/dev/null | awk '
            { gsub(/^[ \t\f]+|[ \t\f]+$/, "") }
            length && !seen[$0]++ && n < 3 { printf "%s%s", (n++ ? " · " : ""), $0 }' > "$tmp/name-$i.txt" || true
    done
    emit "pct 100"
    : > "$tmp/render-ok"
}
render_all | progress_ui "<b>$(plural "$count")</b> aus $name $(wird "$count") vorbereitet …" || true
[ -f "$tmp/render-ok" ] || fail "Das PDF ließ sich nicht umwandeln.

<tt><small>$(esc "$(tail -n 5 "$log" 2>/dev/null)")</small></tt>"

labels=() names=()
for ((i = 1; i <= count; i++)); do
    labels+=("$tmp/etikett-$i.png")
    names+=("$(cat "$tmp/name-$i.txt" 2>/dev/null)")
done

# Ein Streifen: Etiketten aneinander, Schnittmarken dazwischen
strip=()
for label in "${labels[@]}"; do
    [ ${#strip[@]} -gt 0 ] && strip+=(--cutmark)
    strip+=(--image "$label")
done

# Vorschau des Streifens, dreifach vergrößert mit Bandrand
write_preview() {
    ptouch-print --force-tape-width "$px" "${strip[@]}" --writepng "$1" >/dev/null 2>&1 \
        || ptouch-print "${strip[@]}" --writepng "$1" >/dev/null 2>&1 || return 1
    magick "$1" -filter Point -resize 300% -bordercolor '#d8d8d8' -border 12 "$1" 2>/dev/null || true
}

if [ $mode = png ]; then
    preview="$(dirname "$pdf")/streifen.png"
    write_preview "$preview" || fail "Vorschau ließ sich nicht erzeugen."
    say "Vorschau gespeichert: $(esc "$preview")"
    exit 0
fi

# --- Rückfrage -----------------------------------------------------------------------
# Verbrauch: Etiketten plus einmal ~25 mm Vorlauf
cm=$(((total_mm + 25 + 5) / 10))
if [ "$min_mm" = "$max_mm" ]; then length="je $min_mm mm lang"; else length="$min_mm–$max_mm mm lang"; fi
[ $mode = strip ] && length+=", als Streifen mit Schnittmarken"

summary="<big><b>$(plural "$count") drucken?</b></big>

📄  $name
🏷  $pdf_tape mm breit, $length
📏  ≈ $cm cm Band"
[ -n "$band" ] && summary+="
🖨  ${model:+$model · }eingelegt: $(esc "$band")"
for w in "${warnings[@]}"; do summary+="

<span foreground=\"#c01c28\">⚠  $(esc "$w")</span>"; done
summary+="
"
for ((i = 0; i < count && i < 6; i++)); do
    summary+="
<small>$((i + 1)).  $(esc "${names[$i]:-Etikett $((i + 1))}")</small>"
done
[ "$count" -gt 6 ] && summary+="
<small>… und $((count - 6)) weitere</small>"

show_preview() {
    local file="${XDG_RUNTIME_DIR:-$tmp}/etiketten-vorschau.png"
    write_preview "$file" || { say "Vorschau ließ sich nicht erzeugen."; return; }
    if [ -n "${DISPLAY:-}${WAYLAND_DISPLAY:-}" ] && command -v xdg-open >/dev/null; then
        xdg-open "$file" >/dev/null 2>&1 &
    else
        echo "Vorschau: $file"
    fi
}

if [ $confirm = 1 ]; then
    while true; do
        if [ $gui = 1 ]; then
            answer=$(zenity --question --title="$title" --width=460 --ok-label=Drucken --cancel-label=Abbrechen \
                --extra-button=Vorschau --text="$summary" 2>/dev/null) && break
            [ "$answer" = Vorschau ] || exit 0
        else
            plain "$summary"
            echo
            read -r -p "Drucken? [J/n, v = Vorschau] " answer || exit 0
            case $answer in
                [vV]*) ;;
                [nN]*) exit 0 ;;
                *) break ;;
            esac
        fi
        show_preview
    done
fi

# --- Drucken -----------------------------------------------------------------------
# Kettendruck: Jedes Etikett mit Schnitt davor, erst das letzte wird ausgegeben und abgeschnitten
# (der Vorlauf von ~25 mm fällt nur einmal an). Der Balken schätzt ~30 mm/s Druckgeschwindigkeit.
print_all() {
    trap '' PIPE # Dialog zu? Trotzdem zu Ende drucken, sonst bleibt ein halber Auftrag im Drucker.
    local i from to chain
    if [ $mode = strip ]; then
        emit "text Streifen mit $(plural "$count")"
        ptouch-print "${strip[@]}" >/dev/null 2>>"$log" &
        spin $! 0 100 $((total_mm * 10 / 30 + 30)) || return 1
        echo "$count" > "$tmp/gedruckt"
    else
        for i in "${!labels[@]}"; do
            from=$((i * 100 / count)) to=$(((i + 1) * 100 / count))
            emit "pct $from"
            emit "text Etikett $((i + 1)) von $count${names[$i]:+ · ${names[$i]}}"
            chain=(--chain)
            [ "$i" -eq $((count - 1)) ] && chain=()
            # --timeout: auf den vorherigen Auftrag warten, solange der Drucker noch druckt
            ptouch-print --timeout 30 --precut "${chain[@]}" --image "${labels[$i]}" >/dev/null 2>>"$log" &
            spin $! "$from" "$to" $((${lens[$i]:-50} * 10 / 30 + 15)) || return 1
            echo $((i + 1)) > "$tmp/gedruckt"
        done
    fi
    emit "text Fertig ✓"
    emit "pct 100"
    : > "$tmp/print-ok"
}

start=$SECONDS
print_all | progress_ui "<b>$(plural "$count")</b> $(wird "$count") gedruckt …" || true
if [ ! -f "$tmp/print-ok" ]; then
    printed=$(cat "$tmp/gedruckt" 2>/dev/null || echo 0)
    [ "$printed" -gt 0 ] && done_note="Gedruckt wurden $printed von $count, beim nächsten Versuch kommen wieder alle.

" || done_note=""
    fail "Drucken fehlgeschlagen.

$done_note<tt><small>$(esc "$(tail -n 5 "$log" 2>/dev/null)")</small></tt>"
fi
secs=$((SECONDS - start))

# Gedruckte Datei aus dem Weg räumen, damit der nächste Doppelklick nicht dieselben Etiketten druckt
moved=""
if [ $from_downloads = 1 ]; then
    done_dir="$downloads/Etiketten gedruckt"
    mkdir -p "$done_dir"
    mv "$pdf" "$done_dir/Etiketten $(date '+%Y-%m-%d %H-%M-%S').pdf"
    moved="
Das PDF liegt jetzt in „Etiketten gedruckt“."
fi
if [ $gui = 1 ]; then
    zenity --info --title="$title" --width=380 --timeout=10 --ok-label=Fertig \
        --text="<big><b>✅  $(plural "$count") gedruckt</b></big>

≈ $cm cm Band in $secs s.$moved" 2>/dev/null || true
else
    printf '%s✔ %s gedruckt%s %s(≈ %s cm Band in %s s)%s\n' "$green" "$(plural "$count")" "$reset" "$dim" "$cm" "$secs" "$reset"
    [ -n "$moved" ] && echo "${moved#$'\n'}"
fi
exit 0
