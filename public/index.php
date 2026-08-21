<?php
/**
 * IPNA PMS — Front Controller
 *
 * All HTTP requests are routed through this file.
 */

// ── Base path (needed before init.php loads config/config.php)
define('BASE_PATH', dirname(__DIR__));

// ── Bootstrap ─────────────────────────────────────────────────
require_once BASE_PATH . '/includes/init.php';

// ── Installer shortcut ────────────────────────────────────────
if (isset($isInstalled) && !$isInstalled) {
    if (file_exists(BASE_PATH . '/install/index.php')) {
        require BASE_PATH . '/install/index.php';
    } else {
        http_response_code(503);
        echo '<h1>Installation Required</h1><p>The install directory is missing.</p>';
    }
    exit;
}

// ── Parse the request ─────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = '/' . trim($uri, '/');

// Strip BASE_URL prefix if present (for subdirectory deployments)
$baseUrl = rtrim(BASE_URL, '/');
if ($baseUrl !== '' && strpos($uri, $baseUrl) === 0) {
    $uri = substr($uri, strlen($baseUrl));
}
if ($uri === '' || $uri === '/') {
    $uri = '/';
}

$segments = array_values(array_filter(explode('/', $uri)));

// ── Helper: load controller file and dispatch ─────────────────
function dispatch(string $controllerClass, string $action, array $params = []): void
{
    $relative = str_replace('\\', '/', $controllerClass);
    $file     = BASE_PATH . '/controllers/' . $relative . '.php';

    if (!file_exists($file)) {
        http_response_code(500);
        echo '<h1>500 — Controller Not Found</h1><p>' . htmlspecialchars($controllerClass) . '</p>';
        return;
    }

    require_once $file;

    if (!class_exists($controllerClass)) {
        http_response_code(500);
        echo '<h1>500 — Class Not Found</h1><p>' . htmlspecialchars($controllerClass) . '</p>';
        return;
    }

    $controller = new $controllerClass();

    if (!method_exists($controller, $action)) {
        http_response_code(500);
        echo '<h1>500 — Method Not Found</h1><p>' . htmlspecialchars($action) . '</p>';
        return;
    }

    call_user_func_array([$controller, $action], $params);
}

function notFound(): void
{
    http_response_code(404);
    echo '<h1>404 — Page Not Found</h1>';
    exit;
}

function methodNotAllowed(): void
{
    http_response_code(405);
    echo '<h1>405 — Method Not Allowed</h1>';
    exit;
}

// ── Auth middleware check ─────────────────────────────────────
function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}

function requireAdmin(): void
{
    requireAuth();
    $user = $_SESSION['pms_user'] ?? null;
    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo '<h1>403 — Forbidden</h1>';
        exit;
    }
}

// ── Route matching ────────────────────────────────────────────
//
// 1. Exact matches (static routes) are tried first.
// 2. Parameterised routes use regex with named patterns.
//

$path = $uri;

// ── 1. Exact static routes ────────────────────────────────────
$staticGetRoutes = [
    '/dashboard'                => ['DashboardController', 'index'],
    '/login'                    => ['AuthController', 'login'],
    '/admin'                    => ['Admin\\DashboardController', 'index'],
    '/admin/users'              => ['Admin\\UserController', 'index'],
    '/admin/users/create'       => ['Admin\\UserController', 'create'],
    '/admin/team'               => ['Admin\\TeamController', 'index'],
    '/admin/team/create'        => ['Admin\\TeamController', 'create'],
    '/admin/targets'            => ['Admin\\TargetController', 'index'],
    '/admin/activities'         => ['Admin\\ActivityController', 'index'],
    '/admin/activities/create'  => ['Admin\\ActivityController', 'create'],
    '/admin/activity-types'     => ['Admin\\ActivityTypeController', 'index'],
    '/admin/reports/daily'      => ['Admin\\ReportController', 'daily'],
    '/admin/reports/weekly'     => ['Admin\\ReportController', 'weekly'],
    '/admin/reports/monthly'    => ['Admin\\ReportController', 'monthly'],
    '/admin/settings'           => ['Admin\\SettingsController', 'index'],
    '/admin/import'             => ['Admin\\ImportController', 'index'],
    '/reporter/activities'      => ['Reporter\\ActivityController', 'index'],
    '/reporter/activities/create' => ['Reporter\\ActivityController', 'create'],
    '/reporter/targets'         => ['Reporter\\TargetController', 'index'],
    '/reporter/reports/weekly'  => ['Reporter\\ReportController', 'weekly'],
    '/reporter/reports/monthly' => ['Reporter\\ReportController', 'monthly'],
    '/photographer/activities'       => ['Photographer\\ActivityController', 'index'],
    '/photographer/activities/create' => ['Photographer\\ActivityController', 'create'],
    '/photographer/targets'          => ['Photographer\\TargetController', 'index'],
    '/photographer/reports/weekly'   => ['Photographer\\ReportController', 'weekly'],
    '/photographer/reports/monthly'  => ['Photographer\\ReportController', 'monthly'],
    '/api/dashboard-stats'      => ['Api\\DashboardController', 'stats'],
    '/api/activities'           => ['Api\\ActivityController', 'index'],
    '/api/team-ranking'         => ['Api\\TeamController', 'ranking'],
];

