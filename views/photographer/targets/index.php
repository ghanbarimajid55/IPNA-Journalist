<div class="container-fluid px-2 py-3" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">هدف ماهانه من</h5>
    </div>

    <form method="GET" action="<?= BASE_URL ?>/photographer/targets" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-5">
                <label class="form-label small fw-bold">ماه</label>
                <select class="form-select form-select-sm" name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($month ?? intval(date('n')) == $m) ? 'selected' : '' ?>><?= jdate_month($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-5">
                <label class="form-label small fw-bold">سال</label>
                <select class="form-select form-select-sm" name="year">
                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= ($year ?? intval(date('Y'))) == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-2 d-grid">
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </form>

    <?php if (!empty($target)): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center py-4">
                <h6 class="text-muted mb-2">میزان تحقق هدف</h6>
                <?php
                    $achPercent = min(100, $achievement['percentage'] ?? 0);
                    $barColor = $achPercent >= 80 ? 'success' : ($achPercent >= 50 ? 'warning' : 'danger');
                ?>
                <div class="display-4 fw-bold text-<?= $barColor ?> mb-2"><?= number_format($achPercent, 1) ?>%</div>
                <div class="progress" style="height:1.5rem;border-radius:1rem;">
                    <div class="progress-bar bg-<?= $barColor ?> progress-bar-striped progress-bar-animated"
                         style="width:<?= $achPercent ?>%;" role="progressbar">
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    امتیاز فعلی: <?= $stats['current_score'] ?? 0 ?> از <?= $target['score_target'] ?? 0 ?>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-primary" style="font-size:1.4rem;"><?= $stats['current_score'] ?? 0 ?></div>
                        <div class="text-muted small">امتیاز فعلی</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm bg-info bg-opacity-10">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-info" style="font-size:1.4rem;"><?= $target['score_target'] ?? 0 ?></div>
                        <div class="text-muted small">هدف امتیاز</div>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="fw-bold mb-2">جزئیات هر دسته</h6>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php
                    $categories = [
                        'photos' => ['label' => 'عکس', 'icon' => 'bi-camera', 'highlight' => true],
                        'videos' => ['label' => 'ویدیو', 'icon' => 'bi-camera-video', 'highlight' => true],
                        'photo_news' => ['label' => 'خبر تصویری', 'icon' => 'bi-image', 'highlight' => false],
                        'exclusive_photos' => ['label' => 'عکس اختصاصی', 'icon' => 'bi-star', 'highlight' => false],
                    ];
                    foreach ($categories as $key => $cat):
                        $targetVal = $target[$key . '_target'] ?? 0;
                        $actualVal = $stats[$key] ?? 0;
                        $pct = $targetVal > 0 ? min(100, ($actualVal / $targetVal) * 100) : 0;
                        $pctColor = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                ?>
                    <div class="d-flex align-items-center p-3 border-bottom <?= $cat['highlight'] ? 'bg-light' : '' ?>">
                        <i class="bi <?= $cat['icon'] ?> <?= $cat['highlight'] ? 'text-success' : 'text-muted' ?> me-3" style="font-size:1.3rem;"></i>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold small">
                                    <?= $cat['label'] ?>
                                    <?php if ($cat['highlight']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success ms-1">اصلی</span>
                                    <?php endif; ?>
                                </span>
                                <span class="small text-muted"><?= $actualVal ?> / <?= $targetVal ?></span>
                            </div>
                            <div class="progress" style="height:0.5rem;border-radius:0.5rem;">
                                <div class="progress-bar bg-<?= $pctColor ?>" style="width:<?= $pct ?>%;"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bullseye text-muted" style="font-size:3rem;"></i>
                <h6 class="text-muted mt-3">هدفی برای این ماه تعیین نشده</h6>
                <p class="text-muted small">با مدیر خود تماس بگیرید</p>
            </div>
        </div>
    <?php endif; ?>
</div>
