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

$db->exec("INSERT INTO rollen (id, naam) VALUES (3, 'super_admin') ON DUPLICATE KEY UPDATE naam=VALUES(naam)");

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

if (!table_exists($db, 'sales_leads')) {
  $db->exec("CREATE TABLE sales_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    werkgever_id INT NULL,
    opdrachtgever_id INT NULL,
    titel VARCHAR(190) NOT NULL,
    contact_persoon VARCHAR(190) NULL,
    contact_email VARCHAR(190) NULL,
    contact_telefoon VARCHAR(30) NULL,
    gewenste_datum DATE NULL,
    notities TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'nieuw',
    bevestigd_rooster_id INT NULL,
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

$superHash = password_hash('password123', PASSWORD_DEFAULT);
$roleId = (int)$db->query("SELECT id FROM rollen WHERE naam='super_admin' LIMIT 1")->fetchColumn();
$stmt = $db->prepare("INSERT INTO users (naam,email,wachtwoord,rol_id,actief) VALUES ('Super Admin','admin@profplanner.local',?,?,1) ON DUPLICATE KEY UPDATE wachtwoord=VALUES(wachtwoord), rol_id=VALUES(rol_id), actief=1");
$stmt->execute([$superHash, $roleId]);

$werkgeverRole = (int)$db->query("SELECT id FROM rollen WHERE naam='werkgever' LIMIT 1")->fetchColumn();
$werknemerRole = (int)$db->query("SELECT id FROM rollen WHERE naam='werknemer' LIMIT 1")->fetchColumn();
$defaultWerkgeverId = (int)$db->query("SELECT id FROM users WHERE rol_id = {$werkgeverRole} ORDER BY id LIMIT 1")->fetchColumn();
if ($defaultWerkgeverId > 0) {
  $db->exec("UPDATE users SET werkgever_id = {$defaultWerkgeverId} WHERE rol_id = {$werknemerRole} AND (werkgever_id IS NULL OR werkgever_id = 0)");
}

echo "Super admin migration complete\n";
