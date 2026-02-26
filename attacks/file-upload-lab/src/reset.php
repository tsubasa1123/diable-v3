<?php
/**
 * Reset Endpoint - OBLIGATOIRE pour DIABLE
 * Réinitialise le lab à son état initial
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
    
} catch (Exception $e) {
    $status = 'error';
    $message = "❌ Erreur lors de la réinitialisation : " . $e->getMessage();
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
            
            <a href="index.php" class="btn-primary" style="display: inline-block; text-decoration: none;">
                Retour à l'accueil
            </a>
            
            <p style="margin-top: 20px; color: var(--text-secondary);">
                <small>Le lab est maintenant dans son état initial.</small>
            </p>
        </div>
    </div>
</body>
</html>
