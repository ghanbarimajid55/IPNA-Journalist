<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">گزارش ماهانه</h4>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/export/monthly-') ?><?= $year ?>-<?= $month ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i>خروجی CSV
        </a>
        <a href="<?= url('admin/export/monthly-') ?><?= $year ?>-<?= $month ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>خروجی Excel
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('admin/reports/monthly') ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">سال</label>
                <select name="year" class="form-select">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ماه</label>
                <select name="month" class="form-select">
                    <?php
                    $persianMonths = [
                        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
                        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
                        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
                    ];
                    foreach ($persianMonths as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-calendar-month me-1"></i>نمایش گزارش
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($ranking)): ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">رتبه‌بندی ماهانه - <?= $persianMonths[$month] ?> <?= $year ?></h5>
        <small class="text-muted"><?= count($ranking) ?> نفر</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle" id="rankingTable">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">
                        <a href="#" class="text-decoration-none text-dark" onclick="sortTable(0)">
                            # <i class="bi bi-arrow-down-up small"></i>
                        </a>
                    </th>
                    <th>
                        <a href="#" class="text-decoration-none text-dark" onclick="sortTable(1)">
                            نام <i class="bi bi-arrow-down-up small"></i>
                        </a>
                    </th>
                    <th>سمت</th>
                    <th class="text-center sortable" onclick="sortTable(3)">
                        خبر <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(4)">
                        اختصاصی <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(5)">
                        مصاحبه <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(6)">
                        گزارش ویژه <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(7)">
                        عکس <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(8)">
                        گالری <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(9)">
                        ویدئو <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(10)">
                        امتیاز هدف <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(11)">
                        امتیاز کسب شده <i class="bi bi-arrow-down-up small"></i>
                    </th>
                    <th class="text-center sortable" onclick="sortTable(12)">
                        درصد تحقق <i class="bi bi-arrow-down-up small"></i>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ranking as $index => $row): ?>
                    <?php
                    $targetScore = $row['target_score'] ?? 0;
                    $earnedScore = $row['total_score'] ?? 0;
                    $pct = $targetScore > 0 ? min(100, round(($earnedScore / $targetScore) * 100)) : 0;
                    ?>
                    <tr>
                        <td>
                            <?php if ($index === 0): ?>
                                <span class="badge bg-warning text-dark fs-6"><i class="bi bi-trophy"></i> ۱</span>
                            <?php elseif ($index === 1): ?>
                                <span class="badge bg-secondary fs-6">۲</span>
                            <?php elseif ($index === 2): ?>
                                <span class="badge bg-info fs-6">۳</span>
                            <?php else: ?>
                                <?= $index + 1 ?>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold"><?= e($row['user_name']) ?></td>
                        <td><?= e($row['position'] ?? '-') ?></td>
                        <td class="text-center"><?= $row['news_normal'] ?? 0 ?></td>
                        <td class="text-center"><?= $row['news_exclusive'] ?? 0 ?></td>
                        <td class="text-center"><?= $row['interview_count'] ?? 0 ?></td>
                        <td class="text-center"><?= $row['special_report_count'] ?? 0 ?></td>
                        <td class="text-center"><?= $row['photo_news'] ?? 0 ?></td>
                        <td class="text-center"><?= $row['gallery_count'] ?? 0 ?></td>
                        <td class="text-center"><?= $row['exclusive_video_count'] ?? 0 ?></td>
                        <td class="text-center"><?= number_format($targetScore) ?></td>
                        <td class="text-center fw-bold text-primary"><?= number_format($earnedScore) ?></td>
                        <td style="min-width: 130px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : ($pct >= 60 ? 'bg-warning' : 'bg-danger') ?>"
                                         style="width: <?= $pct ?>%"></div>
                                </div>
                                <small class="fw-bold <?= $pct >= 100 ? 'text-success' : ($pct >= 60 ? 'text-warning' : 'text-danger') ?>">
                                    <?= $pct ?>%
                                </small>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-calendar-month fs-1 d-block mb-2"></i>
        سال و ماه را انتخاب کنید و دکمه نمایش را بزنید.
    </div>
</div>
<?php endif; ?>

<script>
let sortDirection = {};
function sortTable(colIndex) {
    const table = document.getElementById('rankingTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    sortDirection[colIndex] = !sortDirection[colIndex];

    rows.sort(function(a, b) {
        let aVal = a.cells[colIndex].textContent.trim();
        let bVal = b.cells[colIndex].textContent.trim();

        const aNum = parseFloat(aVal.replace(/,/g, ''));
        const bNum = parseFloat(bVal.replace(/,/g, ''));

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return sortDirection[colIndex] ? aNum - bNum : bNum - aNum;
        }
        return sortDirection[colIndex] ? aVal.localeCompare(bVal, 'fa') : bVal.localeCompare(aVal, 'fa');
    });

    rows.forEach(function(row) { tbody.appendChild(row); });
}
</script>
