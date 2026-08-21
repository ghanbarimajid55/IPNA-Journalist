<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">مدیریت کاربران</h4>
    <a href="<?= url('admin/users/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>افزودن کاربر جدید
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('admin/users') ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="جستجو بر اساس نام، نام کاربری یا ایمیل..." value="<?= e($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">همه نقش‌ها</option>
                        <option value="admin" <?= ($role_filter ?? '') === 'admin' ? 'selected' : '' ?>>مدیر</option>
                        <option value="reporter" <?= ($role_filter ?? '') === 'reporter' ? 'selected' : '' ?>>خبرنگار</option>
                        <option value="photographer" <?= ($role_filter ?? '') === 'photographer' ? 'selected' : '' ?>>عکاس</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-funnel me-1"></i>فیلتر
                    </button>
                    <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">پاک کردن</a>
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
                    <th>نام</th>
                    <th>نام کاربری</th>
                    <th>ایمیل</th>
                    <th>نقش</th>
                    <th>سمت</th>
                    <th>وضعیت</th>
                    <th class="text-center" style="width: 150px;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            هیچ کاربری یافت نشد.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $index => $user): ?>
                        <tr>
                            <td><?= ($pagination['currentPage'] - 1) * 15 + $index + 1 ?></td>
                            <td class="fw-semibold"><?= e($user['name']) ?></td>
                            <td><code><?= e($user['username']) ?></code></td>
                            <td><?= e($user['email']) ?></td>
                            <td>
                                <?php
                                $roleColors = [
                                    'admin' => 'danger',
                                    'reporter' => 'primary',
                                    'photographer' => 'success',
                                ];
                                $roleLabels = [
                                    'admin' => 'مدیر',
                                    'reporter' => 'خبرنگار',
                                    'photographer' => 'عکاس',
                                ];
                                $color = $roleColors[$user['role']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= $roleLabels[$user['role']] ?? $user['role'] ?></span>
                            </td>
                            <td><?= e($user['position'] ?? '-') ?></td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge bg-success">فعال</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">غیرفعال</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('admin/users/' . $user['id'] . '/edit') ?>" class="btn btn-outline-primary" title="ویرایش">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="حذف"
                                        onclick="confirmDelete(<?= $user['id'] ?>, '<?= e($user['name']) ?>')">
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
            <a class="page-link" href="?page=<?= $pagination['currentPage'] - 1 ?>&search=<?= e($search ?? '') ?>&role=<?= e($role_filter ?? '') ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
            <li class="page-item <?= $i === $pagination['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&search=<?= e($search ?? '') ?>&role=<?= e($role_filter ?? '') ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $pagination['currentPage'] >= $pagination['totalPages'] ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $pagination['currentPage'] + 1 ?>&search=<?= e($search ?? '') ?>&role=<?= e($role_filter ?? '') ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">تأیید حذف کاربر</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>آیا از حذف کاربر <strong id="deleteUserName"></strong> اطمینان دارید؟</p>
                <p class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>این عمل غیرقابل بازگشت است.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <form id="deleteForm" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>حذف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteForm').action = '<?= url("admin/users/delete/") ?>' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
