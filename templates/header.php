<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ProfPlanner</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <div style="font-weight:800;font-size:18px;">ProfPlanner</div>
      <div class="muted" style="color:#cbd5e1;">Planning & klusbeheer</div>
    </div>
    <div class="nav">
      <a href="index.php">Home</a>
      <?php if(isset($_SESSION['user'])): ?>
        <?php if($_SESSION['user']['rol']==='werkgever'): ?>
          <a href="werkgever.php">Dashboard</a>
        <?php else: ?>
          <a href="werknemer.php">Dashboard</a>
        <?php endif; ?>
        <a href="roosters.php">Roosters</a>
        <a href="logout.php">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="container">
