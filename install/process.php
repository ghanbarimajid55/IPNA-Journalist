<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

define('BASE_PATH', dirname(__DIR__));
define('INSTALL_PATH', __DIR__);

function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function addStep(array &$steps, string $msg, string $type = 's'): void {
    $steps[] = ['message' => $msg, 'type' => $type];
}

function cleanSql(string $sql, string $prefix = ''): string {
    if ($prefix) {
        $tables = ['users', 'activity_types', 'monthly_targets', 'activities', 'audit_logs', 'system_settings', 'login_attempts'];
        foreach ($tables as $t) {
            $sql = str_replace('CREATE TABLE ' . $t, 'CREATE TABLE ' . $prefix . $t, $sql);
            $sql = str_replace('INSERT INTO ' . $t, 'INSERT INTO ' . $prefix . $t, $sql);
        }
    }
    return $sql;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'test-db':
        $host     = trim($_POST['host'] ?? 'localhost');
        $dbname   = trim($_POST['dbname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$host || !$dbname || !$username) {
            jsonResponse(['success' => false, 'message' => 'لطفاً تمام فیلدها را پر کنید.']);
        }

        try {
            $dsn = "mysql:host={$host};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            jsonResponse([
                'success' => true,
                'message' => "اتصال موفق! پایگاه داده «{$dbname}» با موفقیت ایجاد/تأیید شد.",
            ]);
        } catch (PDOException $e) {
            jsonResponse([
                'success' => false,
                'message' => 'خطا در اتصال: ' . $e->getMessage(),
            ]);
        }
        break;

    case 'install':
        $host       = trim($_POST['host'] ?? 'localhost');
        $dbname     = trim($_POST['dbname'] ?? '');
        $username   = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $prefix     = trim($_POST['prefix'] ?? '');
        $adminName  = trim($_POST['adminName'] ?? '');
        $adminEmail = trim($_POST['adminEmail'] ?? '');
        $adminUser  = trim($_POST['adminUser'] ?? '');
        $adminPass  = $_POST['adminPass'] ?? '';
        $baseUrl    = trim($_POST['baseUrl'] ?? '/journalist');
        $siteName   = trim($_POST['siteName'] ?? 'IPNA PMS');
        $siteUrl    = trim($_POST['siteUrl'] ?? '');

        $steps = [];

        try {
            // Validate inputs
            if (!$host || !$dbname || !$username || !$adminName || !$adminUser || !$adminPass) {
                throw new Exception('لطفاً تمام فیلدهای ضروری را پر کنید.');
            }
            if (strlen($adminPass) < 6) {
                throw new Exception('کلمه عبور مدیر باید حداقل ۶ کاراکتر باشد.');
            }

            // Step 1: Connect to MySQL
            addStep($steps, 'اتصال به سرور MySQL...', 'i');
            $dsn = "mysql:host={$host};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            addStep($steps, 'اتصال به MySQL برقرار شد.');

            // Step 2: Create database
            addStep($steps, "ساخت پایگاه داده «{$dbname}»...");
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbname}`");
            addStep($steps, "پایگاه داده «{$dbname}» آماده است.");

            // Step 3: Execute schema
            $schemaFile = BASE_PATH . '/database/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception('فایل schema.sql یافت نشد: ' . $schemaFile);
            }
            addStep($steps, 'اجرای اسکیمای دیتابیس...', 'i');
            $schema = file_get_contents($schemaFile);

            // Remove CREATE DATABASE and USE from schema
            $schema = preg_replace('/CREATE DATABASE[^;]+;/i', '', $schema);
            $schema = preg_replace('/USE\s+`?[a-zA-Z0-9_]+`?;/i', '', $schema);

            // Remove comments
            $schema = preg_replace('/-- [^\n]*/', '', $schema);

            $schema = cleanSql($schema, $prefix);

            $statements = array_filter(array_map('trim', explode(';', $schema)));
            $executed = 0;
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt)) continue;
                if (preg_match('/^--/', $stmt) || preg_match('/^\/\//', $stmt)) continue;
                try {
                    $pdo->exec($stmt);
                    $executed++;
                } catch (PDOException $e) {
                    $msg = $e->getMessage();
                    if (strpos($msg, 'already exists') !== false) {
                        continue;
                    }
                    addStep($steps, "هشدار: {$msg}", 'i');
                }
            }
            addStep($steps, "اسکیما اجرا شد ({$executed} دستور).");

            // Step 4: Create admin user
            addStep($steps, 'ایجاد حساب مدیر سامانه...');
            $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT);
            $adminTable = $prefix . 'users';
            $stmt = $pdo->prepare("INSERT INTO `{$adminTable}` (name, username, email, password, role, position, status) VALUES (?, ?, ?, ?, 'admin', 'مدیر سیستم', 'active')");
            $stmt->execute([$adminName, $adminUser, $adminEmail, $hashedPass]);
            $adminId = $pdo->lastInsertId();
            addStep($steps, "حساب مدیر «{$adminUser}» ایجاد شد.");

            // Step 5: Update site settings
            addStep($steps, 'ذخیره تنظیمات سایت...');
            $settingsTable = $prefix . 'system_settings';
            $settings = [
                'site_name' => $siteName,
                'site_url'  => $siteUrl,
            ];
            foreach ($settings as $key => $val) {
                if ($val === '') continue;
                $stmt = $pdo->prepare("INSERT INTO `{$settingsTable}` (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $val, $val]);
            }
            addStep($steps, 'تنظیمات سایت ذخیره شد.');

            // Step 6: Write config/database.php
            addStep($steps, 'نوشتن فایل تنظیمات دیتابیس...');
            $dbConfig = "<?php\n";
            $dbConfig .= "// Auto-generated by IPNA PMS Installer\n";
            $dbConfig .= "// Do not edit manually after installation\n";
            $dbConfig .= "return [\n";
            $dbConfig .= "    'host'     => " . var_export($host, true) . ",\n";
            $dbConfig .= "    'dbname'   => " . var_export($dbname, true) . ",\n";
            $dbConfig .= "    'username' => " . var_export($username, true) . ",\n";
            $dbConfig .= "    'password' => " . var_export($password, true) . ",\n";
            $dbConfig .= "    'charset'  => 'utf8mb4',\n";
            if ($prefix) {
                $dbConfig .= "    'prefix'   => " . var_export($prefix, true) . ",\n";
            }
            $dbConfig .= "];\n";

            $configPath = BASE_PATH . '/config/database.php';
            if (!file_put_contents($configPath, $dbConfig)) {
                throw new Exception('خطا در نوشتن فایل database.php — پوشه config قابل نوشتن نیست.');
            }
            addStep($steps, 'فایل database.php نوشته شد.');

            // Step 7: Write config/config.php with correct BASE_URL
            addStep($steps, 'نوشتن فایل تنظیمات اصلی...');
            $baseUrlEscaped = addslashes($baseUrl);
            $configPhp = "<?php\n";
            $configPhp .= "if (!defined('BASE_PATH')) {\n";
            $configPhp .= "    define('BASE_PATH', dirname(__DIR__));\n";
            $configPhp .= "}\n";
            $configPhp .= "define('APP_NAME', " . var_export($siteName, true) . ");\n";
            $configPhp .= "define('APP_VERSION', '1.0.0');\n";
            $configPhp .= "define('PUBLIC_PATH', BASE_PATH . '/public');\n";
            $configPhp .= "define('STORAGE_PATH', BASE_PATH . '/storage');\n";
            $configPhp .= "\n";
            $configPhp .= "// Base URL — set by installer\n";
            $configPhp .= "define('BASE_URL', '{$baseUrlEscaped}');\n";
            $configPhp .= "\n";
            $configPhp .= "session_start();\n";
            $configPhp .= "\n";
            $configPhp .= "error_reporting(E_ALL);\n";
            $configPhp .= "ini_set('display_errors', 0);\n";
            $configPhp .= "ini_set('log_errors', 1);\n";

            $configConfigPath = BASE_PATH . '/config/config.php';
            if (!file_put_contents($configConfigPath, $configPhp)) {
                throw new Exception('خطا در نوشتن فایل config.php');
            }
            addStep($steps, 'فایل config.php با BASE_URL به‌روزرسانی شد.');

            // Step 8: Create .installed marker
            addStep($steps, 'ایجاد نشانگر نصب...');
            file_put_contents(BASE_PATH . '/.installed', date('Y-m-d H:i:s') . "\ninstalled_by=installer\n");

            // Step 9: Protect install directory with .htaccess
            addStep($steps, 'محافظت از پوشه نصب...');
            $htaccess = "# Install completed - restrict access\n";
            $htaccess .= "# Remove this file to re-enable the installer\n";
            $htaccess .= "Require all denied\n";
            file_put_contents(INSTALL_PATH . '/.htaccess', $htaccess);

            // Also create a web.config for IIS servers
            $webconfig = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $webconfig .= "<configuration>\n";
            $webconfig .= "  <system.webServer>\n";
            $webconfig .= "    <security>\n";
            $webconfig .= "      <requestFiltering>\n";
            $webconfig .= "        <denyUrlSequences>\n";
            $webconfig .= "          <add sequence=\"install\" />\n";
            $webconfig .= "        </denyUrlSequences>\n";
            $webconfig .= "      </requestFiltering>\n";
            $webconfig .= "    </security>\n";
            $webconfig .= "  </system.webServer>\n";
            $webconfig .= "</configuration>\n";
            file_put_contents(INSTALL_PATH . '/web.config', $webconfig);

            addStep($steps, 'پوشه نصب محافظت شد.');

            addStep($steps, '');
            addStep($steps, '✅ نصب با موفقیت انجام شد!', 's');

            jsonResponse(['success' => true, 'steps' => $steps]);

        } catch (Exception $e) {
            addStep($steps, '❌ خطا: ' . $e->getMessage(), 'e');
            jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'steps' => $steps,
            ]);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action: ' . $action]);
}
