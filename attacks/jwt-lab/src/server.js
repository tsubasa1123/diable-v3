const express = require('express');
const jwt = require('jsonwebtoken');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'public')));

// =============================================
//  CONFIGURATION (volontairement vulnérable)
// =============================================
const WEAK_SECRET = 'secret123';  // Clé faible pour brute force
const FLAG_1 = 'DIABLE{jwt_alg_none_bypass}';
const FLAG_2 = 'DIABLE{jwt_weak_secret_cracked}';
const FLAG_3 = 'DIABLE{jwt_rs256_to_hs256_confusion}';
const FLAG_4 = 'DIABLE{jwt_kid_injection_pwned}';

// Clé RSA publique (simulée pour le lab)
const RSA_PUBLIC_KEY = `-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2a2rwplBQLzHPZe5TNJH
xvFSYBSfSOf9lF7DJRT4YOFMXaWjAMuiMIGHxBFxzOFMijQ8h1GWBj7GdF7EbYPl
J3uumDwSf1bMiJPFR3Km91V1g+1xJqGcqNJCOd/6y0r5RCgRRsO/O7X2OJFpLEll
yXHrGKU8D4cELxs3GJJ0V1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1
M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1
M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1M3M1IDAQAB
-----END PUBLIC KEY-----`;

// Base de données simulée
const USERS = {
  alice: { password: 'password123', role: 'user' },
  admin: { password: 'sup3rs3cr3t!', role: 'admin' }
};

// =============================================
//  ROUTES COMMUNES
// =============================================

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'ok', lab: 'JWT Vulnerabilities', version: '1.0.0' });
});

// Reset endpoint
app.post('/reset', (req, res) => {
  res.json({ status: 'reset', message: 'Lab réinitialisé avec succès' });
});

// =============================================
//  SCÉNARIO 1 : alg=none bypass
// =============================================
app.get('/scenario1', (req, res) => res.sendFile(path.join(__dirname, 'public/scenario1.html')));

app.post('/scenario1/login', (req, res) => {
  const { username, password } = req.body;
  const user = USERS[username];
  if (!user || user.password !== password) {
    return res.json({ success: false, message: 'Identifiants incorrects' });
  }
  // Token signé normalement avec HS256
  const token = jwt.sign({ username, role: user.role }, WEAK_SECRET, { algorithm: 'HS256', expiresIn: '1h' });
  res.json({ success: true, token, message: `Bienvenue ${username} ! Votre rôle: ${user.role}` });
});

app.post('/scenario1/admin', (req, res) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) return res.status(401).json({ success: false, message: 'Token manquant' });

  const token = authHeader.replace('Bearer ', '');
  try {
    // VULNÉRABILITÉ: accepte alg=none sans vérification
    const decoded = jwt.decode(token);
    if (!decoded) return res.status(401).json({ success: false, message: 'Token invalide' });

    // Vérification naïve: on décode sans vérifier la signature si alg=none
    const parts = token.split('.');
    const header = JSON.parse(Buffer.from(parts[0], 'base64url').toString());

    if (header.alg === 'none' || header.alg === 'NONE') {
      // VULNÉRABLE: on fait confiance au payload sans vérifier la signature
      if (decoded.role === 'admin') {
        return res.json({ success: true, flag: FLAG_1, message: '🎉 Accès admin obtenu via alg=none !' });
      }
      return res.json({ success: false, message: `Rôle insuffisant: ${decoded.role}` });
    }

    // Vérification normale
    const verified = jwt.verify(token, WEAK_SECRET);
    if (verified.role === 'admin') {
      return res.json({ success: true, flag: FLAG_1, message: '🎉 Accès admin légitime !' });
    }
    return res.json({ success: false, message: `Rôle insuffisant: ${verified.role}` });

  } catch (e) {
    return res.status(401).json({ success: false, message: 'Token invalide: ' + e.message });
  }
});

// =============================================
//  SCÉNARIO 2 : Brute force clé secrète
// =============================================
app.get('/scenario2', (req, res) => res.sendFile(path.join(__dirname, 'public/scenario2.html')));

app.post('/scenario2/login', (req, res) => {
  const { username, password } = req.body;
  const user = USERS[username];
  if (!user || user.password !== password) {
    return res.json({ success: false, message: 'Identifiants incorrects' });
  }
  // VULNÉRABILITÉ: clé secrète faible
  const token = jwt.sign({ username, role: user.role }, WEAK_SECRET, { algorithm: 'HS256', expiresIn: '1h' });
  res.json({ success: true, token, message: `Token généré avec une clé... robuste 😏` });
});

app.post('/scenario2/admin', (req, res) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) return res.status(401).json({ success: false, message: 'Token manquant' });

  const token = authHeader.replace('Bearer ', '');
  try {
    const verified = jwt.verify(token, WEAK_SECRET);
    if (verified.role === 'admin') {
      return res.json({ success: true, flag: FLAG_2, message: '🎉 Clé secrète cassée avec succès !' });
    }
    return res.json({ success: false, message: `Rôle: ${verified.role} - pas suffisant` });
  } catch (e) {
    return res.status(401).json({ success: false, message: 'Token invalide' });
  }
});

