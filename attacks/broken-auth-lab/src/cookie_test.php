<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Cookie Manipulation - VoyagePlus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="diable-header">
        <div class="header-content">
            <h1>✈️ VoyagePlus</h1>
            <p class="subtitle">🔧 Outil de test - Cookie Manipulation</p>
        </div>
    </header>

    <main class="container">
        <section class="upload-section">
            <h2>🍪 Manipulation des cookies</h2>
            
            <div class="upload-card">
                <h3>Cookies actuels :</h3>
                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <th>Cookie</th>
                        <th>Valeur</th>
                    </tr>
                    <?php foreach ($_COOKIE as $name => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($name); ?></td>
                        <td><strong><?php echo htmlspecialchars($value); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($_COOKIE)): ?>
                    <tr><td colspan="2">Aucun cookie trouvé</td></tr>
                    <?php endif; ?>
                </table>

                <h3>Manipulation manuelle :</h3>
                <p>Ouvrez les outils développeur (F12) → Application → Cookies pour modifier :</p>
                <pre style="background: var(--bg-global); padding: 10px;">
1. Connectez-vous en tant que 'alice'
2. Allez dans l'onglet "Application" (Chrome) ou "Stockage" (Firefox)
3. Trouvez le cookie 'role' avec la valeur 'user'
4. Double-cliquez et changez 'user' en 'admin'
5. Rafraîchissez la page
6. Vous devriez voir le panneau admin !</pre>

                <h3>Ou via JavaScript :</h3>
                <pre style="background: var(--bg-global); padding: 10px;">
// Dans la console (F12 → Console)
document.cookie = "role=admin; path=/";
location.reload();</pre>

                <div style="margin-top: 20px; text-align: center;">
                    <a href="dashboard.php" class="btn-primary">Retour au tableau de bord</a>
                </div>
            </div>
        </section>

        <section class="info-section">
            <details open>
                <summary>ℹ️ Scénario 3 - Cookie Manipulation</summary>
                <div class="info-content">
                    <p><strong>Vulnérabilité:</strong> Le rôle utilisateur est stocké dans un cookie non signé</p>
                    <p><strong>Exploitation:</strong> Modifier le cookie 'role' de 'user' à 'admin'</p>
                    <p><strong>Impact:</strong> Élévation de privilèges sans mot de passe</p>
                    <p><strong>Code vulnérable:</strong></p>
                    <pre>setcookie('role', $user['role'], time() + 3600, '/');</pre>
                    <p><strong>Solution:</strong> Ne jamais stocker d'informations sensibles dans les cookies, utiliser des sessions sécurisées</p>
                </div>
            </details>
        </section>
    </main>

    <footer class="diable-footer">
        <p>Lab DIABLE v3.0 - Broken Authentication Lab | Scénario 3: Cookie Manipulation</p>
    </footer>
</body>
</html>
