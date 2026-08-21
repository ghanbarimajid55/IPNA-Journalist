<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">مدیریت اهداف ماهانه</h4>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('admin/targets') ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">سال</label>
                <select name="year" class="form-select">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= ($year ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
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
                        <option value="<?= $num ?>" <?= ($month ?? (int)date('m')) == $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-calendar-event me-1"></i>نمایش
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle" id="targetsTable">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>سمت</th>
                    <th class="text-center">خبر</th>
                    <th class="text-center">اختصاصی</th>
                    <th class="text-center">مصاحبه</th>
                    <th class="text-center">گزارش ویژه</th>
                    <th class="text-center">عکس</th>
                    <th class="text-center">گالری</th>
                    <th class="text-center">ویدئو</th>
                    <th class="text-center">امتیاز هدف</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($targets)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            <i class="bi bi-bullseye fs-1 d-block mb-2"></i>
                            هدفی برای این ماه ثبت نشده است.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($targets as $index => $target): ?>
                        <tr data-id="<?= $target['user_id'] ?>">
                            <td><?= $index + 1 ?></td>
                            <td class="fw-semibold"><?= e($target['user_name']) ?></td>
                            <td><?= e($target['position'] ?? '-') ?></td>
                            <?php
                            $columns = ['news', 'exclusive', 'interview', 'special_report', 'photo', 'gallery', 'video'];
                            foreach ($columns as $col): ?>
                                <td class="text-center">
                                    <input type="number" class="form-control form-control-sm text-center target-input"
                                           style="width: 70px; margin: 0 auto;"
                                           value="<?= $target[$col] ?? 0 ?>"
                                           data-user="<?= $target['user_id'] ?>"
                                           data-field="<?= $col ?>"
                                           min="0">
                                </td>
                            <?php endforeach; ?>
                            <td class="text-center fw-bold text-primary">
                                <?= number_format($target['target_score'] ?? 0) ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary save-row"
                                        data-user="<?= $target['user_id'] ?>" title="ذخیره">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($targets)): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <button class="btn btn-success" id="bulkSaveBtn">
                <i class="bi bi-save me-1"></i>ذخیره همه تغییرات
            </button>
            <small class="text-muted">تغییرات را اعمال کنید و دکمه ذخیره را بزنید.</small>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.save-row').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const userId = this.dataset.user;
        const row = document.querySelector('tr[data-id="' + userId + '"]');
        const data = {};
        row.querySelectorAll('.target-input').forEach(function(input) {
            data[input.dataset.field] = parseInt(input.value) || 0;
        });

        fetch('<?= url("admin/targets/update/") ?>' + userId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= csrf_token() ?>'
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(function(res) {
            if (res.success) {
                showToast('ذخیره شد', 'success');
            }
        });
    });
});

document.getElementById('bulkSaveBtn')?.addEventListener('click', function() {
    const rows = document.querySelectorAll('#targetsTable tbody tr[data-id]');
    const allData = [];

    rows.forEach(function(row) {
        const userId = row.dataset.id;
        const data = { user_id: userId };
        row.querySelectorAll('.target-input').forEach(function(input) {
            data[input.dataset.field] = parseInt(input.value) || 0;
        });
        allData.push(data);
    });

    fetch('<?= url("admin/targets/bulk-update") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        },
        body: JSON.stringify({ targets: allData })
    })
    .then(r => r.json())
    .then(function(res) {
        if (res.success) {
            showToast('تمام اهداف ذخیره شد', 'success');
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
