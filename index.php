<?php require 'config.php'; include 'templates/header.php'; ?>
<div class="card">
  <h2>Inloggen</h2>
  <p class="muted">Kies je rol. Daarna log je in met je eigen account.</p>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
    <a class="btn secondary" href="login.php?rol=super_admin">Inloggen als Super Admin</a>
    <a class="btn" href="login.php?rol=werkgever">Inloggen als Werkgever</a>
    <a class="btn ghost" href="login.php?rol=werknemer">Inloggen als Werknemer</a>
  </div>
</div>
<?php include 'templates/footer.php'; ?>
