# Attacks - Labs de Cybersécurité

**Work Package 3** - Containers & Documentation

---

## 📋 Description

Dossier contenant tous les labs de cybersécurité conteneurisés. Chaque lab est un environnement isolé permettant d'apprendre une vulnérabilité spécifique.

---

## 📦 Labs Disponibles

### 1. sqli-lab/ - SQL Injection
**Auteur:** Kennedy  
**Difficulté:** Moyen  
**Tag:** DB  
**Port:** 8080

**Scénarios:**
- Login Bypass avec commentaires SQL
- Data Extraction avec UNION SELECT
- Privilege Escalation avec UPDATE injection

---

### 2. xpath-lab/ - XPath Injection
**Auteur:** Kennedy  
**Difficulté:** Moyen  
**Tag:** XML  
**Port:** 8081

**Scénarios:**
- Login Bypass avec `or`
- Data Extraction avec union `|`
- Blind XPath avec `substring()`

---

### 3. xxe-lab/ - XML External Entity
**Auteur:** Hamed  
**Difficulté:** Moyen  
**Tag:** XML  
**Port:** 8082

**Scénarios:**
- File Read local
- SSRF vers services internes
- Denial of Service

---

### 4. xss-lab/ - Cross-Site Scripting
**Auteur:** Kantame  
**Difficulté:** Facile à Moyen  
**Tag:** WEB  
**Port:** 8083

**Scénarios:**
- Reflected XSS
- Stored XSS
- DOM-based XSS

---

### 5. csrf/ - Cross-Site Request Forgery
**Auteur:** Chaimae  
**Difficulté:** Moyen  
**Tag:** WEB  
**Port:** 8084

**Scénarios:**
- CSRF simple
- CSRF avec token bypassable
- CSRF avec SameSite cookies

---

### 6. jwt-lab/ - JWT Vulnerabilities
**Auteur:** Thiané  
**Difficulté:** Moyen → Difficile  
**Tag:** JWT  
**Port:** 8085

**Scénarios:**
- alg:none Bypass (CVE-2015-9235)
- Weak Secret Brute Force
- RS256 → HS256 Algorithm Confusion
- kid Header Injection

---

### 7. nosql-injection-lab/ - NoSQL Injection
**Auteur:** Celia  
**Difficulté:** Moyen  
**Tag:** WEB  
**Port:** 3000

**Scénarios:**
- Bypass d'authentification basique
- Énumération des utilisateurs
- Extraction de données sensibles
- JavaScript Injection avec $where

---

### 8. mitm-attack-lab/ - Man-in-the-Middle
**Auteur:** Celia  
**Difficulté:** Moyen  
**Tag:** WEB  
**Port:** 8086

**Scénarios:**
- Interception de credentials basique
- Session Hijacking complet
- Cookie Stealing et réutilisation
- Traffic Modification

---

### 9. Shellshock/ - Rejetto HFS RCE
**Auteur:** Imane  
**Difficulté:** Facile → Moyen  
**Tag:** Web / RCE  
**Port:** 8087

**Scénarios:**
- Injection de macro par octet nul (CVE-2014-6287)
- Exécution de commandes à distance
- Lecture de fichiers sensibles

---

### 10. api_scanning/ - API Scanning
**Auteur:** Imane  
**Difficulté:** Facile → Moyen  
**Tag:** WEB  
**Port:** 8088

**Scénarios:**
- Reconnaissance avec GoLinkFinder
- Découverte d'endpoints cachés
- API sans authentification
- Broken Function Level Auth

---

### 11. app-cve-2025-68613/ - n8n CVE-2025-68613
**Auteur:** Hamza  
**Difficulté:** Moyen  
**Tag:** WEB  
**Port:** 5678

**Scénarios:**
- Exploitation de CVE-2025-68613 sur n8n
- RCE via vulnérabilité n8n

---

### 12. app-idor/ - Insecure Direct Object Reference
**Auteur:** Hamza  
**Difficulté:** Moyen  
**Tag:** WEB / Access Control  
**Port:** 8089

**Scénarios:**
- Profile Access sans autorisation
- API Endpoint sans vérification
- Enumération d'utilisateurs

---

### 13. app-mfa-bypass/ - MFA Bypass
**Auteur:** Hamza  
**Difficulté:** Moyen  
**Tag:** WEB / Auth  
**Port:** 8090

**Scénarios:**
- Meet-In-The-Middle Attack
- OTP Brute Force (4 digits)
- Pas de rate limiting

---

### 14. broken-auth-lab/ - Broken Authentication
**Auteur:** Hamed  
**Difficulté:** Moyen  
**Tag:** AUTH  
**Port:** 8091

**Scénarios:**
- Énumération d'utilisateurs
- OTP faible à 4 chiffres
- Cookie manipulation

---

### 15. email_header_injection/ - Email Header Injection
**Auteur:** Emma  
**Difficulté:** Facile  
**Tag:** AppSec / Email  
**Port:** 8092

**Scénarios:**
- Injection de headers CRLF
- Modification d'en-têtes email
- Redirection de réponses

---

### 16. file-upload-lab/ - File Upload to RCE
**Auteur:** Hamed  
**Difficulté:** Moyen  
**Tag:** WEB / RCE  
**Port:** 8093

