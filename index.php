<?php require 'config.php'; include 'templates/header.php'; ?>
<div class="card">
  <h2>Inloggen</h2>
  <p class="muted">Kies je rol. Daarna log je in met je eigen account.</p>
  <div class="login-actions">
    <a class="btn" href="login.php?rol=werkgever">Inloggen als Werkgever</a>
    <a class="btn ghost" href="login.php?rol=werknemer">Inloggen als Werknemer</a>
    <a class="btn secondary" href="login.php?rol=sales">Inloggen als Sales</a>
  </div>
  <div style="margin-top:8px;">
    <a class="btn subtle" href="login.php?rol=super_admin">Super Admin</a>
  </div>
</div>
<?php include 'templates/footer.php'; ?>
