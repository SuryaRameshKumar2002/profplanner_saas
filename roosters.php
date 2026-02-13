<?php
require 'config.php';
require_login();
include 'templates/header.php';

$user = $_SESSION['user'];
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();

if($user['rol']==='werknemer'){
  $stmt = $db->prepare("
    SELECT r.*, o.naam AS opdrachtgever_naam, u.naam AS werknemer_naam, b.naam AS bus_naam, b.kleur AS bus_kleur, b.image_path AS bus_image_path
    FROM roosters r
    LEFT JOIN opdrachtgevers o ON o.id = r.opdrachtgever_id
    LEFT JOIN users u ON u.id = r.werknemer_id
    LEFT JOIN buses b ON b.id = r.bus_id
    WHERE r.werknemer_id = ?
    ORDER BY r.datum DESC, r.tijd ASC
  ");
  $stmt->execute([$user['id']]);
} elseif ($isSuper) {
  $stmt = $db->query("
    SELECT r.*, o.naam AS opdrachtgever_naam, u.naam AS werknemer_naam, b.naam AS bus_naam, b.kleur AS bus_kleur, b.image_path AS bus_image_path, wg.naam AS werkgever_naam
    FROM roosters r
    LEFT JOIN opdrachtgevers o ON o.id = r.opdrachtgever_id
    LEFT JOIN users u ON u.id = r.werknemer_id
    LEFT JOIN buses b ON b.id = r.bus_id
    LEFT JOIN users wg ON wg.id = r.werkgever_id
    ORDER BY r.datum DESC, r.tijd ASC
  ");
} else {
  $stmt = $db->prepare("
    SELECT r.*, o.naam AS opdrachtgever_naam, u.naam AS werknemer_naam, b.naam AS bus_naam, b.kleur AS bus_kleur, b.image_path AS bus_image_path
    FROM roosters r
    LEFT JOIN opdrachtgevers o ON o.id = r.opdrachtgever_id
    LEFT JOIN users u ON u.id = r.werknemer_id
    LEFT JOIN buses b ON b.id = r.bus_id
    WHERE r.werkgever_id = ?
    ORDER BY r.datum DESC, r.tijd ASC
  ");
  $stmt->execute([$werkgeverId]);
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
    <div>
      <h2>Roosters</h2>
      <div class="muted"><?= $user['rol']==='werkgever' ? "Alle klussen" : ($isSuper ? "Alle klussen (alle werkgevers)" : "Jouw klussen") ?></div>
    </div>
    <?php if($user['rol']==='werkgever'): ?>
      <a class="btn" href="klus_toevoegen.php">+ Klus aanmaken</a>
    <?php endif; ?>
  </div>
  <div style="overflow:auto;margin-top:10px;">
  <table>
    <thead>
      <tr>
        <th>Datum</th><th>Tijd</th><th>Klus</th><th>Locatie</th><th>Bus/Team</th><th>Werknemer</th><?php if($isSuper): ?><th>Werkgever</th><?php endif; ?><th>Status</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r):
        $status = strtolower($r['status'] ?? 'gepland');
        $badge = 'badge';
        if($status==='afgerond') $badge.=' green';
        elseif($status==='afgebroken') $badge.=' red';
        elseif($status==='verzet') $badge.=' yellow';
      ?>
      <tr>
        <td><?= h($r['datum']) ?></td>
        <td><?= h($r['tijd']) ?></td>
        <td><?= h($r['titel'] ?? '') ?></td>
        <td><?= h($r['locatie'] ?? '') ?></td>
        <td>
          <?php if (!empty($r['bus_naam'])): ?>
            <span class="bus-chip">
              <?php if (!empty($r['bus_image_path'])): ?>
                <img src="<?= h($r['bus_image_path']) ?>" alt="<?= h($r['bus_naam']) ?>" class="bus-thumb">
              <?php endif; ?>
              <span class="badge" style="background:<?= h($r['bus_kleur'] ?? '#16a34a') ?>;color:#fff;"><?= h($r['bus_naam']) ?></span>
            </span>
          <?php else: ?>
            <span class="muted">-</span>
          <?php endif; ?>
        </td>
        <td><?= h($r['werknemer_naam'] ?? '') ?></td>
        <?php if($isSuper): ?><td><?= h($r['werkgever_naam'] ?? '-') ?></td><?php endif; ?>
        <td><span class="<?= $badge ?>"><?= h($r['status'] ?? '') ?></span></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap;">
          <a class="btn ghost" href="rooster_detail.php?id=<?= (int)$r['id'] ?>">Open</a>
          <a class="btn ghost" href="share.php?type=job&id=<?= (int)$r['id'] ?>">Share</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$rows): ?>
        <tr><td colspan="<?= $isSuper ? 9 : 8 ?>" class="muted">Geen klussen gevonden.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php include 'templates/footer.php'; ?>
