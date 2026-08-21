<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">مدیریت انواع فعالیت</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
        <i class="bi bi-plus-lg me-1"></i>افزودن نوع جدید
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>کد</th>
                    <th class="text-center">امتیاز</th>
                    <th>دسته‌بندی</th>
                    <th class="text-center">وضعیت</th>
                    <th class="text-center" style="width: 180px;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activityTypes)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-tags fs-1 d-block mb-2"></i>
                            هیچ نوع فعالیتی ثبت نشده است.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activityTypes as $index => $type): ?>
                        <tr data-id="<?= $type['id'] ?>">
                            <td><?= $index + 1 ?></td>
                            <td class="fw-semibold"><?= e($type['name']) ?></td>
                            <td><code><?= e($type['code']) ?></code></td>
                            <td class="text-center">
                                <div class="input-group input-group-sm" style="width: 120px; margin: 0 auto;">
                                    <input type="number" class="form-control text-center score-input"
                                           value="<?= $type['score'] ?>" min="0"
                                           data-id="<?= $type['id'] ?>">
                                    <button class="btn btn-outline-success save-score" data-id="<?= $type['id'] ?>" title="ذخیره">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= e($type['category'] ?? 'عمومی') ?></span></td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input toggle-status" type="checkbox"
                                           data-id="<?= $type['id'] ?>"
                                           <?= $type['is_active'] ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary edit-type" data-id="<?= $type['id'] ?>"
                                            data-name="<?= e($type['name']) ?>"
                                            data-code="<?= e($type['code']) ?>"
                                            data-score="<?= $type['score'] ?>"
                                            data-category="<?= e($type['category'] ?? '') ?>"
                                            title="ویرایش">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger delete-type" data-id="<?= $type['id'] ?>"
                                            data-name="<?= e($type['name']) ?>" title="حذف">
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

<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= url('admin/activity-types/store') ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header border-0">
                    <h5 class="modal-title">افزودن نوع فعالیت جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="مثلاً: خبر">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">کد <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" required placeholder="مثلاً: news" dir="ltr">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">امتیاز <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="score" required min="0" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">دسته‌بندی</label>
                        <input type="text" class="form-control" name="category" placeholder="مثلاً: تولید محتوا">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editTypeForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-header border-0">
                    <h5 class="modal-title">ویرایش نوع فعالیت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">کد <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" id="editCode" required dir="ltr">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">امتیاز <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="score" id="editScore" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">دسته‌بندی</label>
                        <input type="text" class="form-control" name="category" id="editCategory">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.save-score').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const row = document.querySelector('tr[data-id="' + id + '"]');
        const score = row.querySelector('.score-input').value;

        fetch('<?= url("admin/activity-types/update-score/") ?>' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({ score: score })
        })
        .then(r => r.json())
        .then(res => { if (res.success) showToast('امتیاز ذخیره شد', 'success'); });
    });
});

document.querySelectorAll('.toggle-status').forEach(function(toggle) {
    toggle.addEventListener('change', function() {
        const id = this.dataset.id;
        const active = this.checked ? 1 : 0;

        fetch('<?= url("admin/activity-types/toggle-status/") ?>' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({ is_active: active })
        })
        .then(r => r.json())
        .then(res => { if (res.success) showToast('وضعیت بروزرسانی شد', 'success'); });
    });
});

document.querySelectorAll('.edit-type').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('editTypeForm').action = '<?= url("admin/activity-types/") ?>' + this.dataset.id + '/update';
        document.getElementById('editName').value = this.dataset.name;
        document.getElementById('editCode').value = this.dataset.code;
        document.getElementById('editScore').value = this.dataset.score;
        document.getElementById('editCategory').value = this.dataset.category;
        new bootstrap.Modal(document.getElementById('editTypeModal')).show();
    });
});

document.querySelectorAll('.delete-type').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (confirm('آیا از حذف «' + this.dataset.name + '» اطمینان دارید؟')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url("admin/activity-types/") ?>' + this.dataset.id + '/delete';
            form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        }
    });
});

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + type + ' position-fixed top-0 start-50 translate-middle-x mt-3 shadow';
    toast.style.zIndex = '9999';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 2000);
}
</script>
