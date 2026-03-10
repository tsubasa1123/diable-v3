<?php
require_once "config.php";

$_SESSION = [];
session_destroy();

header("Content-Type: application/json");
echo json_encode([
  "status" => "ok",
  "message" => "Reset done. Session cleared.",
  "timestamp" => date("Y-m-d H:i:s")
]);
