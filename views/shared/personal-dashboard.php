<?php
    $role = $user['role'] ?? 'reporter';
    $roleLabel = $role === 'photographer' ? 'عکاس' : 'خبرنگار';
    $roleIcon = $role === 'photographer' ? 'bi-camera' : 'bi-pencil';
    $activityUrl = '/' . $role . '/activities/create';
    $activitiesUrl = '/' . $role . '/activities';
    $targetsUrl = '/' . $role . '/targets';
    $reportsUrl = '/' . $role . '/reports';
?>

<div class="container-fluid px-2 py-3" dir="rtl">
    <div class="card border-0 shadow-sm mb-3 bg-gradient-primary text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca);border-radius:1rem;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center">
                <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center me-3" style="width:56px;height:56px;">
                    <i class="bi <?= $roleIcon ?> fs-3"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($user['full_name'] ?? $user['name'] ?? '') ?></h5>
                    <small class="opacity-75"><?= $roleLabel ?></small>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($target)): ?>
        <?php
            $achPercent = min(100, $achievement['percentage'] ?? 0);
            $barColor = $achPercent >= 80 ? 'success' : ($achPercent >= 50 ? 'warning' : 'danger');
            $currentScore = $monthStats['current_score'] ?? 0;
            $targetScore = $target['score_target'] ?? 0;
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold">پیشرفت ماهانه</span>
                    <span class="badge bg-<?= $barColor ?> fs-6"><?= number_format($achPercent, 1) ?>%</span>
                </div>
                <div class="progress mb-2" style="height:1.2rem;border-radius:1rem;">
                    <div class="progress-bar bg-<?= $barColor ?> progress-bar-striped progress-bar-animated"
                         style="width:<?= $achPercent ?>%;"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span><?= $currentScore ?> امتیاز</span>
                    <span>هدف: <?= $targetScore ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="fw-bold text-primary" style="font-size:1.6rem;"><?= $monthStats['this_month_count'] ?? 0 ?></div>
                    <div class="text-muted small">فعالیت این ماه</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="fw-bold text-success" style="font-size:1.6rem;"><?= $monthStats['this_week_count'] ?? 0 ?></div>
                    <div class="text-muted small">فعالیت این هفته</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="fw-bold text-info" style="font-size:1.6rem;"><?= $monthStats['current_score'] ?? 0 ?></div>
                    <div class="text-muted small">امتیاز ماه</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="fw-bold text-warning" style="font-size:1.6rem;"><?= $monthStats['mission_count'] ?? 0 ?></div>
                    <div class="text-muted small">مأموریت</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($todayActivities)): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <span class="fw-bold">فعالیت‌های امروز</span>
                <span class="badge bg-primary"><?= count($todayActivities) ?></span>
            </div>
            <div class="card-body p-0">
                <?php foreach ($todayActivities as $act): ?>
                    <div class="d-flex align-items-center p-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-bold small"><?= htmlspecialchars($act['title'] ?? '') ?></div>
                            <small class="text-muted">
                                <?= htmlspecialchars($act['activity_type_name'] ?? '') ?>
                                × <?= $act['quantity'] ?? 1 ?>
                            </small>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success"><?= $act['score'] ?? 0 ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center py-4">
                <i class="bi bi-calendar-check text-muted" style="font-size:2rem;"></i>
                <div class="text-muted small mt-2">امروز فعالیتی ثبت نکرده‌اید</div>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-grid gap-2 mb-4">
        <a href="<?= $activityUrl ?>" class="btn btn-primary btn-lg py-3 fw-bold" style="font-size:1.2rem;min-height:56px;">
            <i class="bi bi-plus-circle me-2"></i> ثبت فعالیت جدید
        </a>
    </div>

    <div class="row g-2 mb-4">
        <div class="col-4">
            <a href="<?= $activitiesUrl ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-journal-text text-primary fs-4"></i>
                        <div class="small fw-bold mt-1 text-dark">فعالیت‌ها</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-4">
            <a href="<?= $targetsUrl ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-bullseye text-danger fs-4"></i>
                        <div class="small fw-bold mt-1 text-dark">اهداف</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-4">
            <a href="<?= $reportsUrl ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-bar-chart text-success fs-4"></i>
                        <div class="small fw-bold mt-1 text-dark">گزارش‌ها</div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
