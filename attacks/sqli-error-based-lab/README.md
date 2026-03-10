# Lab DIABLE - SQL Injection basée sur les erreurs 

**Version:** 1.0  
**Auteur:** Rachid Mejdoubi  
**Tag:** WEB  
**Difficulté:** Moyen  

---

## Description

Ce lab simule une **SQL Injection Error-Based** dans une mini application e-commerce.  
La version vulnérable affiche des **erreurs SQL détaillées**, tandis que la version sécurisée corrige la faille via **validation stricte** et **requêtes paramétrées**.  
Un **flag** est délivré lorsque l’objectif pédagogique est atteint.

---

## Objectifs pédagogiques

- Comprendre comment une erreur SQL peut divulguer des informations internes
- Comparer une implémentation vulnérable (concaténation) vs sécurisée (paramétrée)
- Valider la réussite via un **flag** (preuve d’atteinte de l’objectif)

---

## Installation et démarrage

### Prérequis
- Docker Desktop (Windows) ou Docker Engine (Linux)
- Docker Compose

### Démarrage rapide

```bash
docker compose up -d --build
```

### Accès

* UI (6 boutons) : [http://localhost:5000/](http://localhost:5000/)
* Debug : [http://localhost:5000/debug](http://localhost:5000/debug)
* Flag : [http://localhost:5000/flag](http://localhost:5000/flag)
* Health : [http://localhost:5000/health](http://localhost:5000/health)
* Reset : [http://localhost:5000/reset](http://localhost:5000/reset)

---

## Build manuel

```bash
docker build -t diable/sqli-error-based-lab .
docker run -d -p 5000:5000 --name sqli-error-based-lab diable/sqli-error-based-lab
```

---

## Structure du projet

```text
sqli-error-based-lab/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
├── requirements.txt
├── docs/
│   └── module.md
└── src/
    ├── app.py
    ├── config.py
    ├── db.py
    ├── health.py
    ├── reset.py
    ├── static/
    │   └── style.css
    └── templates/
        └── index.html
```

---

## Scénarios disponibles

### Scénario 1 : Observation du comportement normal

**Objectif :** Comprendre la recherche produit par ID en fonctionnement normal.
**Cible :** UI → Zone pratique (bouton “Test normal”)
**Technique :** Entrée attendue (ID numérique)
**Résultat :** L’application retourne un produit ou “aucun résultat”.

---

### Scénario 2 : Déclenchement d’une erreur SQL (Error-Based)

**Objectif :** Observer une erreur SQL renvoyée par la version vulnérable.
**Cible :** UI → Zone pratique (bouton “Test entrée mal formée” puis “Tester (vuln)”)
**Technique :** Entrée mal formée provoquant une erreur côté base
**Résultat :** Affichage d’un message d’erreur SQL (fuite d’informations).

⚠️ Aucun payload “prêt à l’emploi” n’est fourni. L’objectif est l’observation du comportement.

---

### Scénario 3 : Comparaison avec la version sécurisée

**Objectif :** Vérifier que la version sécurisée rejette l’entrée invalide sans fuite.
**Cible :** UI → Zone pratique (bouton “Tester (secure)”)
**Technique :** Validation stricte + requête paramétrée
**Résultat :** Erreur générique / rejet sans informations SQL.

---

### Scénario 4 : Obtention du flag (preuve)

**Objectif :** Valider que le parcours pédagogique a été réalisé.
**Cible :** `/flag` ou bouton “Flag” dans la zone pratique
**Résultat :** Retour du flag si les conditions pédagogiques sont remplies.

---

## Comptes de test

Aucun compte requis.

---

## Théorie : SQL Injection basée sur les erreurs

Le module théorique complet est disponible dans :

* `docs/module.md`

Résumé :

* La faille apparaît quand une entrée utilisateur est concaténée dans une requête SQL.
* En mode Error-Based, les erreurs SQL affichées au client divulguent des informations internes.
* La correction repose sur les requêtes paramétrées, la validation stricte et la gestion d’erreurs.

---

## Mesures de protection

* Requêtes paramétrées (préparées)
* Validation stricte des entrées
* Erreurs génériques côté client + logs côté serveur
* Moindre privilège DB

---

## Commandes utiles

```bash
docker logs -f sqli-error-based-lab
curl http://localhost:5000/health
curl http://localhost:5000/reset
curl http://localhost:5000/flag
```

---

## Statistiques

* 1 lab (SQLi Error-Based)
* 1 base SQLite seedée
* 4 scénarios pédagogiques
* Endpoints : `/health`, `/reset`, `/debug`, `/flag`

---

## Dépannage

* Si le conteneur redémarre :

  ```bash
  docker logs sqli-error-based-lab
  ```

* Si le port est pris :
  modifier `5000:5000` en `5004:5000` dans `docker-compose.yml`

* Si l’UI ne se met pas à jour :

  ```bash
  docker compose build --no-cache
  ```

---

## Licence

Lab développé à des fins éducatives.

**⚠️ Avertissement :** Vulnérabilités intentionnelles à des fins éducatives. Ne jamais déployer en production.

---

## Auteur

**Rachid Mejdoubi**
