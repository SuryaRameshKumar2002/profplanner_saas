<?php
require 'config.php';
require_role('werkgever');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=voltooide_opdrachten.xls");

echo "Datum\tTijd\tTitel\tLocatie\tStatus\n";

$stmt = $db->query("SELECT datum,tijd,titel,locatie,status FROM roosters WHERE status='afgerond' ORDER BY datum DESC");
foreach($stmt as $r){
  echo $r['datum']."\t".$r['tijd']."\t".$r['titel']."\t".$r['locatie']."\t".$r['status']."\n";
}
