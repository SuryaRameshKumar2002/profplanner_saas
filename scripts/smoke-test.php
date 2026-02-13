<?php
if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  echo "CLI only";
  exit;
}

require __DIR__ . '/../config.php';

$requiredTables = [
  'rollen',
  'users',
  'opdrachtgevers',
  'buses',
  'roosters',
  'werknemers_buses',
  'afwezigheden',
  'uploads',
  'sales_leads',
  'sales_appointments',
  'sales_planning_visits',
  'audit_logs',
  'notifications'
];

$missing = [];
foreach ($requiredTables as $table) {
  $stmt = $db->prepare("SHOW TABLES LIKE ?");
  $stmt->execute([$table]);
  if (!$stmt->fetchColumn()) {
    $missing[] = $table;
  }
}

if ($missing) {
  fwrite(STDERR, "Missing tables: " . implode(', ', $missing) . PHP_EOL);
  exit(1);
}

$users = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$jobs = (int)$db->query("SELECT COUNT(*) FROM roosters")->fetchColumn();
$buses = (int)$db->query("SELECT COUNT(*) FROM buses")->fetchColumn();
$leads = (int)$db->query("SELECT COUNT(*) FROM sales_leads")->fetchColumn();
$appointments = (int)$db->query("SELECT COUNT(*) FROM sales_appointments")->fetchColumn();
$visits = (int)$db->query("SELECT COUNT(*) FROM sales_planning_visits")->fetchColumn();
$notifications = (int)$db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();

echo "Schema OK\n";
echo "Users: $users\n";
echo "Roosters: $jobs\n";
echo "Buses: $buses\n";
echo "Leads: $leads\n";
echo "Appointments: $appointments\n";
echo "Planning visits: $visits\n";
echo "Notifications: $notifications\n";
