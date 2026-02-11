<?php
require 'config.php';
require_any_role(['super_admin', 'werkgever']);
include 'templates/header.php';

$user = current_user();
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();
$error = '';
$success = '';

$werkgevers = [];
if ($isSuper) {
  $werkgevers = $db->query("SELECT u.id, u.naam FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werkgever' AND u.actief=1 ORDER BY u.naam")->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lead'])) {
  $titel = trim($_POST['titel'] ?? '');
  $contact = trim($_POST['contact_persoon'] ?? '');
  $email = trim($_POST['contact_email'] ?? '');
  $tel = trim($_POST['contact_telefoon'] ?? '');
  $datum = trim($_POST['gewenste_datum'] ?? '');
  $notities = trim($_POST['notities'] ?? '');
  $selectedWerkgever = (int)($_POST['werkgever_id'] ?? 0);

  $targetWerkgever = $isSuper ? ($selectedWerkgever ?: 0) : ($werkgeverId ?: 0);

  if ($titel === '' || $targetWerkgever <= 0) {
    $error = 'Titel en werkgever zijn verplicht.';
  } else {
    $stmt = $db->prepare("INSERT INTO sales_leads (werkgever_id, titel, contact_persoon, contact_email, contact_telefoon, gewenste_datum, notities, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'nieuw')");
    $stmt->execute([$targetWerkgever, $titel, ($contact ?: null), ($email ?: null), ($tel ?: null), ($datum ?: null), ($notities ?: null)]);
    $success = 'Lead toegevoegd.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $leadId = (int)($_POST['lead_id'] ?? 0);
  $status = trim($_POST['status'] ?? 'nieuw');
  $allowed = ['nieuw','in_behandeling','bevestigd','verloren'];
  if (!in_array($status, $allowed, true)) {
    $status = 'nieuw';
  }

  $where = $isSuper ? "id = ?" : "id = ? AND werkgever_id = ?";
  $params = $isSuper ? [$status, $leadId] : [$status, $leadId, $werkgeverId];
  $stmt = $db->prepare("UPDATE sales_leads SET status = ? WHERE {$where}");
  $stmt->execute($params);
  $success = 'Leadstatus bijgewerkt.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_to_job'])) {
  $leadId = (int)($_POST['lead_id'] ?? 0);

  $leadWhere = $isSuper ? "sl.id = ?" : "sl.id = ? AND sl.werkgever_id = ?";
  $leadParams = $isSuper ? [$leadId] : [$leadId, $werkgeverId];

  $stmt = $db->prepare("SELECT sl.* FROM sales_leads sl WHERE {$leadWhere} LIMIT 1");
  $stmt->execute($leadParams);
  $lead = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$lead) {
    $error = 'Lead niet gevonden.';
  } else {
    $targetWg = (int)$lead['werkgever_id'];
    if ($targetWg <= 0) {
      $error = 'Lead heeft geen werkgever context.';
    } else {
      $opdrachtgeverId = (int)($lead['opdrachtgever_id'] ?? 0);
      if ($opdrachtgeverId <= 0) {
        $name = trim((string)($lead['contact_persoon'] ?: 'Lead Opdrachtgever'));
        $ins = $db->prepare("INSERT INTO opdrachtgevers (werkgever_id, naam, email, telefoonnummer) VALUES (?, ?, ?, ?)");
        $ins->execute([$targetWg, $name, ($lead['contact_email'] ?: null), ($lead['contact_telefoon'] ?: null)]);
        $opdrachtgeverId = (int)$db->lastInsertId();
      }

      $busStmt = $db->prepare("SELECT id FROM buses WHERE werkgever_id = ? AND actief = 1 ORDER BY id LIMIT 1");
      $busStmt->execute([$targetWg]);
      $busId = (int)$busStmt->fetchColumn();

      $werknemerStmt = $db->prepare("SELECT u.id FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werknemer' AND u.werkgever_id=? AND u.actief=1 ORDER BY u.id LIMIT 1");
      $werknemerStmt->execute([$targetWg]);
      $werknemerId = (int)$werknemerStmt->fetchColumn();

      $rooster = $db->prepare("INSERT INTO roosters (datum, tijd, starttijd, eindtijd, titel, locatie, omschrijving, werknemer_id, opdrachtgever_id, werkgever_id, bus_id, status) VALUES (?, '08:00:00', '08:00:00', '16:00:00', ?, '', ?, ?, ?, ?, ?, 'gepland')");
      $rooster->execute([
        ($lead['gewenste_datum'] ?: date('Y-m-d')),
        'Sales bevestigd: ' . $lead['titel'],
        (string)($lead['notities'] ?? ''),
        ($werknemerId ?: null),
        ($opdrachtgeverId ?: null),
        $targetWg,
        ($busId ?: null)
      ]);
      $roosterId = (int)$db->lastInsertId();

      $upd = $db->prepare("UPDATE sales_leads SET status='bevestigd', bevestigd_rooster_id=? WHERE id=?");
      $upd->execute([$roosterId, $leadId]);

      $success = 'Lead bevestigd en rooster aangemaakt.';
    }
  }
}

