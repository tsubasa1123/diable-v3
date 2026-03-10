<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=UTF-8');

try {
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    if (file_exists($dbPath)) {
        unlink($dbPath);
    }

    $db = getDb();

    $db->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL,
        email TEXT NOT NULL,
        bio TEXT NOT NULL
    )');

    $db->exec("INSERT INTO users (username, password, role, email, bio) VALUES
        ('admin', 'T0pSecret!2026', 'Administrateur', 'admin@diable.local', 'Compte privilégié du portail interne.'),
        ('user', 'password', 'Utilisateur', 'user@diable.local', 'Compte de démonstration pour les étudiants.'),
        ('analyst', 'letmein', 'Analyste', 'analyst@diable.local', 'Profil ayant accès aux rapports SQL.'),
        ('guest', 'guest123', 'Invité', 'guest@diable.local', 'Compte peu privilégié.')");

    $db->exec('CREATE TABLE audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        level TEXT NOT NULL,
        message TEXT NOT NULL
    )');

    $db->exec("INSERT INTO audit_logs (level, message) VALUES
        ('INFO', 'Login page initialized'),
        ('WARN', 'Legacy authentication still enabled'),
        ('INFO', 'Search endpoint ready')");

    $db->exec('CREATE TABLE secret_notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner TEXT NOT NULL,
        title TEXT NOT NULL,
        secret TEXT NOT NULL
    )');

    $db->exec("INSERT INTO secret_notes (owner, title, secret) VALUES
        ('admin', 'vpn', 'FLAG{union_leaks_data}'),
        ('analyst', 'db', 'Rapport mensuel archivé'),
        ('user', 'todo', 'Changer mot de passe du compte de démo')");

    $db->exec('CREATE TABLE recovery_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner TEXT NOT NULL,
        token TEXT NOT NULL
    )');

    $db->exec("INSERT INTO recovery_tokens (owner, token) VALUES
        ('admin', 'A7X-PRIVATE-2026'),
        ('user', 'USER-RESET-OK'),
        ('analyst', 'ANL-TOKEN-42')");

    http_response_code(200);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Reset</title><link rel="stylesheet" href="style.css"></head><body>';
    echo '<div class="page"><div class="card"><h1>Reset effectué</h1><p>La base SQLite a été réinitialisée avec succès.</p><p><a class="button" href="index.php">Retour au lab</a></p></div></div>';
    echo '</body></html>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Erreur reset</title><link rel="stylesheet" href="style.css"></head><body>';
    echo '<div class="page"><div class="card"><h1>Erreur</h1><p>Impossible de réinitialiser la base.</p>';
    if ($debugMode) {
        echo '<pre>' . h($e->getMessage()) . '</pre>';
    }
    echo '</div></div></body></html>';
}
