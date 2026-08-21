<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== IPNA PMS Debug Info ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "REQUEST_FILENAME: " . ($_SERVER['REQUEST_FILENAME'] ?? 'N/A') . "\n";
echo "\n--- mod_rewrite test ---\n";
echo "apache_get_modules: " . (function_exists('apache_get_modules') ? (in_array('mod_rewrite', apache_get_modules()) ? 'ENABLED' : 'DISABLED') : 'Cannot check (CGI/FPM mode)') . "\n";
echo "\n--- Files in project root ---\n";
$files = glob(dirname(__DIR__) . '/*');
foreach ($files as $f) {
    echo basename($f) . (is_dir($f) ? '/' : '') . "\n";
}
echo "\n--- config/config.php ---\n";
$configPath = dirname(__DIR__) . '/config/config.php';
if (file_exists($configPath)) {
    echo file_get_contents($configPath);
} else {
    echo "NOT FOUND\n";
}
echo "\n--- config/database.php ---\n";
$dbPath = dirname(__DIR__) . '/config/database.php';
if (file_exists($dbPath)) {
    $db = require $dbPath;
    echo "host: " . ($db['host'] ?? 'N/A') . "\n";
    echo "dbname: " . ($db['dbname'] ?? 'N/A') . "\n";
    echo "username: " . ($db['username'] ?? 'N/A') . "\n";
    echo "password: " . (!empty($db['password']) ? '***SET***' : 'EMPTY') . "\n";
} else {
    echo "NOT FOUND\n";
}
echo "\n--- .installed ---\n";
$marker = dirname(__DIR__) . '/.installed';
echo file_exists($marker) ? 'EXISTS: ' . file_get_contents($marker) : 'NOT FOUND';