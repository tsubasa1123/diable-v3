// src/middleware.js — Middleware d'authentification par clé API

const API_SECRET = process.env.API_SECRET || 'change_me';

/**
 * Vérifie la clé API dans le header X-API-Key.
 * Le site d'apprentissage doit envoyer ce header à chaque requête.
 *
 * Exemple côté site d'apprentissage :
 *   fetch('http://lab-manager:4000/api/spawn', {
 *     method: 'POST',
 *     headers: {
 *       'Content-Type': 'application/json',
 *       'X-API-Key': 'ma_cle_secrete'   ← même valeur que API_SECRET dans .env
 *     },
 *     body: JSON.stringify({ user_id: 42, lab: 'xss' })
 *   })
 */
function requireApiKey(req, res, next) {
    const key = req.headers['x-api-key'];

    if (!key) {
        return res.status(401).json({
            error: 'Clé API manquante',
            hint:  'Ajouter le header X-API-Key à la requête'
        });
    }

    if (key !== API_SECRET) {
        return res.status(403).json({ error: 'Clé API invalide' });
    }

    next();
}

/**
 * Valide que user_id et lab sont présents et valides dans le body.
 */
function validateLabRequest(req, res, next) {
    const { user_id, lab } = req.body;
    const { LABS } = require('./labs');

    if (!user_id) {
        return res.status(400).json({ error: 'Paramètre manquant : user_id' });
    }
    if (!lab) {
        return res.status(400).json({ error: 'Paramètre manquant : lab' });
    }
    if (!LABS[lab]) {
        return res.status(400).json({
            error:     `Lab inconnu : ${lab}`,
            available: Object.keys(LABS)
        });
    }

    next();
}

module.exports = { requireApiKey, validateLabRequest };
