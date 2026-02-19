#  Guide de Structure des Containers - Projet DIABLE v3.0

**Auteur:** Kennedy 
**Date:** 02 Février 2026  
**Version:** 1.0  
**Projet:** Lab DIABLE - DSI ISFA 2025-2026

---

##  Objectif

Ce document définit la structure standardisée que **tous les conteneurs** du projet DIABLE doivent respecter pour garantir la cohérence, la maintenabilité et l'intégration avec la plateforme.

---

##  Structure Standard d'un Conteneur

Chaque conteneur DIABLE doit suivre cette arborescence obligatoire :

```
nom-du-lab/
├── Dockerfile                 #  OBLIGATOIRE - Image Docker
├── docker-compose.yml         #  OBLIGATOIRE - Configuration orchestration
├── .dockerignore              #  OBLIGATOIRE - Exclusions build
├── README.md                  #  OBLIGATOIRE - Documentation complète
├── src/                       #  OBLIGATOIRE - Code source application
│   ├── index.php              # Page d'accueil
│   ├── config.php             # Configuration
│   ├── style.css              # Thème DIABLE
│   ├── health.php             # Health check endpoint
│   ├── reset.php              # Reset endpoint
│   └── [autres fichiers]      # Fichiers spécifiques au lab

```

---

##  Description des Fichiers Obligatoires

### 1. **Dockerfile** 

Le Dockerfile définit l'image Docker du conteneur.

**Template standard :**

```dockerfile
FROM [base-image]:[version]

# Métadonnées OBLIGATOIRES
LABEL maintainer="DIABLE Team"
LABEL description="[Description du lab]"
LABEL version="1.0"

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    [dépendances] \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configuration du serveur web (si applicable)
RUN [commandes de configuration]

# Copie du code source
WORKDIR /var/www/html
COPY src/ /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Initialisation des données (si applicable)
RUN [script d'initialisation]

# Port exposé
EXPOSE [port]

# Variables d'environnement
ENV DEBUG_MODE=false
ENV [autres variables]

# Health check OBLIGATOIRE
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

# Commande de démarrage
CMD [commande]
```

**Exemples concrets :**

- **Lab SQL Injection :** `FROM php:8.1-apache`
- **Lab XPath Injection :** `FROM php:8.1-apache`
- **Lab XXE :** `FROM php:8.1-apache`
- **Lab Command Injection :** `FROM python:3.11-slim`

---

### 2. **docker-compose.yml** 

Configuration pour Docker Compose permettant un déploiement simplifié.

**Template standard :**

```yaml
version: '3.8'

services:
  [nom-du-service]:
    build: .
    image: diable/[nom-du-lab]:latest
    container_name: [nom-du-container]
    ports:
      - "[port-externe]:[port-interne]"
    environment:
      - DEBUG_MODE=false
      - [autres variables]
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health.php"]
      interval: 30s
      timeout: 3s
      retries: 3
      start_period: 5s
    labels:
      - "diable.lab=[nom-technique]"
      - "diable.difficulty=[easy|medium|hard]"
      - "diable.tag=[DB|XML|API|WEB|etc]"
    networks:
      - diable-network

networks:
  diable-network:
    name: diable-network
    driver: bridge
```

**Exemple concret :**

```yaml
version: '3.8'

services:
  sqli-comments-lab:
    build: .
    image: diable/sqli-comments-lab:latest
    container_name: sqli-lab
    ports:
      - "8080:80"
    environment:
      - DEBUG_MODE=false
      - DB_PATH=/var/www/html/database.db
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health.php"]
      interval: 30s
      timeout: 3s
      retries: 3
      start_period: 5s
    labels:
      - "diable.lab=sqli-comments"
      - "diable.difficulty=medium"
      - "diable.tag=DB"
    networks:
      - diable-network

networks:
  diable-network:
    name: diable-network
    driver: bridge
```

---

### 3. **.dockerignore** 

