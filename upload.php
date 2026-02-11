<?php
require 'config.php';
require_login();
include 'templates/header.php';

$user = $_SESSION['user'];

/**
 * Input
 */
$rooster_id = (int)($_GET['rooster_id'] ?? 0);
$requestedType = $_GET['type'] ?? '';

if ($rooster_id <= 0) {
  echo "<div class='error'>Geen geldige rooster_id.</div>";
  include 'templates/footer.php';
  exit;
}

/**
 * Rooster ophalen
 */
$stmt = $db->prepare("SELECT * FROM roosters WHERE id=? LIMIT 1");
$stmt->execute([$rooster_id]);
$rooster = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rooster) {
  echo "<div class='error'>Klus niet gevonden.</div>";
  include 'templates/footer.php';
  exit;
}

/**
 * Werknemer mag alleen eigen klus
 */
if (($user['rol'] ?? '') === 'werknemer') {
  if ((int)($rooster['werknemer_id'] ?? 0) !== (int)($user['id'] ?? 0)) {
    echo "<div class='error'>Geen toegang tot deze klus.</div>";
    include 'templates/footer.php';
    exit;
  }
} elseif (($user['rol'] ?? '') === 'werkgever') {
  if ((int)($rooster['werkgever_id'] ?? 0) !== (int)($user['id'] ?? 0)) {
    echo "<div class='error'>Geen toegang tot deze klus.</div>";
    include 'templates/footer.php';
    exit;
  }
}

/**
 * Upload folder auto-create
 */
$uploadDir = __DIR__ . "/uploads";
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}

/**
 * Determine type
 */
$type = (($user['rol'] ?? '') === 'werkgever') ? 'referentie' : 'foto';
if (($user['rol'] ?? '') === 'werkgever' && $requestedType === 'referentie') {
  $type = 'referentie';
}

/**
 * Lees kolommen van uploads tabel (zodat we de juiste INSERT doen)
 */
$uploadsCols = [];
try {
  $desc = $db->query("DESCRIBE uploads")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($desc as $c) {
    $uploadsCols[] = $c['Field'];
  }
} catch (Throwable $e) {
  echo "<div class='error'>Uploads tabel niet gevonden of geen rechten.</div>";
  include 'templates/footer.php';
  exit;
}

/**
 * Max uploads check voor werknemer (max 5 foto’s) — alleen als kolommen bestaan
 */
$maxReached = false;
if (($user['rol'] ?? '') === 'werknemer') {
  // We tellen alleen als 'type' kolom bestaat
  if (in_array('type', $uploadsCols, true)) {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM uploads WHERE rooster_id=? AND type='foto'");
    $countStmt->execute([$rooster_id]);
    $count = (int)$countStmt->fetchColumn();
    if ($count >= 5) $maxReached = true;
  }
}

/**
 * Upload verwerken
 */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if ($maxReached) {
    $error = "Maximaal 5 foto’s per klus.";
  } elseif (empty($_FILES['bestand']['name'])) {
    $error = "Kies een bestand.";
  } else {

    if (!isset($_FILES['bestand']['error']) || $_FILES['bestand']['error'] !== UPLOAD_ERR_OK) {
      $error = "Upload fout. Probeer opnieuw.";
    } else {

      // Alleen afbeeldingen toestaan
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = finfo_file($finfo, $_FILES['bestand']['tmp_name']);
      finfo_close($finfo);

      $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
      if (!in_array($mime, $allowed, true)) {
        $error = "Alleen afbeeldingen zijn toegestaan (jpg/png/webp/gif).";
      } else {

        // Veilige bestandsnaam
        $orig = basename($_FILES['bestand']['name']);
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig);

        // Unieke naam
        $name = time() . "_" . $safe;
        $target = $uploadDir . "/" . $name;

        if (!move_uploaded_file($_FILES['bestand']['tmp_name'], $target)) {
          $error = "Upload mislukt. Controleer maprechten (uploads/).";
        } else {

          /**
           * ✅ Dynamische INSERT: alleen kolommen gebruiken die bestaan
           * Veel voorkomende varianten:
           * - (rooster_id, bestandsnaam, type)
           * - (rooster_id, filename, type)
           * - (rooster_id, pad, type)
           * - zonder type
           * - met user_id / werknemer_id / werkgever_id
           */

          // Vind juiste bestandsnaam-kolom
          $fileCol = null;
          foreach (['bestandsnaam', 'filename', 'file', 'bestand', 'pad', 'path'] as $candidate) {
            if (in_array($candidate, $uploadsCols, true)) {
              $fileCol = $candidate;
              break;
            }
          }
          if ($fileCol === null) {
            // fallback: toch proberen bestandsnaam
            $fileCol = 'bestandsnaam';
          }

          // Kolommen + values opbouwen
          $cols = [];
          $vals = [];
          $params = [];

          // rooster_id moet bestaan (jij gebruikt dit in rooster_detail.php)
          if (in_array('rooster_id', $uploadsCols, true)) {
            $cols[] = 'rooster_id';
            $vals[] = '?';
            $params[] = $rooster_id;
          }

          // file kolom
          if (in_array($fileCol, $uploadsCols, true)) {
            $cols[] = $fileCol;
            $vals[] = '?';
            $params[] = $name;
          }

          // type kolom
          if (in_array('type', $uploadsCols, true)) {
            $cols[] = 'type';
            $vals[] = '?';
            $params[] = $type;
          }

          // optioneel: user link kolom(s)
          if (in_array('user_id', $uploadsCols, true)) {
            $cols[] = 'user_id';
            $vals[] = '?';
            $params[] = (int)$user['id'];
          } elseif (in_array('werknemer_id', $uploadsCols, true) && ($user['rol'] ?? '') === 'werknemer') {
            $cols[] = 'werknemer_id';
            $vals[] = '?';
            $params[] = (int)$user['id'];
          } elseif (in_array('werkgever_id', $uploadsCols, true) && ($user['rol'] ?? '') === 'werkgever') {
            $cols[] = 'werkgever_id';
            $vals[] = '?';
            $params[] = (int)$user['id'];
          }

          // Als er letterlijk niets te inserten is, stop
          if (count($cols) === 0) {
            $error = "Uploads tabel mist benodigde kolommen (minimaal rooster_id + bestandsnaam).";
          } else {
            $sql = "INSERT INTO uploads (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
            $ins = $db->prepare($sql);
            $ins->execute($params);

            header("Location: rooster_detail.php?id=" . $rooster_id);
            exit;
          }
        }
      }
    }
  }
}
?>

<div class="card">
  <h2>Upload <?= (($user['rol'] ?? '') === 'werkgever') ? "referentiefoto" : "foto" ?></h2>
  <p class="muted">
    Klus: <?= h($rooster['titel'] ?? '') ?> — <?= h($rooster['datum'] ?? '') ?>
  </p>

  <?php if($error): ?>
    <div class="error"><?= h($error) ?></div>
  <?php endif; ?>

  <?php if (($user['rol'] ?? '') === 'werknemer'): ?>
    <div class="notice">Je kunt maximaal 5 foto’s uploaden voor deze klus.</div>
  <?php else: ?>
    <div class="notice">Je voegt een referentiefoto toe voor de werknemer.</div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label>Kies afbeelding</label>
    <input type="file" name="bestand" accept="image/*" required>

    <button class="btn" type="submit">Upload</button>
    <a class="btn ghost" href="rooster_detail.php?id=<?= (int)$rooster_id ?>" style="margin-left:8px;">Terug</a>
  </form>
</div>

<?php include 'templates/footer.php'; ?>
