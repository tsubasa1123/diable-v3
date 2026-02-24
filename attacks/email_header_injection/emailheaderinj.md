---
title: "Email Header Injection (CRLF)"
tag: "AppSec / Email"
difficulty: "Facile"
goal: "Comprendre comment une entrée utilisateur peut injecter des en-têtes email et quelles conséquences cela peut avoir."
fix: "Validation stricte CR/LF, APIs email sécurisées, normalisation, audits, logs & monitoring."
---

# Théorie

## Qu’est-ce que l’attaque ?

L’Email Header Injection consiste à injecter des caractères de fin de ligne (`\r\n`) dans des champs email pour :

- casser la structure du message
- ajouter de nouveaux en-têtes non prévus
- modifier le comportement de l'email

Elle apparaît lorsque les emails sont construits par simple concaténation de chaînes.

---

## Pourquoi elle fonctionne ?

Un email est structuré ainsi :

1. Headers (`To`, `Subject`, etc.)
2. Ligne vide
3. Corps du message

Si l’utilisateur ajoute un saut de ligne, il peut créer artificiellement un nouvel en-tête.


## Quels sont les risques ?

- Modification d’en-têtes (Reply-To, MIME, priorité)
- Redirection de réponses
- Envois abusifs
- Utilisation de l’infrastructure pour du phishing
- Problèmes de réputation
- Confusion des logs
- Chaîne d’attaque avec d’autres failles

---

## Dans quels cas réels elle apparaît ?

- Formulaires de contact
- Support client
- Invitations email
- Réinitialisation de mot de passe (reset password)
- Newsletters

---


Ce nouvel en-tête est interprété comme légitime.

---

## Quels sont les risques ?

- Ajout de `Bcc` ou `Cc` non autorisé
- Modification du `Reply-To`
- Envoi massif de spam via le serveur
- Redirection des réponses utilisateurs
- Dégradation de la réputation du domaine
- Blacklisting SPF/DKIM/DMARC
- Utilisation de l’infrastructure pour du phishing

---

## Cas réels d’apparition

- Formulaires de contact
- Support client
- Envoi d’invitations
- Réinitialisation de mot de passe
- Applications internes mal auditées

---

# Lab

## Scénario du laboratoire

Le laboratoire simule :

1. Une boîte Gmail factice
2. Un email suspect incitant à répondre
3. Un bouton "Répondre"
4. La construction côté serveur d’un email de réponse
5. Une version vulnérable (concaténation naïve)
6. Une prévisualisation brute de l’email généré
7. Un dashboard affichant les événements

La victime ne fait rien de technique.

Elle clique simplement :

> Répondre → Envoyer

Le serveur vulnérable injecte automatiquement un nouvel en-tête (Bcc)

---

## Important : Nature de la simulation

- Ce laboratoire est volontairement simplifié.
- Le but est de montrer le mécanisme interne de l’attaque, pas de reproduire un environnement SMTP réel.
- Il suffit d’imiter un utilisateur naïf qui répond à un email. ("Répondre" et "Envoyer")

---

## Objectif pédagogique

Observer :

- comment une concaténation non sécurisée permet l’ajout d’un en-tête
- comment une validation CRLF bloque l’attaque
- la différence entre mode vulnérable et sécurisé
- l’impact visible dans le raw email preview

---

## Accès au lab

- Inbox simulée : `/`
- Dashboard attaquant : `/attacker`

---

# Débrief

## Pourquoi ça fonctionne ?

### En mode vulnérable :

- concaténation directe des champs
- aucune validation des caractères `\r` et `\n`
- possibilité d’injecter un nouvel en-tête

### En mode sécurisé :

- détection des retours ligne
- rejet ou normalisation des entrées
- blocage de l’injection

---

## Où était la faille ?

- Dans la construction manuelle de l’email :"Subject: " + user_input + "\r\n"
- Sans validation préalable.
- La confiance dans l’entrée utilisateur est la cause principale.

---

## Conséquences possibles

- Envoi d’emails non autorisés
- Ajout invisible de destinataires
- Exploitation de l’infrastructure mail
- Intégration dans une chaîne d’attaque plus large

---

# Checklist développeur

- Bloquer `\r` et `\n` dans tous les champs d’en-têtes
- Utiliser des bibliothèques email sécurisées
- Ne jamais concaténer manuellement les headers
- Limiter la longueur des champs
- Logger les tentatives suspectes
- Tester les entrées via des tests automatisés

---

# Checklist organisation

- Revue de code sécurité régulière
- Tests d’injection automatisés
- Monitoring des envois email
- Alertes sur anomalies SMTP
- Limitation d’abus (rate limiting, quotas)