Fichier d'exclusion pour optimiser le build Docker.

**Template standard :**

```
.git
.gitignore
README.md
docs/
docker-compose.yml
.dockerignore
.vscode/
.idea/
*.swp
*.swo
*~
.DS_Store
Thumbs.db
*.log
*.tmp
*.bak
node_modules/
__pycache__/
*.pyc
.env
```

---

### 4. **README.md** 

Documentation complète du lab (voir section dédiée ci-dessous).

---

### 5. **src/** 

Dossier contenant le code source de l'application vulnérable.

**Fichiers obligatoires dans src/ :**

```
src/
├── index.php (ou index.html/index.py)  #  Page d'accueil
├── config.php (ou config.py/config.js) #  Configuration
├── style.css                           #  Thème DIABLE (palette standardisée)
├── health.php                          #  Endpoint health check
├── reset.php                           #  Endpoint reset
└── [fichiers spécifiques]              #  Selon le lab
```

---

##  Structure du README.md

Chaque README doit contenir **exactement** ces sections dans cet ordre :

```markdown
# Lab DIABLE - [Nom de l'Attaque]

**Version:** 1.0  
**Auteur:** [Nom Prénom]  
**Tag:** [DB|XML|API|WEB|etc]  
**Difficulté:** [Facile|Moyen|Difficile]

---

##  Description

[Description concise de la vulnérabilité - 2-3 phrases]

---

##  Objectifs pédagogiques

- Objectif 1
- Objectif 2
- Objectif 3

---

##  Installation et démarrage

### Prérequis

- Docker Desktop ou Docker Engine
- [Autres prérequis]

### Démarrage rapide

\`\`\`bash
git clone [URL]
cd [nom-du-lab]
docker-compose up -d
http://localhost:[port]
\`\`\`

### Build manuel

\`\`\`bash
docker build -t diable/[nom-du-lab] .
docker run -d -p [port]:80 --name [nom] diable/[nom-du-lab]
\`\`\`

---

##  Structure du projet

\`\`\`
[arborescence]
\`\`\`

---

##  Scénarios disponibles

### Scénario 1: [Nom]

**Objectif:** [description]

**Cible:** [fichier/endpoint]

**Technique:** [nom de la technique]

**Payload:**
\`\`\`
[payload]
\`\`\`

**Résultat:** [description du résultat]

[Répéter pour chaque scénario]

---

##  Comptes de test

| Username | Password | Rôle |
|----------|----------|------|
| admin | ??? | Administrateur |
| user | password | Utilisateur |

---

##  Théorie: [Nom de la vulnérabilité]

[Explication théorique détaillée]

---

##  Mesures de protection

### 1. [Solution 1]

\`\`\`[langage]
[code sécurisé]
\`\`\`

[Répéter pour chaque solution]

---

##  Commandes utiles

\`\`\`bash
# Voir les logs
docker logs -f [nom-container]

# Reset
curl http://localhost:[port]/reset.php

# Health check
curl http://localhost:[port]/health.php
\`\`\`

---

##  Statistiques

- X fichiers créés
- X scénarios d'attaque
- Temps de développement: X heures

---

##  Dépannage

[Solutions aux problèmes courants]

---

##  Licence

Lab développé pour le projet DIABLE v3.0 (DSI ISFA 2025-2026).

**⚠️ Avertissement:** Vulnérabilités intentionnelles à des fins éducatives. Ne JAMAIS déployer en production.

---

##  Auteur

**[Prénom]**  
DSI ISFA 2025-2026  
```

---

##  Thème Visuel Standard (style.css)

Tous les labs DIABLE doivent utiliser la **même palette de couleurs** pour une cohérence visuelle.

**Palette DIABLE obligatoire :**