**Scénarios:**
- Upload de fichier malveillant
- Bypass de validation
- Exécution de code à distance

---

### 17. graphql-lab/ - GraphQL Injection
**Auteur:** Thiané  
**Difficulté:** Moyen → Difficile  
**Tag:** GraphQL / API  
**Port:** 8094

**Scénarios:**
- Introspection Attack
- Data Exfiltration
- Auth Bypass via Mutation

---

### 18. heartbleed-lab/ - Heartbleed
**Auteur:** Imane  
**Difficulté:** Moyen  
**Tag:** Crypto / TLS  
**Port:** 8095

**Scénarios:**
- CVE-2014-0160
- Buffer over-read dans OpenSSL
- Lecture de mémoire sensible
- Extraction de clés privées

---

### 19. log4shell-dockerlab-main/ - Log4Shell
**Auteur:** Farah  
**Difficulté:** Avancé  
**Tag:** RCE / Java  
**Port:** 8096

**Scénarios:**
- CVE-2021-44228
- JNDI Injection
- LDAP malveillant
- Remote Code Execution

---

### 20. sqli-auth-lab/ - SQL Injection Auth
**Auteur:** Lucien  
**Difficulté:** Moyen  
**Tag:** DB  
**Port:** 8097

**Scénarios:**
- Bypass d'authentification (Easy)
- Exfiltration via UNION SELECT (Medium)
- Blind SQLi booléenne (Hard)

---

### 21. sqli-error-based-lab/ - SQL Injection Error-Based
**Auteur:** Rachid  
**Difficulté:** Moyen  
**Tag:** Web  
**Port:** 8098

**Scénarios:**
- SQL Injection basée sur les erreurs
- Divulgation d'informations via erreurs SQL
- Comparaison vulnérable vs sécurisé

---

### 22. path-traversal-lab/ - Path Traversal
**Auteur:** Tarik  
**Difficulté:** Facile  
**Tag:** Web  
**Port:** 8099

**Scénarios:**
- Directory Traversal simple
- Accès aux fichiers sensibles
- Bypass de validation

---

### 23. phishing/ - Phishing
**Auteur:** Emma  
**Difficulté:** Facile  
**Tag:** Social Engineering  
**Port:** 8100

**Scénarios:**
- Détection d'emails de phishing
- Analyse des signaux faibles
- Sensibilisation utilisateurs

---

### 24. ssrf/ - Server-Side Request Forgery
**Auteur:** Chaimae  
**Difficulté:** Moyen  
**Tag:** Web  
**Port:** 8101

**Scénarios:**
- Server-Side Request Forgery
- Fetch d'URL non validée
- Accès aux services internes

---

### 25. blind-sqli/ - Blind SQL Injection
**Auteur:** Farah  
**Difficulté:** Moyen  
**Tag:** DB  
**Port:** 8102

**Scénarios:**
- Blind SQLi avec réponses booléennes
- Extraction caractère par caractère
- Automatisation de l'exploitation

---

### 26. injection_sql_temporell/ - Time-Based SQL Injection
**Auteur:** Mohamed  
**Difficulté:** Moyen  
**Tag:** DB  
**Port:** 8103

**Scénarios:**
- Time-Based Blind SQL Injection
- Utilisation de SLEEP() / WAITFOR
- Extraction de données via timing

---

## 🏗️ Structure Standard d'un Lab

Chaque lab doit suivre cette structure :

```
[nom]-lab/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
├── src/
│   ├── index.php
│   ├── config.php
│   ├── style.css
│   ├── health.php
│   └── reset.php
└── docs/
    └── breakdown_wp1.md
```

**Documentation complète:** `../docs/STRUCTURE_CONTAINERS.md`

---

## 🚀 Lancer un Lab

```bash
# Lab individuel
cd sqli-lab
docker-compose up -d
http://localhost:8080

# Tous les labs (depuis la racine)
docker-compose up -d
```

---

## 🤝 Ajouter un Nouveau Lab

1. **Consulter** `../docs/STRUCTURE_CONTAINERS.md`
2. **Créer** le dossier `[nom]-lab/`
3. **Suivre** la structure standard
4. **Tester** tous les scénarios
5. **Documenter** complètement
6. **Ajouter** l'entrée dans `../docker-compose.yml`


## 📞 Contact

**Responsable WP3:** Kennedy  
**GitHub:** [@tsubasa1123](https://github.com/tsubasa1123)

---

## 📊 Statistiques

- **Total Labs:** 26
- **Ports utilisés:** 8080-8103, 3000, 5678
- **Catégories:**
  - Injection: 9 labs (SQL, NoSQL, XPath, XXE, GraphQL, etc.)
  - Auth: 5 labs (Broken Auth, MFA Bypass, CSRF, JWT, IDOR)
  - RCE: 4 labs (File Upload, Shellshock, Log4Shell, API)
  - Crypto/Network: 2 labs (Heartbleed, MITM)
  - Web: 4 labs (XSS, Path Traversal, SSRF, Email Header)
  - Social Engineering: 1 lab (Phishing)
  - API: 2 labs (API Scanning, GraphQL)

---

**🚀 DIABLE v3.0 - Learn. Hack. Secure.**
