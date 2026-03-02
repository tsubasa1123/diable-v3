# 🎓 Lab DIABLE v3.0 - Digital Infrastructure for Advanced Benign Learning Environments

**Projet:** DSI ISFA 2025-2026  
**Version:** 3.0  
**Date:** Février 2026

---

## 📋 Vue d'ensemble

DIABLE v3.0 est une plateforme pédagogique complète pour l'apprentissage pratique de la cybersécurité offensive et défensive. Le projet fournit des environnements d'apprentissage isolés (containers Docker) permettant aux étudiants d'explorer des vulnérabilités réelles dans un cadre sécurisé et contrôlé.

---

## 🏗️ Architecture du Projet

```
diable/
├── frontend/           # WP2 - Interface utilisateur React
├── backend/            # WP1 - API de gestion
├── orchestrator/       # WP1 - Orchestration Kubernetes
├── attacks/            # WP3 - Labs de cybersécurité
│   ├── sqli-lab/      # SQL Injection
│   ├── xpath-lab/     # XPath Injection
│   ├── xxe-lab/       # XML External Entity
│   ├── xss-lab/       # Cross-Site Scripting
│   └── csrf-lab/      # Cross-Site Request Forgery
├── docker-compose.yml  # Configuration locale
└── docs/              # Documentation globale
```

---

## 🎯 Work Packages

### WP1 - Infrastructure & Orchestration
**Responsables:** [Noms des étudiants]

**Contenu:**
- `backend/` : API REST pour la gestion des labs
- `orchestrator/` : Orchestration Kubernetes des containers
- Monitoring et logging
- API de santé et reset

**Technologies:** Node.js, Kubernetes, Docker, PostgreSQL

---

### WP2 - Interface Web
**Responsables:** [Noms des étudiants]

**Contenu:**
- `frontend/` : Application React pour les utilisateurs
- Dashboard étudiant
- Catalogue des labs
- Système de progression

**Technologies:** React, Tailwind CSS, TypeScript

---

### WP3 - Containers & Documentation
**Responsables:** Kennedy NGOKIA, [Autres étudiants]

**Contenu:**
- `attacks/` : Labs de cybersécurité conteneurisés
- Documentation technique
- Guides pédagogiques

**Technologies:** Docker, PHP, Python, Node.js

---

## 📦 Labs Disponibles

### 1. SQL Injection Lab (`attacks/sqli-lab/`)
**Difficulté:** Moyen  
**Tag:** DB  
**Auteur:** Kennedy NGOKIA

**Scénarios:**
- Login Bypass avec commentaires SQL
- Data Extraction avec UNION SELECT
- Privilege Escalation avec UPDATE injection

---

### 2. XPath Injection Lab (`attacks/xpath-lab/`)
**Difficulté:** Moyen  
**Tag:** XML  
**Auteur:** Kennedy NGOKIA

**Scénarios:**
- Login Bypass avec `or`
- Data Extraction avec union `|`
- Blind XPath avec `substring()`

---

### 3. XXE Lab (`attacks/xxe-lab/`)
**Difficulté:** Difficile  
**Tag:** XML  
**Auteur:** [À assigner]

**Scénarios:**
- File Read local
- SSRF vers services internes
- Denial of Service

---

### 4. XSS Lab (`attacks/xss-lab/`)
**Difficulté:** Facile à Moyen  
**Tag:** WEB  
**Auteur:** [À assigner]

**Scénarios:**
- Reflected XSS
- Stored XSS
- DOM-based XSS

---

### 5. CSRF Lab (`attacks/csrf-lab/`)
**Difficulté:** Moyen  
**Tag:** WEB  
**Auteur:** [À assigner]

**Scénarios:**
- CSRF simple
- CSRF avec token bypassable
- CSRF avec SameSite cookies

---

### 6. JWT Vulnerabilities Lab (`attacks/jwt-lab/`)
**Difficulté:** Moyen → Difficile  
**Tag:** JWT  
**Auteur:** Thiané DIA  

**Scénarios:**
- alg:none Bypass (CVE-2015-9235)
- Weak Secret Brute Force
- RS256 → HS256 Algorithm Confusion
- kid Header Injection
```

---

## 🚀 Installation Complète

### Prérequis

- Docker Desktop (Windows/Mac) ou Docker Engine (Linux)
- Docker Compose
- Node.js 18+ (pour frontend/backend)
- Kubernetes (optionnel, pour orchestration)
- Git

### Installation Locale

```bash
# Cloner le projet
git clone https://github.com/[organisation]/diable.git
cd diable

# Lancer tous les services avec Docker Compose
docker-compose up -d