$staticPostRoutes = [
    '/login'                          => ['AuthController', 'login'],
    '/logout'                         => ['AuthController', 'logout'],
    '/admin/users/store'              => ['Admin\\UserController', 'store'],
    '/admin/team/store'               => ['Admin\\TeamController', 'store'],
    '/admin/targets/store'            => ['Admin\\TargetController', 'store'],
    '/admin/targets/bulk-update'      => ['Admin\\TargetController', 'bulkUpdate'],
    '/admin/activities/store'         => ['Admin\\ActivityController', 'store'],
    '/admin/activity-types/store'     => ['Admin\\ActivityTypeController', 'store'],
    '/admin/settings/update'          => ['Admin\\SettingsController', 'update'],
    '/admin/import/process'           => ['Admin\\ImportController', 'process'],
    '/reporter/activities/store'      => ['Reporter\\ActivityController', 'store'],
    '/photographer/activities/store'  => ['Photographer\\ActivityController', 'store'],
];

// Root redirect
if ($path === '/' || $path === '/index.php') {
    if (isset($isInstalled) && $isInstalled) {
        header('Location: ' . BASE_URL . '/dashboard');
    } else {
        header('Location: ' . BASE_URL . '/login');
    }
    exit;
}

// Check static GET
if ($method === 'GET' && isset($staticGetRoutes[$path])) {
    [$class, $action] = $staticGetRoutes[$path];
    dispatch($class, $action);
    exit;
}

// Check static POST
if ($method === 'POST' && isset($staticPostRoutes[$path])) {
    [$class, $action] = $staticPostRoutes[$path];
    dispatch($class, $action);
    exit;
}

// ── 2. Parameterised routes (regex matching) ─────────────────
//
// Each pattern is matched against the full path.
// Capture groups named {id} and {type} are passed as params.
//

$parameterisedRoutes = [
    'GET'  => [
        '#^/admin/users/(?P<id>\d+)/edit$#'  => ['Admin\\UserController', 'edit'],
        '#^/admin/team/(?P<id>\d+)/edit$#'   => ['Admin\\TeamController', 'edit'],
        '#^/admin/activities/(?P<id>\d+)/edit$#' => ['Admin\\ActivityController', 'edit'],
        '#^/admin/activity-types/(?P<id>\d+)/edit$#' => ['Admin\\ActivityTypeController', 'edit'],
        '#^/admin/export/(?P<type>[a-zA-Z0-9_-]+)$#' => ['Admin\\ExportController', 'export'],
        '#^/reporter/activities/(?P<id>\d+)/edit$#' => ['Reporter\\ActivityController', 'edit'],
        '#^/photographer/activities/(?P<id>\d+)/edit$#' => ['Photographer\\ActivityController', 'edit'],
    ],
    'POST' => [
        '#^/admin/users/(?P<id>\d+)/update$#'       => ['Admin\\UserController', 'update'],
        '#^/admin/users/(?P<id>\d+)/delete$#'       => ['Admin\\UserController', 'delete'],
        '#^/admin/team/(?P<id>\d+)/update$#'        => ['Admin\\TeamController', 'update'],
        '#^/admin/team/(?P<id>\d+)/delete$#'        => ['Admin\\TeamController', 'delete'],
        '#^/admin/activities/(?P<id>\d+)/update$#'  => ['Admin\\ActivityController', 'update'],
        '#^/admin/activities/(?P<id>\d+)/delete$#'  => ['Admin\\ActivityController', 'delete'],
        '#^/admin/activity-types/(?P<id>\d+)/update$#' => ['Admin\\ActivityTypeController', 'update'],
        '#^/admin/activity-types/(?P<id>\d+)/delete$#' => ['Admin\\ActivityTypeController', 'delete'],
        '#^/admin/activity-types/update-score/(?P<id>\d+)$#' => ['Admin\\ActivityTypeController', 'updateScore'],
        '#^/admin/activity-types/toggle-status/(?P<id>\d+)$#' => ['Admin\\ActivityTypeController', 'toggleStatus'],
        '#^/admin/targets/update/(?P<userId>\d+)$#' => ['Admin\\TargetController', 'updateTarget'],
        '#^/reporter/activities/(?P<id>\d+)/update$#'  => ['Reporter\\ActivityController', 'update'],
        '#^/reporter/activities/(?P<id>\d+)/delete$#'  => ['Reporter\\ActivityController', 'delete'],
        '#^/photographer/activities/(?P<id>\d+)/update$#' => ['Photographer\\ActivityController', 'update'],
        '#^/photographer/activities/(?P<id>\d+)/delete$#' => ['Photographer\\ActivityController', 'delete'],
    ],
];

if (isset($parameterisedRoutes[$method])) {
    foreach ($parameterisedRoutes[$method] as $pattern => $handler) {
        if (preg_match($pattern, $path, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $handler;
            dispatch($class, $action, $params);
            exit;
        }
    }
}

// ── No route matched — determine 404 vs 405 ──────────────────
//
// If a route exists for the opposite method, return 405.
//

$allGetPaths  = array_keys($staticGetRoutes);
$allPostPaths = array_keys($staticPostRoutes);

$allParamGetPatterns  = array_keys($parameterisedRoutes['GET'] ?? []);
$allParamPostPatterns = array_keys($parameterisedRoutes['POST'] ?? []);

$isKnownPath = in_array($path, array_merge($allGetPaths, $allPostPaths), true);

if (!$isKnownPath) {
    foreach ($allParamGetPatterns as $p) {
        if (preg_match($p, $path)) { $isKnownPath = true; break; }
    }
}
if (!$isKnownPath) {
    foreach ($allParamPostPatterns as $p) {
        if (preg_match($p, $path)) { $isKnownPath = true; break; }
    }
}

if ($isKnownPath) {
    methodNotAllowed();
}

notFound();
