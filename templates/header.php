<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ProfPlanner - Planning & Klusbeheer</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <a href="index.php" style="text-decoration:none;color:inherit;">
      <div style="font-weight:800;font-size:20px;color:#fff;">🚌 ProfPlanner</div>
      <div class="muted" style="color:#cbd5e1;font-size:13px;">Planning • Roosters • Teams</div>
    </a>
    <div class="nav">
      <?php if(isset($_SESSION['user'])): ?>
        <?php if($_SESSION['user']['rol']==='werkgever'): ?>
          <a href="werkgever.php">Dashboard</a>
          <a href="planner_weekly.php">Weekplanning</a>
          <a href="roosters.php">Alle Roosters</a>
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
