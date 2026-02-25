---
title: "Man-in-the-Middle (MITM)"
tag: "web"
difficulty: "moyen"
goal: "intercepter et manipuler le trafic réseau entre un client et un serveur pour voler des données sensibles"
fix: "HTTPS/TLS, Certificate Pinning, HSTS, cookies sécurisés, mutual TLS"
---

# Théorie

## Définition

L'attaque Man-in-the-Middle (MITM) ou "Homme du milieu" permet à un attaquant de s'interposer secrètement dans une communication entre deux parties pour intercepter, lire, et potentiellement modifier les données échangées sans que les victimes ne s'en aperçoivent.

## Origine de la vulnérabilité

Cette vulnérabilité apparaît lorsque les communications réseau ne sont pas correctement chiffrées et authentifiées. L'absence de HTTPS, les certificats SSL/TLS mal configurés ou non validés, et les protocoles de sécurité obsolètes créent des opportunités pour un attaquant de se positionner entre le client et le serveur.

## Contextes d'apparition

- Applications web utilisant HTTP au lieu de HTTPS
- Réseaux WiFi publics non sécurisés
- Certificats SSL/TLS auto-signés ou expirés
- Absence de Certificate Pinning dans les applications mobiles
- Proxies d'entreprise mal configurés
- Attaques ARP Spoofing sur réseaux locaux
- DNS Spoofing et Cache Poisoning

## Principe d'exploitation

Un attaquant positionné sur le chemin réseau (proxy, routeur compromis, réseau WiFi malveillant) intercepte tout le trafic. Sans chiffrement HTTPS, les données transitent en clair : credentials, cookies de session, données personnelles, etc. L'attaquant peut :

1. **Observer** : Capturer credentials, cookies, données sensibles
2. **Voler** : Réutiliser sessions et tokens interceptés
3. **Modifier** : Altérer les requêtes et réponses
4. **Injecter** : Insérer du code malveillant dans les pages

Le problème fondamental est **l'absence de chiffrement et d'authentification** des communications.

---

# Lab

## Objectif

Comprendre les mécanismes des attaques MITM en observant l'interception de trafic HTTP non chiffré et apprendre les mesures de protection.

## Configuration

Application Node.js simulant une banque en ligne vulnérable + proxy MITM intercepteur en environnement Docker isolé.

### Démarrage avec Docker Compose (méthode recommandée)

```bash
docker-compose up -d
docker-compose logs -f

# Pour arrêter
docker-compose down
```

### Démarrage manuel avec Docker

```bash
# 1. Créer un réseau Docker
docker network create diable-network

# 2. Démarrer le serveur victime
docker build -f Dockerfile.victim -t diable/mitm-victim-server:latest .
docker run -d \
  --name mitm-victim-server \
  --network diable-network \
  -p 8080:8080 \
  -e NODE_ENV=development \
  diable/mitm-victim-server:latest

# 3. Démarrer le proxy MITM
docker build -f Dockerfile.proxy -t diable/mitm-proxy:latest .
docker run -d \
  --name mitm-proxy \
  --network diable-network \
  -p 8888:8888 \
  -e TARGET_HOST=mitm-victim-server \
  -e TARGET_PORT=8080 \
  -e DEBUG_MODE=true \
  diable/mitm-proxy:latest

# 4. Démarrer l'application lab
docker build -t diable/mitm-attack-lab:latest .
docker run -d \
  --name mitm-lab \
  --network diable-network \
  -p 3000:3000 \
  -e VICTIM_SERVER=http://mitm-victim-server:8080 \
  -e MITM_PROXY=http://mitm-proxy:8888 \
  -e NODE_ENV=development \
  diable/mitm-attack-lab:latest

# Pour voir les logs du proxy MITM
docker logs -f mitm-proxy

# Pour arrêter
docker stop mitm-lab mitm-proxy mitm-victim-server
docker rm mitm-lab mitm-proxy mitm-victim-server
docker network rm diable-network
```

## Architecture

```
Client → MITM Proxy (Port 8888) → Victim Server (Port 8080)
            ↓
    Admin API (Port 8889)
    (données interceptées)
```

**Flux normal** : Client ↔ Server (HTTP non chiffré - vulnérable)  
**Flux attaqué** : Client ↔ Attacker Proxy ↔ Server (interception totale)  
**Impact** : L'attaquant voit et peut modifier **TOUT** le trafic

## Services

