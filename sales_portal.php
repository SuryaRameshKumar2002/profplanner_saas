<?php
require 'config.php';
require_sales_access();
include 'templates/header.php';

$user = current_user();
$isSuper = is_super_admin();
$myWerkgeverId = current_werkgever_id();
$error = '';
$success = '';

$werkgevers = [];
if ($isSuper) {
  $werkgevers = $db->query("SELECT u.id, u.naam FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werkgever' AND u.actief=1 ORDER BY u.naam")->fetchAll(PDO::FETCH_ASSOC);
}

$selectedWerkgever = $isSuper ? (int)($_GET['werkgever_id'] ?? ($_POST['werkgever_id'] ?? 0)) : (int)$myWerkgeverId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lead'])) {
  $titel = trim($_POST['titel'] ?? 'Nieuwe lead');
  $gemeente = trim($_POST['gemeente'] ?? '');
  $straatnaam = trim($_POST['straatnaam'] ?? '');
  $huisnummer = trim($_POST['huisnummer'] ?? '');
  $voornaam = trim($_POST['voornaam'] ?? '');
  $achternaam = trim($_POST['achternaam'] ?? '');
  $telefoonnummer = trim($_POST['telefoonnummer'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $bereikbaar_via = trim($_POST['bereikbaar_via'] ?? '');
  $afspraak_datum = trim($_POST['afspraak_datum'] ?? '');
  $notities = trim($_POST['notities'] ?? '');
  $adviesgesprek_gepland = isset($_POST['adviesgesprek_gepland']) ? 1 : 0;

  $targetWerkgever = $isSuper ? $selectedWerkgever : (int)$myWerkgeverId;

  if ($targetWerkgever <= 0 || $gemeente === '' || $straatnaam === '' || $huisnummer === '' || $voornaam === '' || $achternaam === '') {
    $error = 'Vul werkgever, gemeente, straat, huisnummer, voornaam en achternaam in.';
  } else {
    $salesUserId = (int)$user['id'];
    if ($isSuper || is_werkgever()) {
      $managerStmt = $db->prepare("SELECT u.id FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='sales_manager' AND u.werkgever_id = ? AND u.actief=1 ORDER BY u.id LIMIT 1");
      $managerStmt->execute([$targetWerkgever]);
      $salesUserId = (int)$managerStmt->fetchColumn();
      if ($salesUserId <= 0) {
        $salesUserId = null;
      }
    }

    $stmt = $db->prepare("INSERT INTO sales_leads (werkgever_id, sales_user_id, gemeente, straatnaam, huisnummer, voornaam, achternaam, telefoonnummer, email, bereikbaar_via, afspraak_datum, adviesgesprek_gepland, titel, notities, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'nieuw')");
    $stmt->execute([
      $targetWerkgever,
      $salesUserId,
      $gemeente,
      $straatnaam,
      $huisnummer,
      $voornaam,
      $achternaam,
      ($telefoonnummer !== '' ? $telefoonnummer : null),
      ($email !== '' ? $email : null),
      ($bereikbaar_via !== '' ? $bereikbaar_via : null),
      ($afspraak_datum !== '' ? $afspraak_datum : null),
      $adviesgesprek_gepland,
      $titel,
      ($notities !== '' ? $notities : null)
    ]);
    $leadId = (int)$db->lastInsertId();

    if ($adviesgesprek_gepland && $afspraak_datum !== '') {
      $appt = $db->prepare("INSERT INTO sales_appointments (werkgever_id, sales_user_id, lead_id, gemeente, straatnaam, huisnummer, klant_achternaam, email, telefoonnummer, afspraak_datum, bijzonderheden, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'gepland')");
      $appt->execute([
        $targetWerkgever,
        $salesUserId,
        $leadId,
        $gemeente,
        $straatnaam,
        $huisnummer,
        $achternaam,
        ($email !== '' ? $email : null),
        ($telefoonnummer !== '' ? $telefoonnummer : null),
        $afspraak_datum,
        ($notities !== '' ? $notities : null)
      ]);
    }

    $success = 'Lead toegevoegd' . ($adviesgesprek_gepland ? ' en afspraak ingepland.' : '.');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $leadId = (int)($_POST['lead_id'] ?? 0);
  $status = trim($_POST['status'] ?? 'nieuw');
  $allowed = ['nieuw','in_behandeling','bevestigd','verloren'];
  if (!in_array($status, $allowed, true)) {
    $status = 'nieuw';
  }

  if ($isSuper) {
    $stmt = $db->prepare("UPDATE sales_leads SET status = ? WHERE id = ?");
    $stmt->execute([$status, $leadId]);
  } else {
    $stmt = $db->prepare("UPDATE sales_leads SET status = ? WHERE id = ? AND werkgever_id = ?");
    $stmt->execute([$status, $leadId, $myWerkgeverId]);
  }
  $success = 'Leadstatus bijgewerkt.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_to_job'])) {
  $leadId = (int)($_POST['lead_id'] ?? 0);

  if ($isSuper) {
    $stmt = $db->prepare("SELECT sl.* FROM sales_leads sl WHERE sl.id = ? LIMIT 1");
    $stmt->execute([$leadId]);
  } else {
    $stmt = $db->prepare("SELECT sl.* FROM sales_leads sl WHERE sl.id = ? AND sl.werkgever_id = ? LIMIT 1");
    $stmt->execute([$leadId, $myWerkgeverId]);
  }
  $lead = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$lead) {
    $error = 'Lead niet gevonden.';
  } else {
    $targetWg = (int)$lead['werkgever_id'];

    $name = trim((string)$lead['voornaam'] . ' ' . (string)$lead['achternaam']);
    $adres = trim((string)$lead['straatnaam'] . ' ' . (string)$lead['huisnummer'] . ', ' . (string)$lead['gemeente']);

    $ins = $db->prepare("INSERT INTO opdrachtgevers (werkgever_id, naam, email, telefoonnummer, adres) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([
      $targetWg,
      ($name !== '' ? $name : 'Lead Opdrachtgever'),
      ($lead['email'] ?: null),
      ($lead['telefoonnummer'] ?: null),
      ($adres !== '' ? $adres : null)
    ]);
    $opdrachtgeverId = (int)$db->lastInsertId();

    $busStmt = $db->prepare("SELECT id FROM buses WHERE werkgever_id = ? AND actief = 1 ORDER BY id LIMIT 1");
    $busStmt->execute([$targetWg]);
    $busId = (int)$busStmt->fetchColumn();

    $werknemerStmt = $db->prepare("SELECT u.id FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werknemer' AND u.werkgever_id=? AND u.actief=1 ORDER BY u.id LIMIT 1");
    $werknemerStmt->execute([$targetWg]);
    $werknemerId = (int)$werknemerStmt->fetchColumn();

    $datum = substr((string)($lead['afspraak_datum'] ?? ''), 0, 10);
    if ($datum === '') $datum = date('Y-m-d');

    $rooster = $db->prepare("INSERT INTO roosters (datum, tijd, starttijd, eindtijd, titel, locatie, omschrijving, werknemer_id, opdrachtgever_id, werkgever_id, bus_id, status) VALUES (?, '08:00:00', '08:00:00', '16:00:00', ?, ?, ?, ?, ?, ?, ?, 'gepland')");
    $rooster->execute([
      $datum,
      ('Sales bevestigd: ' . ($lead['titel'] ?? 'Lead')),
      $adres,
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

if ($isSuper) {
  if ($selectedWerkgever > 0) {
    $stmt = $db->prepare("SELECT sl.*, w.naam AS werkgever_naam, su.naam AS sales_user_naam FROM sales_leads sl LEFT JOIN users w ON w.id = sl.werkgever_id LEFT JOIN users su ON su.id = sl.sales_user_id WHERE sl.werkgever_id = ? ORDER BY sl.gemaakt_op DESC");
    $stmt->execute([$selectedWerkgever]);
  } else {
    $stmt = $db->query("SELECT sl.*, w.naam AS werkgever_naam, su.naam AS sales_user_naam FROM sales_leads sl LEFT JOIN users w ON w.id = sl.werkgever_id LEFT JOIN users su ON su.id = sl.sales_user_id ORDER BY sl.gemaakt_op DESC");
  }
} else {
  $stmt = $db->prepare("SELECT sl.*, w.naam AS werkgever_naam, su.naam AS sales_user_naam FROM sales_leads sl LEFT JOIN users w ON w.id = sl.werkgever_id LEFT JOIN users su ON su.id = sl.sales_user_id WHERE sl.werkgever_id = ? ORDER BY sl.gemaakt_op DESC");
  $stmt->execute([$myWerkgeverId]);
}
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h2>Sales Portaal - Leads-opslag</h2>
  <p class="muted">Volledige leadregistratie, adviesgesprekken en doorsturen naar planning.</p>
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
          <option value="<?= (int)$w['id'] ?>" <?= ((int)$selectedWerkgever === (int)$w['id'] ? 'selected' : '') ?>><?= h($w['naam']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <div class="grid">
      <div>
        <label>Gemeente *</label>
        <input type="text" name="gemeente" required>
      </div>
      <div>
        <label>Straatnaam *</label>
        <input type="text" name="straatnaam" required>
      </div>
      <div>
        <label>Huisnummer *</label>
        <input type="text" name="huisnummer" required>
      </div>
    </div>

    <div class="grid">
      <div>
        <label>Voornaam *</label>
        <input type="text" name="voornaam" required>
      </div>
      <div>
        <label>Achternaam *</label>
        <input type="text" name="achternaam" required>
      </div>
      <div>
        <label>Titel lead</label>
        <input type="text" name="titel" placeholder="Bijv. Dakisolatie offerte">
      </div>
    </div>

    <div class="grid">
      <div>
        <label>Telefoonnummer</label>
        <input type="text" name="telefoonnummer">
      </div>
      <div>
        <label>E-mailadres</label>
        <input type="email" name="email">
      </div>
      <div>
        <label>Waar op te bereiken?</label>
        <select name="bereikbaar_via">
          <option value="">Kies optie</option>
          <option value="telefoon">Telefoon</option>
          <option value="email">E-mail</option>
          <option value="whatsapp">WhatsApp</option>
        </select>
      </div>
    </div>

    <div class="grid">
      <div>
        <label>Afspraak datum/tijd (adviesgesprek)</label>
        <input type="datetime-local" name="afspraak_datum">
      </div>
      <div>
        <label style="display:flex;align-items:center;gap:8px;margin-top:28px;">
          <input type="checkbox" name="adviesgesprek_gepland" value="1" style="width:auto;margin:0;"> Direct in agenda zetten
        </label>
      </div>
    </div>

    <label>Bijzonderheden / notities</label>
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
        <th>Werkgever</th><th>Gemeente</th><th>Adres</th><th>Klant</th><th>Contact</th><th>Bereikbaar via</th><th>Afspraak</th><th>Status</th><th>Rooster</th><th></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($leads as $l): ?>
        <tr>
          <td><?= h($l['werkgever_naam'] ?? '-') ?></td>
          <td><?= h($l['gemeente']) ?></td>
          <td><?= h($l['straatnaam'] . ' ' . $l['huisnummer']) ?></td>
          <td><?= h(trim(($l['voornaam'] ?? '') . ' ' . ($l['achternaam'] ?? ''))) ?></td>
          <td><?= h(($l['telefoonnummer'] ?: '-') . ' / ' . ($l['email'] ?: '-')) ?></td>
          <td><?= h($l['bereikbaar_via'] ?? '-') ?></td>
          <td><?= h($l['afspraak_datum'] ?? '-') ?></td>
          <td>
            <form method="post" style="display:flex;gap:8px;align-items:center;">
              <?php if ($isSuper && $selectedWerkgever > 0): ?><input type="hidden" name="werkgever_id" value="<?= (int)$selectedWerkgever ?>"><?php endif; ?>
              <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
              <select name="status" style="margin:0;min-width:130px;">
                <option value="nieuw" <?= (($l['status'] ?? '')==='nieuw' ? 'selected' : '') ?>>Nieuw</option>
                <option value="in_behandeling" <?= (($l['status'] ?? '')==='in_behandeling' ? 'selected' : '') ?>>In behandeling</option>
                <option value="bevestigd" <?= (($l['status'] ?? '')==='bevestigd' ? 'selected' : '') ?>>Bevestigd</option>
                <option value="verloren" <?= (($l['status'] ?? '')==='verloren' ? 'selected' : '') ?>>Verloren</option>
              </select>
              <button class="btn ghost" name="update_status" type="submit">Opslaan</button>
            </form>
          </td>
          <td>
            <?php if (!empty($l['bevestigd_rooster_id'])): ?>
              <a href="rooster_detail.php?id=<?= (int)$l['bevestigd_rooster_id'] ?>">#<?= (int)$l['bevestigd_rooster_id'] ?></a>
            <?php else: ?>-
            <?php endif; ?>
          </td>
          <td style="text-align:right;">
            <?php if (($l['status'] ?? '') !== 'bevestigd'): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
                <button class="btn" type="submit" name="confirm_to_job">Bevestig -> Job</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$leads): ?>
        <tr><td colspan="10" class="muted">Nog geen leads.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
