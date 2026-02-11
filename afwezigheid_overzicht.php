<?php
require 'config.php';
require_role('werkgever');
include 'templates/header.php';

$stmt = $db->query("
  SELECT a.id, a.datum, a.reden, u.naam
  FROM afwezigheden a
  JOIN users u ON u.id = a.werknemer_id
  ORDER BY a.datum DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <h2>Afwezigheden</h2>
  <table>
    <thead><tr><th>Werknemer</th><th>Datum</th><th>Reden</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= h($r['naam']) ?></td>
          <td><?= h($r['datum']) ?></td>
          <td><?= h($r['reden'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$rows): ?>
        <tr><td colspan="3" class="muted">Nog geen afwezigheden.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include 'templates/footer.php'; ?>
