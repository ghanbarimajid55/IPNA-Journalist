<?php
$currentUser = Auth::user();
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$role = $currentUser['role'] ?? '';
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($title ?? 'IPNA PMS') ?> | سیستم مدیریت عملکرد</title>
    <link rel="manifest" href="<?= $baseUrl ?>/manifest.json">
    <meta name="theme-color" content="#1a237e">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h4 class="text-white mb-0">
                <i class="bi bi-newspaper"></i> IPNA
            </h4>
            <small class="text-white-50">سیستم مدیریت عملکرد</small>
        </div>
        <ul class="nav flex-column">
            <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin">
                        <i class="bi bi-speedometer2"></i> داشبورد
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/activities">
                        <i class="bi bi-clipboard-data"></i> فعالیت‌ها
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/team">
                        <i class="bi bi-people"></i> تیم
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/users">
                        <i class="bi bi-person-gear"></i> کاربران
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/targets">
                        <i class="bi bi-bullseye"></i> اهداف
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/activity-types">
                        <i class="bi bi-tags"></i> انواع فعالیت
                    </a>
                </li>
                <li class="nav-divider"><hr class="text-white-50 my-2"></li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/reports/daily">
                        <i class="bi bi-calendar-day"></i> گزارش روزانه
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/reports/weekly">
                        <i class="bi bi-calendar-week"></i> گزارش هفتگی
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/reports/monthly">
                        <i class="bi bi-calendar-month"></i> گزارش ماهانه
                    </a>
                </li>
                <li class="nav-divider"><hr class="text-white-50 my-2"></li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/import">
                        <i class="bi bi-upload"></i> ورود اطلاعات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/admin/settings">
                        <i class="bi bi-gear"></i> تنظیمات
                    </a>
                </li>
            <?php elseif ($role === 'reporter'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/reporter/activities/create">
                        <i class="bi bi-clipboard-plus"></i> ثبت فعالیت
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/reporter/activities">
                        <i class="bi bi-list-check"></i> فعالیت‌های من
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/reporter/targets">
                        <i class="bi bi-bullseye"></i> هدف ماهانه
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/reporter/reports/weekly">
                        <i class="bi bi-calendar-week"></i> گزارش هفتگی
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/reporter/reports/monthly">
                        <i class="bi bi-calendar-month"></i> گزارش ماهانه
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/photographer/activities/create">
                        <i class="bi bi-camera"></i> ثبت فعالیت
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/photographer/activities">
                        <i class="bi bi-images"></i> فعالیت‌های من
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/photographer/targets">
                        <i class="bi bi-bullseye"></i> هدف ماهانه
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/photographer/reports/weekly">
                        <i class="bi bi-calendar-week"></i> گزارش هفتگی
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/photographer/reports/monthly">
                        <i class="bi bi-calendar-month"></i> گزارش ماهانه
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer mt-auto p-3">
            <form method="POST" action="<?= $baseUrl ?>/logout">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-outline-light btn-sm w-100">
                    <i class="bi bi-box-arrow-left"></i> خروج
                </button>
            </form>
        </div>
    </nav>

    <!-- Main Content -->
    <div id="main-content" class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary d-md-none me-2">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="d-flex align-items-center me-auto">
                <span class="badge bg-primary me-2">
                    <?php
                    $roleNames = ['admin' => 'مدیر', 'reporter' => 'خبرنگار', 'photographer' => 'عکاس'];
                    echo e($roleNames[$role] ?? $role);
                    ?>
                </span>
                <span class="text-muted"><?= e($currentUser['name'] ?? '') ?></span>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-muted ms-3 d-none d-md-inline"><?= Helper::jalaliDate(date('Y-m-d')) ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-start">
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/dashboard"><i class="bi bi-speedometer2"></i> داشبورد</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="<?= $baseUrl ?>/logout">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-left"></i> خروج</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <?php
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        ?>
        <div id="flash-container" style="position:fixed;top:20px;left:20px;z-index:9999;">
            <?php if ($flashSuccess): ?>
                <div class="toast show" role="alert">
                    <div class="toast-body bg-success text-white d-flex align-items-center">
                        <i class="bi bi-check-circle me-2"></i> <?= e($flashSuccess) ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="toast show" role="alert">
                    <div class="toast-body bg-danger text-white d-flex align-items-center">
                        <i class="bi bi-exclamation-circle me-2"></i> <?= e($flashError) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Page Content -->
        <div class="container-fluid p-4">
            <?= $content ?>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    window.APP_BASE_URL = '<?= $baseUrl ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/app.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/charts.js"></script>
    <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
    
    <!-- PWA Service Worker -->
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= $baseUrl ?>/sw.js');
    }
    </script>
</body>
</html>
