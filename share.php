<?php
require 'config.php';
require_login();
include 'templates/header.php';

$user = current_user();
$werkgeverId = current_werkgever_id();
$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$entityTitle = '';
$entityLink = 'index.php';
$defaultMessage = '';
$recipientPool = [];
$error = '';
$success = '';

if ($id <= 0 || !in_array($type, ['job', 'employee', 'bus', 'user'], true)) {
  $error = 'Invalid share request.';
}

if ($error === '' && $type === 'job') {
  $stmt = $db->prepare("SELECT id, titel, datum, werkgever_id, werknemer_id FROM roosters WHERE id = ? LIMIT 1");
  $stmt->execute([$id]);
  $item = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$item) {
    $error = 'Job not found.';
  } else {
    $owner = (int)($item['werkgever_id'] ?? 0);
    if (!is_super_admin() && $werkgeverId !== null && $owner !== (int)$werkgeverId && (int)$user['id'] !== (int)$item['werknemer_id']) {
      $error = 'No access to this job.';
    }
    $entityTitle = 'Job: ' . ($item['titel'] ?: ('#' . $item['id']));
    $entityLink = 'rooster_detail.php?id=' . (int)$item['id'];
    $defaultMessage = 'Shared job update for ' . ($item['datum'] ?? '');
  }
}

if ($error === '' && $type === 'employee') {
  $stmt = $db->prepare("
    SELECT u.id, u.naam, u.werkgever_id
    FROM users u
    JOIN rollen r ON r.id = u.rol_id
    WHERE u.id = ? AND r.naam = 'werknemer'
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $item = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$item) {
    $error = 'Employee not found.';
  } else {
    if (!is_super_admin() && $werkgeverId !== null && (int)$item['werkgever_id'] !== (int)$werkgeverId) {
      $error = 'No access to this employee.';
    }
    $entityTitle = 'Employee: ' . ($item['naam'] ?: ('#' . $item['id']));
    $entityLink = 'werknemers_management.php';
    $defaultMessage = 'Shared employee account details.';
  }
}

if ($error === '' && $type === 'user') {
  $stmt = $db->prepare("
    SELECT u.id, u.naam, u.werkgever_id, r.naam AS rol_naam
    FROM users u
    JOIN rollen r ON r.id = u.rol_id
    WHERE u.id = ?
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $item = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$item) {
    $error = 'User not found.';
  } else {
    if (!is_super_admin() && $werkgeverId !== null && (int)$item['werkgever_id'] !== (int)$werkgeverId && (int)$item['id'] !== (int)$werkgeverId) {
      $error = 'No access to this user.';
    }
    $entityTitle = 'User: ' . ($item['naam'] ?: ('#' . $item['id'])) . ' (' . ($item['rol_naam'] ?? 'user') . ')';
    $entityLink = 'profile.php';
    $defaultMessage = 'Shared user account details.';
  }
}

if ($error === '' && $type === 'bus') {
  $stmt = $db->prepare("SELECT id, naam, werkgever_id FROM buses WHERE id = ? LIMIT 1");
  $stmt->execute([$id]);
  $item = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$item) {
    $error = 'Bus not found.';
  } else {
    if (!is_super_admin() && $werkgeverId !== null && (int)$item['werkgever_id'] !== (int)$werkgeverId) {
      $error = 'No access to this bus.';
    }
    $entityTitle = 'Bus: ' . ($item['naam'] ?: ('#' . $item['id']));
    $entityLink = 'buses_management.php';
    $defaultMessage = 'Shared bus/team update.';
  }
}

if ($error === '' && $werkgeverId !== null) {
  $stmt = $db->prepare("
    SELECT u.id, u.naam, r.naam AS rol
    FROM users u
    JOIN rollen r ON r.id = u.rol_id
    WHERE u.actief = TRUE
      AND (u.id = ? OR u.werkgever_id = ?)
      AND u.id <> ?
    ORDER BY u.naam
  ");
  $selfId = (int)$user['id'];
  $stmt->execute([(int)$werkgeverId, (int)$werkgeverId, $selfId]);
  $recipientPool = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
  $selected = $_POST['recipient_ids'] ?? [];
  $message = trim($_POST['message'] ?? '');
  $safeMessage = $message !== '' ? $message : $defaultMessage;
  if (empty($selected)) {
    $error = 'Select at least one recipient.';
  } else {
    $insert = $db->prepare("
      INSERT INTO notifications (sender_user_id, recipient_user_id, type, title, message, link)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $senderId = (int)$user['id'];
    $count = 0;
    foreach ($selected as $rid) {
      $rid = (int)$rid;
      if ($rid <= 0) {
        continue;
      }
      $insert->execute([$senderId, $rid, 'share_' . $type, $entityTitle, $safeMessage, $entityLink]);
      $count++;
    }
    $success = "Shared with {$count} user(s).";
  }
}
?>

<div class="card">
  <h2>Share</h2>
  <p class="muted">Send internal notifications and copy/share a direct link.</p>
</div>

<?php if ($error): ?>
  <div class="error"><?= h($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="success"><?= h($success) ?></div>
<?php endif; ?>

<?php if (!$error || $success): ?>
  <div class="card">
    <h3><?= h($entityTitle) ?></h3>
    <label>Direct link</label>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <input id="share-link" type="text" readonly value="<?= h((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['PHP_SELF']) . '/' . $entityLink) ?>">
      <button type="button" class="btn ghost" id="copy-share-link">Copy link</button>
      <button type="button" class="btn" id="native-share">Share</button>
    </div>

    <form method="post" style="margin-top:14px;">
      <label>Recipients</label>
      <div class="recipient-grid">
        <?php foreach ($recipientPool as $recipient): ?>
          <label class="recipient-option">
            <input type="checkbox" name="recipient_ids[]" value="<?= (int)$recipient['id'] ?>">
            <span><?= h($recipient['naam']) ?> <small class="muted">(<?= h($recipient['rol']) ?>)</small></span>
          </label>
        <?php endforeach; ?>
      </div>

      <label>Message</label>
      <textarea name="message" placeholder="<?= h($defaultMessage) ?>"><?= h($_POST['message'] ?? '') ?></textarea>

      <button class="btn" type="submit">Send notification</button>
    </form>
  </div>
<?php endif; ?>

<script>
(() => {
  const input = document.getElementById('share-link');
  const copyBtn = document.getElementById('copy-share-link');
  const nativeBtn = document.getElementById('native-share');
  if (!input) return;

  copyBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(input.value);
      copyBtn.textContent = 'Copied';
      setTimeout(() => { copyBtn.textContent = 'Copy link'; }, 1500);
    } catch (e) {
      input.select();
      document.execCommand('copy');
    }
  });

  nativeBtn?.addEventListener('click', async () => {
    if (navigator.share) {
      await navigator.share({ title: document.title, text: 'ProfPlanner share', url: input.value });
    } else {
      copyBtn?.click();
    }
  });
})();
</script>

<?php include 'templates/footer.php'; ?>
