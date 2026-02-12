<?php
ini_set('session.use_strict_mode', '1');
session_start();

/**
 * Database connectie.
 * Via omgevingsvariabelen aanpasbaar voor productie/hosting:
 * PP_DB_HOST, PP_DB_NAME, PP_DB_USER, PP_DB_PASS
 */
$dbHost = getenv('PP_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('PP_DB_NAME') ?: 'profplanner';
$dbUser = getenv('PP_DB_USER') ?: 'root';
$dbPass = getenv('PP_DB_PASS');
if ($dbPass === false) {
  $dbPass = '';
}

$db = new PDO(
  "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
  $dbUser,
  $dbPass
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
 * Een van meerdere rollen verplicht.
 */
function require_any_role(array $rollen): void {
  require_login();
  $current = $_SESSION['user']['rol'] ?? '';
  if (!in_array($current, $rollen, true)) {
    header("Location: index.php");
    exit;
  }
}

/**
 * Super admin verplicht.
 */
function require_super_admin(): void {
  require_role('super_admin');
}

/**
 * Ingelogde gebruiker ophalen.
 */
function current_user(): array {
  return $_SESSION['user'] ?? [];
}

function is_super_admin(): bool {
  return (current_user()['rol'] ?? '') === 'super_admin';
}

function is_werkgever(): bool {
  return (current_user()['rol'] ?? '') === 'werkgever';
}

function is_werknemer(): bool {
  return (current_user()['rol'] ?? '') === 'werknemer';
}

function is_sales_manager(): bool {
  return (current_user()['rol'] ?? '') === 'sales_manager';
}

function is_sales_agent(): bool {
  return (current_user()['rol'] ?? '') === 'sales_agent';
}

function is_sales_user(): bool {
  $rol = current_user()['rol'] ?? '';
  return in_array($rol, ['sales_manager', 'sales_agent'], true);
}

/**
 * Werkgever context voor datascoping.
 * - werkgever: eigen user id
 * - werknemer: gekoppelde werkgever_id
 * - super_admin: null (ziet alles)
 */
function current_werkgever_id(): ?int {
  $user = current_user();
  $rol = $user['rol'] ?? '';
  if ($rol === 'werkgever') {
    return (int)$user['id'];
  }
  if (in_array($rol, ['werknemer', 'sales_manager', 'sales_agent'], true) && isset($user['werkgever_id'])) {
    return (int)$user['werkgever_id'];
  }
  return null;
}

function require_sales_access(): void {
  require_any_role(['super_admin', 'sales_manager', 'sales_agent', 'werkgever']);
}

function can_manage_sales_users(): bool {
  return is_super_admin() || is_sales_manager() || is_werkgever();
}

/**
 * Productie beveiliging voor debug/demo endpoints.
 * Zet PP_ALLOW_DIAGNOSTICS=1 om lokaal toe te staan.
 */
function deny_in_production(string $featureName = 'Deze actie'): void {
  $allow = getenv('PP_ALLOW_DIAGNOSTICS');
  if ($allow !== '1') {
    http_response_code(403);
    echo "<h2>403</h2><p>" . h($featureName) . " is uitgeschakeld.</p>";
    exit;
  }
}

/**
 * Eenvoudige auditlog voor beheeracties.
 */
function audit_log(PDO $db, string $actie, ?string $doelType = null, ?int $doelId = null, ?string $metadata = null): void {
  try {
    $actor = (int)(current_user()['id'] ?? 0) ?: null;
    $stmt = $db->prepare("
      INSERT INTO audit_logs (actor_user_id, actie, doel_type, doel_id, metadata)
      VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$actor, $actie, $doelType, $doelId, $metadata]);
  } catch (Throwable $e) {
    // Audit logging should never break the main flow.
  }
}

/**
 * Null-safe HTML escapen
 */
function h($value): string {
  return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
