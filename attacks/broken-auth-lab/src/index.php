<?php
// Inclusion de la config TOUT EN HAUT
require_once 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($password === $user['password']) {
                // ✅ Connexion réussie - les cookies sont créés ICI avant tout HTML
                loginWithCookie($user);
                logAttempt($username, true);
                
                $message = "✅ Connexion réussie ! Redirection...";
                $message_type = "success";
                
                // Redirection JavaScript
                echo '<script>setTimeout(() => window.location.href="dashboard.php", 1500);</script>';
            } else {
                logAttempt($username, false);
                $message = "❌ Mot de passe incorrect pour " . htmlspecialchars($username);
                $message_type = "error";
            }
        } else {
            logAttempt($username, false);
            $message = "❌ Utilisateur inconnu: " . htmlspecialchars($username);
            $message_type = "error";
        }
    } catch (Exception $e) {
        $message = "❌ Erreur: " . $e->getMessage();
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoyagePlus - Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header DIABLE -->
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">Espace membre</p>
        </div>
        <div class="header-glow"></div>
    </header>

    <main class="container">
        <!-- Message d'avertissement -->
        <div class="warning-banner">
            <span class="glow-text">⚠️ MAINTENANCE</span> - Amélioration du système d'authentification
        </div>

        <section class="upload-section">
            <h2>🔐 Connexion membre</h2>
            
            <div class="upload-card">
                <form method="POST" class="upload-form">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" name="username" id="username" class="file-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" name="password" id="password" class="file-input" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <span class="btn-glow"></span>
                        Se connecter
                    </button>
                </form>

                <?php if ($message): ?>
                    <div class="<?php echo $message_type; ?>-message"><?php echo $message; ?></div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="reset_request.php" style="color: var(--link-color);">Mot de passe oublié ?</a>
                </div>
            </div>
        </section>

        <!-- Section pédagogique -->
        <section class="info-section">
            <details>
                <summary>ℹ️ À propos de ce lab (info pédagogique)</summary>
                <div class="info-content">
                    <p><strong>Scénario 1 - Énumération d'utilisateurs:</strong></p>
                    <p>Les messages d'erreur sont différents :</p>
                    <ul>
                        <li><span style="color: var(--error-color);">❌ Utilisateur inconnu: X</span> → L'utilisateur X n'existe pas</li>
                        <li><span style="color: var(--error-color);">❌ Mot de passe incorrect pour Y</span> → L'utilisateur Y existe !</li>
                    </ul>
                    <p><strong>Comptes de test:</strong></p>
                    <ul>
                        <li>admin / admin123</li>
                        <li>alice / password123</li>
                        <li>bob / bobpass</li>
                    </ul>
                </div>
            </details>
        </section>
    </main>

    <footer class="diable-footer">
        <p>Lab DIABLE v3.0 - Broken Authentication Lab</p>
        <p class="small">Scénario 1: User Enumeration | Auteur: [Ton Nom]</p>
    </footer>
</body>
</html>