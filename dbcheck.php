<?php
/**
 * dbcheck.php — Diagnostic tool for IPNA PMS database connection.
 * Upload this file to the live site (e.g. ipna.ir/journalist/) and open it.
 * DELETE it after diagnosing.
 */
$BASE = __DIR__;
$configFile = $BASE . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="fa"><meta charset="utf-8"><title>DB Check</title>';
echo '<body style="font-family:Tahoma;background:#f5f6f7;padding:20px;direction:rtl">';
echo '<h2>IPNA PMS — تشخیص اتصال به دیتابیس</h2>';

echo '<h3>1) وضعیت PHP</h3>';
echo '<p>نسخه PHP: ' . htmlspecialchars(PHP_VERSION) . '</p>';
$drivers = PDO::getAvailableDrivers();
$hasMysql = in_array('mysql', $drivers, true);
echo '<p>درایورهای PDO: ' . implode(', ', $drivers) . '</p>';
if (!$hasMysql) {
    echo '<p style="color:#c92a2a"><b>خطا:</b> افزونه <code>pdo_mysql</code> در سرور نصب نیست. خروجی روی آن.</p>';
    echo '</body></html>';
    exit;
}

$config = [];
if (is_file($configFile)) {
    $config = require $configFile;
} else {
    echo '<p style="color:#c92a2a"><b>خطا:</b> فایل <code>config/database.php</code> وجود ندارد. نصب کامل نشده است.</p>';
    echo '</body></html>';
    exit;
}

$host = (string)($config['host'] ?? 'localhost');
$db   = (string)($config['dbname'] ?? '');
$user = (string)($config['username'] ?? '');
$pass = (string)($config['password'] ?? '');
$charset = (string)($config['charset'] ?? 'utf8mb4');

echo '<h3>2) کانفیگ فعلی</h3>';
echo '<p>host: <code>' . htmlspecialchars($host) . '</code></p>';
echo '<p>dbname: <code>' . htmlspecialchars($db) . '</code></p>';
echo '<p>username: <code>' . htmlspecialchars($user) . '</code></p>';
echo '<p>password: <code>' . ($pass !== '' ? '***پنهان***' : '(خالی)') . '</code></p>';

function try_conn(string $label, string $dsn, string $u, string $p): bool
{
    echo '<p style="margin:4px 0"><b>تلاش:</b> ' . htmlspecialchars($label) . ' — ';
    try {
        $pdo = new PDO($dsn, $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        echo '<span style="color:#2f9e44">اتصال موفق ✔</span> (' . htmlspecialchars($pdo->getAttribute(PDO::ATTR_SERVER_VERSION)) . ')';
        return true;
    } catch (PDOException $e) {
        echo '<span style="color:#c92a2a">ناموفق ✖ — ' . htmlspecialchars($e->getMessage()) . '</span>';
        return false;
    }
}

echo '<h3>3) اتصال با کانفیگ فعلی (with dbname)</h3>';
$ok = try_conn('mysql:host=' . $host . ';dbname=' . $db . ';charset=' . $charset, 'mysql:host=' . $host . ';dbname=' . $db . ';charset=' . $charset, $user, $pass);

echo '<h3>4) اتصال به سرویس‌دهنده بدون dbname (برای ایزولاسیون)</h3>';
try_conn('بدون dbname (host=' . $host . ')', 'mysql:host=' . $host . ';charset=' . $charset, $user, $pass);

echo '<h3>5) تلاش با مقادیر جایگزین (در صورت شکست بالا)</h3>';
foreach (['localhost', '127.0.0.1'] as $h) {
    if ($h !== $host) {
        try_conn('host=' . $h . ' + dbname=' . $db, 'mysql:host=' . $h . ';dbname=' . $db . ';charset=' . $charset, $user, $pass);
    }
}

echo '<h3>6) جمع‌بندی</h3>';
if ($ok) {
    echo '<p style="color:#2f9e44"><b>اتصال برقرار است.</b> مشکل از خود کانفیگ نیست؛ خطای اصلیِ پیش‌تر احتمالاً موقتی بود یا کانفیگ پیش از این اصلاح شده.</p>';
} else {
    echo '<p style="color:#c92a2a"><b>اتصال هنوز برقرار نیست.</b> موارد زیر را بررسی کنید:</p>';
    echo '<ul>';
    echo '<li>هاست: در میزبانی اشتراکی معمولاً باید <code>localhost</code> باشد، مگر اینکه میزبان آدرس دیگری بدهد.</li>';
    echo '<li>نام دیتابیس و نام کاربری: در cPanel/podفایل این دو اغلب پیشوند دارند (مثلا <code>ipna_ipna_pms</code> و <code>ipna_admin</code>).</li>';
    echo '<li>دیتابیس باید از پیش ساخته شده باشد (یا نصب‌کننده ساخته باشد) و دسترسی کاربر به آن وجود داشته باشد.</li>';
    echo '<li>رمز عبور و کاربر با کانفیگ همخوان باشند.</li>';
    echo '</ul>';
}

echo '<p style="color:#8a6d3b">پس از پایان، این فایل (<code>dbcheck.php</code>) را از سرور حذف کنید.</p>';
echo '</body></html>';
