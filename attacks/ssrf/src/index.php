<?php
require_once "config.php";
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>DIABLE - SSRF Lab</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="card">
    <h1>Lab DIABLE - SSRF</h1>

    <p>
      Endpoint vulnérable :
      <code>fetch.php?url=...</code>
      récupère une URL côté serveur sans filtre.
    </p>

    <p>
      <b>Objectif :</b> comprendre qu’une URL fournie par l’utilisateur peut forcer
      le serveur à accéder à une ressource locale non prévue.
    </p>

    <form method="GET" action="fetch.php">
      <label>URL à récupérer</label>
      <input name="url" placeholder="Entrez une URL, par ex. http://example.com" />
      <button class="btn" type="submit">Fetch</button>
    </form>

    <hr>

    <p><b>Types d’URL à tester :</b></p>
    <ul>
      <li><code>http://example.com</code> : ressource externe publique</li>
      <li><code>http://localhost/health.php</code> : ressource locale de découverte</li>
      <li><code>http://127.0.0.1/health.php</code> : variante locale équivalente</li>
    </ul>

    <p>
      <b>Pourquoi ?</b> L’objectif est de comparer une récupération normale d’URL externe
      avec un accès à une ressource locale, puis de comprendre qu’une autre ressource locale
      plus sensible peut aussi être atteinte.
    </p>

    <a class="btn" href="reset.php">Reset</a>
  </div>
</body>
</html>
