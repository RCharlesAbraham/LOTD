<?php
/**
 * Check System Requirements API
 * Verifies PHP version, extensions, and server configuration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$checks = [
    'php' => false,
    'pdo' => false,
    'mysql' => false,
    'writable' => false
];

// Check PHP version (7.4+)
$checks['php'] = version_compare(PHP_VERSION, '7.4.0', '>=');

// Check PDO extension
$checks['pdo'] = extension_loaded('pdo') && extension_loaded('pdo_mysql');

// Check MySQL connection (try default localhost)
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    $checks['mysql'] = true;
} catch (PDOException $e) {
    $checks['mysql'] = false;
}

// Check if config directory is writable
$configDir = dirname(__DIR__) . '/../api/config';
$checks['writable'] = is_writable($configDir) || is_writable(dirname($configDir));

// Check if all passed
$all_passed = !in_array(false, $checks);

echo json_encode([
    'success' => true,
    'checks' => $checks,
    'all_passed' => $all_passed,
    'php_version' => PHP_VERSION
]);
