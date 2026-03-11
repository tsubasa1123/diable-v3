
# Lab DIABLE - Phishing

**Version:** 1.0  
**Auteur:** Emma Raz  
**Tag:** WEB  
**Difficulté:** Facile

---

## Description

Ce lab pédagogique simule une attaque de phishing dans laquelle un utilisateur reçoit un email frauduleux imitant un service de livraison.

La victime ouvre l’email, clique sur un lien et est redirigée vers une fausse page de connexion. Les interactions de l’utilisateur sont enregistrées et visualisées dans un tableau de bord attaquant.

Ce lab permet d’observer les différentes étapes d’une attaque d’ingénierie sociale et d’analyser le comportement de la victime.

---

## Objectifs pédagogiques

- Comprendre le fonctionnement d’une attaque de phishing
- Observer les interactions d’une victime face à un email frauduleux
- Visualiser les statistiques d’attaque dans un tableau de bord

---

## Installation et démarrage

### Prérequis

- Docker Desktop ou Docker Engine
- Navigateur web moderne

### Démarrage rapide

```bash
git clone [URL-du-repo]
cd phishing-lab
docker-compose up -d
http://localhost:5000
```

### Build manuel
docker build -t diable/phishing-lab .
docker run -d -p 5000:5000 --name phishing-lab diable/phishing-lab


## Structure du projet

```
phishing-lab/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
├── requirements.txt
└── src/
    ├── app.py
    ├── config.py
    ├── templates/
    │   ├── attacker_dashboard.html
    │   ├── victim_inbox.html
    │   ├── victim_login.html
    │   └── victim_success.html
    └── static/
        ├── style.css
        ├── css/
        │   ├── attacker.css
        │   └── gmail.css
        └── js/
            ├── attacker.js
            └── victim.js

---
```

##  Scénarios disponibles

### Scénario 1: [Email de phishing - côté victime]

**Objectif:** [Simuler la réception d’un email frauduleux.]

**Cible:** [Interface Gmail simulée.]

**Technique:** [Phishing]

**Payload:**
\`\`\`
[Email frauduleux demandant de planifier une livraison]
\`\`\`

**Résultat:** [La victime reçoit l’email, elle ouvre le message et elle clique sur le lien frauduleux]


### Scénario 2: [Email de phishing -côté attaquant]

**Objectif:** [Simuler la récupération d’identifiants.]

**Cible:** [Page de connexion frauduleuse.]

**Technique:** [Phishing - Credential harvesting]

**Payload:**
\`\`\`
[Formulaire de connexion frauduleux]
\`\`\`

**Résultat:** [ La victime saisit ses identifiants, les données sont anonymisées et es statistiques apparaissent dans le dashboard attaquant]



---

## Théorie: Phishing

Le phishing est une technique d’ingénierie sociale visant à tromper les utilisateurs afin qu’ils divulguent des informations sensibles.

Les attaquants utilisent généralement :

- des emails frauduleux
- des pages web falsifiées
- des techniques psychologiques

L’utilisateur est incité à agir rapidement, ce qui réduit sa capacité d’analyse.

---

## Mesures de protection

### 1. Sensibilisation des utilisateurs

Former les utilisateurs à reconnaître les emails suspects.
Exemple : vérifier le domaine de l'expéditeur

### 2. Authentification multi-facteurs
Mot de passe + OTP

Même si un mot de passe est compromis, l’accès reste protégé.

### 3. Filtrage des emails

Les systèmes modernes utilisent :

- SPF
- DKIM
- DMARC

---

## Commandes utiles

```bash
# Voir les logs du conteneur
docker logs -f phishing-lab

# Voir les événements enregistrés (attaques)
curl http://localhost:5000/api/events

# Voir les statistiques de l'attaque
curl http://localhost:5000/api/metrics

# Reset de la base d'événements
curl http://localhost:5000/reset

# Health check du service
curl http://localhost:5000/health

```
### Statistiques
- 21 fichiers crées au total
- 2 scénarios d'attaque
- temps de développement : 15 heures

## Dépannage

### Le conteneur ne démarre pas

Vérifier que Docker est correctement installé :

```bash
docker --version
```
### Port déjà utilisé
Si le port est déjà occupé, modifier le port dans le fichier : docker-compose.yml
ports:
  - "8081:5000"

  ##  Licence

Lab développé pour le projet DIABLE v3.0 (DSI ISFA 2025-2026).

**⚠️ Avertissement:** Vulnérabilités intentionnelles à des fins éducatives. Ne JAMAIS déployer en production.

---

##  Auteur

**[Emma]**  
DSI ISFA 2025-2026  
```

---