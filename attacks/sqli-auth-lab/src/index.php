<?php
require_once __DIR__ . '/config.php';

if (!file_exists($dbPath)) {
    require __DIR__ . '/reset.php';
    exit;
}

$activeLevel = $_GET['level'] ?? 'easy';
$allowedLevels = ['easy', 'medium', 'hard'];
if (!in_array($activeLevel, $allowedLevels, true)) {
    $activeLevel = 'easy';
}

$easyMessage = null;
$easyType = 'warning';
$easySql = '';

$mediumMessage = null;
$mediumType = 'warning';
$mediumSql = '';
$mediumResults = [];

$hardMessage = null;
$hardType = 'warning';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'easy_login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $easySql = "SELECT id, username, role, email FROM users WHERE username = '" . $username . "' AND password = '" . $password . "'";

        try {
            $db = getDb();
            $result = $db->query($easySql);
            $user = $result->fetch();

            if ($user) {
                $easyType = 'success';
                $easyMessage = "Connexion réussie : bienvenue <strong>" . h($user['username']) . "</strong> (" . h($user['role']) . ").";
            } else {
                $easyType = 'error';
                $easyMessage = 'Échec de connexion : identifiants invalides.';
            }
        } catch (Throwable $e) {
            $easyType = 'error';
            $easyMessage = 'Erreur SQL : ' . h($e->getMessage());
        }
    }

    if ($action === 'medium_search') {
        $department = $_POST['department'] ?? '';

        $mediumSql = "SELECT id, username, role, email FROM users WHERE role LIKE '%" . $department . "%' ORDER BY id ASC";

        try {
            $db = getDb();
            $result = $db->query($mediumSql);
            $mediumResults = $result->fetchAll();
            $mediumType = 'success';
            $mediumMessage = count($mediumResults) . ' ligne(s) retournée(s).';
        } catch (Throwable $e) {
            $mediumType = 'error';
            $mediumMessage = 'Erreur SQL : ' . h($e->getMessage());
        }
    }

    if ($action === 'hard_token') {
        $token = $_POST['token'] ?? '';
        $hardSql = "SELECT id FROM recovery_tokens WHERE owner = 'admin' AND token = '" . $token . "'";

        try {
            $db = getDb();
            $result = $db->query($hardSql);
            $row = $result->fetch();

            if ($row) {
                $hardType = 'success';
                $hardMessage = 'Jeton valide. Le contrôle d’accès considère la condition comme vraie.';
            } else {
                $hardType = 'error';
                $hardMessage = 'Jeton invalide.';
            }
        } catch (Throwable $e) {
            $hardType = 'error';
            $hardMessage = 'Jeton invalide.';
            if ($debugMode) {
                $hardMessage .= ' Détail : ' . h($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab DIABLE - Injection SQL</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="header">
    <h1>Lab DIABLE - Injection SQL</h1>
    <p>Trois niveaux de difficulté dans un même conteneur Docker : facile, moyen et difficile.</p>
</header>

<main class="page">
    <div class="notice warning">
        <strong>Usage local uniquement.</strong> Ce lab est volontairement vulnérable et réservé à un cadre pédagogique contrôlé.
    </div>

    <nav class="level-nav card">
        <a class="level-pill <?= $activeLevel === 'easy' ? 'active' : '' ?>" href="?level=easy">Niveau 1 · Facile</a>
        <a class="level-pill <?= $activeLevel === 'medium' ? 'active' : '' ?>" href="?level=medium">Niveau 2 · Moyen</a>
        <a class="level-pill <?= $activeLevel === 'hard' ? 'active' : '' ?>" href="?level=hard">Niveau 3 · Difficile</a>
    </nav>

    <?php if ($activeLevel === 'easy'): ?>
        <section class="card">
            <h2>Niveau 1 - Bypass d’authentification</h2>
            <p class="small">Le formulaire concatène directement <code>username</code> et <code>password</code> dans la clause <code>WHERE</code>. La requête exécutée est affichée pour faciliter l’apprentissage.</p>
            <form method="post" action="?level=easy">
                <input type="hidden" name="action" value="easy_login">
                <label for="easy-username">Username</label>
                <input id="easy-username" name="username" type="text" placeholder="admin">

                <label for="easy-password">Password</label>
                <input id="easy-password" name="password" type="password" placeholder="••••••••">

                <button type="submit">Tester le login</button>
            </form>

            <?php if ($easyMessage): ?>
                <div class="notice <?= h($easyType) ?>"><?= $easyMessage ?></div>
            <?php endif; ?>

            <h3>Requête exécutée</h3>
            <div class="code-block"><?= $easySql ? h($easySql) : "SELECT id, username, role, email FROM users WHERE username = '[input]' AND password = '[input]'" ?></div>

            <div class="hint-box">
                <strong>But pédagogique :</strong> comprendre un bypass simple avec commentaire SQL et condition toujours vraie.
            </div>
        </section>
    <?php endif; ?>

    <?php if ($activeLevel === 'medium'): ?>
        <section class="card">
            <h2>Niveau 2 - Extraction avec UNION SELECT</h2>
            <p class="small">Le filtre par rôle est injecté dans une requête <code>LIKE</code>. Le résultat est affiché dans un tableau à 4 colonnes, ce qui permet de tester des charges <code>UNION SELECT</code>.</p>
            <form method="post" action="?level=medium">
                <input type="hidden" name="action" value="medium_search">
                <label for="medium-department">Rôle / département</label>
                <input id="medium-department" name="department" type="text" placeholder="Utilisateur">
                <button type="submit">Lancer la recherche</button>
            </form>

            <?php if ($mediumMessage): ?>
                <div class="notice <?= h($mediumType) ?>"><?= $mediumMessage ?></div>
            <?php endif; ?>

            <h3>Requête exécutée</h3>
            <div class="code-block"><?= $mediumSql ? h($mediumSql) : "SELECT id, username, role, email FROM users WHERE role LIKE '%[input]%' ORDER BY id ASC" ?></div>

            <?php if ($mediumMessage): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Rôle</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mediumResults as $row): ?>
                            <tr>
                                <td><?= h((string)($row['id'] ?? '')) ?></td>
                                <td><?= h((string)($row['username'] ?? '')) ?></td>
                                <td><?= h((string)($row['role'] ?? '')) ?></td>
                                <td><?= h((string)($row['email'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$mediumResults): ?>
                            <tr><td colspan="4">Aucun résultat.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="hint-box">
                <strong>But pédagogique :</strong> identifier le bon nombre de colonnes puis extraire des données depuis une autre table.
            </div>
        </section>
    <?php endif; ?>

    <?php if ($activeLevel === 'hard'): ?>
        <section class="card">
            <h2>Niveau 3 - Injection booléenne quasi aveugle</h2>
            <p class="small">L’application vérifie un jeton de récupération pour <code>admin</code>. La requête n’est pas affichée, les erreurs ne sont pas révélées, et la réponse se limite à <em>valide</em> ou <em>invalide</em>.</p>
            <form method="post" action="?level=hard">
                <input type="hidden" name="action" value="hard_token">
                <label for="hard-token">Jeton admin</label>
                <input id="hard-token" name="token" type="text" placeholder="Entrer un jeton">
                <button type="submit">Vérifier le jeton</button>
            </form>

            <?php if ($hardMessage): ?>
                <div class="notice <?= h($hardType) ?>"><?= $hardMessage ?></div>
            <?php endif; ?>

            <div class="hint-box">
                <strong>But pédagogique :</strong> utiliser des conditions booléennes SQLite comme <code>substr()</code> pour inférer un secret caractère par caractère.
            </div>
        </section>
    <?php endif; ?>

    <section class="card" style="margin-top:24px;">
        <h2>Aide rapide</h2>
        <p>Comptes légitimes connus : <code>user / password</code> et <code>analyst / letmein</code>.</p>
        <p>Endpoints utiles :</p>
        <ul>
            <li><a href="reset.php">/reset.php</a> pour réinitialiser la base</li>
            <li><a href="health.php">/health.php</a> pour vérifier l’état du service</li>
        </ul>
    </section>
</main>
</body>
</html>
