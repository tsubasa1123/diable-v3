# Lab DIABLE - Injection SQL

**Version:** 1.1  
**Auteur:** OpenAI  
**Tag:** DB  
**Difficulté:** Facile / Moyen / Difficile

---

## Description

Ce lab Docker met en scène une application PHP/SQLite volontairement vulnérable à l’injection SQL. Il contient désormais trois niveaux de difficulté dans la même interface afin de progresser d’un bypass simple vers une extraction par UNION, puis vers une inférence booléenne plus discrète.

---

## Objectifs pédagogiques

- Comprendre l’impact de la concaténation directe d’entrées utilisateur dans des requêtes SQL.
- Exploiter une injection SQL selon trois approches de difficulté croissante.
- Comparer les techniques d’attaque avec les contre-mesures modernes à base de requêtes préparées.

---

## Installation et démarrage

### Prérequis

- Docker Desktop ou Docker Engine
- Docker Compose

### Démarrage rapide

```bash
cd sqli-auth-lab
docker-compose up -d --build
http://localhost:8080
```

### Build manuel

```bash
docker build -t diable/sqli-auth-lab .
docker run -d -p 8080:80 --name sqli-lab diable/sqli-auth-lab
```

---

## Structure du projet

```text
sqli-auth-lab/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
├── solutions.txt
└── src/
    ├── index.php
    ├── config.php
    ├── style.css
    ├── health.php
    └── reset.php
```

---

## Scénarios disponibles

### Scénario 1: Niveau facile - bypass d’authentification

**Objectif:** Se connecter en tant qu’utilisateur privilégié sans connaître le mot de passe.

**Cible:** `src/index.php?level=easy`

**Technique:** Injection SQL dans une clause `WHERE`

**Payload:**

```text
Username: admin' --
Password: nimportequoi
```

**Résultat:** La condition sur le mot de passe est neutralisée et l’application authentifie le compte admin.

### Scénario 2: Niveau moyen - extraction avec UNION SELECT

**Objectif:** Extraire des informations depuis une autre table que `users`.

**Cible:** `src/index.php?level=medium`

**Technique:** Injection SQL dans un `LIKE` puis `UNION SELECT`

**Payload:**

```text
' UNION SELECT id, owner, title, secret FROM secret_notes --
```

**Résultat:** Les données de `secret_notes` sont fusionnées au tableau affiché à l’écran.

### Scénario 3: Niveau difficile - inférence booléenne

**Objectif:** Déduire un secret sans affichage direct de la requête ni des données.

**Cible:** `src/index.php?level=hard`

**Technique:** Injection booléenne basée sur `substr()`

**Payload:**

```text
x' OR substr((SELECT secret FROM secret_notes WHERE owner='admin' LIMIT 1),1,1)='F' --
```

**Résultat:** L’application répond “Jeton valide” si la condition booléenne est vraie.

---

## Comptes de test

| Username | Password | Rôle           |
| -------- | -------- | -------------- |
| admin    | ???      | Administrateur |
| user     | password | Utilisateur    |
| analyst  | letmein  | Analyste       |
| guest    | guest123 | Invité         |

---

## Théorie: Injection SQL

L’injection SQL apparaît lorsqu’une application mélange des données utilisateur et des instructions SQL dans une même chaîne. Selon le contexte, l’attaquant peut altérer une clause `WHERE`, injecter un `UNION SELECT` pour récupérer d’autres jeux de résultats, ou avancer par tests booléens pour inférer un secret sans affichage direct.

Dans ce lab, chaque niveau isole une famille de comportements : le niveau facile montre un contournement immédiatement visible, le niveau moyen permet de raisonner sur le nombre de colonnes et la structure d’un `UNION`, et le niveau difficile introduit une logique plus réaliste de type “blind SQL injection” avec seulement un signal vrai/faux.

---

## Mesures de protection

### 1. Requêtes préparées

```php
$stmt = $db->prepare('SELECT id, username, role, email FROM users WHERE username = :username AND password = :password');
$stmt->execute([
    ':username' => $username,
    ':password' => $password,
]);
$user = $stmt->fetch();
```

### 2. Messages d’erreur neutres

```php
try {
    $stmt = $db->prepare('SELECT id, username, role, email FROM users WHERE role LIKE :term ORDER BY id ASC');
    $stmt->execute([':term' => '%' . $term . '%']);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo 'Une erreur est survenue.';
}
```

### 3. Validation stricte et séparation logique

```text
- Valider le format attendu des entrées
- Réduire les privilèges du compte applicatif
- Séparer les fonctionnalités sensibles des interfaces exposées
```

---

## Commandes utiles

```bash
# Voir les logs
docker logs -f sqli-lab

# Reset
curl http://localhost:8080/reset.php

# Health check
curl http://localhost:8080/health.php
```

---

## Statistiques

- 9 fichiers créés
- 3 scénarios d’attaque
- 3 niveaux de difficulté intégrés

---

## Dépannage

Si la page ne répond pas après le démarrage, vérifier que le port `8080` n’est pas déjà utilisé. Si le health check échoue, consulter `docker-compose logs`. En cas d’état incohérent, appeler `/reset.php` pour recréer la base locale.

---

## Licence

Lab développé pour le projet DIABLE v3.0 (DSI ISFA 2025-2026).
