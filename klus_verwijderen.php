<?php
require 'config.php';
require_role('werkgever');

$id = (int)($_GET['id'] ?? 0);
if($id>0){
  $db->prepare("DELETE FROM roosters WHERE id=? AND werkgever_id=?")->execute([$id, (int)$_SESSION['user']['id']]);
}
header("Location: roosters.php");
