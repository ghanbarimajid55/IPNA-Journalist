<?php

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'init.php') {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Load core configuration
require_once BASE_PATH . '/config/config.php';

// Check if database is configured (not placeholder values)
$isInstalled = false;
$dbConfigPath = BASE_PATH . '/config/database.php';
if (file_exists($dbConfigPath)) {
    $dbConfig = require $dbConfigPath;
    if (
        is_array($dbConfig)
        && !empty($dbConfig['host'])
        && !empty($dbConfig['dbname'])
        && !empty($dbConfig['username'])
        && ($dbConfig['host'] !== 'localhost' || $dbConfig['dbname'] !== 'ipna_pms' || !empty($dbConfig['password']))
    ) {
        $isInstalled = true;
    }
}

// If not installed, redirect to installer
if (!$isInstalled) {
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $installPath = defined('BASE_URL') ? BASE_URL . '/install' : '/install';
    if (strpos($requestUri, $installPath) !== 0) {
        header('Location: ' . $installPath . '/');
        exit;
    }
}

// Load all includes
require_once BASE_PATH . '/includes/Database.php';
require_once BASE_PATH . '/includes/Helper.php';
require_once BASE_PATH . '/includes/Auth.php';
require_once BASE_PATH . '/includes/Router.php';
require_once BASE_PATH . '/includes/Middleware.php';

// Global helper functions for views
function url(string $path = ''): string {
    return Helper::url($path);
}
function e(?string $value): string {
    return Helper::e($value);
}
function csrf_token(): string {
    return Auth::generateCsrfToken();
}

// Set timezone to Asia/Tehran
date_default_timezone_set('Asia/Tehran');

// Define today's Jalali date
if ($isInstalled) {
    $todayGregorian = date('Y-m-d');
    [$todayJy, $todayJm, $todayJd] = Helper::gregorianToJalali(
        (int) date('Y'),
        (int) date('n'),
        (int) date('j')
    );
    define('TODAY_JALALI', sprintf('%04d/%02d/%02d', $todayJy, $todayJm, $todayJd));
    define('TODAY_GREGORIAN', $todayGregorian);
}

// Initialize database connection
if ($isInstalled) {
    try {
        Database::getInstance();
    } catch (RuntimeException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        if (php_sapi_name() !== 'cli') {
            http_response_code(500);
            echo '<h1>Database Connection Error</h1><p>Please check your database configuration.</p>';
        }
        exit;
    }
}
