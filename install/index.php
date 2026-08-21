<?php
session_start();
define('BASE_PATH', dirname(__DIR__));
define('INSTALL_PATH', __DIR__);

$installedMarker = BASE_PATH . '/.installed';
$isInstalled = file_exists($installedMarker);

if ($isInstalled) {
    $detectedUrl = detectBaseUrl();
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب شده</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700&display=swap" rel="stylesheet">
    <style>*{font-family:'Vazirmatn',system-ui,sans-serif}</style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="text-center p-4">
        <div class="mb-4"><i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i></div>
        <h2 class="text-success">سیستم قبلاً نصب شده است</h2>
        <p class="text-muted mt-2">برای بازنصب، فایل <code>.installed</code> از پوشه اصلی را حذف کنید.</p>
        <a href="<?= $detectedUrl ?>/dashboard" class="btn btn-primary mt-3">ورود به سامانه</a>
    </div>
</body>
</html>
<?php
    exit;
}

function detectBaseUrl(): string {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $docRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

    $fullPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
    $relative = str_replace($docRoot, '', $fullPath);
    $relative = preg_replace('#^/+#', '/', $relative);
    $relative = rtrim($relative, '/');

    return $relative !== '' ? $relative : '';
}

$checks = [];
$checks[] = ['label' => 'نسخه PHP >= 8.0',       'pass' => version_compare(PHP_VERSION, '8.0.0', '>=')];
$checks[] = ['label' => 'افزونه PDO',              'pass' => extension_loaded('pdo')];
$checks[] = ['label' => 'افزونه PDO MySQL',        'pass' => extension_loaded('pdo_mysql')];
$checks[] = ['label' => 'افزونه mbstring',          'pass' => extension_loaded('mbstring')];
$checks[] = ['label' => 'افزونه JSON',              'pass' => extension_loaded('json')];
$checks[] = ['label' => 'پشتیبانی Session',        'pass' => function_exists('session_start')];
$checks[] = ['label' => 'پشتیبانی file_put_contents', 'pass' => function_exists('file_put_contents')];
$checks[] = ['label' => 'پوشه config قابل نوشتن',  'pass' => is_writable(BASE_PATH . '/config')];
$checks[] = ['label' => 'پوشه storage قابل نوشتن', 'pass' => is_writable(BASE_PATH . '/storage')];

$allPass = true;
foreach ($checks as $c) {
    if (!$c['pass']) { $allPass = false; break; }
}

