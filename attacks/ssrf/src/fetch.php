<?php
require_once "config.php";

$url = $_GET["url"] ?? "";
if (!$url) {
  http_response_code(400);
  echo "Missing url";
  exit;
}

// SSRF volontaire: aucune validation d'URL
$cmd = "curl -sS -D - --max-time 3 " . escapeshellarg($url) . " 2>&1";
$out = shell_exec($cmd);

header("Content-Type: text/plain; charset=utf-8");
echo $out ?: "No response (curl returned empty output)";
