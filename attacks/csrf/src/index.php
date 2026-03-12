<?php
require_once "config.php";

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    $_SESSION["csrf_token"] = STATIC_CSRF_TOKEN;
}

$csrf = $_SESSION["csrf_token"] ?? "";
$showFlagModal = isset($_SESSION["flag_csrf_level2"]);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>DIABLE - CSRF Lab</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="card">
    <h1>Lab DIABLE - CSRF</h1>
    <p>
      Objectif : montrer qu’un token CSRF statique et prévisible peut être réutilisé
      par un attaquant pour forger une requête.
    </p>

    <?php if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true): ?>
      <p><b>Tu n’es pas connecté.</b></p>
      <a class="btn" href="login.php">Se connecter</a>
    <?php else: ?>
      <p>
        <b>Connecté</b> en tant que user — Solde:
        <b><?php echo (int) $_SESSION["balance"]; ?>€</b>
      </p>

      <form method="POST" action="transfer.php">
        <input
          type="hidden"
          name="csrf_token"
          value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, "UTF-8"); ?>"
        />

        <label>Destinataire</label>
        <input name="to" value="attacker" />

        <label>Montant</label>
        <input name="amount" value="100" />

        <button class="btn" type="submit">Faire un virement</button>
      </form>

      <hr>

      <p>
        <b>Scénario CSRF :</b> inspecter le formulaire, observer que le token CSRF
        est statique, puis le réutiliser dans <code>attacker.html</code> pour lancer l’attaque.
      </p>

      <div class="actions">
        <a class="btn" href="attacker.html" target="_blank">Open attacker page</a>
        <a class="btn" href="reset.php">Reset</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($showFlagModal): ?>
    <div
      id="flagModal"
      style="
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.28);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
      "
    >
      <div
        style="
          width: min(420px, 90vw);
          background: #ff3b30;
          color: #111111;
          border-radius: 18px;
          padding: 28px 24px;
          text-align: center;
          box-shadow: 0 20px 55px rgba(0,0,0,0.35);
          border: 2px solid rgba(0,0,0,0.12);
        "
      >
        <h2
          style="
            margin: 0 0 12px 0;
            font-size: 30px;
            line-height: 1.2;
            color: #111111;
          "
        >
          Vulnerability Exploited!
        </h2>

        <p
          style="
            margin: 0 0 18px 0;
            color: #111111;
            font-size: 16px;
            font-weight: 500;
          "
        >
          The static CSRF token was reused successfully.
        </p>

        <p
          style="
            margin: 18px 0 22px 0;
            color: #111111;
            font-weight: 800;
            font-size: 22px;
            word-break: break-word;
          "
        >
          <?php echo htmlspecialchars($_SESSION["flag_csrf_level2"], ENT_QUOTES, "UTF-8"); ?>
        </p>

        <button
          type="button"
          onclick="document.getElementById('flagModal').style.display='none';"
          style="
            background: #111111;
            color: #ffffff;
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
          "
        >
          Close
        </button>
      </div>
    </div>
  <?php endif; ?>
</body>
</html>
