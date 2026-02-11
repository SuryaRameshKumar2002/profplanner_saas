<?php
require 'config.php';
require_any_role(['werkgever', 'super_admin']);
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=voltooide_opdrachten.xls");

echo "Datum\tTijd\tTitel\tLocatie\tStatus\n";

if ($isSuper) {
  $stmt = $db->query("SELECT datum,tijd,titel,locatie,status FROM roosters WHERE status='afgerond' ORDER BY datum DESC");
} else {
  $stmt = $db->prepare("SELECT datum,tijd,titel,locatie,status FROM roosters WHERE status='afgerond' AND werkgever_id=? ORDER BY datum DESC");
  $stmt->execute([$werkgeverId]);
}
foreach($stmt as $r){
  echo $r['datum']."\t".$r['tijd']."\t".$r['titel']."\t".$r['locatie']."\t".$r['status']."\n";
}
