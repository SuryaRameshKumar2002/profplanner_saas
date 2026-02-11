<?php
require 'config.php';
require_super_admin();
include 'templates/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_werkgever'])) {
  $naam = trim($_POST['naam'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefoon = trim($_POST['telefoonnummer'] ?? '');
  $wachtwoord = $_POST['wachtwoord'] ?? '';

  if ($naam === '' || $email === '' || $wachtwoord === '') {
    $error = 'Naam, email en wachtwoord zijn verplicht.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Voer een geldig emailadres in.';
  } elseif (strlen($wachtwoord) < 6) {
    $error = 'Wachtwoord moet minimaal 6 tekens bevatten.';
  } else {
    $rolId = (int)$db->query("SELECT id FROM rollen WHERE naam='werkgever' LIMIT 1")->fetchColumn();
    $stmt = $db->prepare('INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer, actief) VALUES (?, ?, ?, ?, NULL, ?, 1)');
    try {
      $stmt->execute([$naam, $email, password_hash($wachtwoord, PASSWORD_DEFAULT), $rolId, ($telefoon !== '' ? $telefoon : null)]);
      $newId = (int)$db->lastInsertId();
      audit_log($db, 'create_werkgever', 'users', $newId, json_encode(['email' => $email]));
      $success = 'Werkgever account aangemaakt.';
    } catch (Throwable $e) {
      $error = 'Aanmaken mislukt: ' . $e->getMessage();
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_actief'])) {
  $id = (int)($_POST['id'] ?? 0);
  $active = (int)($_POST['actief'] ?? 0);
  $stmt = $db->prepare("UPDATE users u JOIN rollen r ON r.id=u.rol_id SET u.actief=? WHERE u.id=? AND r.naam='werkgever'");
  $stmt->execute([$active, $id]);
  audit_log($db, 'toggle_werkgever_active', 'users', $id, json_encode(['actief' => $active]));
  $success = 'Status bijgewerkt.';
}

$werkgevers = $db->query("
  SELECT u.id, u.naam, u.email, u.telefoonnummer, u.actief, u.gemaakt_op,
    (SELECT COUNT(*) FROM users w JOIN rollen r2 ON r2.id=w.rol_id WHERE r2.naam='werknemer' AND w.werkgever_id=u.id) AS werknemers_count,
    (SELECT COUNT(*) FROM roosters ro WHERE ro.werkgever_id=u.id) AS roosters_count,
    (SELECT COUNT(*) FROM sales_leads sl WHERE sl.werkgever_id=u.id) AS leads_count
  FROM users u
  JOIN rollen r ON r.id=u.rol_id
  WHERE r.naam='werkgever'
  ORDER BY u.naam
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h2>Werkgevers Beheer</h2>
  <p class="muted">Super admin beheert werkgever accounts en tenant toegang.</p>
</div>

<?php if ($success): ?><div class="success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

<div class="card">
  <h3>+ Nieuwe Werkgever</h3>
  <form method="post">
    <label>Naam *</label>
    <input type="text" name="naam" required>

    <label>Email *</label>
    <input type="email" name="email" required>

    <label>Telefoonnummer</label>
    <input type="text" name="telefoonnummer">

    <label>Tijdelijk wachtwoord *</label>
    <input type="password" name="wachtwoord" minlength="6" required>

    <button class="btn" type="submit" name="create_werkgever">Aanmaken</button>
  </form>
</div>

<div class="card">
  <h3>Bestaande Werkgevers (<?= count($werkgevers) ?>)</h3>
  <div style="overflow:auto;margin-top:12px;">
    <table>
      <thead>
        <tr>
          <th>Naam</th><th>Email</th><th>Werknemers</th><th>Roosters</th><th>Leads</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($werkgevers as $w): ?>
        <tr>
          <td><?= h($w['naam']) ?></td>
          <td><?= h($w['email']) ?></td>
          <td><?= (int)$w['werknemers_count'] ?></td>
          <td><?= (int)$w['roosters_count'] ?></td>
          <td><?= (int)$w['leads_count'] ?></td>
          <td><?= (int)$w['actief'] ? '<span class="badge green">Actief</span>' : '<span class="badge red">Inactief</span>' ?></td>
          <td style="text-align:right;">
            <form method="post" style="display:inline;">
              <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
              <input type="hidden" name="actief" value="<?= (int)$w['actief'] ? 0 : 1 ?>">
              <button class="btn ghost" name="toggle_actief" type="submit"><?= (int)$w['actief'] ? 'Deactiveer' : 'Activeer' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
