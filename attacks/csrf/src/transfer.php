<?php require_once "config.php";

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
  http_response_code(401);
  echo "Not logged in";
  exit;
}

$to = trim($_POST["to"] ?? "unknown");
$amount_raw = $_POST["amount"] ?? 0;

if (!is_numeric($amount_raw)) {
  http_response_code(400);
  echo "Invalid amount";
  exit;
}

$amount = (int)$amount_raw;
if ($amount <= 0) {
  http_response_code(400);
  echo "Invalid amount";
  exit;
}

/*
  Protection faible :
  - token CSRF statique
  - valeur prévisible
  - réutilisable par un attaquant
*/
$token = (string)($_POST["csrf_token"] ?? "");
$expected = (string)($_SESSION["csrf_token"] ?? "");

if ($expected === "" || $token === "" || !hash_equals($expected, $token)) {
  http_response_code(403);
  echo "CSRF token invalid";
  exit;
}

$_SESSION["balance"] = max(0, (int)$_SESSION["balance"] - $amount);

if ($to === CTF_TARGET_USER && $amount >= CTF_MIN_AMOUNT) {
  $_SESSION["flag_csrf_level2"] = FLAG_CSRF_LEVEL2;
}

header("Location: index.php");
exit;
