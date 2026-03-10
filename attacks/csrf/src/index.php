<?php require_once "config.php";

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
  $_SESSION["csrf_token"] = STATIC_CSRF_TOKEN;
}

$csrf = $_SESSION["csrf_token"] ?? "";
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>DIABLE - CSRF Lab</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="card">
    <h1>Lab DIABLE - CSRF</h1>
    <p>Objectif : montrer un transfert bancaire déclenchable via un token CSRF statique et prévisible.</p>

    <?php if (!isset($_SESSION["logged_in"])): ?>
      <p><b>Tu n’es pas connecté.</b></p>
      <a class="btn" href="login.php">Se connecter</a>
    <?php else: ?>
      <p><b>Connecté</b> en tant que user — Solde: <b><?php echo (int)$_SESSION["balance"]; ?>€</b></p>

      <?php if (isset($_SESSION["flag_csrf_level2"])): ?>
        <p class="flag"><b>FLAG:</b> <code><?php echo htmlspecialchars($_SESSION["flag_csrf_level2"], ENT_QUOTES, "UTF-8"); ?></code></p>
      <?php endif; ?>

      <form method="POST" action="transfer.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, "UTF-8"); ?>" />

        <label>Destinataire</label>
        <input name="to" value="attacker" />

        <label>Montant</label>
        <input name="amount" value="100" />

        <button class="btn" type="submit">Faire un virement</button>
      </form>

      <hr>
      <p><b>Scénario CSRF :</b> une page externe peut envoyer un POST vers <code>transfer.php</code>.
      Ici, le token est statique et prévisible, donc l’attaque peut réussir.</p>

      <a class="btn" href="reset.php">Reset</a>
    <?php endif; ?>
  </div>
</body>
</html>
