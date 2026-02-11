<?php
session_start();

/**
 * Database connectie (macOS: 127.0.0.1 i.p.v. localhost)
 */
$db = new PDO(
  "mysql:host=127.0.0.1;dbname=profplanner;charset=utf8mb4",
  "root",
  ""
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * Login verplicht
 */
function require_login(): void {
  if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
  }
}

/**
 * Rol verplicht (gebruikt sessie 'rol' zoals in login.php)
 */
function require_role(string $rol): void {
  require_login();
  if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] !== $rol) {
    header("Location: index.php");
    exit;
  }
}

/**
 * Null-safe HTML escapen
 */
function h($value): string {
  return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
