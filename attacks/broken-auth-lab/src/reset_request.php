<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation mot de passe - VoyagePlus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">Réinitialisation du mot de passe</p>
        </div>
    </header>

    <main class="container">
        <section class="upload-section">
            <h2>🔐 Mot de passe oublié</h2>
            
            <div class="upload-card">
                <form method="POST" class="upload-form">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" name="username" id="username" class="file-input" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        Envoyer le code de réinitialisation
                    </button>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $username = $_POST['username'] ?? '';
                    
                    try {
                        $db = getDB();
                        
                        // Vérifier si l'utilisateur existe
                        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt->execute([$username]);
                        $user = $stmt->fetch();
                        
                        if ($user) {
                            // 🔴 VULNÉRABILITÉ 2: OTP à 4 chiffres (faible)
                            // 0000 à 9999 → 10.000 combinaisons seulement
                            $otp = sprintf("%04d", rand(0, 9999));
                            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                            
                            // Supprimer les anciens tokens
                            $stmt = $db->prepare("DELETE FROM reset_tokens WHERE user_id = ?");
                            $stmt->execute([$user['id']]);
                            
                            // Créer nouveau token
                            $stmt = $db->prepare("INSERT INTO reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                            $stmt->execute([$user['id'], $otp, $expires]);
                            
                            // Simuler l'envoi d'email (afficher le code à l'écran pour la démo)
                            echo '<div class="success-message">';
                            echo '✅ Code envoyé à votre adresse email<br>';
                            echo '<strong style="font-size: 2rem;">' . $otp . '</strong>';
                            echo '<br><a href="reset_verify.php?user=' . $user['id'] . '">Entrer le code</a>';
                            echo '</div>';
                        } else {
                            echo '<div class="error-message">❌ Utilisateur non trouvé</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="error-message">❌ Erreur: ' . $e->getMessage() . '</div>';
                    }
                }
                ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="index.php">Retour à la connexion</a>
                </div>
            </div>
        </section>

        <!-- Section pédagogique -->
        <section class="info-section">
            <details>
                <summary>ℹ️ Scénario 2 - OTP faible (4 chiffres)</summary>
                <div class="info-content">
                    <p><strong>Vulnérabilité:</strong> Code à 4 chiffres (0000-9999) = 10.000 possibilités</p>
                    <p><strong>Exploitation:</strong> Un script peut brute-forcer en quelques minutes</p>
                    <p><strong>Exemple avec ffuf:</strong></p>
                    <pre>ffuf -w <(seq -w 0000 9999) -X POST -d "token=FUZZ" -u http://localhost:8083/reset_verify.php?user=1 -fr "incorrect"</pre>
                </div>
            </details>
        </section>
    </main>

    <footer class="diable-footer">
        <p>Lab DIABLE v3.0 - Broken Authentication Lab | Scénario 2: OTP faible</p>
    </footer>
</body>
</html>
