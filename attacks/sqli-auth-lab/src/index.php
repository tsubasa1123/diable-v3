<?php
require_once __DIR__ . '/config.php';

if (!file_exists($dbPath)) {
    require __DIR__ . '/reset.php';
    exit;
}

$progress = getProgress();
$availableLevels = ['easy', 'medium', 'hard'];
if (allLevelsCompleted()) {
    $availableLevels[] = 'flag';
}
$currentLevel = $_GET['level'] ?? 'easy';
if (!in_array($currentLevel, $availableLevels, true)) {
    $currentLevel = 'easy';
}

$easyMessage = null;
$easyType = 'warning';
$mediumMessage = null;
$mediumType = 'warning';
$hardMessage = null;
$hardType = 'warning';
$searchResults = [];
$searchError = null;
$queryPreview = '';
$sqlLogin = '';
$sqlSearch = '';
$sqlHard = '';
$blindProbe = '';
$secretSubmission = '';

function suspiciousInput(string $value): bool {
    return preg_match("/(--|\/\*|\*\/|'|\"|\bOR\b|\bUNION\b)/i", $value) === 1;
}

function progressPercent(array $progress): int {
    return (int) round((array_sum(array_map(fn($v) => $v ? 1 : 0, $progress)) / 3) * 100);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $sqlLogin = "SELECT id, username, role, email FROM users WHERE username = '" . $username . "' AND password = '" . $password . "'";

    try {
        $db = getDb();
        $result = $db->query($sqlLogin);
        $user = $result->fetch();

        if ($user) {
            $easyType = 'success';
            $easyMessage = "Authentification réussie : bienvenue <strong>" . h($user['username']) . "</strong> (" . h($user['role']) . ").";

            $legitPairs = [
                'admin' => 'T0pSecret!2026',
                'user' => 'password',
                'analyst' => 'letmein',
            ];

            $usedBypass = suspiciousInput($username) || suspiciousInput($password) || (($legitPairs[$user['username']] ?? null) !== $password);
            if ($usedBypass) {
                completeLevel('easy');
                $progress = getProgress();
                $easyMessage .= ' <br><strong>Niveau easy validé.</strong>';
            }
        } else {
            $easyType = 'error';
            $easyMessage = 'Échec de connexion : identifiants invalides.';
        }
    } catch (Throwable $e) {
        $easyType = 'error';
        $easyMessage = 'Erreur SQL : ' . h($e->getMessage());
    }
}

if (isset($_GET['q'])) {
    $q = $_GET['q'];
    $queryPreview = $q;
    $sqlSearch = "SELECT id, username, role, email, bio FROM users WHERE username LIKE '%" . $q . "%' OR role LIKE '%" . $q . "%'";

    try {
        $db = getDb();
        $result = $db->query($sqlSearch);
        $searchResults = $result->fetchAll();

        $knownPasswords = [
            'admin' => 'T0pSecret!2026',
            'user' => 'password',
            'analyst' => 'letmein',
        ];
        $unionDetected = stripos($q, 'union') !== false;
        $validated = false;

        foreach ($searchResults as $row) {
            if (($row['email'] ?? '') === 'flag2@diable.local') {
                $validated = true;
                $mediumMessage = 'Table cachée trouvée : niveau medium validé.';
                break;
            }
            $u = $row['username'] ?? '';
            $role = $row['role'] ?? '';
            $email = $row['email'] ?? '';
            $bio = $row['bio'] ?? '';
            if ($unionDetected && isset($knownPasswords[$u]) && $role === $knownPasswords[$u] && $email === $knownPasswords[$u] && $bio === $knownPasswords[$u]) {
                $validated = true;
                $mediumMessage = 'Extraction via UNION détectée : niveau medium validé.';
                break;
            }
        }

        if ($validated) {
            completeLevel('medium');
            $progress = getProgress();
            $mediumType = 'success';
        }
    } catch (Throwable $e) {
        $searchError = $e->getMessage();
    }
}

