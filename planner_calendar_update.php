<?php
require 'config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid payload']);
  exit;
}

$eventId = (string)($payload['event_id'] ?? '');
$newDate = trim((string)($payload['date'] ?? ''));
$start = trim((string)($payload['start'] ?? ''));
$end = trim((string)($payload['end'] ?? ''));
$status = trim((string)($payload['status'] ?? ''));
$werknemerId = isset($payload['werknemer_id']) ? (int)$payload['werknemer_id'] : null;
$busId = isset($payload['bus_id']) ? (int)$payload['bus_id'] : null;

if (!preg_match('/^(job|appointment)_\d+$/', $eventId)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid event id']);
  exit;
}
if ($newDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid date']);
  exit;
}
if ($start !== '' && !preg_match('/^\d{2}:\d{2}$/', $start)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid start']);
  exit;
}
if ($end !== '' && !preg_match('/^\d{2}:\d{2}$/', $end)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid end']);
  exit;
}

$user = current_user();
$isSuper = is_super_admin();
$isWerkgever = is_werkgever();
$isSales = is_sales_user();

[$kind, $idRaw] = explode('_', $eventId, 2);
$id = (int)$idRaw;

if ($kind === 'job') {
  if (!$isSuper && !$isWerkgever) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No permission']);
    exit;
  }
  $jobStmt = $db->prepare("SELECT id, werkgever_id FROM roosters WHERE id = ? LIMIT 1");
  $jobStmt->execute([$id]);
  $job = $jobStmt->fetch(PDO::FETCH_ASSOC);
  if (!$job) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Job not found']);
    exit;
  }
  if (!$isSuper && (int)$job['werkgever_id'] !== (int)$user['id']) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No access to this job']);
    exit;
  }

  $fields = [];
  $params = [];

  if ($newDate !== '') {
    $fields[] = 'datum = ?';
    $params[] = $newDate;
  }
  if ($start !== '') {
    $fields[] = 'starttijd = ?';
    $fields[] = 'tijd = ?';
    $params[] = $start . ':00';
    $params[] = $start . ':00';
  }
  if ($end !== '') {
    $fields[] = 'eindtijd = ?';
    $params[] = $end . ':00';
  }
  if ($status !== '') {
    $allowedStatus = ['gepland', 'afgerond', 'afgebroken', 'verzet'];
    if (in_array(strtolower($status), $allowedStatus, true)) {
      $fields[] = 'status = ?';
      $params[] = strtolower($status);
    }
  }
  if ($werknemerId !== null && $werknemerId > 0) {
    $fields[] = 'werknemer_id = ?';
    $params[] = $werknemerId;
  }
  if ($busId !== null) {
    $fields[] = 'bus_id = ?';
    $params[] = ($busId > 0 ? $busId : null);
  }

  if (!$fields) {
    echo json_encode(['ok' => true, 'updated' => false]);
    exit;
  }

  $params[] = $id;
  $sql = "UPDATE roosters SET " . implode(', ', $fields) . " WHERE id = ?";
  $stmt = $db->prepare($sql);
  $stmt->execute($params);

  notify_for_scope(
    $db,
    'calendar_job_updated',
    'Planning bijgewerkt',
    'Een klus is bijgewerkt via kalender.',
    'rooster_detail.php?id=' . $id
  );

  echo json_encode(['ok' => true, 'updated' => true]);
  exit;
}

if ($kind === 'appointment') {
  if (!$isSuper && !$isWerkgever && !$isSales) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No permission']);
    exit;
  }

  $apStmt = $db->prepare("SELECT id, werkgever_id, afspraak_datum FROM sales_appointments WHERE id = ? LIMIT 1");
  $apStmt->execute([$id]);
  $ap = $apStmt->fetch(PDO::FETCH_ASSOC);
  if (!$ap) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Appointment not found']);
    exit;
  }

  $scopeWerkgeverId = current_werkgever_id();
  if (!$isSuper && $scopeWerkgeverId !== null && (int)$ap['werkgever_id'] !== (int)$scopeWerkgeverId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No access to this appointment']);
    exit;
  }

  $oldDateTime = (string)$ap['afspraak_datum'];
  $oldTime = substr($oldDateTime, 11, 5);
  $setDate = ($newDate !== '' ? $newDate : substr($oldDateTime, 0, 10));
  $setTime = ($start !== '' ? $start : $oldTime);
  $newDateTime = $setDate . ' ' . $setTime . ':00';

  $fields = ['afspraak_datum = ?'];
  $params = [$newDateTime];
  if ($status !== '') {
    $fields[] = 'status = ?';
    $params[] = $status;
  }
  $params[] = $id;
  $sql = "UPDATE sales_appointments SET " . implode(', ', $fields) . " WHERE id = ?";
  $stmt = $db->prepare($sql);
  $stmt->execute($params);

  notify_for_scope(
    $db,
    'calendar_appointment_updated',
    'Sales afspraak bijgewerkt',
    'Een sales afspraak is bijgewerkt via kalender.',
    'sales_agenda.php'
  );

  echo json_encode(['ok' => true, 'updated' => true]);
  exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unsupported event']);
