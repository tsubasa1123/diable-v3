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

    </main>

    <footer class="diable-footer">
        <p>Lab DIABLE v3.0 - XXE Lab | Scénario 1: File Read</p>
    </footer>
</body>
</html>
