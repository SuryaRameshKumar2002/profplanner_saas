<?php
require 'config.php';
require_super_admin();
include 'templates/header.php';
?>

<div class="card">
  <h2>Instellingen</h2>
  <p class="muted">Systeem- en databaseinstellingen</p>
</div>

<div class="grid">
  <div class="card">
    <h3>Database Info</h3>
    <div style="background:#f3f4f6;padding:12px;border-radius:8px;font-family:monospace;font-size:12px;">
      <?php
      try {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
        echo "<strong>Tabellen: " . count($tables) . "</strong><br>";
        foreach ($tables as $table) {
          echo "- " . h($table[0]) . "<br>";
        }
      } catch (Exception $e) {
        echo "Fout bij lezen tabellen";
      }
      ?>
    </div>
  </div>

  <div class="card">
    <h3>Buses in Systeem</h3>
    <?php
    try {
      $stmt = $db->query("SELECT COUNT(*) as count FROM buses");
      $bus_count = (int)$stmt->fetchColumn();
      echo "<div style='font-size:24px;font-weight:bold;color:#16a34a;'>" . $bus_count . "</div>";
      echo "<div class='muted'>Actieve buses/teams</div>";
    } catch (Exception $e) {
      echo "<div class='error'>Tabel 'buses' bestaat niet. Voer migratie uit.</div>";
    }
    ?>
  </div>

  <div class="card">
    <h3>Totaal Gebruikers</h3>
    <?php
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $user_count = (int)$stmt->fetchColumn();
    echo "<div style='font-size:24px;font-weight:bold;color:#16a34a;'>" . $user_count . "</div>";
    echo "<div class='muted'>Gebruikers in systeem</div>";
    ?>
  </div>

  <div class="card">
    <h3>Totaal Roosters</h3>
    <?php
    $stmt = $db->query("SELECT COUNT(*) as count FROM roosters");
    $rooster_count = (int)$stmt->fetchColumn();
    echo "<div style='font-size:24px;font-weight:bold;color:#16a34a;'>" . $rooster_count . "</div>";
    echo "<div class='muted'>Klussen in systeem</div>";
    ?>
  </div>
</div>

<div class="card">
  <h3>Onderhoud</h3>
  <p class="muted">Systeem onderhoudstaken</p>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <span class="notice" style="margin:0;">Diagnostische reset is uit productie verwijderd.</span>
    <button class="btn secondary" disabled>Database Backup (binnenkort)</button>
  </div>
</div>

<div class="card">
  <h3>Database Migratie</h3>
  <p class="muted">Gebruik de setupbestanden voor een schone installatie:</p>
  <div style="background:#fee2e2;border:1px solid #fecaca;padding:12px;border-radius:8px;margin:12px 0;font-size:13px;">
    <strong>Stap 1:</strong> Importeer <code>database/schema.sql</code><br>
    <strong>Stap 2:</strong> Importeer <code>database/seed_demo.sql</code> (optioneel seeded accounts)<br>
    <strong>Stap 3:</strong> Of voer <code>scripts/setup-local.ps1</code> uit in PowerShell
  </div>
</div>

<?php include 'templates/footer.php'; ?>
