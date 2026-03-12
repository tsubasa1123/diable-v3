<?php
require_once "config.php";

$url = trim($_GET["url"] ?? "");
if ($url === "") {
  http_response_code(400);
  echo "Missing url";
  exit;
}

/*
  SSRF volontaire :
  - aucune validation d'URL
  - l'utilisateur contrôle la destination
  - le serveur effectue lui-même la requête
*/
$cmd = "curl -sS -D - --max-time 3 " . escapeshellarg($url) . " 2>&1";
$out = shell_exec($cmd);
$response = $out ?: "No response (curl returned empty output)";

$is_health_discovery = ($url === SSRF_HEALTH_1 || $url === SSRF_HEALTH_2);
$is_final_success = ($url === SSRF_FINAL_1 || $url === SSRF_FINAL_2);

if ($is_final_success) {
  $_SESSION["flag_ssrf_level1"] = FLAG_SSRF_LEVEL1;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>DIABLE - SSRF Result</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="card">
    <h1>Résultat SSRF</h1>

    <p>
      <b>URL demandée :</b>
      <code><?php echo htmlspecialchars($url, ENT_QUOTES, "UTF-8"); ?></code>
    </p>

    <p><b>Réponse récupérée par le serveur :</b></p>
    <pre class="result-box"><?php echo htmlspecialchars($response, ENT_QUOTES, "UTF-8"); ?></pre>

    <?php if ($is_health_discovery): ?>
      <div class="hint-box">
        <b>Indice :</b> la ressource locale a bien été atteinte. Cherche maintenant une autre
        ressource interne plus sensible.
      </div>
    <?php endif; ?>

    <div class="actions">
      <a class="btn" href="index.php">Back</a>
      <a class="btn" href="reset.php">Reset</a>
    </div>
  </div>

  <?php if ($is_final_success): ?>
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
        <h2 style="margin:0 0 12px 0; font-size:30px; color:#111111;">
          Vulnerability Exploited!
        </h2>

        <p style="margin:0 0 18px 0; color:#111111; font-size:16px; font-weight:500;">
          The server was forced to access a more sensitive internal resource through SSRF.
        </p>

        <p style="margin:18px 0 22px 0; color:#111111; font-weight:800; font-size:22px; word-break:break-word;">
          <?php echo htmlspecialchars($_SESSION["flag_ssrf_level1"], ENT_QUOTES, "UTF-8"); ?>
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
