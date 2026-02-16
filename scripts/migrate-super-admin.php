<?php
if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit('CLI only');
}

require __DIR__ . '/../config.php';

function col_exists(PDO $db, string $table, string $column): bool {
  $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
  $stmt->execute([$column]);
  return (bool)$stmt->fetchColumn();
}

function table_exists(PDO $db, string $table): bool {
  $stmt = $db->prepare("SHOW TABLES LIKE ?");
  $stmt->execute([$table]);
  return (bool)$stmt->fetchColumn();
}

$db->exec("INSERT INTO rollen (id, naam) VALUES (3, 'super_admin'), (4, 'sales_manager'), (5, 'sales_agent') ON DUPLICATE KEY UPDATE naam=VALUES(naam)");

$db->exec("INSERT INTO rollen (id, naam) VALUES (1, 'werkgever'), (2, 'werknemer') ON DUPLICATE KEY UPDATE naam=VALUES(naam)");

if (!col_exists($db, 'users', 'werkgever_id')) {
  $db->exec("ALTER TABLE users ADD COLUMN werkgever_id INT NULL AFTER rol_id");
}
if (!col_exists($db, 'users', 'actief')) {
  $db->exec("ALTER TABLE users ADD COLUMN actief BOOLEAN NOT NULL DEFAULT TRUE AFTER telefoonnummer");
}

if (!col_exists($db, 'opdrachtgevers', 'werkgever_id')) {
  $db->exec("ALTER TABLE opdrachtgevers ADD COLUMN werkgever_id INT NULL AFTER id");
}
if (!col_exists($db, 'buses', 'werkgever_id')) {
  $db->exec("ALTER TABLE buses ADD COLUMN werkgever_id INT NULL AFTER id");
}
if (!col_exists($db, 'buses', 'image_path')) {
  $db->exec("ALTER TABLE buses ADD COLUMN image_path VARCHAR(255) NULL AFTER kleur");
}

