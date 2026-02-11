<?php require 'config.php'; require_role('werknemer'); include 'templates/header.php'; ?>
<div class="grid">
  <div class="card">
    <h3>Mijn Rooster</h3>
    <p class="muted">Bekijk je ingeplande klussen en open de klus om status bij te werken en foto’s te uploaden.</p>
    <a class="btn" href="roosters.php">Open</a>
  </div>

  <div class="card">
    <h3>Afwezigheid melden</h3>
    <p class="muted">Melding gaat automatisch naar werkgever.</p>
    <a class="btn" href="afwezigheid_melden.php">Melden</a>
  </div>
</div>
<?php include 'templates/footer.php'; ?>
