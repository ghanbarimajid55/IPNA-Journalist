<div class="container-fluid px-2 py-3" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">گزارش ماهانه</h5>
    </div>

    <form method="GET" action="<?= BASE_URL ?>/photographer/reports/monthly" class="mb-3">
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

    <?php if (!empty($report)): ?>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-primary" style="font-size:1.5rem;"><?= $report['total_activities'] ?? 0 ?></div>
                        <div class="text-muted small">کل فعالیت‌ها</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-success" style="font-size:1.5rem;"><?= $report['total_photos'] ?? 0 ?></div>
                        <div class="text-muted small">عکس</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-danger" style="font-size:1.5rem;"><?= $report['total_videos'] ?? 0 ?></div>
                        <div class="text-muted small">ویدیو</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center py-3">
                <span class="text-muted">امتیاز کل ماه:</span>
                <span class="fw-bold text-primary ms-2" style="font-size:1.4rem;"><?= $report['total_score'] ?? 0 ?></span>
            </div>
        </div>

        <?php if (!empty($target)): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"> مقایسه با هدف</h6>
                    <?php
                        $achPercent = min(100, $achievement['percentage'] ?? 0);
                        $barColor = $achPercent >= 80 ? 'success' : ($achPercent >= 50 ? 'warning' : 'danger');
                    ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span>هدف: <?= $target['score_target'] ?? 0 ?> امتیاز</span>
                        <span class="fw-bold text-<?= $barColor ?>"><?= number_format($achPercent, 1) ?>%</span>
                    </div>
                    <div class="progress mb-3" style="height:1.2rem;border-radius:1rem;">
                        <div class="progress-bar bg-<?= $barColor ?> progress-bar-striped progress-bar-animated"
                             style="width:<?= $achPercent ?>%;"></div>
                    </div>
                    <div class="text-center text-muted small">
                        فعلی: <?= $report['total_score'] ?? 0 ?> از <?= $target['score_target'] ?? 0 ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($report['type_breakdown'])): ?>
            <h6 class="fw-bold mb-2">تفکیک نوع فعالیت</h6>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-0">
                    <?php foreach ($report['type_breakdown'] as $type): ?>
                        <?php
                            $name = strtolower($type['name'] ?? '');
                            $isPhoto = in_array($name, ['عکس', 'photo', 'تصویر']);
                            $isVideo = in_array($name, ['ویدیو', 'video', 'فیلم']);
                            $iconClass = $isPhoto ? 'text-success' : ($isVideo ? 'text-danger' : 'text-muted');
                            $icon = $isPhoto ? 'bi-camera' : ($isVideo ? 'bi-camera-video' : 'bi-file-earmark-text');
                        ?>
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <span class="fw-bold small">
                                <i class="bi <?= $icon ?> <?= $iconClass ?> me-2"></i>
                                <?= htmlspecialchars($type['name']) ?>
                            </span>
                            <div>
                                <span class="badge bg-primary me-2"><?= $type['count'] ?? 0 ?></span>
                                <span class="badge bg-success"><?= $type['score'] ?? 0 ?> امتیاز</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($dailyScores)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <span class="fw-bold">نمودار امتیاز روزانه</span>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="200"></canvas>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar text-muted" style="font-size:3rem;"></i>
                <h6 class="text-muted mt-3">داده‌ای برای این ماه وجود ندارد</h6>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($dailyScores)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= json_encode(array_column($dailyScores, 'date')) ?>;
    const scores = <?= json_encode(array_column($dailyScores, 'score')) ?>;
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'امتیاز',
                data: scores,
                borderColor: 'rgba(13,110,253,1)',
                backgroundColor: 'rgba(13,110,253,0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
});
</script>
<?php endif; ?>
