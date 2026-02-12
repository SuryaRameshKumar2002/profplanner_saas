<?php
require 'config.php';
require_sales_access();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
  $gemeente = trim($_POST['gemeente'] ?? '');
  $straatnaam = trim($_POST['straatnaam'] ?? '');
  $huisnummer = trim($_POST['huisnummer'] ?? '');
  $klant_achternaam = trim($_POST['klant_achternaam'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefoon = trim($_POST['telefoonnummer'] ?? '');
  $afspraak = trim($_POST['afspraak_datum'] ?? '');
  $bijzonderheden = trim($_POST['bijzonderheden'] ?? '');

  $targetWerkgever = $isSuper ? $selectedWerkgever : (int)$myWerkgeverId;
  $salesUserId = (int)current_user()['id'];
  if ($isSuper || is_werkgever()) {
    $m = $db->prepare("SELECT u.id FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='sales_manager' AND u.werkgever_id=? AND u.actief=1 ORDER BY u.id LIMIT 1");
    $m->execute([$targetWerkgever]);
    $salesUserId = (int)$m->fetchColumn();
    if ($salesUserId <= 0) $salesUserId = null;
  }

  if ($targetWerkgever <= 0 || $gemeente === '' || $straatnaam === '' || $huisnummer === '' || $klant_achternaam === '' || $afspraak === '') {
    $error = 'Vul werkgever, gemeente, straat, huisnummer, achternaam en datum/tijd in.';
  } else {
    $stmt = $db->prepare("INSERT INTO sales_appointments (werkgever_id, sales_user_id, gemeente, straatnaam, huisnummer, klant_achternaam, email, telefoonnummer, afspraak_datum, bijzonderheden, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'gepland')");
    $stmt->execute([
      $targetWerkgever,
      $salesUserId,
      $gemeente,
      $straatnaam,
      $huisnummer,
      $klant_achternaam,
      ($email !== '' ? $email : null),
      ($telefoon !== '' ? $telefoon : null),
      $afspraak,
      ($bijzonderheden !== '' ? $bijzonderheden : null)
    ]);
    $success = 'Afspraak ingepland.';
  }
}

$where = [];
$params = [];
if ($isSuper && $selectedWerkgever > 0) {
  $where[] = 'a.werkgever_id = ?';
  $params[] = $selectedWerkgever;
} elseif (!$isSuper) {
  $where[] = 'a.werkgever_id = ?';
  $params[] = $myWerkgeverId;
}
if (is_sales_agent()) {
  $where[] = 'a.sales_user_id = ?';
  $params[] = (int)current_user()['id'];
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("SELECT a.*, w.naam AS werkgever_naam, su.naam AS sales_user_naam FROM sales_appointments a LEFT JOIN users w ON w.id=a.werkgever_id LEFT JOIN users su ON su.id=a.sales_user_id {$whereSql} ORDER BY a.afspraak_datum ASC");
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h2>Sales Agenda</h2>
  <p class="muted">Plan en beheer afspraken voor adviesgesprekken.</p>
</div>

<?php if ($success): ?><div class="success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

<div class="card">
  <h3>+ Afspraak inplannen</h3>
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
      <div><label>Klant achternaam *</label><input type="text" name="klant_achternaam" required></div>
      <div><label>E-mailadres</label><input type="email" name="email"></div>
      <div><label>Telefoonnummer</label><input type="text" name="telefoonnummer"></div>
    </div>

    <label>Datum/tijd afspraak *</label>
    <input type="datetime-local" name="afspraak_datum" required>

    <label>Bijzonderheden</label>
    <textarea name="bijzonderheden"></textarea>

    <button class="btn" type="submit" name="add_appointment">Afspraak opslaan</button>
  </form>
</div>

<div class="card">
  <h3>Geplande afspraken (<?= count($appointments) ?>)</h3>
  <div style="overflow:auto;">
    <table>
      <thead>
        <tr>
          <th>Werkgever</th><th>Sales</th><th>Gemeente</th><th>Adres</th><th>Klant</th><th>Contact</th><th>Datum/tijd</th><th>Status</th><th>Bijzonderheden</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($appointments as $a): ?>
        <tr>
          <td><?= h($a['werkgever_naam'] ?? '-') ?></td>
          <td><?= h($a['sales_user_naam'] ?? '-') ?></td>
          <td><?= h($a['gemeente']) ?></td>
          <td><?= h($a['straatnaam'] . ' ' . $a['huisnummer']) ?></td>
          <td><?= h($a['klant_achternaam']) ?></td>
          <td><?= h(($a['email'] ?: '-') . ' / ' . ($a['telefoonnummer'] ?: '-')) ?></td>
          <td><?= h($a['afspraak_datum']) ?></td>
          <td><?= h($a['status']) ?></td>
          <td><?= h($a['bijzonderheden'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$appointments): ?><tr><td colspan="9" class="muted">Nog geen afspraken.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
