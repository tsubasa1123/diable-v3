<?php
session_start();

define("APP_NAME", "ssrf");

define("FLAG_SSRF_LEVEL1", "flag{ssrf_private_status_access}");

define("SSRF_HEALTH_1", "http://localhost/health.php");
define("SSRF_HEALTH_2", "http://127.0.0.1/health.php");

define("SSRF_FINAL_1", "http://localhost/private-status.php");
define("SSRF_FINAL_2", "http://127.0.0.1/private-status.php");
