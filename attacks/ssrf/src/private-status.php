<?php
header("Content-Type: application/json");
echo json_encode([
  "status" => "private",
  "service" => "internal-status",
  "message" => "Sensitive internal resource reached through localhost",
  "access" => "restricted",
  "flag_ready" => true
]);
