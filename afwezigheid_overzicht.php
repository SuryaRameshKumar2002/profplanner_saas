<?php
require 'config.php';
require_any_role(['werkgever', 'super_admin']);
include 'templates/header.php';
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();

if ($isSuper) {
  $stmt = $db->query("
    SELECT a.id, a.datum, a.reden, u.naam, wg.naam AS werkgever_naam
    FROM afwezigheden a
    JOIN users u ON u.id = a.werknemer_id
    LEFT JOIN users wg ON wg.id = u.werkgever_id
    ORDER BY a.datum DESC
  ");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $stmt = $db->prepare("
    SELECT a.id, a.datum, a.reden, u.naam, wg.naam AS werkgever_naam
    FROM afwezigheden a
    JOIN users u ON u.id = a.werknemer_id
    LEFT JOIN users wg ON wg.id = u.werkgever_id
    WHERE u.werkgever_id = ?
    ORDER BY a.datum DESC
  ");
  $stmt->execute([$werkgeverId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="card">
  <h2>Afwezigheden</h2>
  <table>
    <thead><tr><th>Werknemer</th><?php if($isSuper): ?><th>Werkgever</th><?php endif; ?><th>Datum</th><th>Reden</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= h($r['naam']) ?></td>
          <?php if($isSuper): ?><td><?= h($r['werkgever_naam'] ?? '-') ?></td><?php endif; ?>
          <td><?= h($r['datum']) ?></td>
          <td><?= h($r['reden'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$rows): ?>
        <tr><td colspan="<?= $isSuper ? 4 : 3 ?>" class="muted">Nog geen afwezigheden.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include 'templates/footer.php'; ?>
