#!/bin/bash
# scripts/build_lab.sh — Builder ou rebuilder les images des labs
# Usage : bash build_lab.sh          → builder tous les labs
# Usage : bash build_lab.sh all      → builder tous les labs
# Usage : bash build_lab.sh xss      → builder un lab spécifique

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LABS_DIR="${LABS_DIR:-$SCRIPT_DIR/../../attacks}"

# ── Couleurs ──────────────────────────────────────────────────────────
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

ok()   { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[~]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1"; }
info() { echo -e "${BLUE}[↓]${NC} $1"; }

# ── Compteurs ─────────────────────────────────────────────────────────
COUNT_BUILD=0
COUNT_PULL=0
COUNT_SKIP=0
COUNT_ERROR=0

build_one() {
    local lab=$1
    local path="$LABS_DIR/$lab"

    # Dossier introuvable
    if [ ! -d "$path" ]; then
        err "Dossier introuvable : $path"
        ((COUNT_ERROR++))
        return 1
    fi

    # Cas 1 — docker-compose.yml présent → priorité absolue
    # Couvre les labs multi-conteneurs même s'ils ont aussi un Dockerfile
    # (ex: log4shell a Dockerfile + Dockerfile.ldap + docker-compose.yml)
    if [ -f "$path/docker-compose.yml" ]; then
        echo "[...] Pré-build des images compose pour $lab..."
        # Variables factices pour satisfaire Docker Compose pendant le build
        # Aucun impact sur les images — juste pour éviter les erreurs "variable non définie"
        if USER_ID=0 \
           LAB_PORT=9999 \
           VULN_PORT=9998 \
           VICTIM_PORT=9997 \
           N8N_PORT=9996 \
           docker compose -f "$path/docker-compose.yml" build; then
            ok "$lab — toutes les images compose buildées"
            ((COUNT_BUILD++))
        else
            err "Échec du build compose : $lab"
            ((COUNT_ERROR++))
            return 1
        fi
        return 0
    fi

    # Cas 2 — Dockerfile seul → build simple (single container)
    if [ -f "$path/Dockerfile" ]; then
        echo "[...] Build de sec-lab-$lab..."
        if docker build -t "sec-lab-$lab" "$path"; then
            ok "sec-lab-$lab buildé"
            ((COUNT_BUILD++))
        else
            err "Échec du build : sec-lab-$lab"
            ((COUNT_ERROR++))
            return 1
        fi
        return 0
    fi

    # Cas 3 — Fichier .image présent → pull depuis Docker Hub
    if [ -f "$path/.image" ]; then
        local image
        image=$(cat "$path/.image" | tr -d '[:space:]')
        info "Pull de l'image Docker Hub : $image"
        if docker pull "$image"; then
            ok "$image pullée"
            ((COUNT_PULL++))
        else
            err "Échec du pull : $image"
            ((COUNT_ERROR++))
            return 1
        fi
        return 0
    fi

    # Cas 4 — Dossier vide ou non reconnu
    warn "$lab — aucun Dockerfile, .image ou docker-compose.yml trouvé, ignoré"
    ((COUNT_SKIP++))
}

# ── Point d'entrée ────────────────────────────────────────────────────
LAB=$1

echo ""
echo "╔══════════════════════════════════════╗"
echo "║       Security Labs — Build          ║"
echo "╚══════════════════════════════════════╝"
echo ""
echo "  LABS_DIR : $LABS_DIR"
echo ""

if [ "$LAB" = "all" ] || [ -z "$LAB" ]; then
    # Builder tous les labs du dossier
    if [ ! -d "$LABS_DIR" ]; then
        err "Dossier LABS_DIR introuvable : $LABS_DIR"
        exit 1
    fi

    for lab_path in "$LABS_DIR"/*/; do
        [ -d "$lab_path" ] || continue
        lab=$(basename "$lab_path")
        echo "─── $lab"
        build_one "$lab"
        echo ""
    done

else
    # Builder un lab spécifique
    echo "─── $LAB"
    build_one "$LAB"
    echo ""
fi

# ── Résumé ────────────────────────────────────────────────────────────
echo "╔══════════════════════════════════════╗"
echo "║              Résumé                  ║"
echo "╠══════════════════════════════════════╣"
printf "║  ✓ Buildés       : %-18s║\n" "$COUNT_BUILD"
printf "║  ↓ Pullés        : %-18s║\n" "$COUNT_PULL"
printf "║  ~ Ignorés       : %-18s║\n" "$COUNT_SKIP"
printf "║  ✗ Erreurs       : %-18s║\n" "$COUNT_ERROR"
echo "╚══════════════════════════════════════╝"
echo ""

# Retourner un code d'erreur si des builds ont échoué
[ "$COUNT_ERROR" -eq 0 ] && exit 0 || exit 1
