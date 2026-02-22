
# Technologies utilisées

- Python (Flask)
- SQLite
- HTML / CSS / JavaScript
- Docker & Docker Compose

---

# Arborescence du projet

```
lab/
│
├── app.py
├── requirements.txt
├── Dockerfile
├── docker-compose.yml
├── events.db (généré automatiquement)
│
├── templates/
│   ├── victim_inbox.html
│   ├── victim_login.html
│   ├── victim_success.html
│   ├── attacker_dashboard.html
│
└── static/
    ├── css/
    │   ├── gmail.css
    │   └── attacker.css
    └── js/
        ├── victim.js
        └── attacker.js
```

---

# Lancer le projet avec Docker

## Prérequis

- Docker Desktop (Windows/macOS)
- ou Docker Engine (Linux)

---

## Démarrage

Dans le dossier du projet :

```bash
docker compose up --build
```

---

## Accès aux différentes interfaces

- Victime : http://localhost:5000/
- Attaquant : http://localhost:5000/attacker
- API Events : http://localhost:5000/api/events
- API Metrics : http://localhost:5000/api/metrics

---

## Arrêter le projet

```bash
Ctrl + C
docker compose down
```

---

# Développement sans rebuild

Pour éviter de reconstruire l’image à chaque modification, ajouter un volume dans `docker-compose.yml` :

```yaml
services:
  lab:
    build: .
    ports:
      - "5000:5000"
    volumes:
      - .:/app
```

Puis relancer :

```bash
docker compose up --build
```

Les modifications HTML / CSS / JS seront visibles après un simple refresh navigateur.

---

# Reset de la base d’événements

Les événements sont stockés dans un fichier SQLite local `events.db`.

## Reset simple

1. Arrêter le lab
2. Supprimer `events.db`
3. Relancer

```bash
docker compose down
rm -f events.db   # Linux/macOS
docker compose up --build
```

Sous Windows, supprimer le fichier via l’explorateur.

---
