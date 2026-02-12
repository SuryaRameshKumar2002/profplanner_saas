<?php
require 'config.php';
require_any_role(['super_admin', 'sales_manager', 'werkgever']);
if (!can_manage_sales_users()) {
  header('Location: index.php');
  exit;
}
include 'templates/header.php';

$isSuper = is_super_admin();
$myWerkgeverId = current_werkgever_id();
$selectedWerkgever = $isSuper ? (int)($_GET['werkgever_id'] ?? ($_POST['werkgever_id'] ?? 0)) : (int)$myWerkgeverId;
$error = '';
$success = '';

$werkgevers = [];
if ($isSuper) {
  $werkgevers = $db->query("SELECT u.id, u.naam FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werkgever' AND u.actief=1 ORDER BY u.naam")->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_sales_user'])) {
  $naam = trim($_POST['naam'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefoon = trim($_POST['telefoonnummer'] ?? '');
  $wachtwoord = $_POST['wachtwoord'] ?? '';
  $roleName = $_POST['role_name'] ?? 'sales_agent';
  $targetWerkgever = $isSuper ? $selectedWerkgever : (int)$myWerkgeverId;

  if (!in_array($roleName, ['sales_manager', 'sales_agent'], true)) $roleName = 'sales_agent';

  if ($naam === '' || $email === '' || $wachtwoord === '' || $targetWerkgever <= 0) {
    $error = 'Naam, email, wachtwoord en werkgever zijn verplicht.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Voer een geldig emailadres in.';
  } elseif (strlen($wachtwoord) < 6) {
    $error = 'Wachtwoord moet minimaal 6 tekens bevatten.';
  } else {
    $roleIdStmt = $db->prepare("SELECT id FROM rollen WHERE naam = ? LIMIT 1");
    $roleIdStmt->execute([$roleName]);
    $roleId = (int)$roleIdStmt->fetchColumn();
    if ($roleId <= 0) {
      $error = 'Sales rol niet gevonden in rollen tabel.';
    } else {
      try {
        $ins = $db->prepare("INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer, actief) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $ins->execute([$naam, $email, password_hash($wachtwoord, PASSWORD_DEFAULT), $roleId, $targetWerkgever, ($telefoon !== '' ? $telefoon : null)]);
        $success = 'Sales gebruiker aangemaakt.';
      } catch (Throwable $e) {
        $error = 'Aanmaken mislukt: ' . $e->getMessage();
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_sales_user'])) {
  $id = (int)($_POST['id'] ?? 0);
  $active = (int)($_POST['actief'] ?? 0);
  if ($isSuper) {
    $upd = $db->prepare("UPDATE users u JOIN rollen r ON r.id=u.rol_id SET u.actief=? WHERE u.id=? AND r.naam IN ('sales_manager','sales_agent')");
    $upd->execute([$active, $id]);
  } else {
    $upd = $db->prepare("UPDATE users u JOIN rollen r ON r.id=u.rol_id SET u.actief=? WHERE u.id=? AND u.werkgever_id=? AND r.naam IN ('sales_manager','sales_agent')");
    $upd->execute([$active, $id, $myWerkgeverId]);
  }
  $success = 'Status bijgewerkt.';
}

$whereSql = '';
$params = [];
if ($isSuper && $selectedWerkgever > 0) {
  $whereSql = ' AND u.werkgever_id = ?';
  $params[] = $selectedWerkgever;
} elseif (!$isSuper) {
  $whereSql = ' AND u.werkgever_id = ?';
  $params[] = $myWerkgeverId;
}

$stmt = $db->prepare("SELECT u.id, u.naam, u.email, u.telefoonnummer, u.actief, u.werkgever_id, r.naam AS rol_naam, wg.naam AS werkgever_naam FROM users u JOIN rollen r ON r.id=u.rol_id LEFT JOIN users wg ON wg.id=u.werkgever_id WHERE r.naam IN ('sales_manager','sales_agent') {$whereSql} ORDER BY u.naam");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h2>Sales Gebruikersbeheer</h2>
  <p class="muted">Beheer loginaccounts voor de sales afdeling.</p>
</div>

<?php if ($success): ?><div class="success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

<div class="card">
  <h3>+ Sales gebruiker toevoegen</h3>
  <form method="post">
    <?php if ($isSuper): ?>
      <label>Werkgever *</label>
      <select name="werkgever_id" required>
        <option value="">Kies werkgever</option>
        <?php foreach ($werkgevers as $w): ?>
          <option value="<?= (int)$w['id'] ?>" <?= ((int)$selectedWerkgever === (int)$w['id'] ? 'selected' : '') ?>><?= h($w['naam']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <div class="grid">
      <div><label>Naam *</label><input type="text" name="naam" required></div>
      <div><label>Email *</label><input type="email" name="email" required></div>
      <div><label>Telefoonnummer</label><input type="text" name="telefoonnummer"></div>
    </div>

    <div class="grid">
      <div>
        <label>Rol</label>
        <select name="role_name">
          <option value="sales_agent">Sales medewerker</option>
          <option value="sales_manager">Sales manager</option>
        </select>
      </div>
      <div><label>Tijdelijk wachtwoord *</label><input type="password" name="wachtwoord" minlength="6" required></div>
    </div>

    <button class="btn" type="submit" name="create_sales_user">Aanmaken</button>
  </form>
</div>

<div class="card">
  <h3>Sales accounts (<?= count($users) ?>)</h3>
  <div style="overflow:auto;">
    <table>
      <thead>
      <tr><th>Naam</th><th>Email</th><th>Rol</th><th>Werkgever</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= h($u['naam']) ?></td>
          <td><?= h($u['email']) ?></td>
          <td><?= h($u['rol_naam']) ?></td>
          <td><?= h($u['werkgever_naam'] ?? '-') ?></td>
          <td><?= (int)$u['actief'] ? '<span class="badge green">Actief</span>' : '<span class="badge red">Inactief</span>' ?></td>
          <td style="text-align:right;">
            <form method="post" style="display:inline;">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <?php if ($isSuper && $selectedWerkgever > 0): ?><input type="hidden" name="werkgever_id" value="<?= (int)$selectedWerkgever ?>"><?php endif; ?>
              <input type="hidden" name="actief" value="<?= (int)$u['actief'] ? 0 : 1 ?>">
              <button class="btn ghost" type="submit" name="toggle_sales_user"><?= (int)$u['actief'] ? 'Deactiveer' : 'Activeer' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$users): ?><tr><td colspan="6" class="muted">Nog geen sales accounts.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