if (isset($_GET['probe'])) {
    $blindProbe = $_GET['probe'];
    $sqlHard = "SELECT id FROM audit_logs WHERE id = 1 AND message = '" . $blindProbe . "'";

    try {
        $db = getDb();
        $result = $db->query($sqlHard);
        $hardRow = $result->fetch();
        if ($hardRow) {
            $hardType = 'success';
            $hardMessage = 'Condition vraie : la requête a retourné au moins une ligne.';
        } else {
            $hardType = 'warning';
            $hardMessage = 'Condition fausse : aucune ligne retournée.';
        }
    } catch (Throwable $e) {
        $hardType = 'error';
        $hardMessage = 'Erreur SQL : ' . h($e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'validate_hard') {
    $secretSubmission = trim($_POST['secret_value'] ?? '');

    try {
        $db = getDb();
        $expected = $db->query("SELECT secret FROM challenge_secrets WHERE name = 'hard_secret'")->fetchColumn();
        if (hash_equals((string)$expected, $secretSubmission)) {
            completeLevel('hard');
            $progress = getProgress();
            $hardType = 'success';
            $hardMessage = 'Secret correct : niveau hard validé.';
        } else {
            $hardType = 'error';
            $hardMessage = 'Secret incorrect.';
        }
    } catch (Throwable $e) {
        $hardType = 'error';
        $hardMessage = 'Erreur SQL : ' . h($e->getMessage());
    }
}

$progress = getProgress();
$flagVisible = allLevelsCompleted();
$progressCount = array_sum(array_map(fn($v) => $v ? 1 : 0, $progress));
$progressPercent = progressPercent($progress);
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
<div class="bg-noise"></div>

<main class="page">
    <section class="topbar">
        <div class="topbar-inner">
            <div class="topbar-content">
                <h1>Lab DIABLE - Injection SQL</h1>
                <p>3 niveaux, indices intégrés, déblocage du flag final</p>
            </div>
            <div class="top-actions">
                <a class="btn secondary" href="?level=easy">Revenir au début</a>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="tabs">
            <a class="tab <?= $currentLevel === 'easy' ? 'active' : '' ?>" href="?level=easy">Easy <?= $progress['easy'] ? '✓' : '' ?></a>
            <a class="tab <?= $currentLevel === 'medium' ? 'active' : '' ?>" href="?level=medium">Medium <?= $progress['medium'] ? '✓' : '' ?></a>
            <a class="tab <?= $currentLevel === 'hard' ? 'active' : '' ?>" href="?level=hard">Hard <?= $progress['hard'] ? '✓' : '' ?></a>
            <?php if ($flagVisible): ?>
                <a class="tab flag-tab <?= $currentLevel === 'flag' ? 'active' : '' ?>" href="?level=flag">Flag final 🏁</a>
            <?php endif; ?>
        </div>

        <div class="progress">
            <strong>Progression <?= $progressCount ?>/3 niveaux complétés</strong>
            <p>Le quatrième onglet apparaît automatiquement uniquement après validation des 3 niveaux.</p>
        </div>
    </section>

    <?php if ($currentLevel === 'easy'): ?>
        <section class="grid">
            <section class="card">
                <h2>Niveau easy - Bypass de connexion</h2>
                <p class="small">Objectif : obtenir une authentification sans utiliser un couple d'identifiants légitime.</p>

                <form method="post" action="?level=easy">
                    <input type="hidden" name="action" value="login">

                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" placeholder="admin">

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="••••••••">

                    <button type="submit">Se connecter</button>
                </form>

                <?php if ($easyMessage): ?>
                    <div class="notice <?= h($easyType) ?>"><?= $easyMessage ?></div>
                <?php endif; ?>

                <h3>Requête exécutée</h3>
                <div class="query-block">
                    <div class="query-header">
                        <span class="query-dot red"></span>
                        <span class="query-dot yellow"></span>
                        <span class="query-dot green"></span>
                        <span class="query-title">SQL Console</span>
                    </div>
                    <pre><?= $sqlLogin ? h($sqlLogin) : "SELECT id, username, role, email FROM users WHERE username = '[input]' AND password = '[input]'" ?></pre>
                </div>
            </section>

            <aside class="card">
                <h3>Indices</h3>
                <ul class="hints">
                    <li class="hint-item">
                        <span class="hint-number">1</span>
                        <div class="hint-content">
                            <strong>Regarde la clause WHERE</strong>
                            <span>Le formulaire concatène directement les deux champs dans une même requête SQL.</span>
                        </div>
                    </li>
                    <li class="hint-item">
                        <span class="hint-number">2</span>
                        <div class="hint-content">
                            <strong>Pense logique booléenne</strong>
                            <span>Si une condition devient toujours vraie, le mot de passe n'a plus vraiment d'importance.</span>
                        </div>
                    </li>
                    <li class="hint-item">
                        <span class="hint-number">3</span>
                        <div class="hint-content">
                            <strong>Un commentaire peut aider</strong>
                            <span>La fin de la requête peut parfois être neutralisée pour ignorer une vérification.</span>
                        </div>
                    </li>
                </ul>
            </aside>
        </section>
    <?php elseif ($currentLevel === 'medium'): ?>
        <section class="grid">
            <section class="card">
                <h2>Niveau medium - Extraction par UNION</h2>
                <p class="small">Objectif : exfiltrer des données supplémentaires dans la même table de résultats.</p>

                <form method="get" action="index.php">
                    <input type="hidden" name="level" value="medium">

                    <label for="q">Recherche</label>
                    <input id="q" name="q" type="text" placeholder="user">

                    <button type="submit">Rechercher</button>
                </form>

                <h3>Requête exécutée</h3>
                <div class="query-block">
                    <div class="query-header">
                        <span class="query-dot red"></span>
                        <span class="query-dot yellow"></span>
                        <span class="query-dot green"></span>
                        <span class="query-title">SQL Console</span>
                    </div>
                    <pre><?= $sqlSearch ? h($sqlSearch) : "SELECT id, username, role, email, bio FROM users WHERE username LIKE '%[q]%' OR role LIKE '%[q]%'" ?></pre>
                </div>

                <?php if ($searchError): ?>
                    <div class="notice error">Erreur SQL : <?= h($searchError) ?></div>
                <?php elseif (isset($_GET['q'])): ?>
                    <div class="notice <?= h($mediumType) ?>">
                        <?= $mediumMessage ?: ('Résultats pour <code>' . h($queryPreview) . '</code> : ' . count($searchResults) . ' ligne(s).') ?>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Rôle</th>
                                    <th>Email</th>
                                    <th>Bio</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($searchResults as $row): ?>
                                <tr>
                                    <td><?= h((string)($row['id'] ?? '')) ?></td>
                                    <td><?= h($row['username'] ?? '') ?></td>
                                    <td><?= h($row['role'] ?? '') ?></td>
                                    <td><?= h($row['email'] ?? '') ?></td>
                                    <td><?= h($row['bio'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$searchResults): ?>
                                <tr><td colspan="5">Aucun résultat.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="card">
                <h3>Indices</h3>
                <ul class="hints">
                    <li class="hint-item">
                        <span class="hint-number">1</span>
                        <div class="hint-content">
                            <strong>Le SELECT principal renvoie 5 colonnes</strong>
                            <span>Un UNION valide doit renvoyer exactement le même nombre de colonnes.</span>
                        </div>
                    </li>
                    <li class="hint-item">
                        <span class="hint-number">2</span>
                        <div class="hint-content">
                            <strong>Réutilise l'affichage existant</strong>
                            <span>Tu peux faire apparaître d'autres données dans les colonnes rôle, email et bio si leurs types sont compatibles.</span>
                        </div>
                    </li>
                    <li class="hint-item">
                        <span class="hint-number">3</span>
                        <div class="hint-content">
                            <strong>La validation accepte deux chemins</strong>
                            <span>Soit une table cachée est exfiltrée, soit les mots de passe des comptes présents à l'écran sont révélés via UNION.</span>
                        </div>
                    </li>
                </ul>
            </aside>
        </section>
    <?php elseif ($currentLevel === 'hard'): ?>
        <section class="grid">
            <section class="card">
                <h2>Niveau hard - Blind SQLi booléenne</h2>
                <p class="small">Objectif : déduire un secret sans l'afficher directement, uniquement via des réponses vraies ou fausses.</p>

                <form method="get" action="index.php">
                    <input type="hidden" name="level" value="hard">

                    <label for="probe">Valeur du champ message</label>
                    <input id="probe" name="probe" type="text" placeholder="test">

                    <button type="submit">Tester</button>
                </form>

                <h3>Requête exécutée</h3>
                <div class="query-block">
                    <div class="query-header">
                        <span class="query-dot red"></span>
                        <span class="query-dot yellow"></span>
                        <span class="query-dot green"></span>
                        <span class="query-title">SQL Console</span>
                    </div>
                    <pre><?= $sqlHard ? h($sqlHard) : "SELECT id FROM audit_logs WHERE id = 1 AND message = '[probe]'" ?></pre>
                </div>

                <?php if ($hardMessage && !isset($_POST['action'])): ?>
                    <div class="notice <?= h($hardType) ?>"><?= $hardMessage ?></div>
                <?php endif; ?>

                <h3>Indices</h3>
                <ul class="hints">
                    <li class="hint-item">
                        <span class="hint-number">1</span>
                        <div class="hint-content">
                            <strong>Observe le vrai/faux</strong>
                            <span>Quand la condition passe, l'application le dit clairement. C'est une oracle booléenne.</span>
                        </div>
                    </li>
                    <li class="hint-item">
                        <span class="hint-number">2</span>
                        <div class="hint-content">
                            <strong>Teste par morceaux</strong>
                            <span>Longueur, premier caractère, puis sous-chaînes progressives sont souvent plus simples qu'une extraction directe.</span>
                        </div>
                    </li>
                    <li class="hint-item">
                        <span class="hint-number">3</span>
                        <div class="hint-content">
                            <strong>Le secret final est stocké ailleurs</strong>
                            <span>La table audit_logs ne contient pas le secret, elle ne sert qu'à porter la condition injectée.</span>
                        </div>
                    </li>
                </ul>
            </section>

            <section class="card">
                <h2>Validation du niveau hard</h2>
                <p class="small">Quand tu as déduit le secret, saisis-le ici pour valider le niveau.</p>

                <form method="post" action="?level=hard">
                    <input type="hidden" name="action" value="validate_hard">

                    <label for="secret_value">Secret déduit</label>
                    <input id="secret_value" name="secret_value" type="text" placeholder="secret">

                    <button type="submit">Valider</button>
                </form>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'validate_hard'): ?>
                    <div class="notice <?= h($hardType) ?>"><?= $hardMessage ?></div>
                <?php endif; ?>

                <h3>Conseils</h3>
                <ul class="hints">
                    <li class="hint-item">
                        <span class="hint-number">•</span>
                        <div class="hint-content">
                            <strong>Étape 1</strong>
                            <span>Commence par vérifier qu'une table cible existe.</span>
                        </div>
                    </li>
                    <li class="hint-item">
                        <span class="hint-number">•</span>
                        <div class="hint-content">
                            <strong>Étape 2</strong>
                            <span>Ensuite, teste la longueur du secret.</span>
                        </div>
                    </li>
                    <li class="hint-item">
                        <span class="hint-number">•</span>
                        <div class="hint-content">
                            <strong>Étape 3</strong>
                            <span>Termine caractère par caractère avec <code>substr</code>.</span>
                        </div>
                    </li>
                </ul>
            </section>
        </section>
    <?php elseif ($currentLevel === 'flag' && $flagVisible): ?>
        <section class="card flag-card">
            <h2>Bravo, les 3 niveaux sont validés</h2>
            <p class="small">Le quatrième onglet est maintenant débloqué automatiquement.</p>
            <div class="flag-box"><?= h($finalFlag) ?></div>
        </section>
    <?php endif; ?>

    <section class="card info-card">
        <h2>Infos utiles</h2>
        <p class="small">Le lab reste volontairement vulnérable et doit être utilisé uniquement en local. Les endpoints techniques restent présents pour le conteneur, mais ils ne sont plus exposés dans l'interface.</p>
        <p class="footer-note">Comptes visibles pour le test normal : <code>user / password</code> et <code>analyst / letmein</code>.</p>
    </section>
</main>
</body>
</html>