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
        ('analyst', 'letmein', 'Analyste', 'analyst@diable.local', 'Profil ayant accès aux rapports SQL.')");

    $db->exec('CREATE TABLE audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        level TEXT NOT NULL,
        message TEXT NOT NULL
    )');

    $db->exec("INSERT INTO audit_logs (level, message) VALUES
        ('INFO', 'Login page initialized'),
        ('WARN', 'Legacy authentication still enabled'),
        ('INFO', 'Search endpoint ready')");

    $db->exec('CREATE TABLE secrets (
        id INTEGER PRIMARY KEY,
        username TEXT NOT NULL,
        role TEXT NOT NULL,
        email TEXT NOT NULL,
        bio TEXT NOT NULL
    )');

    $db->exec("INSERT INTO secrets (id, username, role, email, bio) VALUES
        (9001, 'flag_medium', 'Classifie', 'flag2@diable.local', 'Niveau moyen validé : la table cachee a été exfiltrée.')");

    $db->exec('CREATE TABLE challenge_secrets (
        name TEXT PRIMARY KEY,
        secret TEXT NOT NULL
    )');

    $db->exec("INSERT INTO challenge_secrets (name, secret) VALUES
        ('hard_secret', 'p@ssage-obscur')");

    setcookie('lab_progress', '', time() - 3600, '/');
    unset($_COOKIE['lab_progress']);

    http_response_code(200);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Reset</title><link rel="stylesheet" href="style.css"></head><body>';
    echo '<div class="page"><div class="card"><h1>Reset effectué</h1><p>La base SQLite et la progression ont été réinitialisées avec succès.</p><p><a class="button" href="index.php">Retour au lab</a></p></div></div>';
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
