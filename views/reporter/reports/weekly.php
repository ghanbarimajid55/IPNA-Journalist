<div class="container-fluid px-2 py-3" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">گزارش هفتگی</h5>
    </div>

    <form method="GET" action="<?= BASE_URL ?>/reporter/reports/weekly" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-5">
                <label class="form-label small fw-bold">از تاریخ</label>
                <input type="text" class="form-control form-control-sm" name="start_date"
                       value="<?= htmlspecialchars($startDate ?? '') ?>" placeholder="۱۴۰۵/۰۶/۰۱">
            </div>
            <div class="col-5">
                <label class="form-label small fw-bold">تا تاریخ</label>
                <input type="text" class="form-control form-control-sm" name="end_date"
                       value="<?= htmlspecialchars($endDate ?? '') ?>" placeholder="۱۴۰۵/۰۶/۰۷">
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
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-primary" style="font-size:1.5rem;"><?= $report['total_activities'] ?? 0 ?></div>
                        <div class="text-muted small">کل فعالیت‌ها</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-info" style="font-size:1.5rem;"><?= $report['total_news'] ?? 0 ?></div>
                        <div class="text-muted small">اخبار</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-success" style="font-size:1.5rem;"><?= $report['total_photos'] ?? 0 ?></div>
                        <div class="text-muted small">عکس</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold text-warning" style="font-size:1.5rem;"><?= $report['total_videos'] ?? 0 ?></div>
                        <div class="text-muted small">ویدیو</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center py-3">
                <span class="text-muted">امتیاز کل هفته:</span>
                <span class="fw-bold text-primary ms-2" style="font-size:1.4rem;"><?= $report['total_score'] ?? 0 ?></span>
            </div>
        </div>

        <?php if (!empty($dailyBreakdown)): ?>
            <h6 class="fw-bold mb-2">عملکرد روزانه</h6>
            <div class="table-responsive mb-3">
                <table class="table table-hover table-sm bg-white shadow-sm rounded-3 overflow-hidden">
                    <thead class="table-light">
                        <tr>
                            <th>روز</th>
                            <th>تاریخ</th>
                            <th class="text-center">فعالیت</th>
                            <th class="text-center">اخبار</th>
                            <th class="text-center">عکس</th>
                            <th class="text-center">ویدیو</th>
                            <th class="text-center">امتیاز</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dailyBreakdown as $day): ?>
                            <tr>
                                <td class="small fw-bold"><?= $day['day_name'] ?? '' ?></td>
                                <td class="small"><?= htmlspecialchars($day['date'] ?? '') ?></td>
                                <td class="text-center"><?= $day['activities_count'] ?? 0 ?></td>
                                <td class="text-center"><?= $day['news_count'] ?? 0 ?></td>
                                <td class="text-center"><?= $day['photos_count'] ?? 0 ?></td>
                                <td class="text-center"><?= $day['videos_count'] ?? 0 ?></td>
                                <td class="text-center"><span class="badge bg-primary"><?= $day['score'] ?? 0 ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <span class="fw-bold">نمودار عملکرد روزانه</span>
                </div>
                <div class="card-body">
                    <canvas id="weeklyChart" height="200"></canvas>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bar-chart text-muted" style="font-size:3rem;"></i>
                <h6 class="text-muted mt-3">داده‌ای برای نمایش وجود ندارد</h6>
                <p class="text-muted small">بازه زمانی را انتخاب کنید</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($dailyBreakdown)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= json_encode(array_column($dailyBreakdown, 'day_name')) ?>;
    const scores = <?= json_encode(array_column($dailyBreakdown, 'score')) ?>;
    new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'امتیاز',
                data: scores,
                backgroundColor: 'rgba(13,110,253,0.6)',
                borderColor: 'rgba(13,110,253,1)',
                borderWidth: 1,
                borderRadius: 6
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
