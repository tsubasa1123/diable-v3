# Lab DIABLE - Injection SQL

**Version:** 1.1  
**Auteur:** Lucien Hanquiez
**Tag:** DB  
**Difficulté:** Moyen

---

## Description

Ce lab Docker met en scène une application PHP/SQLite volontairement vulnérable à l'injection SQL. Il permet d'observer un bypass d'authentification, une extraction par `UNION`, puis une déduction de secret en blind SQLi booléenne, dans un environnement local isolé.

---

## Nouveautés de cette version

- Validation corrigée du niveau **medium**
- Indices intégrés pour **easy**, **medium** et **hard**
- Apparition automatique du **4e onglet flag** quand les 3 niveaux sont validés
- Interface graphique retravaillée dans un style proche des CSS fournis
- Endpoints techniques conservés pour le fonctionnement interne, mais non exposés dans l'interface

---

## Installation et démarrage

```bash
cd sqli-auth-lab
docker compose up -d --build
```

Application :

```text
http://localhost:8080
```

---

## Progression pédagogique

### Easy

Objectif : contourner l'authentification en influençant la clause `WHERE`.

### Medium

Objectif : exfiltrer des données supplémentaires via `UNION SELECT` avec le bon nombre de colonnes.

### Hard

Objectif : déduire un secret uniquement avec des réponses vraies ou fausses.

### Flag final

Le 4e onglet apparaît uniquement quand les trois niveaux sont validés.

---

## Structure du projet

```text
sqli-auth-lab/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
└── src/
    ├── index.php
    ├── config.php
    ├── style.css
    ├── health.php
    └── reset.php
```

---

## Notes

- Le lab est prévu pour un usage **local uniquement**.
- Les vulnérabilités sont **intentionnelles**.
- Le style a été rapproché de la direction visuelle des feuilles `auth.css`, `account.css` et `dashboard.css`.

---

## Avertissement

Ne jamais exposer ce conteneur en production ou sur Internet.
