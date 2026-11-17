#!/bin/sh
# Downloads the Latin subset of Archivo (variable width + weight) from Google Fonts.
set -eu
DEST="${1:-public/assets/fonts}"
mkdir -p "$DEST"
UA="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"
CSS=$(curl -fsSL -A "$UA" "https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&display=swap")
URL=$(printf '%s\n' "$CSS" | awk '/\/\* latin \*\//{f=1} f && /src: url/{match($0,/https:[^)]+/); print substr($0,RSTART,RLENGTH); exit}')
[ -n "$URL" ] || { echo "Could not find Archivo latin subset"; exit 1; }
curl -fsSL "$URL" -o "$DEST/archivo.woff2"
echo "Saved $DEST/archivo.woff2"
