<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ProfPlanner - Planning & Klusbeheer</title>
<link rel="stylesheet" href="assets/style.css">
<link rel="icon" type="image/png" href="assets/favicon.png">
</head>
<body>
<header class="top">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <a href="index.php" class="brand-link">
      <img src="assets/logo.png" alt="ProfPlanner" class="brand-logo">
      <div class="muted" style="color:#cbd5e1;font-size:13px;">Planning - Roosters - Teams</div>
    </a>
    <div class="nav">
      <?php if(isset($_SESSION['user'])): ?>
        <?php if($_SESSION['user']['rol']==='super_admin'): ?>
          <a href="super_admin.php">Super Admin</a>
          <a href="werkgevers_management.php">Werkgevers</a>
          <a href="sales_dashboard.php">Sales</a>
          <a href="roosters.php">Alle Roosters</a>
          <a href="settings.php">Settings</a>
        <?php elseif($_SESSION['user']['rol']==='werkgever'): ?>
          <a href="werkgever.php">Dashboard</a>
          <a href="planner_weekly.php">Weekplanning</a>
          <a href="sales_dashboard.php">Sales</a>
          <a href="roosters.php">Alle Roosters</a>
        <?php elseif(in_array($_SESSION['user']['rol'], ['sales_manager','sales_agent'], true)): ?>
          <a href="sales_dashboard.php">Sales Dashboard</a>
          <a href="sales_portal.php">Leads</a>
          <a href="sales_agenda.php">Agenda</a>
          <a href="sales_planning.php">Planning</a>
          <?php if(in_array($_SESSION['user']['rol'], ['sales_manager'], true)): ?>
            <a href="sales_users_management.php">Sales Users</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="werknemer.php">Dashboard</a>
          <a href="planner_weekly.php">Mijn Planning</a>
          <a href="roosters.php">Mijn Roosters</a>
        <?php endif; ?>
        <a href="logout.php">Logout (<?= h($_SESSION['user']['naam']) ?>)</a>
      <?php else: ?>
        <a href="index.php">Inloggen</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="container">
