#!/bin/bash
# scripts/build_lab.sh — Builder ou rebuilder l'image d'un lab
# Usage : bash build_lab.sh xss
# Usage : bash build_lab.sh all

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LABS_DIR="${LABS_DIR:-$SCRIPT_DIR/../../attacks}"
LAB=$1

build_one() {
    local lab=$1
    local path="$LABS_DIR/$lab"
    if [ ! -d "$path" ]; then
        echo "[✗] Lab introuvable : $path"
        return 1
    fi
    echo "[...] Build de sec-lab-$lab..."
    docker build -t "sec-lab-$lab" "$path" && echo "[✓] sec-lab-$lab buildé"
}

if [ "$LAB" = "all" ]; then
    for lab in xss-lab sqli-lab xpath-lab phishing path-traversal-lab nosql-injection-lab; do
        build_one "$lab"
    done
elif [ -n "$LAB" ]; then
    build_one "$LAB"
else
    echo "Usage : bash build_lab.sh <lab_id|all>"
    echo "Labs disponibles : xss-lab, sqli-lab, xpath-lab, phishing, path-traversal-lab, nosql-injection-lab"
fi
