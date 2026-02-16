<?php
require 'config.php';

$rol = $_GET['rol'] ?? '';
if (!in_array($rol, ['super_admin', 'werkgever', 'werknemer', 'sales'], true)) {
  header("Location: index.php");
  exit;
}

include 'templates/header.php';
?>

<div class="card">
  <h2>Login - <?= h(ucfirst($rol)) ?></h2>

  <form method="post">
    <?= csrf_input() ?>
    <label>Email</label>
    <input type="email" name="email" required>

    <label>Wachtwoord</label>
    <input type="password" name="wachtwoord" required>

    <button class="btn" name="login" type="submit">Inloggen</button>
  </form>
</div>

<?php
if (isset($_POST['login'])) {
  $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
  $pass = (string)($_POST['wachtwoord'] ?? '');
  $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);

  try {
    $db->exec("
      CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        success BOOLEAN NOT NULL DEFAULT FALSE,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_login_attempts_email_ip_time (email, ip, attempted_at),
        INDEX idx_login_attempts_time (attempted_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $db->exec("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");

    $rateStmt = $db->prepare("
      SELECT COUNT(*)
      FROM login_attempts
      WHERE email = ?
        AND ip = ?
        AND success = 0
        AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)
    ");
    $rateStmt->execute([$email, $ip]);
    $failedCount = (int)$rateStmt->fetchColumn();
    if ($failedCount >= 10) {
      echo "<div class='error'>Te veel inlogpogingen. Probeer opnieuw na 15 minuten.</div>";
      include 'templates/footer.php';
      exit;
    }
  } catch (Throwable $e) {
    // Do not block login if attempt logging fails.
  }

  $stmt = $db->prepare("
    SELECT u.*, r.naam AS rol
    FROM users u
    JOIN rollen r ON r.id = u.rol_id
    WHERE u.email = ?
    LIMIT 1
  ");
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  $isSalesRoleMatch = ($rol === 'sales' && in_array(($user['rol'] ?? ''), ['sales_manager', 'sales_agent'], true));
  $isExactRoleMatch = ($rol !== 'sales' && ($user['rol'] ?? '') === $rol);

  if (
    $user &&
    (int)($user['actief'] ?? 1) === 1 &&
    password_verify($pass, (string)$user['wachtwoord']) &&
    ($isExactRoleMatch || $isSalesRoleMatch)
  ) {
    try {
      $insOk = $db->prepare("INSERT INTO login_attempts (email, ip, success) VALUES (?, ?, 1)");
      $insOk->execute([$email, $ip]);
      $clearFail = $db->prepare("DELETE FROM login_attempts WHERE email = ? AND ip = ? AND success = 0");
      $clearFail->execute([$email, $ip]);
    } catch (Throwable $e) {
      // ignore
    }

    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    if ($rol === 'super_admin') {
      header("Location: super_admin.php");
    } elseif ($rol === 'sales') {
      header("Location: sales_dashboard.php");
    } else {
      header("Location: {$rol}.php");
    }
    exit;
  }

  try {
    $insFail = $db->prepare("INSERT INTO login_attempts (email, ip, success) VALUES (?, ?, 0)");
    $insFail->execute([$email, $ip]);
  } catch (Throwable $e) {
    // ignore
  }
  echo "<div class='error'>Ongeldige email, wachtwoord of verkeerde rol.</div>";
}

include 'templates/footer.php';
