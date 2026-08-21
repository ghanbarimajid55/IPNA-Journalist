<?php
$title = 'داشبورد مدیریت';

$todayStats = $todayStats ?? [];
$weekStats = $weekStats ?? [];
$monthStats = $monthStats ?? [];
$topPerformers = $topPerformers ?? [];
$activityDistribution = $activityDistribution ?? [];
$dailyProduction = $dailyProduction ?? [];
$teamTargets = $teamTargets ?? [];

$activityTypes = [
    'news'           => ['label' => 'خبر عادی',     'icon' => 'bi-file-earmark-text', 'color' => '#4361ee'],
    'exclusive_news' => ['label' => 'خبر اختصاصی',  'icon' => 'bi-star',              'color' => '#f72585'],
    'interview'      => ['label' => 'مصاحبه',        'icon' => 'bi-mic',               'color' => '#7209b7'],
    'special_report' => ['label' => 'گزارش ویژه',   'icon' => 'bi-journal-richtext',  'color' => '#3a0ca3'],
    'news_photo'     => ['label' => 'عکس خبری',     'icon' => 'bi-camera',            'color' => '#4cc9f0'],
    'photo_gallery'  => ['label' => 'گالری عکس',    'icon' => 'bi-images',            'color' => '#06d6a0'],
    'exclusive_video'=> ['label' => 'ویدئوی اختصاصی','icon' => 'bi-camera-video',     'color' => '#fca311'],
];

$periods = [
    'today'  => ['label' => 'امروز',       'stats' => $todayStats],
    'week'   => ['label' => 'این هفته',    'stats' => $weekStats],
    'month'  => ['label' => 'این ماه',     'stats' => $monthStats],
];

$medals = ['bi-trophy-fill text-warning', 'bi-trophy-fill text-secondary', 'bi-trophy-fill text-danger'];

$todayJalali = Helper::jalaliDate(date('Y-m-d'));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 fw-bold">
            <i class="bi bi-speedometer2 ms-2"></i>داشبورد مدیریت
        </h3>
        <span class="text-muted">
            <i class="bi bi-calendar3 ms-1"></i><?= $todayJalali ?>
        </span>
    </div>
    <div>
        <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> چاپ
        </button>
    </div>
</div>

<!-- Time Period Tabs -->
<ul class="nav nav-pills mb-4" id="periodTabs" role="tablist">
    <?php foreach ($periods as $key => $period): ?>
        <li class="nav-item" role="presentation">
            <button
                class="nav-link <?= $key === 'today' ? 'active' : '' ?>"
                id="tab-<?= $key ?>"
                data-bs-toggle="pill"
                data-bs-target="#panel-<?= $key ?>"
                type="button"
                role="tab"
                aria-controls="panel-<?= $key ?>"
                aria-selected="<?= $key === 'today' ? 'true' : 'false' ?>"
            >
                <?php if ($key === 'today'): ?>
                    <i class="bi bi-sun ms-1"></i>
                <?php elseif ($key === 'week'): ?>
                    <i class="bi bi-calendar-week ms-1"></i>
                <?php else: ?>
                    <i class="bi bi-calendar-month ms-1"></i>
                <?php endif; ?>
                <?= $period['label'] ?>
            </button>
        </li>
    <?php endforeach; ?>
</ul>

