<?php
require __DIR__ . '/config.php';
deny_in_production('Local diagnostic check');
header('Content-Type: text/plain; charset=utf-8');

echo "ProfPlanner local check\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Script: " . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";

echo "\nPHP execution: OK\n";

try {
    $db->query('SELECT 1');
    echo "DB connection: OK\n";
} catch (Throwable $e) {
    echo "DB connection: FAIL\n";
    echo "Reason: " . $e->getMessage() . "\n";
}
