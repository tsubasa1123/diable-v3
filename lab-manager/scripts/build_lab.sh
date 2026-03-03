#!/bin/bash
# scripts/build_lab.sh — Builder ou rebuilder l'image d'un lab
# Usage : bash build_lab.sh xss
# Usage : bash build_lab.sh all

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LABS_DIR="${LABS_DIR:-$SCRIPT_DIR/../../labs}"

build_one() {
    local lab=$1
    local path="$LABS_DIR/$lab"
    if [ ! -f "$path/Dockerfile" ]; then
        echo "[✗] Pas de Dockerfile trouvé dans : $path"
        return 1
    fi
    echo "[...] Build de sec-lab-$lab..."
    docker build -t "sec-lab-$lab" "$path" && echo "[✓] sec-lab-$lab buildé"
}

LAB=$1

if [ "$LAB" = "all" ] || [ -z "$LAB" ]; then
    # Lire automatiquement tous les sous-dossiers de LABS_DIR
    for lab_path in "$LABS_DIR"/*/; do
        lab=$(basename "$lab_path")
        build_one "$lab"
    done
elif [ -n "$LAB" ]; then
    build_one "$LAB"
fi
