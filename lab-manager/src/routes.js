// src/routes.js — Définition de toutes les routes API
const express  = require('express');
const router   = express.Router();
const { requireApiKey, validateLabRequest } = require('./middleware');
const { spawnLab, destroyLab, getLabStatus } = require('./docker');
const { get, run, all } = require('./db');
const { LABS } = require('./labs');

const LAB_TTL = parseInt(process.env.LAB_TTL || '2700');  // secondes

// ─────────────────────────────────────────────────────────────────────
//  GET /api/labs
//  Retourne la liste des labs disponibles (pas besoin d'auth)
//  → Le site d'apprentissage peut l'utiliser pour afficher le catalogue
// ─────────────────────────────────────────────────────────────────────
router.get('/labs', (req, res) => {
    const list = Object.values(LABS).map(l => ({
        id:        l.id,
        title:     l.title,
        exercises: l.exercises,
    }));
    res.json({ labs: list });
});

// ─────────────────────────────────────────────────────────────────────
//  POST /api/spawn
//  Crée (ou récupère) un conteneur pour l'apprenant
//
//  Body  : { "user_id": "42", "lab": "xss" }
//  Retour: { "url": "http://IP:8051", "expires_in": 2700, "status": "running" }
// ─────────────────────────────────────────────────────────────────────
router.post('/spawn', requireApiKey, validateLabRequest, async (req, res) => {
    const { user_id, lab } = req.body;

    try {
        // Vérifier s'il existe déjà une session active en BDD
        const existing = await get(
            `SELECT * FROM lab_sessions WHERE user_id = ? AND lab_id = ? AND status != 'stopped'`,
            [String(user_id), lab]
        );

        const expiresAt = new Date(Date.now() + LAB_TTL * 1000).toISOString();

        if (existing) {
            // Remettre à jour le TTL
            await run(
                `UPDATE lab_sessions SET last_active = CURRENT_TIMESTAMP, expires_at = ?, status = 'running' WHERE id = ?`,
                [expiresAt, existing.id]
            );
            console.log(`[API] Session réutilisée : user=${user_id} lab=${lab} port=${existing.port}`);
            return res.json({
                url:        existing.port ? `http://${process.env.BASE_HOST || 'localhost'}:${existing.port}` : null,
                expires_in: LAB_TTL,
                status:     'running',
                reused:     true,
            });
        }

        // Spawner le conteneur
        const { containerName, port, url } = await spawnLab(String(user_id), lab);

        // Sauvegarder la session en BDD
        await run(
            `INSERT OR REPLACE INTO lab_sessions
             (user_id, lab_id, container_name, port, status, started_at, last_active, expires_at)
             VALUES (?, ?, ?, ?, 'running', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ?)`,
            [String(user_id), lab, containerName, port, expiresAt]
        );

        res.json({ url, expires_in: LAB_TTL, status: 'running', reused: false });

    } catch (err) {
        console.error(`[API] Erreur spawn user=${user_id} lab=${lab}:`, err.message);
        res.status(500).json({ error: 'Impossible de démarrer le lab', detail: err.message });
    }
});

// ─────────────────────────────────────────────────────────────────────
//  POST /api/destroy
//  Détruit le conteneur d'un apprenant pour un lab donné
//
//  Body  : { "user_id": "42", "lab": "xss" }
//  Retour: { "success": true }
// ─────────────────────────────────────────────────────────────────────
router.post('/destroy', requireApiKey, validateLabRequest, async (req, res) => {
    const { user_id, lab } = req.body;

    try {
        await destroyLab(String(user_id), lab);

        await run(
            `UPDATE lab_sessions SET status = 'stopped' WHERE user_id = ? AND lab_id = ?`,
            [String(user_id), lab]
        );

        res.json({ success: true });

    } catch (err) {
        console.error(`[API] Erreur destroy user=${user_id} lab=${lab}:`, err.message);
        res.status(500).json({ error: 'Impossible de détruire le lab', detail: err.message });
    }
});

// ─────────────────────────────────────────────────────────────────────
//  GET /api/status?user_id=42&lab=xss
//  Vérifie si un conteneur tourne et combien de temps il lui reste
//
//  Retour: { "running": true, "url": "http://IP:8051", "expires_in": 1200 }
// ─────────────────────────────────────────────────────────────────────
router.get('/status', requireApiKey, async (req, res) => {
    const { user_id, lab } = req.query;

    if (!user_id || !lab) {
        return res.status(400).json({ error: 'Paramètres manquants : user_id, lab' });
    }

    try {
        const session = await get(
            `SELECT * FROM lab_sessions WHERE user_id = ? AND lab_id = ? AND status != 'stopped'`,
            [String(user_id), lab]
        );

        if (!session) {
            return res.json({ running: false });
        }

        // Calculer le temps restant
        const expiresAt  = new Date(session.expires_at).getTime();
        const expiresIn  = Math.max(0, Math.round((expiresAt - Date.now()) / 1000));
        const url        = `http://${process.env.BASE_HOST || 'localhost'}:${session.port}`;

        // Vérifier aussi côté Docker (le conteneur peut avoir crashé)
        const dockerStatus = await getLabStatus(String(user_id), lab);

        if (!dockerStatus.running) {
            // Le conteneur est mort, on met à jour la BDD
            await run(
                `UPDATE lab_sessions SET status = 'stopped' WHERE id = ?`,
                [session.id]
            );
            return res.json({ running: false });
        }

        // Mettre à jour last_active
        await run(
            `UPDATE lab_sessions SET last_active = CURRENT_TIMESTAMP WHERE id = ?`,
            [session.id]
        );

        res.json({ running: true, url, expires_in: expiresIn });

    } catch (err) {
        console.error(`[API] Erreur status user=${user_id} lab=${lab}:`, err.message);
        res.status(500).json({ error: 'Erreur lors de la vérification du statut' });
    }
});

// ─────────────────────────────────────────────────────────────────────
//  GET /api/health
//  Endpoint de santé — pas d'auth requise
//  Utile pour vérifier que le Lab Manager est bien démarré
// ─────────────────────────────────────────────────────────────────────
router.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

module.exports = router;
