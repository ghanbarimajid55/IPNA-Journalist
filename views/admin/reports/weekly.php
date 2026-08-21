<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">گزارش هفتگی</h4>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('admin/reports/weekly') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">از تاریخ</label>
                <input type="text" name="start_date" class="form-control persian-date-input"
                       value="<?= e($startDate) ?>" placeholder="۱۴۰۳/۰۵/۰۸" dir="ltr">
            </div>
            <div class="col-md-4">
                <label class="form-label">تا تاریخ</label>
                <input type="text" name="end_date" class="form-control persian-date-input"
                       value="<?= e($endDate) ?>" placeholder="۱۴۰۳/۰۵/۱۴" dir="ltr">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-calendar-week me-1"></i>نمایش گزارش
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($report)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">خلاصه هفتگی</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-center">
                    <h2 class="text-primary mb-0"><?= $report['summary']['total_activities'] ?? 0 ?></h2>
                    <small class="text-muted">کل فعالیت‌ها</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h2 class="text-success mb-0"><?= $report['summary']['total_score'] ?? 0 ?></h2>
                    <small class="text-muted">مجموع امتیاز</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h2 class="text-info mb-0"><?= $report['summary']['active_users'] ?? 0 ?></h2>
                    <small class="text-muted">اعضای فعال</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h2 class="text-warning mb-0"><?= $report['summary']['avg_score'] ?? 0 ?></h2>
                    <small class="text-muted">میانگین امتیاز</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">عملکرد هر کاربر</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>سمت</th>
                    <th class="text-center">تعداد فعالیت</th>
                    <th class="text-center">مجموع امتیاز</th>
                    <th class="text-center">مأموریت</th>
                    <th class="text-center">رتبه</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($report['users'] ?? []) as $index => $u): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td class="fw-semibold"><?= e($u['name']) ?></td>
                        <td><?= e($u['position'] ?? '-') ?></td>
                        <td class="text-center">
                            <span class="badge bg-info"><?= $u['activity_count'] ?></span>
                        </td>
                        <td class="text-center fw-bold text-primary"><?= $u['total_score'] ?></td>
                        <td class="text-center"><?= $u['mission_count'] ?? 0 ?></td>
                        <td class="text-center">
                            <?php if ($index === 0): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-trophy me-1"></i>۱</span>
                            <?php elseif ($index === 1): ?>
                                <span class="badge bg-secondary"><i class="bi bi-award me-1"></i>۲</span>
                            <?php elseif ($index === 2): ?>
                                <span class="badge bg-info"><i class="bi bi-award me-1"></i>۳</span>
                            <?php else: ?>
                                <?= $index + 1 ?>
                            <?php endif; ?>
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
        <i class="bi bi-calendar-week fs-1 d-block mb-2"></i>
        تاریخ شروع و پایان را انتخاب کنید و دکمه نمایش را بزنید.
    </div>
</div>
<?php endif; ?>
