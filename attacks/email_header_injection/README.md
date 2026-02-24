# Email Header Injection (CRLF) – Lab pédagogique

# Technologies utilisées

- Python (Flask)
- SQLite
- HTML / CSS / JavaScript
- Docker & Docker Compose

---

# Technologies utilisées

- Python (Flask)
- SQLite
- HTML / CSS / JavaScript
- Docker & Docker Compose

---

# Arborescence du projet
lab/
├── README.md
├── app.py
├── requirements.txt
├── Dockerfile
├── docker-compose.yml
├── events.db (généré automatiquement)
│
├── templates/
│ ├── victim_inbox.html
│ └── attacker_dashboard.html
│
└── static/
├── css/
│ ├── gmail.css
│ └── attacker.css
└── js/
├── victim.js
└── attacker.js*
│
├── data
│ └── events.db

# Lancer le projet avec Docker

## Prérequis

- Docker Desktop (Windows/macOS)
- ou Docker Engine (Linux)

---

## Démarrage

Dans le dossier du projet :

```bash
docker compose up --build

#Accès aux interfaces (PORT 8080)

Victime : http://localhost:8080/

Attaquant : http://localhost:8080/attacker

API Events : http://localhost:8080/api/events

API Metrics : http://localhost:8080/api/metrics

#Ctrl + C
docker compose down

#Développement sans build
#Pour éviter de reconstruire l’image à chaque modification, ajouter un volume dans docker-compose.yml :
services:
  lab:
    build: .
    ports:
      - "8080:5000"
    volumes:
      - .:/app

docker compose up

#Reset de la base d’événements
docker compose down
rm -f events.db   # Linux/macOS
docker compose up