<?php
/**
 * IPNA PMS — Front Controller
 *
 * All HTTP requests are routed through this file.
 */

// ── Base path (needed before init.php loads config/config.php)
define('BASE_PATH', dirname(__DIR__));

// Allow Apache SetEnv to provide BASE_URL before config.php exists,
// which is needed when the app is installed in a subdirectory and the
// installer redirect must use the correct path. Only infer it when the
// generated config file is not present, because config.php defines BASE_URL
// itself.
if (!defined('BASE_URL') && !file_exists(BASE_PATH . '/config/config.php')) {
    if (!empty($_SERVER['BASE_URL'])) {
        define('BASE_URL', rtrim($_SERVER['BASE_URL'], '/'));
    } elseif (!empty($_SERVER['SCRIPT_NAME']) && str_ends_with($_SERVER['SCRIPT_NAME'], '/public/index.php')) {
        $base = substr($_SERVER['SCRIPT_NAME'], 0, -strlen('/public/index.php'));
        define('BASE_URL', $base !== '' ? $base : '');
    }
}

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
// Controller classes are autoloaded from controllers/ (see includes/init.php),
// e.g. Admin_UserController -> controllers/Admin/UserController.php
function dispatch(string $controllerClass, string $action, array $params = []): void
{
    if (!class_exists($controllerClass)) {
        http_response_code(500);
        echo '<h1>500 — Controller Not Found</h1><p>' . htmlspecialchars($controllerClass) . '</p>';
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

// ── CSRF protection (Phase 1) ─────────────────────────────────
// Verified for every non-GET/HEAD request before dispatch.
// Token is read from the form body (csrf_token) or the X-CSRF-TOKEN header.
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!Auth::verifyCsrfToken($token)) {
        http_response_code(403);
        echo '<h1>403 — Invalid CSRF token</h1>';
        exit;
    }
}

// ── Auth middleware check ─────────────────────────────────────
function requireAuth(): void
{
    if (!Auth::check()) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}

