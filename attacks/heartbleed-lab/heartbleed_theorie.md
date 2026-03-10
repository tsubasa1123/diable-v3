---
title: "Heartbleed (CVE-2014-0160)"
tag: "Crypto / TLS"
difficulty: "Moyen"
goal: "Lire des données sensibles en mémoire (clés privées, sessions, credentials)."
fix: "Mise à jour OpenSSL, désactivation Heartbeat, rotation des clés, PFS."
---

## Théorie

### Qu’est-ce que l’attaque ?

Heartbleed est une vulnérabilité de **divulgation d’information** affectant OpenSSL.  
Elle permet à un attaquant de lire des fragments arbitraires de la mémoire du serveur via l’extension **TLS Heartbeat**.

### Pourquoi existe-t-elle ?

La faille provient d’un **manque de vérification de taille** :

- Le client indique une longueur,
- OpenSSL lui fait confiance,
- Le serveur retourne plus de données que prévu.

> **Important :** il s’agit d’un **buffer over-read** (lecture excessive), pas d’un buffer overflow (écriture).

### Cas d’apparition

- Serveurs HTTPS utilisant une version vulnérable d’OpenSSL  
- VPN (ex : OpenVPN)  
- Serveurs mail (SMTP sur TLS)  
- Équipements réseau / IoT  

### Exemple illustratif

- Le client envoie :  
  > « voici 1 octet »  
- mais annonce :  
  > « j’envoie 64 Ko »

- Le serveur répond :  
  - l’octet réel,  
  - **+ 64 Ko de mémoire adjacente**.

**Objectif pédagogique :** montrer que la fuite provient d’un contrôle insuffisant des tailles, sans écriture mémoire.

---

## Lab

### Objectif du lab

- Exploiter la vulnérabilité Heartbleed sur un serveur HTTPS vulnérable.
- Utiliser un outil (Metasploit, Nmap, ou script Python) pour envoyer une requête Heartbeat malformée.
- Observer la réponse du serveur et identifier des données sensibles (flag, clés, sessions, credentials) dans la mémoire retournée.

### Étapes détaillées

1. **Accéder au service**
   - URL : `https://<ip_du_lab>:4443/` (certificat auto-signé)
   - Vérifier que le service est actif.

2. **Scanner la vulnérabilité**
   - Utiliser Nmap :
     ```bash
     nmap -sV -p 4443 --script=ssl-heartbleed <ip_du_lab>
     ```
   - Confirmer que le serveur est vulnérable.

3. **Exploiter la fuite mémoire**
   - Utiliser Metasploit :
     ```bash
     msfconsole -x "use auxiliary/scanner/ssl/openssl_heartbleed; set RHOSTS <ip_du_lab>; set RPORT 4443; run"
     ```
   - Ou utiliser un script Python (ex : ssltest.py).

4. **Analyser la réponse**
   - Rechercher des chaînes lisibles (ex : flag, credentials).
   - Filtrer la sortie :
     ```bash
     ... | grep DIABLE
     ```
   - Documenter la fuite et expliquer son origine.

### Règles

- L’utilisateur doit démontrer l’exploitation (pas de dump automatique).
- La réponse doit contenir des données sensibles en clair.
- Relier la fuite à la gestion mémoire TLS.

### Accès

- Service : HTTPS sur port 4443
- Indice : examiner la taille des réponses Heartbeat
- Outils recommandés : Metasploit, Nmap, scripts Python

---

## Débrief

### Pourquoi l’exploitation a-t-elle réussi ?

Parce que le serveur a lu au-delà de la mémoire attendue, sans vérifier la taille réelle des données envoyées par le client.

### Origine de la vulnérabilité

Dans l’implémentation de l’extension **TLS Heartbeat** d’OpenSSL.

### Correction

- Mettre à jour OpenSSL  
- Désactiver Heartbeat si non nécessaire  
- Régénérer certificats et clés  
- Activer la **Perfect Forward Secrecy (PFS)**

### Remarque importante

- La mémoire retournée par Heartbleed est **aléatoire** :
  - Le contenu peut varier à chaque exploitation.
  - Il est parfois nécessaire de relancer l’attaque plusieurs fois pour obtenir le flag ou des données sensibles.
  - Cela reflète le comportement réel de la vulnérabilité.
