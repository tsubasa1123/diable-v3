-- Création de la base de données SQLite pour Broken Authentication Lab

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insertion des utilisateurs (mots de passe en clair volontairement pour la démo)
INSERT OR IGNORE INTO users (username, email, password, role) VALUES 
    ('admin', 'admin@voyageplus.com', 'admin123', 'admin'),
    ('alice', 'alice@example.com', 'password123', 'user'),
    ('bob', 'bob@example.com', 'bobpass', 'user'),
    ('charlie', 'charlie@example.com', 'charlie2024', 'user'),
    ('secretary', 'secretary@voyageplus.com', 'welcome2024', 'user'),
    ('webmaster', 'web@voyageplus.com', 'web123', 'user');

-- Table pour les tokens de réinitialisation (scénario 3)
CREATE TABLE IF NOT EXISTS reset_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    used INTEGER DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table pour les tentatives de connexion (pour simulation brute-force)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    username TEXT,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    success INTEGER DEFAULT 0
);

-- Vue pour les statistiques (optionnel)
CREATE VIEW IF NOT EXISTS user_stats AS
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role;
