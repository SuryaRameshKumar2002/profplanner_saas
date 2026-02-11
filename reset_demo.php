<?php
require 'config.php';
deny_in_production('Demo reset');
require_super_admin();

$db->exec("INSERT IGNORE INTO rollen (id, naam) VALUES (1,'werkgever'),(2,'werknemer'),(3,'super_admin')");

$hash = password_hash("123456", PASSWORD_DEFAULT);

$stmt = $db->prepare("
  INSERT INTO users (naam,email,wachtwoord,rol_id)
  VALUES ('Werkgever','werkgever@test.nl',?,1)
  ON DUPLICATE KEY UPDATE wachtwoord=VALUES(wachtwoord), rol_id=1, naam='Werkgever'
");
$stmt->execute([$hash]);

$stmt = $db->prepare("
  INSERT INTO users (naam,email,wachtwoord,rol_id)
  VALUES ('Werknemer','werknemer@test.nl',?,2)
  ON DUPLICATE KEY UPDATE wachtwoord=VALUES(wachtwoord), rol_id=2, naam='Werknemer'
");
$stmt->execute([$hash]);

echo "<pre>";
$row = $db->query("SELECT wachtwoord FROM users WHERE email='werkgever@test.nl'")->fetch(PDO::FETCH_ASSOC);
echo "Verify 123456: ";
var_dump(password_verify("123456", $row['wachtwoord']));
echo "</pre>";
