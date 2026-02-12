<?php
require 'config.php';
require_sales_access();
include 'templates/header.php';

$isSuper = is_super_admin();
$myWerkgeverId = current_werkgever_id();
$selectedWerkgever = $isSuper ? (int)($_GET['werkgever_id'] ?? ($_POST['werkgever_id'] ?? 0)) : (int)$myWerkgeverId;
$error = '';
$success = '';

$allowedStatuses = ['GEPLAND', 'BEZOCHT AFGEWEZEN', 'BEZOCHT INTERESSE', 'BEZOCHT VERKOCHT'];

$werkgevers = [];
if ($isSuper) {
  $werkgevers = $db->query("SELECT u.id, u.naam FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werkgever' AND u.actief=1 ORDER BY u.naam")->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_planning'])) {
  $gemeente = trim($_POST['gemeente'] ?? '');
  $straatnaam = trim($_POST['straatnaam'] ?? '');
  $huisnummer = trim($_POST['huisnummer'] ?? '');
  $status = trim($_POST['status'] ?? 'GEPLAND');
  $gepland_op = trim($_POST['gepland_op'] ?? '');
  $notities = trim($_POST['notities'] ?? '');

  if (!in_array($status, $allowedStatuses, true)) $status = 'GEPLAND';

  $targetWerkgever = $isSuper ? $selectedWerkgever : (int)$myWerkgeverId;
  $salesUserId = (int)current_user()['id'];
  if ($isSuper || is_werkgever()) {
    $m = $db->prepare("SELECT u.id FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='sales_manager' AND u.werkgever_id=? AND u.actief=1 ORDER BY u.id LIMIT 1");
    $m->execute([$targetWerkgever]);
    $salesUserId = (int)$m->fetchColumn();
    if ($salesUserId <= 0) $salesUserId = null;
  }

  if ($targetWerkgever <= 0 || $gemeente === '' || $straatnaam === '' || $huisnummer === '') {
    $error = 'Vul werkgever, gemeente, straat en huisnummer in.';
  } else {
    $stmt = $db->prepare("INSERT INTO sales_planning_visits (werkgever_id, sales_user_id, gemeente, straatnaam, huisnummer, status, gepland_op, bezocht_op, notities) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      $targetWerkgever,
      $salesUserId,
      $gemeente,
      $straatnaam,
      $huisnummer,
      $status,
      ($gepland_op !== '' ? $gepland_op : null),
      ($status !== 'GEPLAND' ? date('Y-m-d H:i:s') : null),
      ($notities !== '' ? $notities : null)
    ]);
    $success = 'Planningregel toegevoegd.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_planning_status'])) {
  $id = (int)($_POST['id'] ?? 0);
  $status = trim($_POST['status'] ?? 'GEPLAND');
  if (!in_array($status, $allowedStatuses, true)) $status = 'GEPLAND';

  if ($isSuper) {
    $stmt = $db->prepare("UPDATE sales_planning_visits SET status=?, bezocht_op=? WHERE id=?");
    $stmt->execute([$status, ($status !== 'GEPLAND' ? date('Y-m-d H:i:s') : null), $id]);
  } else {
    $stmt = $db->prepare("UPDATE sales_planning_visits SET status=?, bezocht_op=? WHERE id=? AND werkgever_id=?");
    $stmt->execute([$status, ($status !== 'GEPLAND' ? date('Y-m-d H:i:s') : null), $id, $myWerkgeverId]);
  }
  $success = 'Status bijgewerkt.';
}

$where = [];
$params = [];
if ($isSuper && $selectedWerkgever > 0) {
  $where[] = 'p.werkgever_id = ?';
  $params[] = $selectedWerkgever;
} elseif (!$isSuper) {
  $where[] = 'p.werkgever_id = ?';
  $params[] = $myWerkgeverId;
}
if (is_sales_agent()) {
  $where[] = 'p.sales_user_id = ?';
  $params[] = (int)current_user()['id'];
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("SELECT p.*, w.naam AS werkgever_naam, su.naam AS sales_user_naam FROM sales_planning_visits p LEFT JOIN users w ON w.id=p.werkgever_id LEFT JOIN users su ON su.id=p.sales_user_id {$whereSql} ORDER BY p.gemeente, p.straatnaam, p.huisnummer");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h2>Sales Planning</h2>
  <p class="muted">Gemeente planning met bezoekstatussen.</p>
</div>

<?php if ($success): ?><div class="success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

<div class="card">
  <h3>+ Planningregel toevoegen</h3>
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
      <div><label>Gemeente *</label><input type="text" name="gemeente" required></div>
      <div><label>Straatnaam *</label><input type="text" name="straatnaam" required></div>
      <div><label>Huisnummer *</label><input type="text" name="huisnummer" required></div>
    </div>

    <div class="grid">
      <div>
        <label>Status</label>
        <select name="status">
          <?php foreach ($allowedStatuses as $s): ?>
            <option value="<?= h($s) ?>"><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Gepland op</label>
        <input type="date" name="gepland_op">
      </div>
    </div>

    <label>Notities</label>
    <textarea name="notities"></textarea>

    <button class="btn" type="submit" name="add_planning">Toevoegen</button>
  </form>
</div>

<div class="card">
  <h3>Planning overzicht (<?= count($rows) ?>)</h3>
  <div style="overflow:auto;">
    <table>
      <thead>
      <tr>
        <th>Werkgever</th><th>Sales</th><th>Gemeente</th><th>Straat</th><th>Huisnr</th><th>Status</th><th>Gepland</th><th>Bezocht op</th><th>Notities</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['werkgever_naam'] ?? '-') ?></td>
          <td><?= h($r['sales_user_naam'] ?? '-') ?></td>
          <td><?= h($r['gemeente']) ?></td>
          <td><?= h($r['straatnaam']) ?></td>
          <td><?= h($r['huisnummer']) ?></td>
          <td>
            <form method="post" style="display:flex;gap:8px;align-items:center;">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <?php if ($isSuper && $selectedWerkgever > 0): ?><input type="hidden" name="werkgever_id" value="<?= (int)$selectedWerkgever ?>"><?php endif; ?>
              <select name="status" style="margin:0;min-width:170px;">
                <?php foreach ($allowedStatuses as $s): ?>
                  <option value="<?= h($s) ?>" <?= (($r['status'] ?? '') === $s ? 'selected' : '') ?>><?= h($s) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn ghost" type="submit" name="update_planning_status">Opslaan</button>
            </form>
          </td>
          <td><?= h($r['gepland_op'] ?? '-') ?></td>
          <td><?= h($r['bezocht_op'] ?? '-') ?></td>
          <td><?= h($r['notities'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="9" class="muted">Nog geen planningregels.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