// Endpoint pour tester un secret (simulation brute force)
app.post('/scenario2/bruteforce', (req, res) => {
  const { token, secret } = req.body;
  try {
    const verified = jwt.verify(token, secret);
    res.json({ success: true, message: `✅ Secret trouvé: "${secret}"`, payload: verified });
  } catch (e) {
    res.json({ success: false, message: `❌ Secret incorrect: "${secret}"` });
  }
});

// =============================================
//  SCÉNARIO 3 : RS256 → HS256 Confusion
// =============================================
app.get('/scenario3', (req, res) => res.sendFile(path.join(__dirname, 'public/scenario3.html')));

app.get('/scenario3/public-key', (req, res) => {
  // VULNÉRABILITÉ: la clé publique est accessible
  res.json({ publicKey: RSA_PUBLIC_KEY, message: 'Clé publique du serveur' });
});

app.post('/scenario3/login', (req, res) => {
  const { username, password } = req.body;
  const user = USERS[username];
  if (!user || user.password !== password) {
    return res.json({ success: false, message: 'Identifiants incorrects' });
  }
  const token = jwt.sign({ username, role: user.role }, WEAK_SECRET, { algorithm: 'HS256', expiresIn: '1h' });
  res.json({ success: true, token, message: `Token RS256 généré (simulé)` });
});

app.post('/scenario3/admin', (req, res) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) return res.status(401).json({ success: false, message: 'Token manquant' });

  const token = authHeader.replace('Bearer ', '');
  try {
    // VULNÉRABILITÉ: accepte HS256 avec la clé publique comme secret
    const parts = token.split('.');
    const header = JSON.parse(Buffer.from(parts[0], 'base64url').toString());

    let verified;
    if (header.alg === 'HS256') {
      // Le serveur utilise la clé publique RSA comme secret HMAC → vulnérable !
      verified = jwt.verify(token, RSA_PUBLIC_KEY, { algorithms: ['HS256'] });
    } else {
      verified = jwt.verify(token, WEAK_SECRET);
    }

    if (verified.role === 'admin') {
      return res.json({ success: true, flag: FLAG_3, message: '🎉 Confusion RS256/HS256 exploitée !' });
    }
    return res.json({ success: false, message: `Rôle: ${verified.role}` });
  } catch (e) {
    return res.status(401).json({ success: false, message: 'Token invalide: ' + e.message });
  }
});

// =============================================
//  SCÉNARIO 4 : kid Header Injection
// =============================================
app.get('/scenario4', (req, res) => res.sendFile(path.join(__dirname, 'public/scenario4.html')));

// Simule un keystore interne
const KEY_STORE = {
  'key-prod': WEAK_SECRET,
  'key-dev': 'devkey'
};

app.post('/scenario4/login', (req, res) => {
  const { username, password } = req.body;
  const user = USERS[username];
  if (!user || user.password !== password) {
    return res.json({ success: false, message: 'Identifiants incorrects' });
  }
  const token = jwt.sign(
    { username, role: user.role },
    KEY_STORE['key-prod'],
    { algorithm: 'HS256', header: { kid: 'key-prod', alg: 'HS256', typ: 'JWT' }, expiresIn: '1h' }
  );
  res.json({ success: true, token, message: 'Token avec kid header généré' });
});

app.post('/scenario4/admin', (req, res) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) return res.status(401).json({ success: false, message: 'Token manquant' });

  const token = authHeader.replace('Bearer ', '');
  try {
    const parts = token.split('.');
    const header = JSON.parse(Buffer.from(parts[0], 'base64url').toString());
    const kid = header.kid || 'key-prod';

    // VULNÉRABILITÉ: injection dans le kid - si kid = "../../" ou une valeur contrôlée
    // Simulation: si kid contient "../" on accepte une clé vide (vulnérable)
    let secret;
    if (kid.includes('../') || kid.includes('..\\')) {
      secret = ''; // Path traversal → clé vide
    } else {
      secret = KEY_STORE[kid] || WEAK_SECRET;
    }

    const verified = jwt.verify(token, secret, { algorithms: ['HS256'] });
    if (verified.role === 'admin') {
      return res.json({ success: true, flag: FLAG_4, message: '🎉 kid injection réussie !' });
    }
    return res.json({ success: false, message: `Rôle: ${verified.role}` });
  } catch (e) {
    return res.status(401).json({ success: false, message: 'Token invalide: ' + e.message });
  }
});

// =============================================
//  PAGE D'ACCUEIL
// =============================================
app.get('/', (req, res) => res.sendFile(path.join(__dirname, 'public/index.html')));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`🔐 JWT Lab démarré sur le port ${PORT}`));
