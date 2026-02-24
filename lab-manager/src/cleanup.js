// src/cleanup.js — Nettoyage automatique des conteneurs expirés
// Appelé toutes les 5 minutes par le scheduler dans index.js

const { all, run } = require('./db');
const { cleanupExpired } = require('./docker');

async function runCleanup() {
    try {
        // Trouver toutes les sessions expirées ou inactives depuis plus de 30 min
        const expired = await all(`
            SELECT * FROM lab_sessions
            WHERE status = 'running'
            AND (
                expires_at < CURRENT_TIMESTAMP
                OR last_active < datetime('now', '-30 minutes')
            )
        `);

        if (expired.length === 0) return;

        console.log(`[Cleanup] ${expired.length} conteneur(s) expiré(s) à détruire...`);

        const destroyed = await cleanupExpired(expired);

        // Mettre à jour la BDD
        for (const session of expired) {
            await run(
                `UPDATE lab_sessions SET status = 'stopped' WHERE id = ?`,
                [session.id]
            );
        }

        if (destroyed.length > 0) {
            console.log(`[Cleanup] Détruits : ${destroyed.join(', ')}`);
        }

    } catch (err) {
        console.error('[Cleanup] Erreur :', err.message);
    }
}

module.exports = { runCleanup };
