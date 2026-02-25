# Attacks - Labs de Cybersécurité

**Work Package 3** - Containers & Documentation

---

## 📋 Description

Dossier contenant tous les labs de cybersécurité conteneurisés. Chaque lab est un environnement isolé permettant d'apprendre une vulnérabilité spécifique.

---

## 📦 Labs Disponibles

### ✅ sqli-lab/ - SQL Injection
**Auteur:** Kennedy NGOKIA  
**Difficulté:** Moyen  
**Tag:** DB  
**Port:** 8080

**Scénarios:**
1. Login Bypass avec commentaires
2. Data Extraction (UNION SELECT)
3. Privilege Escalation (UPDATE)

---

### ✅ xpath-lab/ - XPath Injection
**Auteur:** Kennedy NGOKIA  
**Difficulté:** Moyen  
**Tag:** XML  
**Port:** 8081

**Scénarios:**
1. Login Bypass (`or`)
2. Data Extraction (union `|`)
3. Blind XPath (`substring()`)

---

### 🔨 xxe-lab/ - XML External Entity
**Auteur:** [À assigner]  
**Difficulté:** Difficile  
**Tag:** XML  
**Port:** 8090

**Scénarios:**
1. File Read local
2. SSRF vers services internes
3. Denial of Service

---

### 🔨 xss-lab/ - Cross-Site Scripting
**Auteur:** [À assigner]  
**Difficulté:** Facile à Moyen  
**Tag:** WEB  
**Port:** 8100

**Scénarios:**
1. Reflected XSS
2. Stored XSS
3. DOM-based XSS

---

### 🔨 csrf-lab/ - Cross-Site Request Forgery
**Auteur:** [À assigner]  
**Difficulté:** Moyen  
**Tag:** WEB  
**Port:** 8110

**Scénarios:**
1. CSRF simple
2. CSRF avec token bypassable
3. CSRF avec SameSite cookies

### ✅ nosql-injection-lab/ - NoSQL Injection
**Auteur:** Celia IMAKHLOUFEN
**Difficulté:** Moyen  
**Tag:** WEB  
**Port:** 3000

---

## 🏗️ Structure Standard d'un Lab

Chaque lab doit suivre cette structure :

```
[nom]-lab/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
├── src/
│   ├── index.php
│   ├── config.php
│   ├── style.css
│   ├── health.php
│   └── reset.php
└── docs/
    └── breakdown_wp1.md
```

**Documentation complète:** `../docs/STRUCTURE_CONTAINERS.md`

---

## 🚀 Lancer un Lab

```bash
# Lab individuel
cd sqli-lab
docker-compose up -d
http://localhost:8080

# Tous les labs (depuis la racine)
docker-compose up -d
```

---

## 🤝 Ajouter un Nouveau Lab

1. **Consulter** `../docs/STRUCTURE_CONTAINERS.md`
2. **Créer** le dossier `[nom]-lab/`
3. **Suivre** la structure standard
4. **Tester** tous les scénarios
5. **Documenter** complètement
6. **Ajouter** l'entrée dans `../docker-compose.yml`

---

## 📊 Progression

| Lab | Auteur | Statut | Scénarios |
|-----|--------|--------|-----------|
| sqli-lab | Kennedy NGOKIA | ✅ Complet | 3/3 |
| xpath-lab | Kennedy NGOKIA | ✅ Complet | 3/3 |
| xxe-lab | [À assigner] | 🔨 À développer | 0/3 |
| xss-lab | [À assigner] | 🔨 À développer | 0/3 |
| csrf-lab | [À assigner] | 🔨 À développer | 0/3 |

---

## 📞 Contact

**Responsable WP3:** Kennedy NGOKIA  
**Email:** ngokiakennedy@gmail.com  
**GitHub:** [@tsubasa1123](https://github.com/tsubasa1123)
