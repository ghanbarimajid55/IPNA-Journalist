<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">ویرایش فعالیت</h4>
    <a href="<?= url('admin/activities') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right me-1"></i>بازگشت
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('admin/activities/' . $activity['id'] . '/update') ?>" id="activityForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="_method" value="PUT">

            <div class="row g-4">
                <div class="col-md-4">
                    <label for="user_id" class="form-label">کاربر <span class="text-danger">*</span></label>
                    <select class="form-select" id="user_id" name="user_id" required>
                        <option value="">انتخاب کاربر...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $activity['user_id'] == $user['id'] ? 'selected' : '' ?>>
                                <?= e($user['name']) ?> (<?= e($user['position'] ?? $user['role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">انتخاب کاربر الزامی است.</div>
                </div>

                <div class="col-md-4">
                    <label for="date" class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control persian-date-input" id="date" name="date" required
                           value="<?= e($activity['date']) ?>" placeholder="۱۴۰۳/۰۵/۱۵" dir="ltr">
                    <div class="invalid-feedback">تاریخ الزامی است.</div>
                </div>

                <div class="col-md-4">
                    <label for="activity_type_id" class="form-label">نوع فعالیت <span class="text-danger">*</span></label>
                    <select class="form-select" id="activity_type_id" name="activity_type_id" required>
                        <option value="">انتخاب نوع...</option>
                        <?php foreach ($activityTypes as $type): ?>
                            <option value="<?= $type['id'] ?>"
                                data-score="<?= $type['score'] ?>"
                                <?= $activity['activity_type_id'] == $type['id'] ? 'selected' : '' ?>>
                                <?= e($type['name']) ?> (<?= $type['score'] ?> امتیاز)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">انتخاب نوع فعالیت الزامی است.</div>
                </div>

                <div class="col-md-3">
                    <label for="quantity" class="form-label">تعداد <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required
                           value="<?= $activity['quantity'] ?>" min="1">
                    <div class="invalid-feedback">تعداد الزامی است.</div>
                </div>

                <div class="col-md-5">
                    <label for="title" class="form-label">عنوان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required
                           value="<?= e($activity['title']) ?>">
                    <div class="invalid-feedback">عنوان الزامی است.</div>
                </div>

                <div class="col-md-4">
                    <label for="link" class="form-label">لینک</label>
                    <input type="url" class="form-control" id="link" name="link"
                           value="<?= e($activity['link'] ?? '') ?>" placeholder="https://..." dir="ltr">
                </div>

                <div class="col-md-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_mission" name="is_mission" value="1"
                               <?= $activity['is_mission'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_mission">مأموریت ویژه</label>
                    </div>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">توضیحات</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= e($activity['description'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center">
                            <small class="text-muted">امتیاز تقریبی</small>
                            <div class="fs-4 fw-bold text-primary" id="estimatedScore">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>ذخیره تغییرات
                </button>
                <a href="<?= url('admin/activities') ?>" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('activityForm').addEventListener('submit', function(e) {
    let valid = true;
    ['user_id', 'date', 'activity_type_id', 'quantity', 'title'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el.value.trim()) {
            el.classList.add('is-invalid');
            valid = false;
        } else {
            el.classList.remove('is-invalid');
            el.classList.add('is-valid');
        }
    });
    if (!valid) e.preventDefault();
});

function updateScore() {
    const typeSelect = document.getElementById('activity_type_id');
    const selected = typeSelect.options[typeSelect.selectedIndex];
    const score = selected.dataset.score || 0;
    const qty = parseInt(document.getElementById('quantity').value) || 0;
    document.getElementById('estimatedScore').textContent = (score * qty).toLocaleString('fa-IR');
}

document.getElementById('activity_type_id').addEventListener('change', updateScore);
document.getElementById('quantity').addEventListener('input', updateScore);
updateScore();
</script>