if (!table_exists($db, 'sales_leads')) {
  $db->exec("CREATE TABLE sales_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    werkgever_id INT NULL,
    sales_user_id INT NULL,
    opdrachtgever_id INT NULL,
    gemeente VARCHAR(120) NOT NULL DEFAULT '',
    straatnaam VARCHAR(190) NOT NULL DEFAULT '',
    huisnummer VARCHAR(30) NOT NULL DEFAULT '',
    voornaam VARCHAR(120) NOT NULL DEFAULT '',
    achternaam VARCHAR(120) NOT NULL DEFAULT '',
    telefoonnummer VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    bereikbaar_via VARCHAR(80) NULL,
    afspraak_datum DATETIME NULL,
    adviesgesprek_gepland BOOLEAN NOT NULL DEFAULT FALSE,
    titel VARCHAR(190) NOT NULL DEFAULT 'Nieuwe lead',
    notities TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'nieuw',
    bevestigd_rooster_id INT NULL,
    gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

$salesLeadCols = [
  "sales_user_id INT NULL AFTER werkgever_id",
  "gemeente VARCHAR(120) NOT NULL DEFAULT '' AFTER opdrachtgever_id",
  "straatnaam VARCHAR(190) NOT NULL DEFAULT '' AFTER gemeente",
  "huisnummer VARCHAR(30) NOT NULL DEFAULT '' AFTER straatnaam",
  "voornaam VARCHAR(120) NOT NULL DEFAULT '' AFTER huisnummer",
  "achternaam VARCHAR(120) NOT NULL DEFAULT '' AFTER voornaam",
  "telefoonnummer VARCHAR(30) NULL AFTER achternaam",
  "email VARCHAR(190) NULL AFTER telefoonnummer",
  "bereikbaar_via VARCHAR(80) NULL AFTER email",
  "afspraak_datum DATETIME NULL AFTER bereikbaar_via",
  "adviesgesprek_gepland BOOLEAN NOT NULL DEFAULT FALSE AFTER afspraak_datum"
];
foreach ($salesLeadCols as $colDef) {
  $parts = explode(' ', trim($colDef), 2);
  $colName = $parts[0];
  if (!col_exists($db, 'sales_leads', $colName)) {
    $db->exec("ALTER TABLE sales_leads ADD COLUMN {$colDef}");
  }
}

if (!table_exists($db, 'sales_appointments')) {
  $db->exec("CREATE TABLE sales_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    werkgever_id INT NULL,
    sales_user_id INT NULL,
    lead_id INT NULL,
    gemeente VARCHAR(120) NOT NULL,
    straatnaam VARCHAR(190) NOT NULL,
    huisnummer VARCHAR(30) NOT NULL,
    klant_achternaam VARCHAR(120) NOT NULL,
    email VARCHAR(190) NULL,
    telefoonnummer VARCHAR(30) NULL,
    afspraak_datum DATETIME NOT NULL,
    bijzonderheden TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'gepland',
    gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if (!table_exists($db, 'sales_planning_visits')) {
  $db->exec("CREATE TABLE sales_planning_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    werkgever_id INT NULL,
    sales_user_id INT NULL,
    lead_id INT NULL,
    gemeente VARCHAR(120) NOT NULL,
    straatnaam VARCHAR(190) NOT NULL,
    huisnummer VARCHAR(30) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'GEPLAND',
    gepland_op DATE NULL,
    bezocht_op DATETIME NULL,
    notities TEXT NULL,
    gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if (!table_exists($db, 'audit_logs')) {
  $db->exec("CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT NULL,
    actie VARCHAR(80) NOT NULL,
    doel_type VARCHAR(80) NULL,
    doel_id INT NULL,
    metadata TEXT NULL,
    gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if (!table_exists($db, 'notifications')) {
  $db->exec("CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_user_id INT NULL,
    recipient_user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'general',
    title VARCHAR(190) NOT NULL,
    message TEXT NULL,
    link VARCHAR(255) NULL,
    gelezen_op DATETIME NULL,
    gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_recipient (recipient_user_id),
    INDEX idx_notifications_read (gelezen_op)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if (!table_exists($db, 'login_attempts')) {
  $db->exec("CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    success BOOLEAN NOT NULL DEFAULT FALSE,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_email_ip_time (email, ip, attempted_at),
    INDEX idx_login_attempts_time (attempted_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
$superHash = password_hash('Pp!Sup3rAdm1n#2026', PASSWORD_DEFAULT);
$roleId = (int)$db->query("SELECT id FROM rollen WHERE naam='super_admin' LIMIT 1")->fetchColumn();
$stmt = $db->prepare("INSERT INTO users (naam,email,wachtwoord,rol_id,actief) VALUES ('Super Admin','superadmin@profplanner.app',?,?,1) ON DUPLICATE KEY UPDATE wachtwoord=VALUES(wachtwoord), rol_id=VALUES(rol_id), actief=1");
$stmt->execute([$superHash, $roleId]);

$werkgeverRole = (int)$db->query("SELECT id FROM rollen WHERE naam='werkgever' LIMIT 1")->fetchColumn();
$werknemerRole = (int)$db->query("SELECT id FROM rollen WHERE naam='werknemer' LIMIT 1")->fetchColumn();

$employerHash = password_hash('Pp!Werkg3ver#2026', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (naam,email,wachtwoord,rol_id,actief) VALUES ('Employer Account','werkgever@profplanner.app',?,?,1) ON DUPLICATE KEY UPDATE naam=VALUES(naam), wachtwoord=VALUES(wachtwoord), rol_id=VALUES(rol_id), actief=1");
$stmt->execute([$employerHash, $werkgeverRole]);

$defaultWerkgeverId = (int)$db->query("SELECT id FROM users WHERE email = 'werkgever@profplanner.app' LIMIT 1")->fetchColumn();

$employeeHash = password_hash('Pp!Werkn3mer#2026', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (naam,email,wachtwoord,rol_id,werkgever_id,actief) VALUES ('Employee Account','werknemer@profplanner.app',?,?,?,1) ON DUPLICATE KEY UPDATE naam=VALUES(naam), wachtwoord=VALUES(wachtwoord), rol_id=VALUES(rol_id), werkgever_id=VALUES(werkgever_id), actief=1");
$stmt->execute([$employeeHash, $werknemerRole, ($defaultWerkgeverId > 0 ? $defaultWerkgeverId : null)]);

if ($defaultWerkgeverId > 0) {
  $db->exec("UPDATE users SET werkgever_id = {$defaultWerkgeverId} WHERE rol_id = {$werknemerRole} AND (werkgever_id IS NULL OR werkgever_id = 0)");
}

$salesManagerRole = (int)$db->query("SELECT id FROM rollen WHERE naam='sales_manager' LIMIT 1")->fetchColumn();
$salesAgentRole = (int)$db->query("SELECT id FROM rollen WHERE naam='sales_agent' LIMIT 1")->fetchColumn();

$salesManagerHash = password_hash('Pp!SalesMng#2026', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (naam,email,wachtwoord,rol_id,werkgever_id,actief) VALUES ('Sales Manager','salesmanager@profplanner.app',?,?,?,1) ON DUPLICATE KEY UPDATE wachtwoord=VALUES(wachtwoord), rol_id=VALUES(rol_id), werkgever_id=VALUES(werkgever_id), actief=1");
$stmt->execute([$salesManagerHash, $salesManagerRole, ($defaultWerkgeverId > 0 ? $defaultWerkgeverId : null)]);

$salesAgentHash = password_hash('Pp!SalesAg3nt#2026', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (naam,email,wachtwoord,rol_id,werkgever_id,actief) VALUES ('Sales Agent','salesagent@profplanner.app',?,?,?,1) ON DUPLICATE KEY UPDATE wachtwoord=VALUES(wachtwoord), rol_id=VALUES(rol_id), werkgever_id=VALUES(werkgever_id), actief=1");
$stmt->execute([$salesAgentHash, $salesAgentRole, ($defaultWerkgeverId > 0 ? $defaultWerkgeverId : null)]);

echo "Super admin migration complete\n";
