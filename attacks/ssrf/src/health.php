<?php
header("Content-Type: application/json");
echo json_encode([
  "status" => "healthy",
  "service" => "ssrf-fetch-lab",
  "timestamp" => date("Y-m-d H:i:s"),
  "checks" => [
    "session" => ["status" => "ok"]
  ],
  "hint" => "Try another local endpoint: /private-status.php"
]);
