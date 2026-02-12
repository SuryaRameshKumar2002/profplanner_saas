<?php
require 'config.php';
require_sales_access();
include 'templates/header.php';

$isSuper = is_super_admin();
$myWerkgeverId = current_werkgever_id();
$selectedWerkgever = $isSuper ? (int)($_GET['werkgever_id'] ?? 0) : (int)$myWerkgeverId;

$werkgevers = [];
if ($isSuper) {
  $werkgevers = $db->query("SELECT u.id, u.naam FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werkgever' AND u.actief=1 ORDER BY u.naam")->fetchAll(PDO::FETCH_ASSOC);
}

$whereSql = '';
$params = [];
if ($selectedWerkgever > 0) {
  $whereSql = ' WHERE werkgever_id = ?';
  $params[] = $selectedWerkgever;
}

$leadStmt = $db->prepare("SELECT COUNT(*) FROM sales_leads" . $whereSql);
$leadStmt->execute($params);
$leadCount = (int)$leadStmt->fetchColumn();

$apptStmt = $db->prepare("SELECT COUNT(*) FROM sales_appointments" . $whereSql);
$apptStmt->execute($params);
$apptCount = (int)$apptStmt->fetchColumn();

$planStmt = $db->prepare("SELECT COUNT(*) FROM sales_planning_visits" . $whereSql);
$planStmt->execute($params);
$planCount = (int)$planStmt->fetchColumn();

$todayStmt = $db->prepare("SELECT COUNT(*) FROM sales_appointments" . ($selectedWerkgever > 0 ? " WHERE werkgever_id = ? AND DATE(afspraak_datum)=CURDATE()" : " WHERE DATE(afspraak_datum)=CURDATE()"));
$todayStmt->execute($selectedWerkgever > 0 ? [$selectedWerkgever] : []);
$todayCount = (int)$todayStmt->fetchColumn();
?>

<div class="card" style="background:linear-gradient(135deg,#f3f4f6 0%,#ffffff 100%);border:none;">
  <h2>Sales Dashboard</h2>
  <p class="muted">Offerteprogramma: leads, agenda, planning en sales teambeheer.</p>
</div>

<?php if ($isSuper): ?>
<div class="card">
  <form method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
    <div style="min-width:260px;">
      <label>Werkgever filter (Super Admin)</label>
      <select name="werkgever_id">
        <option value="0">Alle werkgevers</option>
        <?php foreach ($werkgevers as $w): ?>
          <option value="<?= (int)$w['id'] ?>" <?= ((int)$selectedWerkgever === (int)$w['id'] ? 'selected' : '') ?>><?= h($w['naam']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Filter</button>
  </form>
</div>
<?php endif; ?>

<div class="grid">
  <div class="card">
    <h3>Leads</h3>
    <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $leadCount ?></div>
    <p class="muted">Leads-opslag met volledige klantgegevens.</p>
    <a class="btn" href="sales_portal.php<?= $selectedWerkgever > 0 ? ('?werkgever_id=' . (int)$selectedWerkgever) : '' ?>">Open Leads</a>
  </div>

  <div class="card">
    <h3>Agenda</h3>
    <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $apptCount ?></div>
    <p class="muted">Afspraken en adviesgesprekken.</p>
    <a class="btn" href="sales_agenda.php<?= $selectedWerkgever > 0 ? ('?werkgever_id=' . (int)$selectedWerkgever) : '' ?>">Open Agenda</a>
  </div>

  <div class="card">
    <h3>Vandaag</h3>
    <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $todayCount ?></div>
    <p class="muted">Geplande afspraken op vandaag.</p>
    <a class="btn ghost" href="sales_agenda.php<?= $selectedWerkgever > 0 ? ('?werkgever_id=' . (int)$selectedWerkgever) : '' ?>">Bekijk</a>
  </div>

  <div class="card">
    <h3>Planning</h3>
    <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $planCount ?></div>
    <p class="muted">Bezoekstatus per gemeente/straat/huisnummer.</p>
    <a class="btn" href="sales_planning.php<?= $selectedWerkgever > 0 ? ('?werkgever_id=' . (int)$selectedWerkgever) : '' ?>">Open Planning</a>
  </div>

  <?php if (can_manage_sales_users()): ?>
  <div class="card">
    <h3>Sales Gebruikers</h3>
    <p class="muted">Beheer sales manager/medewerker accounts.</p>
    <a class="btn ghost" href="sales_users_management.php<?= $selectedWerkgever > 0 ? ('?werkgever_id=' . (int)$selectedWerkgever) : '' ?>">Beheer users</a>
  </div>
  <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
