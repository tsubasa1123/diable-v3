const express = require('express');
const bodyParser = require('body-parser');
const cookieParser = require('cookie-parser');
const session = require('express-session');

const app = express();
const PORT = 8080;

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(cookieParser());

// VULNÉRABILITÉ 1: Session sur HTTP non sécurisé
app.use(session({
  secret: 'vulnerable-secret-key',
  resave: false,
  saveUninitialized: true,
  cookie: { 
    secure: false,  // VULNÉRABLE: Pas de flag secure
    httpOnly: false, // VULNÉRABLE: Pas de flag httpOnly
    maxAge: 3600000
  }
}));

// Base de données en mémoire (simulation)
const users = [
  { 
    id: 1, 
    username: 'admin', 
    password: 'Admin123!', 
    email: 'admin@bank.local',
    role: 'admin',
    balance: 50000,
    secret: 'FLAG{U4EoBR3oA1IJ1XnHWy}'
  },
  { 
    id: 2, 
    username: 'alice', 
    password: 'alice2024', 
    email: 'alice@bank.local',
    role: 'user',
    balance: 2500,
    secret: 'Numéro de carte: 4532-1234-5678-9010'
  },
  { 
    id: 3, 
    username: 'bob', 
    password: 'bobsecure', 
    email: 'bob@bank.local',
    role: 'user',
    balance: 1800,
    secret: 'CVV: 123'
  }
];

const transactions = [];

// VULNERABILITÉ 2: Communication HTTP non chiffrée
app.post('/api/login', (req, res) => {
  const { username, password } = req.body;

  // VULNÉRABLE: Credentials transférés en clair
  const user = users.find(u => u.username === username && u.password === password);

  if (user) {
    // VULNÉRABLE: Session ID exposé sans protection
    req.session.userId = user.id;
    req.session.username = user.username;
    req.session.role = user.role;

    // VULNÉRABLE: Token dans un cookie non sécurisé
    res.cookie('auth_token', `${user.id}:${user.username}:${Date.now()}`, {
      httpOnly: false,
      secure: false
    });

    res.json({
      success: true,
      message: 'Connexion réussie',
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        role: user.role,
        balance: user.balance
      },
      sessionId: req.sessionID // VULNÉRABLE: Exposition du session ID
    });
  } else {
    res.status(401).json({
      success: false,
      message: 'Identifiants invalides'
    });
  }
});

// VULNÉRABILITÉ 3: Pas de validation du referer ou CSRF token
app.post('/api/transfer', (req, res) => {
  if (!req.session.userId) {
    return res.status(401).json({
      success: false,
      message: 'Non authentifié'
    });
  }

  const { to, amount } = req.body;
  const fromUser = users.find(u => u.id === req.session.userId);
  const toUser = users.find(u => u.username === to);

  if (!fromUser || !toUser) {
    return res.status(400).json({
      success: false,
      message: 'Utilisateurs invalides'
    });
  }

  if (fromUser.balance < amount) {
    return res.status(400).json({
      success: false,
      message: 'Fonds insuffisants'
    });
  }

  // Effectuer le transfert
  fromUser.balance -= amount;
  toUser.balance += amount;

  const transaction = {
    id: transactions.length + 1,
    from: fromUser.username,
    to: toUser.username,
    amount: amount,
    timestamp: new Date().toISOString()
  };

  transactions.push(transaction);

  res.json({
    success: true,
    message: 'Transfert réussi',
    transaction: transaction,
    newBalance: fromUser.balance
  });
});

// VULNÉRABILITÉ 4: Informations sensibles transférées en clair
app.get('/api/account', (req, res) => {
  if (!req.session.userId) {
    return res.status(401).json({
      success: false,
      message: 'Non authentifié'
    });
  }

  const user = users.find(u => u.id === req.session.userId);

  if (!user) {
    return res.status(404).json({
      success: false,
      message: 'Utilisateur introuvable'
    });
  }

  // VULNÉRABLE: Secret transmis en clair sur HTTP
  res.json({
    success: true,
    user: {
      id: user.id,
      username: user.username,
      email: user.email,
      role: user.role,
      balance: user.balance,
      secret: user.secret
    }
  });
});

// Liste des transactions
app.get('/api/transactions', (req, res) => {
  if (!req.session.userId) {
    return res.status(401).json({
      success: false,
      message: 'Non authentifié'
    });
  }

  const user = users.find(u => u.id === req.session.userId);
  const userTransactions = transactions.filter(
    t => t.from === user.username || t.to === user.username
  );

  res.json({
    success: true,
    transactions: userTransactions
  });
});

// Logout
app.post('/api/logout', (req, res) => {
  req.session.destroy();
  res.clearCookie('auth_token');
  res.json({
    success: true,
    message: 'Déconnexion réussie'
  });
});

// Liste des utilisateurs (pour les tests)
app.get('/api/users', (req, res) => {
  res.json({
    success: true,
    users: users.map(u => ({
      id: u.id,
      username: u.username,
      email: u.email,
      role: u.role
    }))
  });
});

// Health check
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'mitm-victim-server',
    timestamp: new Date().toISOString()
  });
});

// Reset endpoint
app.post('/reset', (req, res) => {
  // Réinitialiser les balances
  users[0].balance = 50000;
  users[1].balance = 2500;
  users[2].balance = 1800;
  
  // Vider les transactions
  transactions.length = 0;

  res.json({
    success: true,
    message: 'Base de données réinitialisée avec succès'
  });
});

app.listen(PORT, () => {
  console.log(`Serveur Victime en cours d'exécution sur le port HTTP ${PORT}`);
  console.log(`ATTENTION: Ce serveur est intentionnellement NON SÉCURISÉ à des fins éducatives`);
  console.log(`   - Pas de chiffrement HTTPS`);
  console.log(`   - Gestion de session faible`);
  console.log(`   - Pas de protection CSRF`);
  console.log(`   - Données sensibles en clair`);
});