```css
:root {
    /* Palette principale */
    --bg-global: #0B0F14;        /* Fond global */
    --bg-header: #0F1A2B;        /* Header/Navigation */
    --bg-card: #141B26;          /* Cards/Sections */
    --btn-primary: #D41414;      /* Bouton principal */
    --btn-primary-hover: #FF2A2A; /* Bouton hover */
    --link-color: #1F6AFF;       /* Liens */
    --glow-color: #00C2FF;       /* Effets glow */
    --text-primary: #F2F4F8;     /* Texte principal */
    --text-secondary: #8A8F98;   /* Texte secondaire */
    
    /* Couleurs secondaires */
    --success-color: #16a34a;
    --error-color: #dc2626;
    --warning-color: #ea580c;
    --border-color: #1F2937;
    --border-glow: rgba(0, 194, 255, 0.3);
}
```

**Fichier style.css complet :** Utiliser le template du lab SQL Injection ou XPath Injection comme référence.

---

##  Endpoints Obligatoires

Tous les labs doivent exposer ces endpoints :

### 1. **Health Check** - `/health.php`

Retourne un JSON avec le statut du service.

**Exemple de réponse :**

```json
{
  "status": "healthy",
  "service": "nom-du-lab",
  "timestamp": "2026-02-02 22:00:00",
  "checks": {
    "database": {
      "status": "ok",
      "path": "/path/to/db"
    }
  }
}
```

**Code HTTP :**
- `200` si healthy
- `503` si unhealthy

---

### 2. **Reset** - `/reset.php`

Réinitialise les données à leur état initial.

**Fonctionnalités :**
- Réinitialiser la base de données
- Restaurer les fichiers XML
- Afficher un message de confirmation

---

##  Labels Docker Obligatoires

Chaque container doit avoir ces labels dans `docker-compose.yml` :

```yaml
labels:
  - "diable.lab=[nom-technique]"           # Ex: sqli-comments, xpath-injection
  - "diable.difficulty=[easy|medium|hard]" # Niveau de difficulté
  - "diable.tag=[DB|XML|API|WEB|NET]"     # Catégorie technique
```

**Tags disponibles :**
- `DB` : Bases de données (SQL, NoSQL)
- `XML` : Technologies XML (XPath, XXE)
- `API` : API REST, GraphQL
- `WEB` : Web classique (XSS, CSRF)
- `NET` : Réseau, protocoles
- `AUTH` : Authentification
- `CRYPTO` : Cryptographie

---

##  Naming Convention

### Nom du dossier :

```
[type]-[nom]-lab
```

Exemples :
- `sqli-comments-lab`
- `xpath-injection-lab`
- `xxe-file-read-lab`
- `command-injection-lab`

### Nom de l'image Docker :

```
diable/[nom-du-lab]
```

Exemples :
- `diable/sqli-comments-lab`
- `diable/xpath-injection-lab`

### Nom du container :

```
[nom-court]-lab
```

Exemples :
- `sqli-lab`
- `xpath-lab`
- `xxe-lab`




##  Workflow de Développement Recommandé

### 1. Initialisation

```bash
# Créer la structure
mkdir [nom]-lab
cd [nom]-lab
mkdir src docs

# Créer les fichiers de base
touch Dockerfile docker-compose.yml .dockerignore README.md
touch src/{index.php,config.php,style.css,health.php,reset.php}
```

### 2. Développement

1. Coder l'application vulnérable dans `src/`
2. Tester localement avec `docker build` et `docker run`
3. Documenter au fur et à mesure dans README.md

### 3. Finalisation

1. Appliquer le thème DIABLE
2. Ajouter health check et reset
3. Créer le breakdown WP1
4. Tester tous les scénarios
5. Valider avec la checklist

### 4. Soumission

1. Pusher sur GitHub
2. Partager le lien du repo
3. Partager le breakdown avec WP1
4. Documenter dans le rapport individuel


---

##  Historique des Versions

| Version | Date | Auteur | Changements |
|---------|------|--------|-------------|
| 1.0 | 02/02/2026 | Kennedy | Version initiale |

---

**Bon développement !**
