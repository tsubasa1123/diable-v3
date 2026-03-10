#!/bin/bash
# scripts/setup.sh — Installation complète sur le VPS
# Usage : sudo bash setup.sh
set -e

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
log()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }

echo ""
echo "╔══════════════════════════════════════╗"
echo "║     Security Labs — Setup VPS        ║"
echo "╚══════════════════════════════════════╝"
echo ""

# ── 1. Variables ──────────────────────────────────────────────────────
LABS_DIR="/opt/labs"
MANAGER_DIR="/opt/lab-manager"

read -p "IP ou hostname du VPS (ex: 192.168.1.10) : " BASE_HOST
read -p "Clé API secrète (partagée avec le site) : " API_SECRET
[ -z "$BASE_HOST" ]  && err "BASE_HOST ne peut pas être vide"
[ -z "$API_SECRET" ] && err "API_SECRET ne peut pas être vide"

# ── 2. Docker ─────────────────────────────────────────────────────────
if ! command -v docker &> /dev/null; then
    warn "Docker non trouvé, installation en cours..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker && systemctl start docker
    log "Docker installé"
else
    log "Docker déjà installé ($(docker --version))"
fi

# ── 3. Réseau Docker partagé ──────────────────────────────────────────
if ! docker network ls | grep -q "lab-network"; then
    docker network create lab-network
    log "Réseau Docker 'lab-network' créé"
else
    log "Réseau Docker 'lab-network' déjà existant"
fi

# ── 4. Dossier des labs ───────────────────────────────────────────────
mkdir -p "$LABS_DIR"
log "Dossier labs créé : $LABS_DIR"

warn "Copie tes labs dans $LABS_DIR :"
warn "  $LABS_DIR/xss/       (avec Dockerfile)"
warn "  $LABS_DIR/sqli/      (avec Dockerfile)"
warn "  $LABS_DIR/xpath/     (avec Dockerfile)"
warn "  $LABS_DIR/csrf/      (avec Dockerfile)"
warn "  $LABS_DIR/cmdinjection/ (avec Dockerfile)"

# ── 5. Build des images labs ──────────────────────────────────────────
echo ""
echo "Build des images Docker des labs..."
for lab in xss sqli xpath csrf cmdinjection; do
    LAB_PATH="$LABS_DIR/$lab"
    if [ -d "$LAB_PATH" ] && [ -f "$LAB_PATH/Dockerfile" ]; then
        docker build -t "sec-lab-$lab" "$LAB_PATH"
        log "Image sec-lab-$lab buildée"
    else
        warn "Lab $lab introuvable dans $LAB_PATH — à builder plus tard"
    fi
done

# ── 6. Lab Manager ────────────────────────────────────────────────────
mkdir -p "$MANAGER_DIR"
cp -r . "$MANAGER_DIR/"

# Créer le .env
cat > "$MANAGER_DIR/.env" <<EOF
BASE_HOST=$BASE_HOST
API_PORT=4000
API_SECRET=$API_SECRET
LAB_TTL=2700
LABS_DIR=$LABS_DIR
DOCKER_NETWORK=lab-network
ALLOWED_ORIGIN=*
EOF

log ".env créé dans $MANAGER_DIR"

# Démarrer le Lab Manager
cd "$MANAGER_DIR"
docker compose up -d --build
log "Lab Manager démarré sur le port 4000"

# ── 7. Ouvrir les ports firewall ──────────────────────────────────────
if command -v ufw &> /dev/null; then
    ufw allow 4000/tcp comment "Lab Manager API"   2>/dev/null || true
    ufw allow 8000:8999/tcp comment "Labs apprenants" 2>/dev/null || true
    log "Ports ouverts dans UFW (4000 et 8000-8999)"
fi

# ── 8. Test de l'API ──────────────────────────────────────────────────
sleep 3
if curl -sf "http://localhost:4000/api/health" > /dev/null; then
    log "API Lab Manager répond correctement ✓"
else
    warn "L'API ne répond pas encore — attends quelques secondes puis teste :"
    warn "  curl http://localhost:4000/api/health"
fi

# ── Résumé ────────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║                    SETUP TERMINÉ                    ║"
echo "╠══════════════════════════════════════════════════════╣"
printf "║  API Lab Manager : http://%-27s║\n" "$BASE_HOST:4000"
printf "║  Clé API         : %-31s║\n" "$API_SECRET"
echo "║                                                      ║"
echo "║  → Donne la clé API à l'autre développeur           ║"
echo "║  → Vérifie : curl http://$BASE_HOST:4000/api/health  ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
