<?php
require 'config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid date range']);
  exit;
}

$user = current_user();
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();
$events = [];
$lastUpdated = '1970-01-01 00:00:00';

if (($user['rol'] ?? '') === 'werknemer') {
  $stmt = $db->prepare("
    SELECT r.id, r.datum, r.starttijd, r.eindtijd, r.titel, r.locatie, r.status, r.gewijzigd_op,
           b.naam AS bus_naam, b.kleur AS bus_kleur
    FROM roosters r
    LEFT JOIN buses b ON b.id = r.bus_id
    WHERE r.werknemer_id = ?
      AND r.datum >= ?
      AND r.datum < ?
    ORDER BY r.datum, r.starttijd
  ");
  $stmt->execute([(int)$user['id'], $start, $end]);
} elseif ($isSuper) {
  $stmt = $db->prepare("
    SELECT r.id, r.datum, r.starttijd, r.eindtijd, r.titel, r.locatie, r.status, r.gewijzigd_op,
           b.naam AS bus_naam, b.kleur AS bus_kleur, wg.naam AS werkgever_naam
    FROM roosters r
    LEFT JOIN buses b ON b.id = r.bus_id
    LEFT JOIN users wg ON wg.id = r.werkgever_id
    WHERE r.datum >= ?
      AND r.datum < ?
    ORDER BY r.datum, r.starttijd
  ");
  $stmt->execute([$start, $end]);
} else {
  $stmt = $db->prepare("
    SELECT r.id, r.datum, r.starttijd, r.eindtijd, r.titel, r.locatie, r.status, r.gewijzigd_op,
           b.naam AS bus_naam, b.kleur AS bus_kleur
    FROM roosters r
    LEFT JOIN buses b ON b.id = r.bus_id
    WHERE r.werkgever_id = ?
      AND r.datum >= ?
      AND r.datum < ?
    ORDER BY r.datum, r.starttijd
  ");
  $stmt->execute([(int)$werkgeverId, $start, $end]);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
  $title = trim((string)($row['titel'] ?? 'Klus'));
  if (!empty($row['bus_naam'])) {
    $title = $row['bus_naam'] . ' - ' . $title;
  }
  if ($isSuper && !empty($row['werkgever_naam'])) {
    $title .= ' (' . $row['werkgever_naam'] . ')';
  }
  $events[] = [
    'id' => 'job_' . (int)$row['id'],
    'kind' => 'job',
    'title' => $title,
    'date' => $row['datum'],
    'start' => $row['starttijd'] ? substr((string)$row['starttijd'], -8, 5) : '',
    'end' => $row['eindtijd'] ? substr((string)$row['eindtijd'], -8, 5) : '',
    'status' => $row['status'] ?? 'gepland',
    'location' => $row['locatie'] ?? '',
    'color' => $row['bus_kleur'] ?? '#16a34a',
    'link' => 'rooster_detail.php?id=' . (int)$row['id'],
    'updated_at' => $row['gewijzigd_op'] ?? null,
  ];
  if (!empty($row['gewijzigd_op']) && strcmp($row['gewijzigd_op'], $lastUpdated) > 0) {
    $lastUpdated = $row['gewijzigd_op'];
  }
}

if (in_array(($user['rol'] ?? ''), ['sales_manager', 'sales_agent'], true) || is_werkgever() || $isSuper) {
  if ($isSuper) {
    $aStmt = $db->prepare("
      SELECT id, afspraak_datum, klant_achternaam, straatnaam, huisnummer, gemeente, status, gewijzigd_op
      FROM sales_appointments
      WHERE DATE(afspraak_datum) >= ?
        AND DATE(afspraak_datum) < ?
      ORDER BY afspraak_datum
    ");
    $aStmt->execute([$start, $end]);
  } else {
    $aStmt = $db->prepare("
      SELECT id, afspraak_datum, klant_achternaam, straatnaam, huisnummer, gemeente, status, gewijzigd_op
      FROM sales_appointments
      WHERE werkgever_id = ?
        AND DATE(afspraak_datum) >= ?
        AND DATE(afspraak_datum) < ?
      ORDER BY afspraak_datum
    ");
    $aStmt->execute([(int)$werkgeverId, $start, $end]);
  }
  $appointments = $aStmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($appointments as $a) {
    $date = substr((string)$a['afspraak_datum'], 0, 10);
    $time = substr((string)$a['afspraak_datum'], 11, 5);
    $events[] = [
      'id' => 'appointment_' . (int)$a['id'],
      'kind' => 'appointment',
      'title' => 'Sales afspraak: ' . ($a['klant_achternaam'] ?? ''),
      'date' => $date,
      'start' => $time,
      'end' => '',
      'status' => $a['status'] ?? 'gepland',
      'location' => trim(($a['straatnaam'] ?? '') . ' ' . ($a['huisnummer'] ?? '') . ', ' . ($a['gemeente'] ?? '')),
      'color' => '#0ea5e9',
      'link' => 'sales_agenda.php',
      'updated_at' => $a['gewijzigd_op'] ?? null,
    ];
    if (!empty($a['gewijzigd_op']) && strcmp($a['gewijzigd_op'], $lastUpdated) > 0) {
      $lastUpdated = $a['gewijzigd_op'];
    }
  }
}

echo json_encode([
  'ok' => true,
  'events' => $events,
  'last_updated' => $lastUpdated,
], JSON_UNESCAPED_UNICODE);