- **Port 3000** : Interface du lab (documentation)
- **Port 8080** : Serveur victime (banque en ligne HTTP)
- **Port 8888** : Proxy MITM intercepteur
- **Port 8889** : API admin pour consulter les données interceptées

## Techniques d'exploitation

1. **Interception de credentials** : Capture des username/password lors du login
2. **Session hijacking** : Vol des session IDs et cookies
3. **Cookie stealing** : Réutilisation des cookies volés
4. **Data exfiltration** : Capture de données sensibles (numéros de carte, secrets)
5. **Traffic modification** : Modification des requêtes et réponses à la volée
6. **Content injection** : Injection de code malveillant dans les pages

## Données disponibles

### Utilisateurs de test

| Username | Password | Rôle | Balance | Secret |
|----------|----------|------|---------|--------|
| admin | Admin123! | admin | $50,000 | FLAG{mitm_attack_master} |
| alice | alice2024 | user | $2,500 | Numéro de carte: 4532-1234-5678-9010 |
| bob | bobsecure | user | $1,800 | CVV: 123 |

## Scénarios d'attaque

### Scénario 1 : Interception de credentials

```bash
# 1. Victime se connecte via le proxy
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"alice2024"}'

# 2. Attaquant consulte les credentials interceptés
curl http://localhost:8889/intercepted/credentials
```

### Scénario 2 : Session hijacking

```bash
# 1. Victime se connecte
curl -X POST http://localhost:8888/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"alice2024"}' \
  -c cookies.txt

# 2. Attaquant récupère la session
curl http://localhost:8889/intercepted/sessions

# 3. Attaquant réutilise la session volée
curl http://localhost:8888/api/account \
  -b cookies.txt
```

### Scénario 3 : Vol de données sensibles

```bash
# Victime consulte son compte (données sensibles)
curl http://localhost:8888/api/account \
  -b cookies.txt

# Attaquant voit les secrets transitant en clair
curl http://localhost:8889/intercepted/all
```

## Vulnérabilités démontrées

1. ⚠️ **HTTP non chiffré** - Trafic lisible en clair
2. ⚠️ **Cookies non sécurisés** - Absence des flags Secure et HttpOnly
3. ⚠️ **Session ID exposé** - Renvoyé dans la réponse JSON
4. ⚠️ **Pas de validation d'intégrité** - Modifications non détectées
5. ⚠️ **Données sensibles en clair** - Passwords, secrets, numéros de carte

## Protection et mitigation

### 1. Forcer HTTPS partout

```javascript
// Redirection HTTP → HTTPS
app.use((req, res, next) => {
  if (!req.secure && process.env.NODE_ENV === 'production') {
    return res.redirect('https://' + req.headers.host + req.url);
  }
  next();
});
```

### 2. Cookies sécurisés

```javascript
app.use(session({
  secret: process.env.SESSION_SECRET,
  cookie: { 
    secure: true,        // HTTPS uniquement
    httpOnly: true,      // Pas accessible via JavaScript
    sameSite: 'strict',  // Protection CSRF
    maxAge: 3600000
  }
}));
```

### 3. HSTS (HTTP Strict Transport Security)

```javascript
app.use((req, res, next) => {
  res.setHeader(
    'Strict-Transport-Security',
    'max-age=31536000; includeSubDomains; preload'
  );
  next();
});
```

### 4. Certificate Pinning

Épingler le certificat SSL/TLS attendu dans l'application pour détecter les certificats frauduleux.

### 5. Mutual TLS (mTLS)

Authentification bidirectionnelle avec certificats client et serveur.

### 6. Ne jamais faire confiance aux réseaux publics

Toujours utiliser un VPN sur les réseaux WiFi publics.

---

## Standards DIABLE

Ce lab respecte les standards DIABLE v3.0 :

- ✅ Architecture multi-conteneurs isolée
- ✅ Endpoints `/health` et `/reset` 
- ✅ Documentation complète (README, lab.md, EXPLOITATION.md)
- ✅ Interface web avec palette DIABLE
- ✅ Health checks configurés
- ✅ Labels Docker standardisés

## Ressources

- [OWASP: Man-in-the-Middle Attacks](https://owasp.org/www-community/attacks/Man-in-the-middle_attack)
- [HTTPS Everywhere](https://www.eff.org/https-everywhere)
- [Certificate Pinning Guide](https://owasp.org/www-community/controls/Certificate_and_Public_Key_Pinning)
- [HSTS Preload List](https://hstspreload.org/)
