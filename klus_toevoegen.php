<?php
require 'config.php';
require_role('werkgever');
include 'templates/header.php';

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editing = $editId > 0;

$data = [
  'datum' => '',
  'starttijd' => '',
  'eindtijd' => '',
  'titel' => '',
  'locatie' => '',
  'omschrijving' => '',
  'werknemer_id' => '',
  'opdrachtgever_id' => '',
];

if ($editing) {
  $s = $db->prepare("SELECT * FROM roosters WHERE id=? LIMIT 1");
  $s->execute([$editId]);
  $row = $s->fetch(PDO::FETCH_ASSOC);
  if ($row) { $data = array_merge($data, $row); }
}

/** werknemers via rollen */
$werknemers = $db->query("
  SELECT u.id, u.naam
  FROM users u
  JOIN rollen r ON r.id = u.rol_id
  WHERE r.naam = 'werknemer'
  ORDER BY u.naam
")->fetchAll(PDO::FETCH_ASSOC);

$opdrachtgevers = $db->query("
  SELECT id, naam
  FROM opdrachtgevers
  ORDER BY naam
")->fetchAll(PDO::FETCH_ASSOC);

/**
 * Detecteer kolomtypes van starttijd/eindtijd (TIME vs DATETIME)
 */
$desc = $db->query("DESCRIBE roosters")->fetchAll(PDO::FETCH_ASSOC);
$types = [];
foreach ($desc as $c) {
  $types[$c['Field']] = strtolower($c['Type']);
}
$start_is_datetime = isset($types['starttijd']) && (str_contains($types['starttijd'], 'datetime') || str_contains($types['starttijd'], 'timestamp'));
$eind_is_datetime  = isset($types['eindtijd'])  && (str_contains($types['eindtijd'], 'datetime') || str_contains($types['eindtijd'], 'timestamp'));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $werkgever_id = (int)($_SESSION['user']['id'] ?? 0);

  $datum = trim($_POST['datum'] ?? '');
  $start_in = trim($_POST['starttijd'] ?? '');
  $eind_in  = trim($_POST['eindtijd'] ?? '');

  if ($datum === '' || $start_in === '' || $eind_in === '') {
    $error = "Vul datum, starttijd en eindtijd in.";
  } else {

    // Als DB DATETIME verwacht: combineer datum + tijd
    $starttijd = $start_is_datetime ? ($datum . ' ' . $start_in . ':00') : $start_in;
    $eindtijd  = $eind_is_datetime  ? ($datum . ' ' . $eind_in  . ':00') : $eind_in;

    $payload = [
      $datum,
      $starttijd,
      $eindtijd,
      $_POST['titel'] ?? '',
      $_POST['locatie'] ?? '',
      $_POST['omschrijving'] ?? '',
      (int)($_POST['werknemer_id'] ?? 0),
      (int)($_POST['opdrachtgever_id'] ?? 0),
      $werkgever_id
    ];

    if ($editing) {
      $stmt = $db->prepare("
        UPDATE roosters
        SET datum=?,
            starttijd=?,
            eindtijd=?,
            titel=?,
            locatie=?,
            omschrijving=?,
            werknemer_id=?,
            opdrachtgever_id=?,
            werkgever_id=?
        WHERE id=?
      ");
      $stmt->execute(array_merge($payload, [$editId]));
    } else {
      $stmt = $db->prepare("
        INSERT INTO roosters (
          datum, starttijd, eindtijd, titel, locatie, omschrijving,
          werknemer_id, opdrachtgever_id, werkgever_id, status
        )
        VALUES (?,?,?,?,?,?,?,?,?, 'gepland')
      ");
      $stmt->execute($payload);
      $editId = (int)$db->lastInsertId();
    }

    header("Location: rooster_detail.php?id=" . $editId);
    exit;
  }
}
?>

<div class="card">
  <h2><?= $editing ? "Klus wijzigen" : "Klus doorsturen naar werknemer" ?></h2>
  <p class="muted">Vul locatie + projectinfo (isolatie) in. Werknemer ziet dit direct in zijn/haar rooster.</p>

  <?php if($error): ?>
    <div class="error"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="grid">
      <div>
        <label>Datum</label>
        <input type="date" name="datum" required value="<?= h($data['datum']) ?>">
      </div>

      <div>
        <label>Starttijd</label>
        <input type="time" name="starttijd" required value="<?= h(substr((string)($data['starttijd'] ?? ''), 11, 5) ?: ($data['starttijd'] ?? '')) ?>">
      </div>

      <div>
        <label>Eindtijd</label>
        <input type="time" name="eindtijd" required value="<?= h(substr((string)($data['eindtijd'] ?? ''), 11, 5) ?: ($data['eindtijd'] ?? '')) ?>">
      </div>
    </div>

    <label>Klus titel</label>
    <input type="text" name="titel" required value="<?= h($data['titel']) ?>">

    <label>Locatie</label>
    <input type="text" name="locatie" required value="<?= h($data['locatie']) ?>">

    <label>Project info (isolatie)</label>
    <textarea name="omschrijving" required><?= h($data['omschrijving']) ?></textarea>

    <div class="grid">
      <div>
        <label>Werknemer</label>
        <select name="werknemer_id" required>
          <option value="">Kies werknemer</option>
          <?php foreach ($werknemers as $w): ?>
            <option value="<?= (int)$w['id'] ?>" <?= ((int)$data['werknemer_id'] === (int)$w['id'] ? 'selected' : '') ?>>
              <?= h($w['naam']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Opdrachtgever</label>
        <select name="opdrachtgever_id" required>
          <option value="">Kies opdrachtgever</option>
          <?php foreach ($opdrachtgevers as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= ((int)$data['opdrachtgever_id'] === (int)$o['id'] ? 'selected' : '') ?>>
              <?= h($o['naam']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <button class="btn" type="submit"><?= $editing ? "Opslaan" : "Klus aanmaken" ?></button>
    <a class="btn ghost" href="roosters.php" style="margin-left:8px;">Annuleren</a>
  </form>
</div>

<?php include 'templates/footer.php'; ?>