$detectedUrl = detectBaseUrl();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سامانه مدیریت عملکرد IPNA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
:root{--pri:#1a56db;--pri-l:#3b82f6;--pri-d:#1e40af;--acc:#0ea5e9;--si:#cbd5e1;--sa:#1a56db;--sd:#16a34a}
*{font-family:'Vazirmatn',system-ui,sans-serif;box-sizing:border-box}
body{background:linear-gradient(135deg,#eff6ff 0%,#f0fdf4 100%);min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:0;margin:0}
.inst-hdr{background:linear-gradient(135deg,var(--pri),var(--pri-d));width:100%;padding:1.5rem 0;color:#fff;text-align:center;box-shadow:0 4px 20px rgba(26,86,219,.25)}
.inst-hdr h1{margin:0;font-size:1.6rem;font-weight:700}
.inst-hdr p{margin:.3rem 0 0;opacity:.85;font-size:.9rem}
.inst-logo{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;font-size:1.5rem;font-weight:800;margin-bottom:.5rem;border:1px solid rgba(255,255,255,.2)}
.container-inst{width:100%;max-width:740px;padding:1.5rem;flex:1}
.steps-bar{display:flex;justify-content:center;gap:0;margin-bottom:2rem;padding:.5rem 0}
.step-item{display:flex;flex-direction:column;align-items:center;position:relative;flex:1;max-width:120px}
.step-item:not(:last-child)::after{content:'';position:absolute;top:18px;right:-50%;width:100%;height:3px;background:var(--si);z-index:0;transition:background .4s}
.step-item.done:not(:last-child)::after{background:var(--sd)}
.step-item.active:not(:last-child)::after{background:linear-gradient(90deg,var(--sa),var(--si))}
.step-circle{width:36px;height:36px;border-radius:50%;background:var(--si);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;z-index:1;transition:all .4s;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.step-item.active .step-circle{background:var(--sa);box-shadow:0 0 0 4px rgba(26,86,219,.2),0 2px 8px rgba(0,0,0,.1);transform:scale(1.1)}
.step-item.done .step-circle{background:var(--sd)}
.step-label{font-size:.72rem;color:#94a3b8;margin-top:.5rem;font-weight:500;text-align:center;transition:color .3s}
.step-item.active .step-label{color:var(--pri);font-weight:700}
.step-item.done .step-label{color:var(--sd)}
.inst-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(26,86,219,.08);padding:2rem;border:1px solid rgba(0,0,0,.04);animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.inst-card h3{font-weight:700;color:#1e293b;margin-bottom:.3rem}
.inst-card .sub{color:#64748b;font-size:.88rem;margin-bottom:1.5rem}
.req-item{display:flex;align-items:center;padding:.65rem .9rem;border-radius:10px;margin-bottom:.4rem;background:#f8fafc;border:1px solid #e2e8f0;transition:all .3s}
.req-item .icon{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-left:.75rem;flex-shrink:0;font-size:.85rem}
.req-item.pass .icon{background:#dcfce7;color:#16a34a}
.req-item.fail .icon{background:#fee2e2;color:#dc2626}
.req-item.pass{border-color:#bbf7d0}
.req-item.fail{border-color:#fecaca}
.req-item .lbl{flex:1;font-weight:500;font-size:.9rem}
.req-item .sts{font-size:.8rem;font-weight:600}
.req-item.pass .sts{color:#16a34a}
.req-item.fail .sts{color:#dc2626}
.form-label{font-weight:600;color:#334155;font-size:.88rem;margin-bottom:.35rem}
.form-control,.form-select{border-radius:10px;padding:.65rem 1rem;border:1.5px solid #e2e8f0;font-size:.92rem;transition:all .2s}
.form-control:focus,.form-select:focus{border-color:var(--pri-l);box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.btn{border-radius:10px;padding:.6rem 1.5rem;font-weight:600;font-size:.9rem;transition:all .2s}
.btn-pri{background:linear-gradient(135deg,var(--pri),var(--pri-d));border:none;color:#fff;box-shadow:0 2px 10px rgba(26,86,219,.25)}
.btn-pri:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(26,86,219,.35);color:#fff}
.btn-suc{background:linear-gradient(135deg,#16a34a,#15803d);border:none;color:#fff}
.btn-suc:hover{color:#fff}
.btn:disabled{opacity:.6;transform:none!important}
.progress{height:8px;border-radius:10px;background:#e2e8f0;overflow:hidden}
.progress-bar{background:linear-gradient(90deg,var(--pri),var(--acc));border-radius:10px;transition:width .6s ease}
.progress-text{font-size:.82rem;color:#64748b;margin-top:.4rem;text-align:center}
.inst-log{background:#1e293b;border-radius:10px;padding:1rem;max-height:260px;overflow-y:auto;font-family:'Courier New',monospace;font-size:.78rem;direction:ltr;text-align:left;margin-top:1rem}
.inst-log .ll{padding:.15rem 0;color:#94a3b8}
.inst-log .ll.s{color:#4ade80}
.inst-log .ll.e{color:#f87171}
.inst-log .ll.i{color:#60a5fa}
.success-box{text-align:center;padding:2rem 1rem}
.success-icon{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#dcfce7,#bbf7d0);display:inline-flex;align-items:center;justify-content:center;font-size:2.5rem;color:#16a34a;margin-bottom:1rem;animation:popIn .5s ease}
@keyframes popIn{0%{transform:scale(0)}70%{transform:scale(1.15)}100%{transform:scale(1)}}
.test-result{padding:.6rem 1rem;border-radius:8px;font-size:.85rem;font-weight:500;margin-top:.5rem;display:none}
.test-result.show{display:block}
.test-result.s{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0}
.test-result.e{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
.hidden{display:none!important}
.alert{border-radius:10px;font-size:.88rem}
.detect-url{background:#f0f9ff;border:1px dashed #93c5fd;border-radius:8px;padding:.5rem .8rem;margin-top:.4rem;font-size:.82rem;color:#1e40af;display:flex;align-items:center;gap:.5rem}
.detect-url code{background:#dbeafe;padding:.1rem .4rem;border-radius:4px;font-weight:600}
</style>
</head>
<body>
<div class="inst-hdr">
    <div class="inst-logo">IPNA</div>
    <h1>نصب سامانه مدیریت عملکرد خبرنگاران</h1>
    <p>راهنمای قدم به قدم نصب و راه‌اندازی سامانه</p>
</div>

<div class="container-inst">
    <div class="steps-bar" id="stepsBar">
        <div class="step-item active" data-step="1">
            <div class="step-circle">۱</div>
            <div class="step-label">پیش‌نیازها</div>
        </div>
        <div class="step-item" data-step="2">
            <div class="step-circle">۲</div>
            <div class="step-label">پایگاه داده</div>
        </div>
        <div class="step-item" data-step="3">
            <div class="step-circle">۳</div>
            <div class="step-label">مدیر سامانه</div>
        </div>
        <div class="step-item" data-step="4">
            <div class="step-circle">۴</div>
            <div class="step-label">تنظیمات</div>
        </div>
        <div class="step-item" data-step="5">
            <div class="step-circle">۵</div>
            <div class="step-label">نصب</div>
        </div>
    </div>

    <!-- Step 1: Requirements -->
    <div class="inst-card step-content" id="step1">
        <h3><i class="bi bi-clipboard-check me-2"></i>بررسی پیش‌نیازهای سیستم</h3>
        <p class="sub">لطفاً مطمئن شوید تمام الزامات زیر برآورده شده‌اند.</p>
        <div id="reqList">
        <?php foreach ($checks as $c): ?>
            <div class="req-item <?= $c['pass'] ? 'pass' : 'fail' ?>">
                <div class="icon"><i class="bi bi-<?= $c['pass'] ? 'check-lg' : 'x-lg' ?>"></i></div>
                <div class="lbl"><?= htmlspecialchars($c['label']) ?></div>
                <div class="sts"><?= $c['pass'] ? 'موجود' : 'موجود نیست' ?></div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php if (!$allPass): ?>
            <div class="alert alert-danger mt-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                لطفاً تمام مشکلات بالا را برطرف کرده و مجدداً تلاش کنید.
            </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between mt-4">
            <div></div>
            <button class="btn btn-pri" <?= $allPass ? 'onclick="goStep(2)"' : 'disabled' ?>>
                <i class="bi bi-arrow-left me-1"></i>مرحله بعد
            </button>
        </div>
    </div>

    <!-- Step 2: Database -->
    <div class="inst-card step-content hidden" id="step2">
        <h3><i class="bi bi-database me-2"></i>تنظیمات پایگاه داده</h3>
        <p class="sub">اطلاعات اتصال به سرور MySQL را وارد کنید.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">میزبان (Host)</label>
                <input type="text" class="form-control" id="dbHost" value="localhost" placeholder="localhost">
            </div>
            <div class="col-md-6">
                <label class="form-label">نام پایگاه داده</label>
                <input type="text" class="form-control" id="dbName" value="ipna_pms" placeholder="ipna_pms">
            </div>
            <div class="col-md-6">
                <label class="form-label">نام کاربری</label>
                <input type="text" class="form-control" id="dbUser" value="" placeholder="نام کاربری دیتابیس">
            </div>
            <div class="col-md-6">
                <label class="form-label">کلمه عبور</label>
                <input type="password" class="form-control" id="dbPass" value="" placeholder="کلمه عبور دیتابیس">
            </div>
            <div class="col-md-6">
                <label class="form-label">پیشوند جداول (اختیاری)</label>
                <input type="text" class="form-control" id="dbPrefix" value="" placeholder="مثال: ipna_">
            </div>
        </div>
        <div class="test-result" id="dbTestResult"></div>
        <div class="d-flex justify-content-between mt-4">
            <button class="btn btn-outline-secondary" onclick="goStep(1)">
                <i class="bi bi-arrow-right me-1"></i>مرحله قبل
            </button>
            <div>
                <button class="btn btn-outline-primary me-2" id="testDbBtn" onclick="testDb()">
                    <i class="bi bi-plug me-1"></i>تست اتصال
                </button>
                <button class="btn btn-pri" id="dbNextBtn" onclick="goStep(3)" disabled>
                    <i class="bi bi-arrow-left me-1"></i>مرحله بعد
                </button>
            </div>
        </div>
    </div>

    <!-- Step 3: Admin Account -->
    <div class="inst-card step-content hidden" id="step3">
        <h3><i class="bi bi-person-gear me-2"></i>ایجاد حساب مدیر</h3>
        <p class="sub">حساب مدیر سامانه را ایجاد کنید.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام مدیر <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="adminName" placeholder="نام و نام خانوادگی">
            </div>
            <div class="col-md-6">
                <label class="form-label">ایمیل <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="adminEmail" placeholder="admin@ipna.ir">
            </div>
            <div class="col-md-6">
                <label class="form-label">نام کاربری <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="adminUser" placeholder="admin" autocomplete="off">
            </div>
            <div class="col-md-6">
                <label class="form-label">کلمه عبور <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="adminPass" placeholder="حداقل ۶ کاراکتر" autocomplete="new-password">
            </div>
            <div class="col-md-6">
                <label class="form-label">تکرار کلمه عبور <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="adminPass2" placeholder="تکرار کلمه عبور" autocomplete="new-password">
            </div>
        </div>
        <div id="adminError" class="alert alert-danger mt-3 hidden"></div>
        <div class="d-flex justify-content-between mt-4">
            <button class="btn btn-outline-secondary" onclick="goStep(2)">
                <i class="bi bi-arrow-right me-1"></i>مرحله قبل
            </button>
            <button class="btn btn-pri" onclick="validateAdmin()">
                <i class="bi bi-arrow-left me-1"></i>مرحله بعد
            </button>
        </div>
    </div>

    <!-- Step 4: Site Settings -->
    <div class="inst-card step-content hidden" id="step4">
        <h3><i class="bi bi-gear me-2"></i>تنظیمات سایت</h3>
        <p class="sub">اطلاعات کلی سامانه و آدرس دسترسی را تنظیم کنید.</p>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">آدرس پایه سایت (BASE_URL)</label>
                <input type="text" class="form-control" id="baseUrl" value="<?= htmlspecialchars($detectedUrl) ?>" placeholder="/journalist">
                <div class="detect-url">
                    <i class="bi bi-info-circle"></i>
                    به صورت خودکار تشخیص داده شد: <code><?= htmlspecialchars($detectedUrl) ?: '/' ?></code>
                    — اگر در زیرپوشه دیگری استفاده می‌کنید تغییر دهید.
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">نام سایت</label>
                <input type="text" class="form-control" id="siteName" value="سامانه مدیریت عملکرد خبرنگاران IPNA">
            </div>
            <div class="col-md-6">
                <label class="form-label">آدرس سایت</label>
                <input type="url" class="form-control" id="siteUrl" placeholder="https://ipna.ir/journalist">
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <button class="btn btn-outline-secondary" onclick="goStep(3)">
                <i class="bi bi-arrow-right me-1"></i>مرحله قبل
            </button>
            <button class="btn btn-suc" onclick="startInstall()">
                <i class="bi bi-rocket-takeoff me-1"></i>شروع نصب
            </button>
        </div>
    </div>

    <!-- Step 5: Installation Progress -->
    <div class="inst-card step-content hidden" id="step5">
        <h3><i class="bi bi-download me-2"></i>در حال نصب...</h3>
        <p class="sub" id="installSub">لطفاً صبر کنید، عملیات در حال انجام است.</p>
        <div class="progress" style="height:8px">
            <div class="progress-bar" id="installBar" role="progressbar" style="width:0%"></div>
        </div>
        <div class="progress-text" id="installProgressText">0%</div>
        <div class="inst-log" id="installLog"></div>
    </div>

    <!-- Success -->
    <div class="inst-card step-content hidden" id="stepSuccess">
        <div class="success-box">
            <div class="success-icon"><i class="bi bi-check-lg"></i></div>
            <h3 class="text-success mb-2">نصب با موفقیت انجام شد!</h3>
            <p class="text-muted">سامانه مدیریت عملکرد خبرنگاران IPNA آماده استفاده است.</p>
            <div class="alert alert-warning mt-3" style="font-size:.85rem">
                <i class="bi bi-shield-lock me-1"></i>
                <strong>امنیت:</strong> پوشه <code>install/</code> پس از نصب غیرفعال شد. برای نصب مجدد فایل <code>.installed</code> را حذف کنید.
            </div>
            <div class="mt-4">
                <a id="successLink" href="#" class="btn btn-pri btn-lg">
                    <i class="bi bi-box-arrow-in-left me-2"></i>ورود به سامانه
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const DB_FILE = 'process.php';
const DETECTED_BASE_URL = <?= json_encode($detectedUrl) ?>;
let dbConnected = false;
let adminData = {};

function goStep(n) {
    if (n === 3 && !dbConnected) {
        alert('ابتدا اتصال به پایگاه داده را تست کنید.');
        return;
    }
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('step' + n).classList.remove('hidden');
    updateSteps(n);
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function updateSteps(current) {
    document.querySelectorAll('.step-item').forEach(el => {
        const s = parseInt(el.dataset.step);
        el.classList.remove('active', 'done');
        if (s === current) el.classList.add('active');
        else if (s < current) el.classList.add('done');
    });
}

function testDb() {
    const btn = document.getElementById('testDbBtn');
    const res = document.getElementById('dbTestResult');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>در حال تست...';
    res.className = 'test-result';

    const data = new FormData();
    data.append('action', 'test-db');
    data.append('host', document.getElementById('dbHost').value);
    data.append('dbname', document.getElementById('dbName').value);
    data.append('username', document.getElementById('dbUser').value);
    data.append('password', document.getElementById('dbPass').value);

    fetch(DB_FILE, { method: 'POST', body: data })
        .then(r => r.json())
        .then(j => {
            if (j.success) {
                res.className = 'test-result show s';
                res.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + j.message;
                dbConnected = true;
                document.getElementById('dbNextBtn').disabled = false;
            } else {
                res.className = 'test-result show e';
                res.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + j.message;
                dbConnected = false;
                document.getElementById('dbNextBtn').disabled = true;
            }
        })
        .catch(() => {
            res.className = 'test-result show e';
            res.innerHTML = '<i class="bi bi-x-circle me-1"></i>خطا در ارتباط با سرور نصب';
            dbConnected = false;
            document.getElementById('dbNextBtn').disabled = true;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plug me-1"></i>تست اتصال';
        });
}

function validateAdmin() {
    const errDiv = document.getElementById('adminError');
    const name = document.getElementById('adminName').value.trim();
    const email = document.getElementById('adminEmail').value.trim();
    const user = document.getElementById('adminUser').value.trim();
    const pass = document.getElementById('adminPass').value;
    const pass2 = document.getElementById('adminPass2').value;

    if (!name || !email || !user || !pass) {
        errDiv.textContent = 'لطفاً تمام فیلدها را پر کنید.';
        errDiv.classList.remove('hidden');
        return;
    }
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        errDiv.textContent = 'فرمت ایمیل صحیح نیست.';
        errDiv.classList.remove('hidden');
        return;
    }
    if (user.length < 3) {
        errDiv.textContent = 'نام کاربری باید حداقل ۳ کاراکتر باشد.';
        errDiv.classList.remove('hidden');
        return;
    }
    if (!user.match(/^[a-zA-Z0-9._-]+$/)) {
        errDiv.textContent = 'نام کاربری فقط شامل حروف لاتین، اعداد، نقطه و خط تیره باشد.';
        errDiv.classList.remove('hidden');
        return;
    }
    if (pass.length < 6) {
        errDiv.textContent = 'کلمه عبور باید حداقل ۶ کاراکتر باشد.';
        errDiv.classList.remove('hidden');
        return;
    }
    if (pass !== pass2) {
        errDiv.textContent = 'کلمه عبور و تکرار آن مطابقت ندارند.';
        errDiv.classList.remove('hidden');
        return;
    }

    errDiv.classList.add('hidden');
    adminData = { name, email, username: user, password: pass };
    goStep(4);
}

function startInstall() {
    goStep(5);
    document.querySelectorAll('.step-item').forEach(el => el.classList.add('done'));
    document.querySelector('.step-item:last-child').classList.remove('done');
    document.querySelector('.step-item:last-child').classList.add('active');

    const log = document.getElementById('installLog');
    const bar = document.getElementById('installBar');
    const txt = document.getElementById('installProgressText');

    const baseUrlInput = document.getElementById('baseUrl').value.trim() || DETECTED_BASE_URL;

    const params = {
        action: 'install',
        host: document.getElementById('dbHost').value,
        dbname: document.getElementById('dbName').value,
        username: document.getElementById('dbUser').value,
        password: document.getElementById('dbPass').value,
        prefix: document.getElementById('dbPrefix').value,
        adminName: adminData.name,
        adminEmail: adminData.email,
        adminUser: adminData.username,
        adminPass: adminData.password,
        baseUrl: baseUrlInput,
        siteName: document.getElementById('siteName').value || 'IPNA PMS',
        siteUrl: document.getElementById('siteUrl').value || ''
    };

    function addLog(msg, cls) {
        const d = document.createElement('div');
        d.className = 'll ' + (cls || '');
        d.textContent = '> ' + msg;
        log.appendChild(d);
        log.scrollTop = log.scrollHeight;
    }

    function setProgress(pct, msg) {
        bar.style.width = pct + '%';
        txt.textContent = pct + '%' + (msg ? ' — ' + msg : '');
    }

    addLog('Starting installation...', 'i');
    setProgress(5, 'شروع نصب...');

    const fd = new FormData();
    for (const [k, v] of Object.entries(params)) fd.append(k, v);

    fetch(DB_FILE, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(j => {
            if (j.steps && j.steps.length) {
                const totalSteps = j.steps.length;
                j.steps.forEach((s, i) => {
                    const pct = Math.round(((i + 1) / totalSteps) * 100);
                    setTimeout(() => {
                        addLog(s.message, s.type || 's');
                        setProgress(pct, s.message);
                    }, i * 150);
                });
            }
            setTimeout(() => {
                if (j.success) {
                    setProgress(100, 'نصب با موفقیت انجام شد!');
                    addLog('Installation completed successfully!', 's');
                    document.getElementById('installSub').innerHTML = '<span class="text-success fw-bold">نصب با موفقیت انجام شد!</span>';
                    const finalUrl = (baseUrlInput || '') + '/dashboard';
                    document.getElementById('successLink').href = finalUrl;
                    document.getElementById('stepSuccess').classList.remove('hidden');
                    document.getElementById('step5').classList.add('hidden');
                    document.querySelector('.step-item:last-child').classList.add('done');
                } else {
                    setProgress(0, 'خطا!');
                    addLog('Error: ' + j.message, 'e');
                    document.getElementById('installSub').innerHTML = '<span class="text-danger fw-bold">خطا در نصب: ' + j.message + '</span>';
                    bar.classList.add('bg-danger');
                }
            }, j.steps ? j.steps.length * 150 + 300 : 500);
        })
        .catch(err => {
            addLog('Fatal error: ' + err.message, 'e');
            setProgress(0, 'خطا در ارتباط با سرور');
            document.getElementById('installSub').innerHTML = '<span class="text-danger fw-bold">خطا در ارتباط با سرور نصب</span>';
            bar.classList.add('bg-danger');
        });
}
</script>
</body>
</html>