<!-- KPI Cards (Tab Panels) -->
<div class="tab-content" id="periodTabContent">
    <?php foreach ($periods as $pKey => $period): ?>
        <div
            class="tab-pane fade <?= $pKey === 'today' ? 'show active' : '' ?>"
            id="panel-<?= $pKey ?>"
            role="tabpanel"
            aria-labelledby="tab-<?= $pKey ?>"
        >
            <div class="row g-3 mb-4">
                <?php foreach ($activityTypes as $type => $meta): ?>
                    <?php if ($type === 'exclusive_video') echo '</div><div class="row g-3 mb-4">'; ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card border-0 shadow-sm h-100 kpi-card" style="border-right: 4px solid <?= $meta['color'] ?> !important;">
                            <div class="card-body d-flex align-items-center">
                                <div
                                    class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                    style="width:48px;height:48px;background:<?= $meta['color'] ?>15;"
                                >
                                    <i class="bi <?= $meta['icon'] ?> fs-4" style="color:<?= $meta['color'] ?>;"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold fs-4 lh-1">
                                        <?= number_format($period['stats'][$type] ?? 0) ?>
                                    </div>
                                    <small class="text-muted"><?= $meta['label'] ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Total Score Card -->
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 kpi-card" style="border-right: 4px solid #1a237e !important; background: linear-gradient(135deg, #1a237e08, #1a237e15);">
                        <div class="card-body d-flex align-items-center">
                            <div
                                class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                style="width:48px;height:48px;background:#1a237e20;"
                            >
                                <i class="bi bi-award fs-4" style="color:#1a237e;"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="fw-bold fs-4 lh-1" style="color:#1a237e;">
                                    <?= number_format($period['stats']['total_score'] ?? 0) ?>
                                </div>
                                <small class="text-muted fw-semibold">امتیاز کل</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Line Chart - Daily Production -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-graph-up ms-2 text-primary"></i>عملکرد روزانه
                    <small class="text-muted fw-normal ms-2">(۳۰ روز اخیر)</small>
                </h6>
            </div>
            <div class="card-body">
                <div style="position:relative;height:300px;">
                    <canvas id="dailyProductionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Doughnut Chart - Activity Distribution -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-pie-chart ms-2 text-primary"></i>توزیع فعالیت‌ها
                </h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="position:relative;height:280px;width:100%;max-width:280px;margin:auto;">
                    <canvas id="activityDistChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Team Performance Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-people ms-2 text-primary"></i>عملکرد تیم
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="teamTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px;">رتبه</th>
                        <th data-sort="name">نام</th>
                        <th data-sort="position">سمت</th>
                        <th class="text-center" data-sort="news">خبر</th>
                        <th class="text-center" data-sort="exclusive">اختصاصی</th>
                        <th class="text-center" data-sort="interview">مصاحبه</th>
                        <th class="text-center" data-sort="special">گزارش ویژه</th>
                        <th class="text-center" data-sort="photo">عکس</th>
                        <th class="text-center" data-sort="gallery">گالری</th>
                        <th class="text-center" data-sort="video">ویدئو</th>
                        <th class="text-center" data-sort="score">امتیاز</th>
                        <th class="text-center" data-sort="target">هدف</th>
                        <th class="text-center" style="min-width:120px;">تحقق</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teamTargets as $idx => $member): ?>
                        <?php
                        $achievement = ($member['target'] ?? 0) > 0
                            ? round(($member['current'] ?? 0) / ($member['target'] ?? 1) * 100)
                            : 0;
                        $barColor = $achievement >= 80 ? 'success' : ($achievement >= 50 ? 'warning' : 'danger');
                        ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $idx + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($member['name'] ?? '') ?></td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($member['position'] ?? '') ?></span></td>
                            <td class="text-center"><?= $member['scores']['news'] ?? 0 ?></td>
                            <td class="text-center"><?= $member['scores']['exclusive_news'] ?? 0 ?></td>
                            <td class="text-center"><?= $member['scores']['interview'] ?? 0 ?></td>
                            <td class="text-center"><?= $member['scores']['special_report'] ?? 0 ?></td>
                            <td class="text-center"><?= $member['scores']['news_photo'] ?? 0 ?></td>
                            <td class="text-center"><?= $member['scores']['photo_gallery'] ?? 0 ?></td>
                            <td class="text-center"><?= $member['scores']['exclusive_video'] ?? 0 ?></td>
                            <td class="text-center fw-bold"><?= number_format($member['current'] ?? 0) ?></td>
                            <td class="text-center"><?= number_format($member['target'] ?? 0) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px;">
                                        <div
                                            class="progress-bar bg-<?= $barColor ?>"
                                            style="width:<?= min($achievement, 100) ?>%"
                                            role="progressbar"
                                            aria-valuenow="<?= $achievement ?>"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                    <small class="text-<?= $barColor ?> fw-bold" style="min-width:38px;text-align:left;"><?= $achievement ?>%</small>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($teamTargets)): ?>
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">داده‌ای موجود نیست</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top 10 Performers -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-trophy ms-2 text-warning"></i>۱۰ عملکرد برتر
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach (array_slice($topPerformers, 0, 10) as $idx => $performer): ?>
                <?php
                $isTop3 = $idx < 3;
                $cardBorder = $idx === 0 ? 'warning' : ($idx === 1 ? 'secondary' : ($idx === 2 ? 'danger' : 'light'));
                $bgGrad = $idx === 0
                    ? 'linear-gradient(135deg, #ffd70010, #ffd70005)'
                    : ($idx === 1 ? 'linear-gradient(135deg, #c0c0c010, #c0c0c005)' : ($idx === 2 ? 'linear-gradient(135deg, #cd7f3210, #cd7f3205)' : ''));
                ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2 <?= $idx === 5 ? 'clearfix d-none d-lg-block' : '' ?>">
                    <div
                        class="card h-100 text-center border-<?= $isTop3 ? $cardBorder : 'light' ?> shadow-sm performer-card"
                        style="<?= $bgGrad ?>"
                    >
                        <div class="card-body py-3 px-2">
                            <!-- Rank Badge -->
                            <div class="position-relative mb-2">
                                <?php if ($isTop3): ?>
                                    <i class="bi <?= $medals[$idx] ?> fs-1"></i>
                                <?php else: ?>
                                    <div
                                        class="mx-auto rounded-circle d-flex align-items-center justify-content-center fw-bold text-muted"
                                        style="width:42px;height:42px;background:#f0f0f0;font-size:1.1rem;"
                                    >
                                        <?= $idx + 1 ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Name -->
                            <h6 class="card-title fw-bold mb-1 small">
                                <?= htmlspecialchars($performer['name'] ?? '') ?>
                            </h6>
                            <!-- Position -->
                            <small class="text-muted d-block mb-2">
                                <?= htmlspecialchars($performer['position'] ?? '') ?>
                            </small>
                            <!-- Score -->
                            <div class="fw-bold fs-5 text-primary mb-1">
                                <?= number_format($performer['total_score'] ?? 0) ?>
                                <small class="fs-6 text-muted fw-normal">امتیاز</small>
                            </div>
                            <!-- Achievement -->
                            <?php
                            $ach = $performer['achievement'] ?? 0;
                            $achColor = $ach >= 80 ? 'success' : ($ach >= 50 ? 'warning' : 'danger');
                            ?>
                            <div class="progress mt-1" style="height:6px;">
                                <div class="progress-bar bg-<?= $achColor ?>" style="width:<?= min($ach, 100) ?>%"></div>
                            </div>
                            <small class="text-<?= $achColor ?> fw-bold"><?= $ach ?>% تحقق</small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($topPerformers)): ?>
                <div class="col-12 text-center text-muted py-4">داده‌ای موجود نیست</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Charts Initialization Script -->
