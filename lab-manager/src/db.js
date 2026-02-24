// src/db.js — Gestion SQLite des sessions de labs
const sqlite3 = require('sqlite3').verbose();
const path    = require('path');

const DB_FILE = path.join(__dirname, '..', 'data', 'sessions.db');

// Créer le dossier data si besoin
const fs = require('fs');
const dataDir = path.dirname(DB_FILE);
if (!fs.existsSync(dataDir)) fs.mkdirSync(dataDir, { recursive: true });

const db = new sqlite3.Database(DB_FILE);

// Promisify pour utiliser async/await
const run  = (sql, params = []) => new Promise((res, rej) => db.run(sql, params, function(err) { err ? rej(err) : res(this); }));
const get  = (sql, params = []) => new Promise((res, rej) => db.get(sql, params, (err, row) => err ? rej(err) : res(row)));
const all  = (sql, params = []) => new Promise((res, rej) => db.all(sql, params, (err, rows) => err ? rej(err) : res(rows)));

async function init() {
    await run(`
        CREATE TABLE IF NOT EXISTS lab_sessions (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id         TEXT NOT NULL,
            lab_id          TEXT NOT NULL,
            container_id    TEXT,
            container_name  TEXT UNIQUE,
            port            INTEGER,
            status          TEXT DEFAULT 'starting',  -- starting | running | stopped
            started_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_active     DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at      DATETIME,
            UNIQUE(user_id, lab_id)
        )
    `);
    console.log('[DB] Base de données initialisée');
}

module.exports = { db, run, get, all, init };
