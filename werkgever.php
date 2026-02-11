<?php require 'config.php'; require_role('werkgever'); include 'templates/header.php'; ?>
<div class="grid">
  <div class="card">
    <h3>Roosters & Klussen</h3>
    <p class="muted">Klus doorsturen naar werknemers met locatie, info en (optioneel) referentiefoto’s.</p>
    <a class="btn" href="klus_toevoegen.php">+ Klus aanmaken</a>
    <a class="btn ghost" href="roosters.php" style="margin-left:8px;">Overzicht</a>
  </div>

  <div class="card">
    <h3>Afwezigheden</h3>
    <p class="muted">Inkomende afwezigheidsmeldingen van werknemers.</p>
    <a class="btn" href="afwezigheid_overzicht.php">Open</a>
  </div>

  <div class="card">
    <h3>Export</h3>
    <p class="muted">Exporteer voltooide opdrachten.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn" href="export_excel.php">Excel</a>
      <a class="btn secondary" href="export_pdf.php">PDF</a>
    </div>
  </div>
</div>
<?php include 'templates/footer.php'; ?>
