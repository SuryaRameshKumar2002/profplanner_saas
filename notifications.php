<?php
require 'config.php';
require_login();
include 'templates/header.php';

$userId = (int)(current_user()['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
  $stmt = $db->prepare("UPDATE notifications SET gelezen_op = NOW() WHERE recipient_user_id = ? AND gelezen_op IS NULL");
  $stmt->execute([$userId]);
}

if (isset($_GET['read']) && ctype_digit((string)$_GET['read'])) {
  $nid = (int)$_GET['read'];
  $stmt = $db->prepare("UPDATE notifications SET gelezen_op = NOW() WHERE id = ? AND recipient_user_id = ?");
  $stmt->execute([$nid, $userId]);
  header('Location: notifications.php');
  exit;
}

$stmt = $db->prepare("
  SELECT n.*, u.naam AS sender_naam
  FROM notifications n
  LEFT JOIN users u ON u.id = n.sender_user_id
  WHERE n.recipient_user_id = ?
  ORDER BY n.gemaakt_op DESC
  LIMIT 150
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_user_id = ? AND gelezen_op IS NULL");
$unreadStmt->execute([$userId]);
$unread = (int)$unreadStmt->fetchColumn();
?>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h2>Notifications</h2>
      <p class="muted">Internal updates and shares inside ProfPlanner.</p>
    </div>
    <form method="post">
      <button type="submit" name="mark_all_read" class="btn ghost">Mark all read (<?= $unread ?>)</button>
    </form>
  </div>
</div>

<div class="card">
  <?php if (empty($items)): ?>
    <p class="muted">No notifications yet.</p>
  <?php else: ?>
    <div class="notification-list">
      <?php foreach ($items as $n): ?>
        <div class="notification-item <?= empty($n['gelezen_op']) ? 'unread' : '' ?>">
          <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
            <strong><?= h($n['title']) ?></strong>
            <span class="muted"><?= h((string)$n['gemaakt_op']) ?></span>
          </div>
          <div class="muted" style="margin-top:4px;">From: <?= h($n['sender_naam'] ?? 'System') ?></div>
          <?php if (!empty($n['message'])): ?>
            <p style="margin:8px 0 0;"><?= nl2br(h($n['message'])) ?></p>
          <?php endif; ?>
          <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
            <?php if (!empty($n['link'])): ?>
              <a class="btn" href="<?= h($n['link']) ?>">Open</a>
            <?php endif; ?>
            <?php if (empty($n['gelezen_op'])): ?>
              <a class="btn ghost" href="notifications.php?read=<?= (int)$n['id'] ?>">Mark read</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
