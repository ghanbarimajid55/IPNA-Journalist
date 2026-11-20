<?php
/**
 * PHP built-in server router for smoke tests.
 * Serves existing files in public/ directly; everything else goes through the front controller.
 */
$publicDir = __DIR__ . '/../public';
$rootDir = __DIR__ . '/..';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// The installer lives outside public/ and must be executed directly.
if (str_starts_with($uri, '/install')) {
    $installTarget = $rootDir . $uri;
    if (file_exists($installTarget) && is_file($installTarget)) {
        return false;
    }
    if ($uri === '/install' || $uri === '/install/') {
        require $rootDir . '/install/index.php';
        exit;
    }
}

$target = $publicDir . $uri;
if ($uri !== '/' && file_exists($target) && is_file($target)) {
    return false;
}

require $publicDir . '/index.php';
