# Lab DIABLE - Email Header Injection

**Version:** 1.0  
**Auteur:** Emma Raz  
**Tag:** WEB  
**Difficulté:** Facile

---

## Description

Ce lab pédagogique simule une attaque de type **Email Header Injection** via l’injection de caractères CRLF dans des champs d’en-tête d’email.

La victime interagit avec une interface de messagerie simulée et répond à un message piégé. Côté serveur, la construction naïve de l’email permet d’illustrer comment des en-têtes supplémentaires peuvent être injectés.

Ce lab permet d’observer la différence entre une implémentation vulnérable et une implémentation corrigée, ainsi que les événements enregistrés dans un tableau de bord attaquant.

---

## Objectifs pédagogiques

- Comprendre le fonctionnement d’une attaque Email Header Injection
- Observer l’impact de caractères CRLF dans des champs d’en-tête
- Visualiser les statistiques et événements dans un tableau de bord attaquant

---

## Installation et démarrage

### Prérequis

- Docker Desktop ou Docker Engine
- Navigateur web moderne

### Démarrage rapide

```bash
git clone [URL-du-repo]
cd email-header-injection-lab
docker-compose up -d
http://localhost:8080

```

### Build manuel
docker build -t diable/email-header-injection-lab .
docker run -d -p 8080:5000 --name email-header-injection-lab diable/email-header-injection-lab

## Structure du projet
email-header-injection-lab/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
├── requirements.txt
└── src/
    ├── app.py
    ├── config.py
    ├── health.py
    ├── reset.py
    ├── templates/
    │   ├── victim_inbox.html
    │   └── attacker_dashboard.html
    └── static/
        ├── style.css
        ├── css/
        │   ├── gmail.css
        │   └── attacker.css
        └── js/
            ├── victim.js
            └── attacker.js

## Scénarios disponibles

### Scénario 1: Réponse à un email piégé - côté victime

**Objectif :**  
Simuler la réponse d’un utilisateur à un email frauduleux dans une interface de messagerie.

**Cible :**  
Interface Gmail simulée.

**Technique :**  
Email Header Injection.

**Payload :**

```text
Réponse à un email avec construction serveur vulnérable des en-têtes
```

**Résultat :**

La victime ouvre l’email, clique sur **“Répondre”**, puis envoie une réponse.  
Le serveur construit un email brut et l’interface affiche le résultat dans la prévisualisation.

---

### Scénario 2: Injection CRLF - côté attaquant

**Objectif :**

Simuler l’injection d’en-têtes supplémentaires dans un email construit de manière vulnérable.

**Cible :**

Fonction serveur de construction d’email.

**Technique :**

CRLF Injection / Email Header Injection.

**Payload :** \r\nBcc: attacker@evil.test


**Résultat :**

L’email brut généré contient un en-tête additionnel injecté.  
L’événement est enregistré comme une injection observée dans le dashboard attaquant.

---

## Comptes de test

Ce lab ne contient pas de comptes réels.  
L’interface simule uniquement l’ouverture d’un email et la réponse à un message afin d’illustrer une attaque pédagogique.

| Username | Password | Rôle |
|----------|----------|------|
| demo@gmail.com | password123 | Utilisateur simulé |

---

## Théorie: Email Header Injection

L’Email Header Injection est une vulnérabilité qui apparaît lorsqu’une application construit des emails en concaténant directement des entrées utilisateur dans les champs d’en-tête.

Un attaquant peut injecter des caractères spéciaux comme **CR (`\r`)** et **LF (`\n`)** afin de casser la structure attendue de l’email et ajouter de nouveaux en-têtes.

Les conséquences possibles incluent :

- l’ajout d’un champ `Bcc`
- l’ajout d’un champ `Cc`
- la modification du contenu de certains en-têtes
- l’utilisation abusive du système d’envoi de mails

Dans ce lab, une construction naïve de l’email permet d’observer l’effet d’une injection CRLF dans les en-têtes.

---

## Mesures de protection

### 1. Validation des entrées

Les champs utilisés dans les en-têtes doivent être contrôlés afin d’interdire les caractères CR et LF.

Exemple : Refuser toute entrée contenant \r ou \n


### 2. Normalisation des données

Avant de construire un email, il faut nettoyer les champs utilisateurs et supprimer les caractères de contrôle.
Sujet nettoyé + suppression des caractères de contrôle

Même si un utilisateur saisit une valeur inattendue, l’application doit empêcher toute modification de la structure des en-têtes.

### 3. Utilisation de bibliothèques sûres

Il faudrait utiliser des bibliothèques de messagerie qui gèrent la construction des en-têtes au lieu de concaténer manuellement des chaînes

---

## Commandes utiles

# Voir les logs du conteneur
docker logs -f email-header-injection-lab

# Voir les événements enregistrés (attaques)
curl http://localhost:8080/api/events

# Voir les statistiques de l'attaque
curl http://localhost:8080/api/metrics

# Reset de la base d'événements
curl http://localhost:8080/reset

# Health check du service
curl http://localhost:8080/health


## Statistiques

- 19 fichiers créés au total  
- 2 scénarios d'attaque  
- Temps de développement : 17 heures  

## Dépannage

### Le conteneur ne démarre pas

Vérifier que Docker est correctement installé : docker --version

### Port déjà utilisé

Si le port est déjà occupé, modifier le port dans le fichier `docker-compose.yml`.

Exemple : "8081:5000"


---

## Licence

Lab développé pour le projet **DIABLE v3.0 (DSI ISFA 2025-2026)**.

⚠️ **Avertissement :** Vulnérabilités intentionnelles à des fins éducatives. Ne jamais déployer en production.

---

## Auteur

**Emma**  
DSI ISFA 2025-2026