# 🔐 JWT Vulnerabilities Lab — DIABLE v3.0

**Auteure :** Thiané DIA  
**Promo :** DSI ISFA 2025-2026  
**Difficulté :** Moyen → Difficile  
**Tags :** JWT, Authentication Bypass, Brute Force, Algorithm Confusion

---

## 📋 Description

Ce lab explore les 4 principales vulnérabilités des JSON Web Tokens (JWT), qui sont massivement utilisés pour l'authentification dans les APIs web modernes.

---

## 🎯 Scénarios

| # | Attaque | Difficulté | Flag |
|---|---------|-----------|------|
| 1 | alg:none Bypass | Moyen | `DIABLE{jwt_alg_none_bypass}` |
| 2 | Weak Secret Brute Force | Moyen | `DIABLE{jwt_weak_secret_cracked}` |
| 3 | RS256 → HS256 Confusion | Difficile | `DIABLE{jwt_rs256_to_hs256_confusion}` |
| 4 | kid Header Injection | Difficile | `DIABLE{jwt_kid_injection_pwned}` |

---

## 🚀 Lancement

### Option 1 — Docker Compose (recommandé)
```bash
# Créer le réseau DIABLE si pas encore fait
docker network create diable-network

# Lancer le lab
docker-compose up -d

# Accéder au lab
http://localhost:8084
```

### Option 2 — Lab individuel
```bash
npm install
npm start
# http://localhost:3000
```

### Option 3 — Intégration dans le docker-compose global DIABLE
Ajoutez dans le `docker-compose.yml` racine :
```yaml
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
```

---

## 🏗️ Structure

```
jwt-lab/
├── Dockerfile
├── docker-compose.yml
├── package.json
├── README.md
└── src/
    ├── server.js          # Serveur Express + logique vulnérable
    └── public/
        ├── index.html     # Page d'accueil DIABLE
        ├── scenario1.html # alg:none bypass
        ├── scenario2.html # Brute force secret
        ├── scenario3.html # RS256→HS256 confusion
        └── scenario4.html # kid injection
```

---

## 📡 Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /` | Page d'accueil |
| `GET /health` | Health check |
| `POST /reset` | Reset du lab |
| `POST /scenario1/login` | Login (retourne token) |
| `POST /scenario1/admin` | Zone admin (vulnérable alg:none) |
| `POST /scenario2/login` | Login (clé faible) |
| `POST /scenario2/bruteforce` | Endpoint de test brute force |
| `POST /scenario2/admin` | Zone admin (vérifie HS256 faible) |
| `GET /scenario3/public-key` | Clé publique RSA exposée |
| `POST /scenario3/admin` | Zone admin (vulnérable confusion) |
| `POST /scenario4/login` | Login (token avec kid) |
| `POST /scenario4/admin` | Zone admin (vulnérable kid injection) |

---

## 🛡️ Contre-mesures (résumé)

1. **alg:none** → Toujours spécifier `{ algorithms: ['HS256'] }` côté serveur
2. **Brute force** → Clé de 256+ bits aléatoire, jamais de mot du dictionnaire
3. **RS256/HS256** → Ne jamais accepter plusieurs algorithmes sans contrôle strict
4. **kid injection** → Whitelist des kid valides, ne jamais les utiliser comme chemins

---

## 🔧 Outils recommandés

- [jwt.io](https://jwt.io) — Décodage/encodage JWT
- `hashcat -m 16500` — Brute force JWT
- [jwt_tool](https://github.com/ticarpi/jwt_tool) — Toolkit complet
- Python `PyJWT` — Forge de tokens

---

*DIABLE v3.0 — Usage pédagogique uniquement — DSI ISFA 2025-2026*