# Accéder à la plateforme
http://localhost:3000  # Frontend
http://localhost:5000  # Backend API
http://localhost:8080  # SQL Injection Lab
http://localhost:8081  # XPath 
http://localhost:8084  # JWT Vulnerabilities Lab
Injection Lab
```

### Installation d'un Lab Individuel

```bash
cd attacks/sqli-lab
docker-compose up -d
http://localhost:8080
```

---

## 📂 Structure Détaillée

### `frontend/` - Interface Utilisateur

```
frontend/
├── src/
│   ├── components/    # Composants React
│   ├── pages/        # Pages de l'application
│   ├── services/     # API calls
│   └── styles/       # CSS/Tailwind
├── public/
├── package.json
└── README.md
```

### `backend/` - API de Gestion

```
backend/
├── src/
│   ├── controllers/  # Logique métier
│   ├── models/       # Modèles de données
│   ├── routes/       # Endpoints API
│   └── middleware/   # Auth, logging
├── config/
├── package.json
└── README.md
```

### `orchestrator/` - Orchestration Kubernetes

```
orchestrator/
├── manifests/        # YAML Kubernetes
│   ├── deployments/
│   ├── services/
│   └── ingress/
├── helm/            # Charts Helm
└── README.md
```

### `attacks/` - Labs de Cybersécurité

```
attacks/
├── sqli-lab/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── src/
│   ├── README.md
│   └── docs/
├── xpath-lab/
│   └── [même structure]
├── xxe-lab/
├── xss-lab/
└── csrf-lab/
```

### `docs/` - Documentation

```
docs/
├── STRUCTURE_CONTAINERS.md  # Guide structure labs
├── ARCHITECTURE.md           # Architecture globale
├── API.md                   # Documentation API
├── CONTRIBUTION.md          # Guide contribution
└── DEPLOYMENT.md            # Guide déploiement
```

---

## 🔧 docker-compose.yml Global

Le fichier `docker-compose.yml` à la racine permet de lancer tous les services simultanément.

```yaml
version: '3.8'

services:
  # Frontend WP2
  frontend:
    build: ./frontend
    ports:
      - "3000:3000"
    environment:
      - REACT_APP_API_URL=http://localhost:5000
    networks:
      - diable-network

  # Backend WP1
  backend:
    build: ./backend
    ports:
      - "5000:5000"
    environment:
      - DATABASE_URL=postgresql://diable:password@db:5432/diable
    depends_on:
      - db
    networks:
      - diable-network

  # Database
  db:
    image: postgres:15
    environment:
      - POSTGRES_DB=diable
      - POSTGRES_USER=diable
      - POSTGRES_PASSWORD=password
    volumes:
      - postgres-data:/var/lib/postgresql/data
    networks:
      - diable-network

  # SQL Injection Lab
  sqli-lab:
    build: ./attacks/sqli-lab
    ports:
      - "8080:80"
    labels:
      - "diable.lab=sqli-comments"
      - "diable.difficulty=medium"
      - "diable.tag=DB"
    networks:
      - diable-network

  # XPath Injection Lab
  xpath-lab:
    build: ./attacks/xpath-lab
    ports:
      - "8081:80"
    labels:
      - "diable.lab=xpath-injection"
      - "diable.difficulty=medium"
      - "diable.tag=XML"
    networks:
      - diable-network

  # JWT Vulnerabilities Lab
  jwt-lab:
    build: ./attacks/jwt-lab
    ports:
      - "8084:3000"
    labels:
      - "diable.lab=jwt-vulnerabilities"
      - "diable.difficulty=medium-hard"
      - "diable.tag=JWT"
    networks:
      - diable-network

networks:
  diable-network:
    driver: bridge

volumes:
  postgres-data:
```

---

## 🎨 Thème Visuel

Tous les labs partagent le thème DIABLE standardisé :

```css
:root {
    --bg-global: #0B0F14;
    --bg-header: #0F1A2B;
    --bg-card: #141B26;
    --btn-primary: #D41414;
    --btn-primary-hover: #FF2A2A;
    --link-color: #1F6AFF;
    --glow-color: #00C2FF;
    --text-primary: #F2F4F8;
    --text-secondary: #8A8F98;
}
```

---

## 📊 Progression Pédagogique

### Niveau 1 - Débutant
- [ ] XSS Reflected
- [ ] SQL Injection - Login Bypass
- [ ] Command Injection basique

### Niveau 2 - Intermédiaire
- [x] SQL Injection - Data Extraction
- [x] XPath Injection - Union
- [ ] CSRF
- [ ] XXE - File Read

### Niveau 3 - Avancé
- [x] SQL Injection - Privilege Escalation
- [x] XPath Injection - Blind
- [ ] XXE - SSRF to RCE
- [ ] Deserialization

---

## 🤝 Contribution

### Ajouter un Nouveau Lab

1. Créer un dossier dans `attacks/[nom]-lab/`
2. Suivre la structure standard (voir `docs/STRUCTURE_CONTAINERS.md`)
3. Inclure Dockerfile, docker-compose.yml, README.md
4. Ajouter l'entrée dans le docker-compose.yml global
5. Documenter dans ce README

### Standards

✅ Structure conforme  
✅ Thème DIABLE appliqué  
✅ Health check endpoint  
✅ Reset endpoint  
✅ Documentation complète  
✅ Au moins 2 scénarios fonctionnels

---

## 📞 Contact

### Responsables par Work Package

**WP1 - Infrastructure:**
- Email: wp1@diable-project.fr (fictif)

**WP2 - Frontend:**
- Email: wp2@diable-project.fr (fictif)

**WP3 - Containers:**
- Kennedy NGOKIA: ngokiakennedy@gmail.com
- Email général: wp3@diable-project.fr (fictif)

---

## 📜 Licence

Projet pédagogique - DSI ISFA 2025-2026

⚠️ **Vulnérabilités intentionnelles** - Usage éducatif uniquement

---

## 📊 Statistiques

- **Labs disponibles:** 3
- **Labs en développement:** 3+
- **Work Packages:** 3
- **Contributeurs:** 10+
- **Lignes de code:** 5000+

---

**Bienvenue dans DIABLE ! 🚀**

_Learn. Hack. Secure._