<?php
$dailyLabels = array_keys($dailyProduction);
$dailyValues = array_values($dailyProduction);
$distLabels = array_keys($activityDistribution);
$distValues = array_values($activityDistribution);
$distColors = [];
foreach ($distLabels as $dl) {
    foreach ($activityTypes as $type => $meta) {
        $keyMap = [
            'خبر عادی' => 'news', 'خبر اختصاصی' => 'exclusive_news',
            'مصاحبه' => 'interview', 'گزارش ویژه' => 'special_report',
            'عکس خبری' => 'news_photo', 'گالری عکس' => 'photo_gallery',
            'ویدئوی اختصاصی' => 'exclusive_video',
        ];
        if (in_array($dl, array_keys($keyMap)) && $keyMap[$dl] === $type) {
            $distColors[] = $meta['color'];
            break 1;
        }
    }
}
if (count($distColors) < count($distLabels)) {
    $fallback = ['#4361ee','#f72585','#7209b7','#3a0ca3','#4cc9f0','#06d6a0','#fca311'];
    while (count($distColors) < count($distLabels)) {
        $distColors[] = $fallback[count($distColors) % count($fallback)];
    }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ── Daily Production Line Chart ── */
    const dailyCtx = document.getElementById('dailyProductionChart');
    if (dailyCtx) {
        new Chart(dailyCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($dailyLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'امتیاز کل',
                    data: <?= json_encode($dailyValues) ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67,97,238,0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#4361ee',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a237e',
                        titleFont: { family: 'Vazirmatn' },
                        bodyFont: { family: 'Vazirmatn' },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Vazirmatn', size: 11 },
                            maxRotation: 45,
                            maxTicksLimit: 15
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            font: { family: 'Vazirmatn', size: 11 }
                        }
                    }
                }
            }
        });
    }

    /* ── Activity Distribution Doughnut Chart ── */
    const distCtx = document.getElementById('activityDistChart');
    if (distCtx) {
        new Chart(distCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($distLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode($distValues) ?>,
                    backgroundColor: <?= json_encode($distColors) ?>,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverBorderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Vazirmatn', size: 11 },
                            padding: 12,
                            usePointStyle: true,
                            pointStyleWidth: 10
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1a237e',
                        titleFont: { family: 'Vazirmatn' },
                        bodyFont: { family: 'Vazirmatn' },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    /* ── Table Column Sorting ── */
    document.querySelectorAll('#teamTable th[data-sort]').forEach(function(th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const col = th.getAttribute('data-sort');
            const idx = Array.from(th.parentNode.children).indexOf(th);
            const asc = th.dataset.dir !== 'asc';
            th.dataset.dir = asc ? 'asc' : 'desc';

            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(function(a, b) {
                const cellA = a.children[idx]?.textContent.trim() || '';
                const cellB = b.children[idx]?.textContent.trim() || '';
                const numA = parseFloat(cellA.replace(/,/g, ''));
                const numB = parseFloat(cellB.replace(/,/g, ''));
                if (!isNaN(numA) && !isNaN(numB)) {
                    return asc ? numA - numB : numB - numA;
                }
                return asc ? cellA.localeCompare(cellB, 'fa') : cellB.localeCompare(cellA, 'fa');
            });

            rows.forEach(function(r) { tbody.appendChild(r); });
        });
    });
});
</script>

<style>
.kpi-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    border-right-width: 4px !important;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08) !important;
}
.performer-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.performer-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}
#teamTable thead th {
    white-space: nowrap;
    font-size: 0.8rem;
    text-transform: none;
    color: #495057;
}
#teamTable tbody td {
    font-size: 0.85rem;
}
#periodTabs .nav-link {
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
}
</style>
