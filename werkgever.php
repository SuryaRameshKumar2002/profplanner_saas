<?php require 'config.php'; require_role('werkgever'); include 'templates/header.php'; ?>

<div class="card" style="background:linear-gradient(135deg,#f3f4f6 0%,#ffffff 100%);border:none;">
  <h2>Werkgever Dashboard</h2>
  <p class="muted">Beheer klussen, teams, werknemers en planning</p>
</div>

<div class="grid">
  <div class="card">
    <h3>📋 Roosters & Klussen</h3>
    <p class="muted">Klus doorsturen naar werknemers met locatie, info en foto's.</p>
    <a class="btn" href="klus_toevoegen.php">+ Klus aanmaken</a>
    <a class="btn ghost" href="roosters.php" style="margin-left:8px;">Overzicht</a>
  </div>

  <div class="card">
    <h3>📅 Weekplanning</h3>
    <p class="muted">Bekijk wekelijkse planning gegroepeerd per team/bus.</p>
    <a class="btn" href="planner_weekly.php">Weekplanner</a>
  </div>

  <div class="card">
    <h3>🚌 Bus & Team Beheer</h3>
    <p class="muted">Beheer teams (HV01, HV02, DVI) en wijs werknemers toe.</p>
    <a class="btn" href="buses_management.php">Beheren</a>
  </div>

  <div class="card">
    <h3>❌ Afwezigheden</h3>
    <p class="muted">Inkomende afwezigheidsmeldingen van werknemers.</p>
    <a class="btn" href="afwezigheid_overzicht.php">Overzicht</a>
  </div>

  <div class="card">
    <h3>👥 Werknemers</h3>
    <p class="muted">Beheer werknemers en hun rollen.</p>
    <a class="btn" href="werknemers_management.php">Beheren</a>
  </div>

  <div class="card">
    <h3>💼 Opdrachtgevers</h3>
    <p class="muted">Beheer klanten en opdrachtgevers.</p>
    <a class="btn" href="klanten_management.php">Beheren</a>
  </div>

  <div class="card">
    <h3>📊 Export</h3>
    <p class="muted">Exporteer voltooide opdrachten.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn" href="export_excel.php">Excel</a>
      <a class="btn secondary" href="export_pdf.php">PDF</a>
    </div>
  </div>

  <div class="card">
    <h3>⚙️ Instellingen</h3>
    <p class="muted">Beheer je account en profielinstellingen.</p>
    <a class="btn ghost" href="profile.php">Profiel</a>
  </div>
</div>

<?php include 'templates/footer.php'; ?>
