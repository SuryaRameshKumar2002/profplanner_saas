<?php
require 'config.php';
require_login();
include 'templates/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo "<div class='error'>Ongeldige klus.</div>";
  include 'templates/footer.php';
  exit;
}

/**
 * Klus ophalen
 */
$stmt = $db->prepare("
  SELECT r.*,
         o.naam AS opdrachtgever_naam,
         u.naam AS werknemer_naam,
         b.naam AS bus_naam,
         b.kleur AS bus_kleur
  FROM roosters r
  LEFT JOIN opdrachtgevers o ON o.id = r.opdrachtgever_id
  LEFT JOIN users u ON u.id = r.werknemer_id
  LEFT JOIN buses b ON b.id = r.bus_id
  WHERE r.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r) {
  echo "<div class='error'>Klus niet gevonden.</div>";
  include 'templates/footer.php';
  exit;
}

$user = $_SESSION['user'];

/**
 * Werknemer mag alleen eigen klus zien
 */
if (($user['rol'] ?? '') === 'werknemer' && (int)($r['werknemer_id'] ?? 0) !== (int)($user['id'] ?? 0)) {
  echo "<div class='error'>Geen toegang.</div>";
  include 'templates/footer.php';
  exit;
}
if (($user['rol'] ?? '') === 'werkgever' && (int)($r['werkgever_id'] ?? 0) !== (int)($user['id'] ?? 0)) {
  echo "<div class='error'>Geen toegang.</div>";
  include 'templates/footer.php';
  exit;
}

/**
 * Uploads ophalen
 */
$uploads = [];
$uploadsCols = [];
try {
  $desc = $db->query("DESCRIBE uploads")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($desc as $c) $uploadsCols[] = $c['Field'];

  if (in_array('rooster_id', $uploadsCols, true)) {
    $uStmt = $db->prepare("SELECT * FROM uploads WHERE rooster_id=? ORDER BY id DESC");
    $uStmt->execute([$id]);
    $uploads = $uStmt->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  $uploads = [];
}

/**
 * Status badge
 */
$status = strtolower((string)($r['status'] ?? 'gepland'));
$badge = 'badge';
if ($status === 'afgerond') $badge .= ' green';
elseif ($status === 'afgebroken') $badge .= ' red';
elseif ($status === 'verzet') $badge .= ' yellow';

/**
 * Tijd netjes tonen (TIME of DATETIME)
 */
$startRaw = (string)($r['starttijd'] ?? '');
$eindRaw  = (string)($r['eindtijd'] ?? '');

$startTijd = $startRaw;
$eindTijd  = $eindRaw;

if (strlen($startRaw) >= 16 && strpos($startRaw, ' ') !== false) $startTijd = substr($startRaw, 11, 5);
if (strlen($eindRaw)  >= 16 && strpos($eindRaw,  ' ') !== false) $eindTijd  = substr($eindRaw, 11, 5);
?>

<div class="grid">

  <div class="card">
    <h2><?= h($r['titel'] ?? 'Klus') ?></h2>
    <p class="muted"><span class="<?= h($badge) ?>"><?= h($r['status'] ?? 'gepland') ?></span></p>

    <p><b>Datum:</b> <?= h($r['datum'] ?? '') ?> &nbsp; <b>Tijd:</b> <?= h($startTijd) ?> - <?= h($eindTijd) ?></p>
    <p><b>Locatie:</b> <?= h($r['locatie'] ?? '') ?></p>
    <p><b>Opdrachtgever:</b> <?= h($r['opdrachtgever_naam'] ?? '') ?></p>
    <p><b>Werknemer:</b> <?= h($r['werknemer_naam'] ?? '') ?></p>
    <?php if (!empty($r['bus_naam'])): ?>
      <p><b>Bus/Team:</b> <span class="badge" style="background:<?= h($r['bus_kleur'] ?? '#16a34a') ?>;color:#fff;"><?= h($r['bus_naam']) ?></span></p>
    <?php endif; ?>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:14px 0;">

    <p><b>Project info (isolatie):</b></p>
    <div class="muted"><?= nl2br(h($r['omschrijving'] ?? '')) ?></div>

    <?php if (!empty($r['toelichting'])): ?>
      <p style="margin-top:12px;"><b>Toelichting:</b></p>
      <div class="muted"><?= nl2br(h($r['toelichting'])) ?></div>
    <?php endif; ?>

    <?php if (!empty($r['extra_werkzaamheden'])): ?>
      <p style="margin-top:12px;"><b>Extra werkzaamheden:</b></p>
      <div class="muted"><?= nl2br(h($r['extra_werkzaamheden'])) ?></div>
    <?php endif; ?>

    <?php if (($user['rol'] ?? '') === 'werkgever'): ?>
      <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
        <a class="btn" href="klus_toevoegen.php?edit=<?= (int)$r['id'] ?>">Wijzigen</a>
        <a class="btn secondary" href="klus_verwijderen.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Verwijderen?')">Verwijderen</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Foto’s / Uploads</h3>
    <p class="muted">Klik op een bestand om te openen.</p>

    <?php if (($user['rol'] ?? '') === 'werknemer'): ?>
      <a class="btn" href="upload.php?rooster_id=<?= (int)$r['id'] ?>">Foto uploaden</a>
    <?php else: ?>
      <a class="btn" href="upload.php?rooster_id=<?= (int)$r['id'] ?>&type=referentie">Referentiefoto toevoegen</a>
    <?php endif; ?>

    <div style="margin-top:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;">
      <?php
        // welke kolom bevat bestandsnaam?
        $fileCandidates = ['bestandsnaam','filename','file','bestand','pad','path'];
      ?>

      <?php foreach ($uploads as $up): ?>
        <?php
          $fn = '';
          foreach ($fileCandidates as $c) {
            if (isset($up[$c]) && $up[$c] !== '') { $fn = (string)$up[$c]; break; }
          }
          if ($fn === '') continue;

          // link naar file in uploads/
          $href = 'uploads/' . rawurlencode($fn);
        ?>

        <a class="card" style="padding:10px;text-decoration:none;color:inherit;" href="<?= h($href) ?>" target="_blank">
          <div class="muted" style="font-size:12px;margin-bottom:6px;"><?= h($up['type'] ?? 'foto') ?></div>
          <div style="font-weight:700;font-size:13px;word-break:break-word;"><?= h($fn) ?></div>
        </a>
      <?php endforeach; ?>

      <?php if (!$uploads): ?>
        <div class="muted">Nog geen uploads.</div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (($user['rol'] ?? '') === 'werknemer'): ?>
  <div class="card">
    <h3>Status bijwerken</h3>

    <form method="post" action="rooster_status.php?id=<?= (int)$r['id'] ?>">
      <label>Status</label>
      <select name="status" required>
        <option value="gepland" <?= (($r['status'] ?? '') === 'gepland' ? 'selected' : '') ?>>Gepland</option>
        <option value="afgerond">Klus afgerond</option>
        <option value="afgebroken">Klus afgebroken</option>
        <option value="verzet">Klus verzet</option>
      </select>

      <label>Toelichting / reden</label>
      <textarea name="toelichting" placeholder="Bijv. waarom afgebroken of verzet..."></textarea>

      <label>Extra werkzaamheden (optioneel)</label>
      <textarea name="extra_werkzaamheden" placeholder="Extra werkzaamheden? Zo ja, omschrijf."></textarea>

      <button class="btn" type="submit">Opslaan</button>
    </form>
  </div>
  <?php endif; ?>

</div>

<?php include 'templates/footer.php'; ?>
