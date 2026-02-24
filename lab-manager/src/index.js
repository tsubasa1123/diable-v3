// src/index.js — Point d'entrée du Lab Manager
require('dotenv').config();

const express = require('express');
const cors    = require('cors');
const { init: initDb } = require('./db');
const routes  = require('./routes');
const { runCleanup } = require('./cleanup');

const app  = express();
const PORT = process.env.API_PORT || 3000;

// ── Middlewares ───────────────────────────────────────────────────────
app.use(express.json());

// CORS : autoriser uniquement le site d'apprentissage
// En dev, on accepte tout ; en prod, restreindre à l'IP/domaine du site
app.use(cors({
    origin: process.env.ALLOWED_ORIGIN || '*',
    methods: ['GET', 'POST'],
}));

// ── Routes ────────────────────────────────────────────────────────────
app.use('/api', routes);

// Route racine — documentation rapide des endpoints
app.get('/', (req, res) => {
    res.json({
        name:    'Lab Manager API',
        version: '1.0.0',
        endpoints: {
            'GET  /api/health':                          'Vérifier que le service tourne',
            'GET  /api/labs':                            'Liste des labs disponibles',
            'POST /api/spawn   { user_id, lab }':        'Démarrer un lab pour un apprenant',
            'POST /api/destroy { user_id, lab }':        'Arrêter un lab',
            'GET  /api/status?user_id=X&lab=Y':          'Statut d\'un lab',
        },
        auth: 'Header X-API-Key requis sur tous les endpoints sauf /health et /labs',
    });
});

// ── Démarrage ─────────────────────────────────────────────────────────
async function start() {
    // Initialiser la base de données
    await initDb();

    // Démarrer le serveur
    app.listen(PORT, () => {
        console.log(`\n🚀 Lab Manager démarré sur le port ${PORT}`);
        console.log(`   BASE_HOST  : ${process.env.BASE_HOST || 'localhost'}`);
        console.log(`   LABS_DIR   : ${process.env.LABS_DIR || '/opt/labs'}`);
        console.log(`   LAB_TTL    : ${process.env.LAB_TTL || 2700}s\n`);
    });

    // Cleanup automatique toutes les 5 minutes
    setInterval(runCleanup, 5 * 60 * 1000);
    console.log('[Scheduler] Cleanup automatique activé (toutes les 5 min)');
}

start().catch(err => {
    console.error('Erreur au démarrage :', err);
    process.exit(1);
});
