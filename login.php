<?php
require 'config.php';

$rol = $_GET['rol'] ?? '';
if (!in_array($rol, ['werkgever','werknemer'], true)) {
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

    if ($user && password_verify($pass, $user['wachtwoord']) && $user['rol'] === $rol) {
        $_SESSION['user'] = $user; // bevat 'rol'
        header("Location: {$rol}.php");
        exit;
    } else {
        echo "<div class='error'>Ongeldige email, wachtwoord of verkeerde rol.</div>";
    }
}

include 'templates/footer.php';
