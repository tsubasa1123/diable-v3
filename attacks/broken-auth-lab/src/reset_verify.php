<?php
require_once 'config.php';

$user_id = $_GET['user'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification code - VoyagePlus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">Vérification du code</p>
        </div>
    </header>

    <main class="container">
        <section class="upload-section">
            <h2>🔐 Entrez le code reçu</h2>
            
            <div class="upload-card">
                <form method="POST" class="upload-form">
                    <div class="form-group">
                        <label for="token">Code à 4 chiffres</label>
                        <input type="text" name="token" id="token" class="file-input" maxlength="4" pattern="[0-9]{4}" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Vérifier</button>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id > 0) {
                    $token = $_POST['token'] ?? '';
                    
                    try {
                        $db = getDB();
                        
                        // Vérifier le token
                        $stmt = $db->prepare("
                            SELECT * FROM reset_tokens 
                            WHERE user_id = ? AND token = ? AND expires_at > datetime('now') AND used = 0
                        ");
                        $stmt->execute([$user_id, $token]);
                        $valid = $stmt->fetch();
                        
                        if ($valid) {
                            // Marquer comme utilisé
                            $stmt = $db->prepare("UPDATE reset_tokens SET used = 1 WHERE id = ?");
                            $stmt->execute([$valid['id']]);
                            
                            // 🔴 RÉUSSITE - Rediriger vers changement de mot de passe
                            echo '<div class="success-message">';
                            echo '✅ Code valide ! Redirection vers le changement de mot de passe...';
                            echo '<br><a href="reset_password.php?user=' . $user_id . '">Changer le mot de passe</a>';
                            echo '</div>';
                        } else {
                            echo '<div class="error-message">❌ Code invalide ou expiré</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="error-message">❌ Erreur: ' . $e->getMessage() . '</div>';
                    }
                }
                ?>
            </div>
        </section>
    </main>
</body>
</html>
