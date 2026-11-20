<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">گزارش روزانه</h4>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('admin/reports/daily') ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">تاریخ</label>
                <input type="text" name="date" class="form-control persian-date-input"
                       value="<?= e($date) ?>" placeholder="۱۴۰۳/۰۵/۱۵" dir="ltr">
            </div>
            <div class="col-md-3">
                <label class="form-label">کاربر</label>
                <select name="user_id" class="form-select">
                    <option value="">همه</option>
                    <?php foreach ($report['users'] ?? [] as $u): ?>
                        <option value="<?= $u['user_id'] ?>" <?= ($_GET['user_id'] ?? '') == $u['user_id'] ? 'selected' : '' ?>>
                            <?= e($u['user_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">نوع فعالیت</label>
                <select name="type_id" class="form-select">
                    <option value="">همه</option>
                    <?php foreach ($activityTypes as $type): ?>
                        <option value="<?= $type['id'] ?>" <?= ($_GET['type_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                            <?= e($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>نمایش گزارش
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <div class="text-primary"><i class="bi bi-clipboard-data fs-3"></i></div>
                <h3 class="mb-0 mt-2"><?= $report['total_activities'] ?? 0 ?></h3>
                <small class="text-muted">کل فعالیت‌ها</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <div class="text-success"><i class="bi bi-people fs-3"></i></div>
                <h3 class="mb-0 mt-2"><?= $report['active_users'] ?? 0 ?></h3>
                <small class="text-muted">کاربران فعال</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <div class="text-warning"><i class="bi bi-star fs-3"></i></div>
                <h3 class="mb-0 mt-2"><?= $report['total_score'] ?? 0 ?></h3>
                <small class="text-muted">مجموع امتیاز</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info h-100">
            <div class="card-body text-center">
                <div class="text-info"><i class="bi bi-flag fs-3"></i></div>
                <h3 class="mb-0 mt-2"><?= $report['mission_count'] ?? 0 ?></h3>
                <small class="text-muted">مأموریت ویژه</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">جزئیات فعالیت‌ها</h5>
        <small class="text-muted"><?= e($date) ?></small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>نوع فعالیت</th>
                    <th class="text-center">تعداد</th>
                    <th>عنوان</th>
                    <th class="text-center">امتیاز</th>
                    <th class="text-center">مأموریت</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($report['activities'])): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            فعالیتی برای این تاریخ ثبت نشده است.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($report['activities'] as $index => $act): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td class="fw-semibold"><?= e($act['user_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($act['type_name']) ?></span></td>
                            <td class="text-center"><?= $act['quantity'] ?></td>
                            <td><?= e($act['title']) ?></td>
                            <td class="text-center fw-bold text-primary"><?= $act['score'] ?></td>
                            <td class="text-center">
                                <?= $act['is_mission'] ? '<span class="badge bg-warning text-dark">بله</span>' : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
