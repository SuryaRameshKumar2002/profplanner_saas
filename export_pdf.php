<?php
require 'config.php';
require_any_role(['werkgever', 'super_admin']);
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();

if ($isSuper) {
  $stmt = $db->query("SELECT datum,tijd,titel,locatie,status FROM roosters WHERE status='afgerond' ORDER BY datum DESC");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $stmt = $db->prepare("SELECT datum,tijd,titel,locatie,status FROM roosters WHERE status='afgerond' AND werkgever_id=? ORDER BY datum DESC");
  $stmt->execute([$werkgeverId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Simpele printbare HTML (klant kan "Print to PDF" gebruiken).
header("Content-Type: text/html; charset=utf-8");
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Voltooide opdrachten</title>
<style>
body{font-family:Arial;margin:30px}
h1{margin:0 0 15px}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ddd;padding:8px;font-size:12px}
th{background:#f3f4f6}
</style>
</head>
<body>
<h1>Voltooide opdrachten</h1>
<p>Exporteer via je browser: <b>Bestand → Afdrukken → Opslaan als PDF</b>.</p>
<table>
<thead><tr><th>Datum</th><th>Tijd</th><th>Titel</th><th>Locatie</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?= htmlspecialchars($r['datum']) ?></td>
<td><?= htmlspecialchars($r['tijd']) ?></td>
<td><?= htmlspecialchars($r['titel']) ?></td>
<td><?= htmlspecialchars($r['locatie']) ?></td>
<td><?= htmlspecialchars($r['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<script>window.print();</script>
</body>
</html>
