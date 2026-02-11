<?php 
require 'config.php'; 
require_role('werknemer'); 
include 'templates/header.php'; 

// Haal statistieken op
$user = $_SESSION['user'];

// Aantal klussen deze week
$stmt = $db->prepare("
  SELECT COUNT(*) as count FROM roosters 
  WHERE werknemer_id = ? 
  AND DATE(datum) >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
  AND DATE(datum) < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)
");
$stmt->execute([$user['id']]);
$week_jobs = $stmt->fetchColumn();

// Aantal afgeronde klussen
$stmt = $db->prepare("SELECT COUNT(*) as count FROM roosters WHERE werknemer_id = ? AND status = 'afgerond'");
$stmt->execute([$user['id']]);
$completed_jobs = $stmt->fetchColumn();

// Bus informatie
$stmt = $db->prepare("
  SELECT DISTINCT b.* FROM buses b
  JOIN werknemers_buses wb ON wb.bus_id = b.id
  WHERE wb.user_id = ?
");
$stmt->execute([$user['id']]);
$my_buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card" style="background:linear-gradient(135deg,#f3f4f6 0%,#ffffff 100%);border:none;">
  <h2>Welkom, <?= h($user['naam']) ?></h2>
  <p class="muted">Bekijk je rooster, update klussen en rapporteer afwezigheid</p>
</div>

<div class="grid">
  <div class="card">
    <h3>📊 Jouw Statistieken</h3>
    <div style="display:grid;gap:12px;">
      <div style="background:#f3f4f6;padding:12px;border-radius:8px;">
        <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $week_jobs ?></div>
        <div class="muted">Klussen deze week</div>
      </div>
      <div style="background:#f3f4f6;padding:12px;border-radius:8px;">
        <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $completed_jobs ?></div>
        <div class="muted">Afgeronde klussen</div>
      </div>
    </div>
  </div>

  <div class="card">
    <h3>📋 Mijn Rooster</h3>
    <p class="muted">Bekijk je ingeplande klussen en update status.</p>
    <a class="btn" href="roosters.php">Bekijk Rooster</a>
    <a class="btn ghost" href="planner_weekly.php" style="margin-left:8px;">Weekplanning</a>
  </div>

  <div class="card">
    <h3>❌ Afwezigheid melden</h3>
    <p class="muted">Rapporteer afwezigheid. Melding gaat naar werkgever.</p>
    <a class="btn" href="afwezigheid_melden.php">Melden</a>
  </div>

  <?php if (!empty($my_buses)): ?>
  <div class="card">
    <h3>🚌 Mijn Teams</h3>
    <p class="muted">Teams waaraan je bent toegewezen</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
      <?php foreach ($my_buses as $bus): ?>
        <span class="badge" style="background:<?= h($bus['kleur']) ?>;color:#fff;padding:8px 12px;">
          <?= h($bus['naam']) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <h3>📸 Uploads</h3>
    <p class="muted">Upload foto's van klussen.</p>
    <a class="btn" href="upload.php">Upload</a>
  </div>

  <div class="card">
    <h3>👤 Mijn Profiel</h3>
    <p class="muted">Bekijk je profiel en contactinformatie.</p>
    <a class="btn ghost" href="profile.php">Bekijk</a>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
