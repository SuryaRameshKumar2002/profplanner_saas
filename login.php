<?php
require 'config.php';

$rol = $_GET['rol'] ?? '';
if (!in_array($rol, ['super_admin','werkgever','werknemer','sales'], true)) {
    header("Location: index.php");
    exit;
}

include 'templates/header.php';
?>

<div class="card">
  <h2>Login — <?= h(ucfirst($rol)) ?></h2>

  <form method="post">
    <label>Email</label>
    <input type="email" name="email" required>

    <label>Wachtwoord</label>
    <input type="password" name="wachtwoord" required>

    <button class="btn" name="login" type="submit">Inloggen</button>
  </form>
</div>

<?php
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = $_POST['wachtwoord'];

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
      password_verify($pass, $user['wachtwoord']) &&
      ($isExactRoleMatch || $isSalesRoleMatch)
    ) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user; // bevat 'rol'
        if ($rol === 'super_admin') {
          header("Location: super_admin.php");
        } elseif ($rol === 'sales') {
          header("Location: sales_dashboard.php");
        } else {
          header("Location: {$rol}.php");
        }
        exit;
    } else {
        echo "<div class='error'>Ongeldige email, wachtwoord of verkeerde rol.</div>";
    }
}

include 'templates/footer.php';
