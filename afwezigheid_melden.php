<?php
require 'config.php';
require_role('werknemer');
include 'templates/header.php';

if($_POST){
  $db->prepare("INSERT INTO afwezigheden (werknemer_id, datum, reden) VALUES (?,?,?)")
    ->execute([$_SESSION['user']['id'], $_POST['datum'], $_POST['reden'] ?? null]);
  header("Location: werknemer.php");
  exit;
}
?>
<div class="card">
  <h2>Afwezigheid melden</h2>
  <form method="post">
    <label>Datum</label>
    <input type="date" name="datum" required>
    <label>Reden (optioneel)</label>
    <input type="text" name="reden" placeholder="Bijv. ziek, afspraak, etc.">
    <button class="btn" type="submit">Melden</button>
  </form>
</div>
<?php include 'templates/footer.php'; ?>
