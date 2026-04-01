# Blind SQL Injection Lab

Mini lab pedagogique en `Flask` + `SQLite` pour comprendre une attaque de type `boolean-based blind SQL injection`.

L'application est volontairement vulnerable: elle ne montre ni erreur SQL ni resultat brut, mais elle laisse fuiter un bit d'information via sa reponse:

- `User exists`
- `User not found`

Cette difference suffit pour reconstruire une information sensible caractere par caractere.

## Objectif

Ce projet sert a:

- comprendre le principe d'une `blind SQL injection`
- observer comment une condition vraie ou fausse change la reponse applicative
- automatiser l'extraction d'un secret avec un script Python simple
- illustrer les bonnes pratiques de correction

## Stack

- `Python 3.10`
- `Flask`
- `SQLite`
- `HTML/CSS`
- `requests` pour le script d'exploitation

## Structure du projet

```text
blind-sqli/
|-- app.py
|-- exploit.py
|-- Dockerfile
|-- requirements.txt
|-- README.md
`-- templates/
    |-- index.html
    `-- about.html
```

Notes:

- `users.db` est cree automatiquement au premier lancement de `app.py`
- `exploit.py` automatise une extraction simple du mot de passe de `admin`

## Fonctionnement de la vulnerabilite

Le point faible est ici:

```python
query = f"SELECT * FROM users WHERE username = '{username}'"
```

La valeur saisie par l'utilisateur est concatenee directement dans la requete SQL.

Exemple normal:

```sql
SELECT * FROM users WHERE username = 'admin'
```

Exemple injecte:

```text
admin' OR '1'='1
```

Ce qui donne:

```sql
SELECT * FROM users WHERE username = 'admin' OR '1'='1'
```

La condition devient toujours vraie.

## Pourquoi c'est une blind SQLi

L'application ne renvoie pas directement le contenu de la base.
Elle retourne seulement une reponse boolenne observable:

- si la condition est vraie, on obtient `User exists`
- si la condition est fausse, on obtient `User not found`

Cela permet de tester des hypotheses sur les donnees sans jamais afficher la requete ni les erreurs SQL.

## Exemples de payloads

Condition vraie:

```text
admin' AND 1=1--
```

Condition fausse:

```text
admin' AND 1=2--
```

Test du premier caractere du mot de passe:

```text
admin' AND substr(password,1,1)='s'--
```

Si l'application repond `User exists`, on peut deduire que le premier caractere est `s`.

## Base de donnees

Au demarrage, l'application cree une table `users` si elle n'existe pas:

```sql
CREATE TABLE IF NOT EXISTS users(
    id INTEGER PRIMARY KEY,
    username TEXT,
    password TEXT
)
```

Des utilisateurs de demonstration sont inseres, dont:

```text
admin / secret123
john / password1
alice / alice123
```

## Lancer le projet en local

### 1. Installer les dependances

```bash
pip install -r requirements.txt
```

### 2. Demarrer l'application

```bash
python app.py
```

Application disponible sur:

```text
http://127.0.0.1:5000
```

## Lancer avec Docker

### 1. Construire l'image

```bash
docker build -t blind-sqli-lab .
```

### 2. Demarrer le conteneur

```bash
docker run --rm -p 5000:5000 blind-sqli-lab
```

## Exploitation automatique

Le script `exploit.py` teste les caracteres un par un sur le compte `admin`.

Lancement:

```bash
python exploit.py
```

Exemple de sortie:

```text
Found: s
Found: se
Found: sec
Found: secr
Found: secre
Found: secret
Found: secret1
Found: secret12
Found: secret123

Password = secret123
```

## Deroule de l'attaque

1. Identifier le parametre injectable
2. Verifier qu'une condition vraie et une condition fausse changent la reponse
3. Cibler une valeur sensible, ici le mot de passe de `admin`
4. Tester chaque position avec `substr(...)`
5. Reconstituer la valeur complete

## Limites du lab

Ce lab est volontairement simple:

- une seule route principale
- une reponse binaire facile a exploiter
- une base SQLite locale
- pas de filtrage d'entree
- pas de defense applicative

Il est donc ideal pour apprendre, mais plus simple qu'une application reelle.

## Correction recommande

La mitigation de base est l'utilisation de requetes parametrees.

Version sure:

```python
cursor.execute("SELECT * FROM users WHERE username = ?", (username,))
```

Bonnes pratiques complementaires:

- valider et normaliser les entrees
- limiter les informations revelees par l'application
- journaliser les comportements suspects
- utiliser un ORM ou des acces DB parametrables
- ajouter des tests de securite sur les points sensibles

## Avertissement

Projet strictement educatif.
Ne jamais utiliser ces techniques sur un systeme reel sans autorisation explicite.
