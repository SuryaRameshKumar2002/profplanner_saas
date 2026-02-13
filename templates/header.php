<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ProfPlanner - Planning & Klusbeheer</title>
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?: time() ?>">
<link rel="icon" type="image/png" href="assets/favicon.png">
</head>
<body>
<header class="top">
  <div class="top-inner">
    <a href="index.php" class="brand-link">
      <img src="assets/logo.png" alt="ProfPlanner" class="brand-logo">
      <div class="brand-subtitle">Planning - Roosters - Teams</div>
    </a>
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="siteNav">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-overlay" id="navOverlay"></div>
    <nav class="nav" id="siteNav">
      <?php if(isset($_SESSION['user'])): ?>
        <?php
          $unreadNotifications = 0;
          try {
            $notifyStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_user_id = ? AND gelezen_op IS NULL");
            $notifyStmt->execute([(int)$_SESSION['user']['id']]);
            $unreadNotifications = (int)$notifyStmt->fetchColumn();
          } catch (Throwable $e) {
            $unreadNotifications = 0;
          }
        ?>
        <?php if($_SESSION['user']['rol']==='super_admin'): ?>
          <a href="super_admin.php">Super Admin</a>
          <a href="werkgevers_management.php">Werkgevers</a>
          <a href="sales_dashboard.php">Sales</a>
          <a href="planner_calendar.php">Calendar</a>
          <a href="roosters.php">Alle Roosters</a>
          <a href="settings.php">Settings</a>
        <?php elseif($_SESSION['user']['rol']==='werkgever'): ?>
          <a href="werkgever.php">Dashboard</a>
          <a href="planner_weekly.php">Weekplanning</a>
          <a href="planner_calendar.php">Calendar</a>
          <a href="sales_dashboard.php">Sales</a>
          <a href="roosters.php">Alle Roosters</a>
        <?php elseif(in_array($_SESSION['user']['rol'], ['sales_manager','sales_agent'], true)): ?>
          <a href="sales_dashboard.php">Sales Dashboard</a>
          <a href="planner_calendar.php">Calendar</a>
          <a href="sales_portal.php">Leads</a>
          <a href="sales_agenda.php">Agenda</a>
          <a href="sales_planning.php">Planning</a>
          <?php if(in_array($_SESSION['user']['rol'], ['sales_manager'], true)): ?>
            <a href="sales_users_management.php">Sales Users</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="werknemer.php">Dashboard</a>
          <a href="planner_weekly.php">Mijn Planning</a>
          <a href="planner_calendar.php">Calendar</a>
          <a href="roosters.php">Mijn Roosters</a>
        <?php endif; ?>
        <a href="notifications.php" class="notif-link">Notifications <?php if($unreadNotifications > 0): ?><span class="notif-badge"><?= (int)$unreadNotifications ?></span><?php endif; ?></a>
        <a href="logout.php">Logout (<?= h($_SESSION['user']['naam']) ?>)</a>
      <?php else: ?>
        <a href="index.php">Inloggen</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
