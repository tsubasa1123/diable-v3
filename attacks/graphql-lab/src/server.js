const express = require('express');
const { graphqlHTTP } = require('express-graphql');
const { buildSchema } = require('graphql');
const path = require('path');
const app = express();

app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// =============================================
//  FLAGS
// =============================================
const FLAG_1 = 'DIABLE{graphql_introspection_exposed}';
const FLAG_2 = 'DIABLE{graphql_data_exfiltration_pwned}';
const FLAG_3 = 'DIABLE{graphql_auth_bypass_admin}';

// =============================================
//  BASE DE DONNÉES SIMULÉE (volontairement sensible)
// =============================================
const USERS = [
  { id: '1', username: 'alice',  email: 'alice@company.com',  role: 'user',  salary: 35000, password: 'alice123',       creditCard: '4532-XXXX-XXXX-1234' },
  { id: '2', username: 'bob',    email: 'bob@company.com',    role: 'user',  salary: 42000, password: 'bob456',         creditCard: '4916-XXXX-XXXX-5678' },
  { id: '3', username: 'carol',  email: 'carol@company.com',  role: 'user',  salary: 38000, password: 'carol789',       creditCard: '5425-XXXX-XXXX-9012' },
  { id: '4', username: 'admin',  email: 'admin@company.com',  role: 'admin', salary: 95000, password: 'Adm1n$uper!',    creditCard: '4111-XXXX-XXXX-3456' },
  { id: '5', username: 'dave',   email: 'dave@company.com',   role: 'user',  salary: 29000, password: 'dave321',        creditCard: '3782-XXXX-XXXX-7890' },
];

const ORDERS = [
  { id: 'ORD-001', userId: '1', product: 'Laptop Pro', amount: 1299.99, status: 'delivered' },
  { id: 'ORD-002', userId: '2', product: 'iPhone 15',  amount: 999.00,  status: 'pending'   },
  { id: 'ORD-003', userId: '4', product: 'Server Rack', amount: 15000.00, status: 'delivered' },
];

const SECRET_DOCS = [
  { id: 'DOC-001', title: 'Rapport Financier Q4 2025', content: 'Bénéfice net: 2.4M€. Objectif 2026: 3.1M€', classification: 'CONFIDENTIEL' },
  { id: 'DOC-002', title: 'Plan Stratégique 2026',    content: 'Acquisition cible: StartupXYZ pour 8M€',      classification: 'SECRET' },
];

// =============================================
//  SCHÉMA GRAPHQL (volontairement exposé)
// =============================================
const schema = buildSchema(`
  type User {
    id: ID
    username: String
    email: String
    role: String
    salary: Int
    password: String
    creditCard: String
    orders: [Order]
  }

  type Order {
    id: ID
    userId: ID
    product: String
    amount: Float
    status: String
    user: User
  }

  type Document {
    id: ID
    title: String
    content: String
    classification: String
  }

  type AuthPayload {
    success: Boolean
    token: String
    user: User
    flag: String
    message: String
  }

  type Query {
    # VULNÉRABLE: introspection activée + données sensibles exposées
    user(id: ID!): User
    users: [User]
    order(id: ID!): Order
    orders: [Order]
    documents: [Document]
    me: User
    flag1: String
  }

  type Mutation {
    login(username: String!, password: String!): AuthPayload
    loginAdmin(token: String): AuthPayload
  }
`);

// =============================================
//  RÉSOLVEURS
// =============================================
let currentUser = null;

const root = {
  // VULNÉRABLE: retourne tous les utilisateurs avec données sensibles
  users: () => {
    return USERS;
  },

  user: ({ id }) => {
    return USERS.find(u => u.id === id);
  },

  orders: () => ORDERS,
  order: ({ id }) => ORDERS.find(o => o.id === id),

  // VULNÉRABLE: documents confidentiels accessibles sans auth
  documents: () => SECRET_DOCS,

  me: () => currentUser,

  flag1: () => FLAG_1,

  login: ({ username, password }) => {
    const user = USERS.find(u => u.username === username && u.password === password);
    if (!user) return { success: false, message: 'Identifiants incorrects' };
    currentUser = user;
    const token = Buffer.from(JSON.stringify({ id: user.id, role: user.role })).toString('base64');
    return { success: true, token, user, message: `Bienvenue ${username}` };
  },

  // VULNÉRABLE: bypass auth si token manipulé avec role=admin
  loginAdmin: ({ token }) => {
    try {
      if (!token) return { success: false, message: 'Token requis' };
      const decoded = JSON.parse(Buffer.from(token, 'base64').toString());
      // VULNÉRABILITÉ: on fait confiance au role dans le token sans vérification
      if (decoded.role === 'admin') {
        const adminUser = USERS.find(u => u.role === 'admin');
        return { success: true, flag: FLAG_3, user: adminUser, message: '🎉 Accès admin obtenu !' };
      }
      return { success: false, message: `Rôle insuffisant: ${decoded.role}` };
    } catch(e) {
      return { success: false, message: 'Token invalide' };
    }
  }
};

// =============================================
//  ENDPOINT GRAPHQL — VULNÉRABLE (introspection activée)
// =============================================
app.use('/graphql', graphqlHTTP({
  schema,
  rootValue: root,
  graphiql: true, // VULNÉRABILITÉ: interface GraphiQL exposée en production
  customFormatErrorFn: (err) => ({
    message: err.message,
    locations: err.locations,
    stack: err.stack, // VULNÉRABILITÉ: stack traces exposées
  })
}));

// =============================================
//  ENDPOINT POUR FLAG 2 (data exfiltration)
// =============================================
app.post('/api/check-exfiltration', (req, res) => {
  const { data } = req.body;
  // Vérifie que l'étudiant a bien extrait les données sensibles
  if (data && data.includes('Adm1n$uper!')) {
    res.json({ success: true, flag: FLAG_2, message: '🎉 Données sensibles exfiltrées avec succès !' });
  } else if (data && data.includes('95000')) {
    res.json({ success: true, flag: FLAG_2, message: '🎉 Données financières exfiltrées !' });
  } else {
    res.json({ success: false, message: 'Continuez à creuser...' });
  }
});

// =============================================
//  ROUTES
// =============================================
app.get('/health', (req, res) => res.json({ status: 'ok', lab: 'GraphQL Injection Lab', version: '1.0.0' }));
app.post('/reset', (req, res) => { currentUser = null; res.json({ status: 'reset' }); });
app.get('/', (req, res) => res.sendFile(path.join(__dirname, 'public/index.html')));
app.get('/scenario1', (req, res) => res.sendFile(path.join(__dirname, 'public/scenario1.html')));
app.get('/scenario2', (req, res) => res.sendFile(path.join(__dirname, 'public/scenario2.html')));
app.get('/scenario3', (req, res) => res.sendFile(path.join(__dirname, 'public/scenario3.html')));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`🕸️ GraphQL Lab démarré sur le port ${PORT}`));
