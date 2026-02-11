<?php
require 'config.php';
require_role('werknemer');

$id = (int)($_GET['id'] ?? 0);
if($id<=0){ header("Location: roosters.php"); exit; }

$status = $_POST['status'] ?? 'gepland';
$toelichting = $_POST['toelichting'] ?? '';
$extra = $_POST['extra_werkzaamheden'] ?? '';

$stmt = $db->prepare("
  UPDATE roosters
  SET status=?, toelichting=?, extra_werkzaamheden=?
  WHERE id=? AND werknemer_id=?
");
$stmt->execute([$status, $toelichting, $extra, $id, $_SESSION['user']['id']]);

header("Location: rooster_detail.php?id=".$id);