if ($isSuper) {
  $stmt = $db->query("SELECT sl.*, w.naam AS werkgever_naam FROM sales_leads sl LEFT JOIN users w ON w.id = sl.werkgever_id ORDER BY sl.gemaakt_op DESC");
} else {
  $stmt = $db->prepare("SELECT sl.*, w.naam AS werkgever_naam FROM sales_leads sl LEFT JOIN users w ON w.id = sl.werkgever_id WHERE sl.werkgever_id = ? ORDER BY sl.gemaakt_op DESC");
  $stmt->execute([$werkgeverId]);
}
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h2>CRM / Sales Portal</h2>
  <p class="muted">Beheer leads en stuur bevestigde sales door naar planning (roosters).</p>
</div>

<?php if ($success): ?><div class="success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

<div class="card">
  <h3>+ Nieuwe Lead</h3>
  <form method="post">
    <?php if ($isSuper): ?>
      <label>Werkgever *</label>
      <select name="werkgever_id" required>
        <option value="">Kies werkgever</option>
        <?php foreach ($werkgevers as $w): ?>
          <option value="<?= (int)$w['id'] ?>"><?= h($w['naam']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <label>Titel *</label>
    <input type="text" name="titel" required>

    <div class="grid">
      <div>
        <label>Contactpersoon</label>
        <input type="text" name="contact_persoon">
      </div>
      <div>
        <label>Contact email</label>
        <input type="email" name="contact_email">
      </div>
      <div>
        <label>Contact telefoon</label>
        <input type="text" name="contact_telefoon">
      </div>
    </div>

    <label>Gewenste datum</label>
    <input type="date" name="gewenste_datum">

    <label>Notities</label>
    <textarea name="notities"></textarea>

    <button class="btn" type="submit" name="add_lead">Lead opslaan</button>
  </form>
</div>

<div class="card">
  <h3>Leads (<?= count($leads) ?>)</h3>
  <div style="overflow:auto;">
    <table>
      <thead>
        <tr>
          <th>Werkgever</th><th>Titel</th><th>Contact</th><th>Datum</th><th>Status</th><th>Rooster</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($leads as $l): ?>
        <tr>
          <td><?= h($l['werkgever_naam'] ?? '-') ?></td>
          <td><?= h($l['titel']) ?></td>
          <td><?= h($l['contact_persoon'] ?? '-') ?></td>
          <td><?= h($l['gewenste_datum'] ?? '-') ?></td>
          <td>
            <form method="post" style="display:flex;gap:8px;align-items:center;">
              <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
              <select name="status" style="margin:0;min-width:130px;">
                <option value="nieuw" <?= ($l['status']==='nieuw' ? 'selected' : '') ?>>Nieuw</option>
                <option value="in_behandeling" <?= ($l['status']==='in_behandeling' ? 'selected' : '') ?>>In behandeling</option>
                <option value="bevestigd" <?= ($l['status']==='bevestigd' ? 'selected' : '') ?>>Bevestigd</option>
                <option value="verloren" <?= ($l['status']==='verloren' ? 'selected' : '') ?>>Verloren</option>
              </select>
              <button class="btn ghost" name="update_status" type="submit">Opslaan</button>
            </form>
          </td>
          <td>
            <?php if (!empty($l['bevestigd_rooster_id'])): ?>
              <a href="rooster_detail.php?id=<?= (int)$l['bevestigd_rooster_id'] ?>">#<?= (int)$l['bevestigd_rooster_id'] ?></a>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
          <td style="text-align:right;">
            <?php if (($l['status'] ?? '') !== 'bevestigd'): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
                <button class="btn" type="submit" name="confirm_to_job">Bevestig → Job</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$leads): ?>
        <tr><td colspan="7" class="muted">Nog geen leads.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
