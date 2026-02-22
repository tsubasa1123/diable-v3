---
title: "Phishing"
tag: "Social Engineering"
difficulty: "Facile"
goal: "Identifier un email de phishing, comprendre les signaux faibles, éviter l’action dangereuse."
fix: "Sensibilisation utilisateurs, SPF/DKIM/DMARC, filtrage email, MFA, procédures internes, entraînement régulier."
---

# Théorie

## Qu’est-ce que l’attaque ?

Le phishing est une attaque de **manipulation psychologique** visant à pousser une victime à effectuer une action non souhaitée :
- cliquer sur un lien
- ouvrir une pièce jointe
- fournir des identifiants ou des informations sensibles

L’attaque repose plus sur **l’humain** que sur une faille technique.

## Pourquoi elle fonctionne ?

Parce qu’elle exploite :
- la **confiance** (expéditeur connu, marque légitime)
- l’**urgence** (compte bloqué, action immédiate requise)
- la **peur** ou la **récompense**
- l’automatisme (cliquer sans vérifier)

Même une infrastructure techniquement sécurisée reste vulnérable si l’utilisateur est trompé.

## Dans quels cas réels elle apparaît ?

- Emails RH / IT (mot de passe, MFA, mise à jour)
- Factures / livraisons
- Invitations (Docs, SharePoint, Drive)
- Messages internes falsifiés (CEO fraud, spear phishing)

---

# Lab

## Objectif du lab

Observer un email reçu dans un contexte réaliste et analyser ses éléments et décider :
- de **cliquer**
- de **signaler**
- ou d’**ignorer**

Puis comprendre les conséquences de cette action.

## Règles

- Aucun avertissement explicite au départ
- L’email imite un contexte crédible
- Le but n’est pas de “piéger”, mais de **faire réfléchir**


## Accès
- Indice : observe l’expéditeur, le lien, le ton, l’urgence
- Cette attaque est **simulée** et volontairement **facile** pour l’apprentissage.
- Essayez d’**imiter le comportement d’un utilisateur naïf** dans un contexte réel.

# Architecture du Lab
Le lab est structuré en deux perspectives :

## Côté Victime

- Réception d’un email simulé
- Analyse visuelle et contextuelle
- Décision : cliquer ou ignorer
- Saisie éventuelle d’identifiants

Objectif : observer le comportement humain.

---

## Côté Attaquant

- Collecte d’événements (ouverture, clic, soumission)
- Analyse statistique :
  - taux d’ouverture
  - taux de clic
  - taux de soumission
- Profil technique anonymisé :
  - OS
  - Navigateur
  - IP anonymisée simulée

(Les métriques sont basées sur des événements bruts et ne représentent pas des utilisateurs uniques.)
Objectif : comprendre comment un attaquant exploite les données comportementales.
---

# Débrief

## Pourquoi ça a fonctionné (ou pas) ?

Parce que l’email utilisait :
- un **contexte légitime**
- un **message plausible**
- un **déclencheur émotionnel** (urgence, peur, opportunité)

Le clic n’est pas une “erreur”, mais une réaction humaine normale.

## Où était le signal d’alerte ?

Exemples possibles :
- domaine expéditeur modifié
- lien masqué ou raccourci
- ton trop urgent ou inhabituel
- demande inhabituelle d’action ou d’information


### Checklist utilisateur

- Vérifier l’expéditeur réel
- Survoler les liens avant de cliquer
- Se méfier de l’urgence
- Signaler les emails suspects

### Checklist organisation

- Formations régulières
- Simulations de phishing
- MFA (authentification multifacteur)
- Procédures claires de signalement

## Que se passe-t-il après le clic ?

Si la victime fournit ses identifiants :

- L’attaquant peut accéder au compte compromis
- Lire ou exfiltrer des emails
- Réinitialiser d’autres comptes (réutilisation de mot de passe)
- Envoyer du phishing interne depuis un compte légitime
- Escalader vers des accès plus sensibles

Une simple compromission peut devenir :
- Un accès au réseau interne
- Une fraude financière
- Une fuite de données
- Une propagation latérale

Le phishing est souvent la **porte d’entrée initiale** d’attaques plus complexes (ransomware, intrusion interne, fraude).