function requireAdmin(): void
{
    requireAuth();
    if (Auth::role() !== 'admin') {
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

// ── CSRF: enforce for all state-changing methods ──────────────
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    verifyCsrf();
}

// ── 1. Exact static routes ────────────────────────────────────
$staticGetRoutes = [
    '/dashboard'                => ['DashboardController', 'index'],
    '/login'                    => ['AuthController', 'login'],
    '/admin'                    => ['Admin_DashboardController', 'index'],
    '/admin/users'              => ['Admin_UserController', 'index'],
    '/admin/users/create'       => ['Admin_UserController', 'create'],
    '/admin/team'               => ['Admin_TeamController', 'index'],
    '/admin/team/create'        => ['Admin_TeamController', 'create'],
    '/admin/targets'            => ['Admin_TargetController', 'index'],
    '/admin/activities'         => ['Admin_ActivityController', 'index'],
    '/admin/activities/create'  => ['Admin_ActivityController', 'create'],
    '/admin/activity-types'     => ['Admin_ActivityTypeController', 'index'],
    '/admin/reports/daily'      => ['Admin_ReportController', 'daily'],
    '/admin/reports/weekly'     => ['Admin_ReportController', 'weekly'],
    '/admin/reports/monthly'    => ['Admin_ReportController', 'monthly'],
    '/admin/settings'           => ['Admin_SettingsController', 'index'],
    '/admin/import'             => ['Admin_ImportController', 'index'],
    '/reporter/activities'      => ['Reporter_ActivityController', 'index'],
    '/reporter/activities/create' => ['Reporter_ActivityController', 'create'],
    '/reporter/targets'         => ['Reporter_TargetController', 'index'],
    '/reporter/reports/weekly'  => ['Reporter_ReportController', 'weekly'],
    '/reporter/reports/monthly' => ['Reporter_ReportController', 'monthly'],
    '/photographer/activities'       => ['Photographer_ActivityController', 'index'],
    '/photographer/activities/create' => ['Photographer_ActivityController', 'create'],
    '/photographer/targets'          => ['Photographer_TargetController', 'index'],
    '/photographer/reports/weekly'   => ['Photographer_ReportController', 'weekly'],
    '/photographer/reports/monthly'  => ['Photographer_ReportController', 'monthly'],
    '/api/dashboard-stats'      => ['Api_DashboardController', 'stats'],
    '/api/activities'           => ['Api_ActivityController', 'index'],
    '/api/team-ranking'         => ['Api_TeamController', 'ranking'],
];

$staticPostRoutes = [
    '/login'                          => ['AuthController', 'login'],
    '/logout'                         => ['AuthController', 'logout'],
    '/admin/users/store'              => ['Admin_UserController', 'store'],
    '/admin/team/store'               => ['Admin_TeamController', 'store'],
    '/admin/targets/store'            => ['Admin_TargetController', 'store'],
    '/admin/targets/bulk-update'      => ['Admin_TargetController', 'bulkUpdate'],
    '/admin/activities/store'         => ['Admin_ActivityController', 'store'],
    '/admin/activity-types/store'     => ['Admin_ActivityTypeController', 'store'],
    '/admin/settings/update'          => ['Admin_SettingsController', 'update'],
    '/admin/import/process'           => ['Admin_ImportController', 'process'],
    '/reporter/activities/store'      => ['Reporter_ActivityController', 'store'],
    '/photographer/activities/store'  => ['Photographer_ActivityController', 'store'],
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
        '#^/admin/users/(?P<id>\d+)/edit$#'  => ['Admin_UserController', 'edit'],
        '#^/admin/team/(?P<id>\d+)/edit$#'   => ['Admin_TeamController', 'edit'],
        '#^/admin/activities/(?P<id>\d+)/edit$#' => ['Admin_ActivityController', 'edit'],
        '#^/admin/activity-types/(?P<id>\d+)/edit$#' => ['Admin_ActivityTypeController', 'edit'],
        '#^/admin/export/(?P<type>[a-zA-Z0-9_-]+)$#' => ['Admin_ExportController', 'export'],
        '#^/reporter/activities/(?P<id>\d+)/edit$#' => ['Reporter_ActivityController', 'edit'],
        '#^/photographer/activities/(?P<id>\d+)/edit$#' => ['Photographer_ActivityController', 'edit'],
    ],
    'POST' => [
        '#^/admin/users/(?P<id>\d+)/update$#'       => ['Admin_UserController', 'update'],
        '#^/admin/users/(?P<id>\d+)/delete$#'       => ['Admin_UserController', 'delete'],
        '#^/admin/team/(?P<id>\d+)/update$#'        => ['Admin_TeamController', 'update'],
        '#^/admin/team/(?P<id>\d+)/delete$#'        => ['Admin_TeamController', 'delete'],
        '#^/admin/activities/(?P<id>\d+)/update$#'  => ['Admin_ActivityController', 'update'],
        '#^/admin/activities/(?P<id>\d+)/delete$#'  => ['Admin_ActivityController', 'delete'],
        '#^/admin/activity-types/(?P<id>\d+)/update$#' => ['Admin_ActivityTypeController', 'update'],
        '#^/admin/activity-types/(?P<id>\d+)/delete$#' => ['Admin_ActivityTypeController', 'delete'],
        '#^/admin/activity-types/update-score/(?P<id>\d+)$#' => ['Admin_ActivityTypeController', 'updateScore'],
        '#^/admin/activity-types/toggle-status/(?P<id>\d+)$#' => ['Admin_ActivityTypeController', 'toggleStatus'],
        '#^/admin/targets/update/(?P<userId>\d+)$#' => ['Admin_TargetController', 'updateTarget'],
        '#^/reporter/activities/(?P<id>\d+)/update$#'  => ['Reporter_ActivityController', 'update'],
        '#^/reporter/activities/(?P<id>\d+)/delete$#'  => ['Reporter_ActivityController', 'delete'],
        '#^/photographer/activities/(?P<id>\d+)/update$#' => ['Photographer_ActivityController', 'update'],
        '#^/photographer/activities/(?P<id>\d+)/delete$#' => ['Photographer_ActivityController', 'delete'],
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
