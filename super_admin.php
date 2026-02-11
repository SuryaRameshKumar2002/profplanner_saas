<?php
require 'config.php';
require_super_admin();
include 'templates/header.php';

$totWerkgevers = (int)$db->query("SELECT COUNT(*) FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werkgever' AND u.actief=1")->fetchColumn();
$totWerknemers = (int)$db->query("SELECT COUNT(*) FROM users u JOIN rollen r ON r.id=u.rol_id WHERE r.naam='werknemer' AND u.actief=1")->fetchColumn();
$totJobs = (int)$db->query("SELECT COUNT(*) FROM roosters")->fetchColumn();
$totLeads = (int)$db->query("SELECT COUNT(*) FROM sales_leads")->fetchColumn();
?>

<div class="card" style="background:linear-gradient(135deg,#f3f4f6 0%,#ffffff 100%);border:none;">
  <h2>Super Admin Dashboard</h2>
  <p class="muted">Globale toegang tot werkgevers, jobs, CRM/Sales en platform instellingen.</p>
</div>

<div class="grid">
  <div class="card">
    <h3>Werkgevers</h3>
    <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $totWerkgevers ?></div>
    <p class="muted">Actieve werkgever accounts</p>
    <a class="btn" href="werkgevers_management.php">Beheren</a>
  </div>

  <div class="card">
    <h3>Werknemers</h3>
    <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $totWerknemers ?></div>
    <p class="muted">Totaal in alle werkgevers</p>
    <a class="btn ghost" href="werknemers_management.php">Overzicht</a>
  </div>

  <div class="card">
    <h3>Roosters</h3>
    <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $totJobs ?></div>
    <p class="muted">Totaal jobs in planning</p>
    <a class="btn ghost" href="roosters.php">Open</a>
  </div>

  <div class="card">
    <h3>CRM/Sales</h3>
    <div style="font-size:24px;font-weight:bold;color:#16a34a;"><?= $totLeads ?></div>
    <p class="muted">Leads in sales portal</p>
    <a class="btn" href="sales_portal.php">Open CRM/Sales</a>
  </div>

  <div class="card">
    <h3>Instellingen</h3>
    <p class="muted">Alleen super admin heeft toegang.</p>
    <a class="btn ghost" href="settings.php">Settings</a>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
