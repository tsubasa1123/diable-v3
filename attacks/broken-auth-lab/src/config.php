<?php
/**
 * Configuration pour Broken Authentication Lab
 */

// Démarrer la session
session_start();

// Configuration de la base de données
define('DB_PATH', __DIR__ . '/db/database.sqlite');

// Fonction de connexion à la base de données
function getDB() {
    try {
        // Vérifier que le fichier existe
        if (!file_exists(DB_PATH)) {
            error_log("Base de données non trouvée: " . DB_PATH);
            die("Erreur: Base de données non trouvée");
        }
        
        // Vérifier les permissions
        if (!is_readable(DB_PATH)) {
            error_log("Base de données non lisible: " . DB_PATH);
            die("Erreur: Base de données non lisible");
        }
        
        if (!is_writable(dirname(DB_PATH))) {
            error_log("Dossier DB non accessible en écriture: " . dirname(DB_PATH));
            die("Erreur: Dossier DB non accessible en écriture");
        }
        
        // Connexion avec options pour améliorer performance
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec("PRAGMA journal_mode = WAL");  // Améliore performance SQLite
        $db->exec("PRAGMA synchronous = NORMAL"); // Équilibre performance/sécurité
        
        error_log("Connexion DB réussie");
        return $db;
    } catch (PDOException $e) {
        error_log("Erreur DB: " . $e->getMessage());
        die("Erreur de connexion DB: " . $e->getMessage());
    }
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

// Fonction pour logger les tentatives (optionnel)
function logAttempt($username, $success) {
    try {
        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)");
        $stmt->execute([$ip, $username, $success ? 1 : 0]);
    } catch (Exception $e) {
        // Silently fail - ne pas bloquer la connexion
    }
}

// 🔴 VULNÉRABILITÉ 3: Fonctions de cookie (manipulables par le client)
function loginWithCookie($user) {
    // Stocker les infos dans des cookies (FAIBLE SÉCURITÉ !)
    setcookie('user_id', $user['id'], time() + 3600, '/');
    setcookie('username', $user['username'], time() + 3600, '/');
    setcookie('role', $user['role'], time() + 3600, '/');
    
    // Aussi en session pour compatibilité
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
}

function checkCookieLogin() {
    if (isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
        // Restaurer la session depuis les cookies
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        $_SESSION['username'] = $_COOKIE['username'] ?? '';
        $_SESSION['role'] = $_COOKIE['role'] ?? 'user';
        return true;
    }
    return false;
}

function logout() {
    // Détruire session
    session_destroy();
    
    // 🔴 Supprimer les cookies (mais l'utilisateur peut les avoir sauvegardés)
    setcookie('user_id', '', time() - 3600, '/');
    setcookie('username', '', time() - 3600, '/');
    setcookie('role', '', time() - 3600, '/');
}

// Vérifier le login par cookie au début
checkCookieLogin();
