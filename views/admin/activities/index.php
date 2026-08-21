<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">مدیریت فعالیت‌ها</h4>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/export/monthly-') ?><?= date('Y') ?>-<?= date('n') ?>" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>خروجی اکسل
        </a>
        <a href="<?= url('admin/activities/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>افزودن فعالیت
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('admin/activities') ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">جستجو</label>
                    <input type="text" name="search" class="form-control" placeholder="عنوان فعالیت..."
                           value="<?= e($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">کاربر</label>
                    <select name="user_id" class="form-select">
                        <option value="">همه</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($filters['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                <?= e($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">نوع فعالیت</label>
                    <select name="activity_type_id" class="form-select">
                        <option value="">همه</option>
                        <?php foreach ($activityTypes as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= ($filters['activity_type_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                <?= e($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">از تاریخ</label>
                    <input type="text" name="date_from" class="form-control persian-date"
                           value="<?= e($filters['date_from'] ?? '') ?>" placeholder="۱۴۰۳/۰۱/۰۱">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">تا تاریخ</label>
                    <input type="text" name="date_to" class="form-control persian-date"
                           value="<?= e($filters['date_to'] ?? '') ?>" placeholder="۱۴۰۳/۱۲/۲۹">
                </div>
                <div class="col-md-1 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <a href="<?= url('admin/activities') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>تاریخ</th>
                    <th>نام</th>
                    <th>نوع فعالیت</th>
                    <th class="text-center">تعداد</th>
                    <th>عنوان</th>
                    <th class="text-center">امتیاز</th>
                    <th class="text-center">مأموریت</th>
                    <th class="text-center" style="width: 120px;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-clipboard-data fs-1 d-block mb-2"></i>
                            هیچ فعالیتی یافت نشد.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activities as $index => $activity): ?>
                        <tr>
                            <td><?= ($pagination['currentPage'] - 1) * 15 + $index + 1 ?></td>
                            <td class="text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i><?= e($activity['date']) ?></td>
                            <td class="fw-semibold"><?= e($activity['user_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($activity['type_name']) ?></span></td>
                            <td class="text-center"><?= $activity['quantity'] ?></td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= e($activity['title']) ?>">
                                    <?= e($activity['title']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-primary"><?= $activity['score'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($activity['is_mission']): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-flag me-1"></i>بله</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('admin/activities/' . $activity['id'] . '/edit') ?>" class="btn btn-outline-primary" title="ویرایش">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="حذف"
                                            onclick="confirmDelete(<?= $activity['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pagination['totalPages'] > 1): ?>
<nav class="mt-4 d-flex justify-content-center">
    <ul class="pagination">
        <li class="page-item <?= $pagination['currentPage'] <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $pagination['currentPage'] - 1 ?>&<?= http_build_query($filters) ?>"><i class="bi bi-chevron-right"></i></a>
        </li>
        <?php
        $start = max(1, $pagination['currentPage'] - 2);
        $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
        ?>
        <?php if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=1&<?= http_build_query($filters) ?>">۱</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $pagination['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($filters) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($end < $pagination['totalPages']): ?>
            <?php if ($end < $pagination['totalPages'] - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['totalPages'] ?>&<?= http_build_query($filters) ?>"><?= $pagination['totalPages'] ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $pagination['currentPage'] >= $pagination['totalPages'] ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $pagination['currentPage'] + 1 ?>&<?= http_build_query($filters) ?>"><i class="bi bi-chevron-left"></i></a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">تأیید حذف فعالیت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>آیا از حذف این فعالیت اطمینان دارید؟</p>
                <p class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>این عمل غیرقابل بازگشت است.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <form id="deleteForm" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>حذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('deleteForm').action = '<?= url("admin/activities/delete/") ?>' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
