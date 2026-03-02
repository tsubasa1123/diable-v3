<?php
require_once 'config.php';

$user_id = $_GET['user'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id > 0) {
    $new_password = $_POST['password'] ?? '';
    
    try {
        $db = getDB();
        
        // 🔴 Changer le mot de passe sans vérification supplémentaire
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_password, $user_id]);
        
        echo '<div class="success-message">✅ Mot de passe modifié ! <a href="index.php">Se connecter</a></div>';
    } catch (Exception $e) {
        echo '<div class="error-message">❌ Erreur: ' . $e->getMessage() . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau mot de passe - VoyagePlus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">Nouveau mot de passe</p>
        </div>
    </header>

    <main class="container">
        <section class="upload-section">
            <h2>🔐 Choisir un nouveau mot de passe</h2>
            
            <div class="upload-card">
                <form method="POST" class="upload-form">
                    <div class="form-group">
                        <label for="password">Nouveau mot de passe</label>
                        <input type="password" name="password" id="password" class="file-input" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Changer le mot de passe</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
