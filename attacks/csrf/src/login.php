<?php require_once "config.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $u = $_POST["username"] ?? "";
  $p = $_POST["password"] ?? "";
  if ($u === DEFAULT_USER && $p === DEFAULT_PASS) {
    $_SESSION["logged_in"] = true;
    header("Location: index.php");
    exit;
  } else {
    $error = "Identifiants invalides (user/password).";
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8" /><link rel="stylesheet" href="style.css"><title>Login</title></head>
<body>
  <div class="card">
    <h1>Connexion</h1>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="POST">
      <label>Username</label>
      <input name="username" value="user" />
      <label>Password</label>
      <input name="password" value="password" type="password" />
      <button class="btn" type="submit">Login</button>
    </form>
    <p><a href="index.php">Retour</a></p>
  </div>
</body>
</html>
