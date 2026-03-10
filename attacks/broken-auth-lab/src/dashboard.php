<?php
require_once 'config.php';
requireLogin();

// 🔥 FORCER l'utilisation des cookies (VULNÉRABILITÉ)
if (isset($_COOKIE['role'])) {
    $_SESSION['role'] = $_COOKIE['role'];
}
if (isset($_COOKIE['username'])) {
    $_SESSION['username'] = $_COOKIE['username'];
}
if (isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
}

$role = $_SESSION['role'] ?? 'user';
$username = $_SESSION['username'] ?? 'Utilisateur';
$user_id = $_SESSION['user_id'] ?? '?';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord - VoyagePlus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">Bienvenue, <?php echo htmlspecialchars($username); ?></p>
        </div>
    </header>

    <main class="container">
        <div class="upload-section">
            <h2>📊 Tableau de bord</h2>
            <div class="upload-card">
                <p>Connecté en tant que: <strong><?php echo $role; ?></strong></p>
                <p>ID Utilisateur: <?php echo $user_id; ?></p>
                
                <?php if ($role === 'admin'): ?>
                <div style="margin-top: 20px; padding: 15px; background: rgba(212, 20, 20, 0.1); border-radius: 5px;">
                    <h3>👑 Panneau Admin</h3>
                    <a href="admin.php" class="btn-primary" style="display: inline-block;">Accéder à l'administration</a>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 30px;">
                    <a href="logout.php" style="color: var(--error-color);">Déconnexion</a>
                </div>
            </div>
        </div>

        <!-- Section test cookie - SÉPARÉE et à la fin -->
        <div style="margin-top: 30px; padding: 20px; background: rgba(0, 194, 255, 0.1); border-radius: 5px; border: 1px solid var(--glow-color);">
            <h3>🍪 Zone de test - Manipulation de cookies</h3>
            <p><strong>Cookies actuels :</strong></p>
            <pre style="background: var(--bg-global); padding: 10px; border-radius: 4px;"><?php print_r($_COOKIE); ?></pre>
            
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <a href="cookie_test.php" class="btn-primary" style="background: var(--link-color); text-decoration: none;">🔧 Outil de test avancé</a>
                <button onclick="makeAdmin()" style="background: transparent; border: 1px solid var(--glow-color); color: var(--glow-color); padding: 10px 20px; border-radius: 4px; cursor: pointer;">⚡ Devenir admin (JavaScript)</button>
            </div>
        </div>

        <script>
        function makeAdmin() {
            document.cookie = "role=admin; path=/";
            alert("Cookie modifié ! Rafraîchis la page (F5) pour voir le changement.");
            location.reload();
        }
        </script>
    </main>
</body>
</html>