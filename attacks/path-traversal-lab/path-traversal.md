---
title: "Directory / Path Traversal"
tag: "Web"
difficulty: "Facile"
goal: "Accéder à des fichiers sensibles du serveur en manipulant les chemins de fichiers via une entrée non sécurisée."
fix: "Validation stricte des chemins, vérifier que le chemin résolu reste dans le dossier autorisé, utiliser os.path.normpath() + startswith(), ne jamais exposer les chemins bruts à l'utilisateur."
---

# Théorie

## Qu'est-ce que l'attaque ?

Le Directory Traversal (aussi appelé Path Traversal) consiste à manipuler un paramètre de fichier dans une URL ou un formulaire pour sortir du répertoire autorisé et accéder à des fichiers sensibles du serveur.

L'attaquant utilise des séquences `../` pour remonter dans l'arborescence du système de fichiers.

L'impact peut inclure :
- Lecture de fichiers de configuration sensibles
- Accès à des mots de passe ou clés API
- Lecture du code source de l'application
- Accès à des fichiers système (/etc/passwd, etc.)

## Pourquoi elle existe ?

Elle apparaît lorsque l'application utilise directement une entrée utilisateur pour construire un chemin de fichier sans valider que ce chemin reste dans le dossier autorisé.

Le problème vient de la confusion entre :
- Ce que l'utilisateur est censé accéder (un dossier restreint)
- Ce que le système de fichiers permet réellement d'atteindre

Si le chemin n'est pas contrôlé, un attaquant peut remonter librement dans l'arborescence.

## Dans quels cas réels elle apparaît ?

- Téléchargement de fichiers via un paramètre URL
- Affichage de documents ou images dynamiques
- Systèmes de templates ou CMS
- APIs exposant des chemins de fichiers
- Logs ou exports de fichiers

## Types principaux

- **Simple Traversal** : utilisation directe de `../` pour remonter dans les dossiers.
- **Encoded Traversal** : encodage des caractères (`%2e%2e%2f`) pour contourner des filtres basiques.
- **Double Encoding** : encodage multiple (`%252e%252e%252f`) pour bypasser des protections supplémentaires.

## Exemple simple

Si le serveur construit un chemin ainsi :

- `open("/app/files/" + filename)`

Et que l'entrée utilisateur est `../../secret/flag.txt`, le chemin devient :

- `/app/files/../../secret/flag.txt` → résolu en `/app/secret/flag.txt`

> Objectif pédagogique : comprendre que la vulnérabilité vient de l'absence de validation du chemin final après résolution.

---

# Lab

## Objectif du lab

Observer comment un paramètre `file` dans l'URL est utilisé pour lire des fichiers sur le serveur et réussir à accéder à un fichier en dehors du dossier autorisé.

## Règles

- Aucun payload donné au départ
- Tu dois analyser le paramètre exposé dans l'URL
- Observer comment le serveur répond selon les chemins que tu fournis
- Comprendre pourquoi le serveur ouvre le fichier que tu demandes

## Accès

- URL: http://localhost:5000
- Identifiants: Aucun requis
- Indice: observe le paramètre `file` dans l'URL et essaie de remonter dans les dossiers

---

# Débrief

## Pourquoi ça a fonctionné ?

Parce que l'application a utilisé directement l'entrée utilisateur pour construire le chemin du fichier sans vérifier que ce chemin reste dans le dossier autorisé.

Le système de fichiers a résolu les séquences `../` et a ouvert le fichier demandé.

## Où était la vulnérabilité ?

Dans le endpoint `/view` qui fait :

```python
filepath = os.path.abspath(os.path.join(BASE_DIR, filename))
# Aucune vérification que filepath reste dans BASE_DIR
with open(filepath, "r") as f:
    return f.read()
```

## Comment corriger ?

Vérifier que le chemin résolu reste bien dans le dossier autorisé :

```python
filepath = os.path.normpath(os.path.join(BASE_DIR, filename))
if not filepath.startswith(BASE_DIR):
    abort(403)
```

Le endpoint `/secure-view` dans ce lab démontre exactement cette correction.

## Références

- [OWASP Path Traversal](https://owasp.org/www-community/attacks/Path_Traversal)
- [CWE-22: Improper Limitation of a Pathname](https://cwe.mitre.org/data/definitions/22.html)
