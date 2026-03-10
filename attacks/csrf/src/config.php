<?php
session_start();

define("APP_NAME", "csrf-transfer-lab");

define("DEFAULT_USER", "user");
define("DEFAULT_PASS", "password");

if (!isset($_SESSION["balance"])) {
  $_SESSION["balance"] = 1000;
}

define("FLAG_CSRF_LEVEL2", "flag{csrf_level2_static_token_bypass}");
define("CTF_TARGET_USER", "attacker");
define("CTF_MIN_AMOUNT", 200);

define("STATIC_CSRF_TOKEN", "STATIC_TOKEN_123");
