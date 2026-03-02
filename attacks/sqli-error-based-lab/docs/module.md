---
title: "SQL Injection basée sur les erreurs"
tag: "Web"
difficulty: "Moyen"
goal: "Comprendre comment une erreur SQL peut révéler des informations sensibles lorsqu’une requête est construite avec une entrée non sécurisée."
fix: "Requêtes paramétrées, suppression des erreurs SQL côté client, validation stricte des types, moindre privilège DB, logs internes uniquement."
---

# Théorie

## Qu’est-ce que l’attaque ?

La SQL Injection basée sur les erreurs consiste à provoquer volontairement une **erreur SQL** afin que l’application révèle des informations internes.

Lorsque l’application affiche directement les messages d’erreur de la base de données, un attaquant peut :
- Identifier le type de SGBD
- Découvrir des noms de tables ou de colonnes
- Comprendre la structure des requêtes
- Confirmer la présence d’une vulnérabilité

L’attaque repose sur l’observation des différences de comportement du serveur lorsqu’une entrée invalide est fournie.

---

## Pourquoi elle fonctionne ?

Elle apparaît lorsque :
- L’application construit une requête SQL par concaténation
- Les entrées utilisateur ne sont pas paramétrées
- Les erreurs SQL sont affichées au client
- Le mode debug est activé (ou une exception remonte)

Le problème vient de la confusion entre :
- **Donnée utilisateur** (devrait rester une valeur)
- **Code SQL** (interprété par la base)

Si l’entrée est injectée dans la requête, la base peut produire une erreur riche en informations.

---

## Quels sont les risques ?

- Fuite d’informations techniques
- Identification du SGBD
- Cartographie de la base
- Contournement d’authentification
- Accès non autorisé aux données
- Suppression ou modification de données
- Chaîne d’attaque vers exfiltration complète

---

## Dans quels cas réels elle apparaît ?

- Paramètre `id` dans une URL (`/product?id=...`)
- Recherche (`/search?q=...`)
- Formulaire de login mal sécurisé
- Filtres dynamiques (`sort`, `order`)
- Back-office développés rapidement
- Applications laissées en mode debug en production

---

## Signatures fréquentes d’erreurs

- `ORA-xxxxx` → Oracle
- `SQLSTATE` / `syntax error at or near` → PostgreSQL
- `Unclosed quotation mark` → SQL Server
- `You have an error in your SQL syntax` → MySQL

Ces messages sont des indices précieux pour un attaquant.

---

# Lab

## Scénario du laboratoire

Le laboratoire simule :
- Une application e-commerce simple
- Une recherche produit via un ID
- Une version vulnérable (concaténation + erreurs détaillées)
- Une version sécurisée (requête paramétrée + validation + erreur générique)
- Un système de **flag** pour valider l’objectif pédagogique
- Un endpoint debug pour visualiser les événements

---

## Important : Nature de la simulation

- Le lab est volontairement simplifié.
- Il ne vise pas à attaquer un système réel.
- Il montre le mécanisme pédagogique de la fuite d’erreur.
- Aucun accès externe ou réel à une base sensible.

---

## Objectif pédagogique

Observer :
- Comment une entrée influence la requête SQL
- Comment une erreur révèle des informations internes
- La différence entre mode vulnérable et sécurisé
- La validation de réussite via un flag

---

## Accès au lab

- Interface (6 boutons) : `/`
- Debug : `/debug`
- Flag : `/flag`
- Health : `/health`
- Reset : `/reset`

Indice : tester une entrée attendue puis une entrée mal formée et comparer la différence entre vulnérable et sécurisé.

---

# Débrief

## Pourquoi ça fonctionne ?

### En mode vulnérable :
- Concaténation directe du paramètre
- Aucune validation de type
- Erreur SQL renvoyée au client
- Fuite d’informations via le message d’erreur

### En mode sécurisé :
- Requête paramétrée
- Validation stricte (entier)
- Message d’erreur générique
- Logs internes uniquement

---

## Conséquences possibles

- Fuite d’informations internes
- Identification de la structure DB
- Exfiltration de données
- Intégration dans une attaque plus large

---

# Checklist développeur

- Toujours utiliser des requêtes paramétrées
- Ne jamais concaténer du SQL
- Désactiver le mode debug en production
- Retourner des erreurs génériques au client
- Logger côté serveur uniquement
- Validation stricte (types + whitelists)
- Principe du moindre privilège DB

---

# Checklist organisation

- Politique Secure Coding obligatoire
- Revue de code sécurité régulière
- Tests SAST / DAST automatisés
- Monitoring des erreurs 500
- Pentest périodique
- WAF en complément