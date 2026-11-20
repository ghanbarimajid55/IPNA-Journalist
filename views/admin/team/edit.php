<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">ویرایش عضو تیم: <?= e($member['name']) ?></h4>
    <a href="<?= url('admin/team') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right me-1"></i>بازگشت
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('admin/team/' . $member['id'] . '/update') ?>" id="teamForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="_method" value="PUT">

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="name" class="form-label">نام کامل <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?= e(old('name', $member['name'])) ?>">
                    <div class="invalid-feedback">نام الزامی است.</div>
                </div>

                <div class="col-md-6">
                    <label for="username" class="form-label">نام کاربری <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" required
                           value="<?= e(old('username', $member['username'])) ?>" pattern="[a-zA-Z0-9_]+" dir="ltr">
                    <div class="invalid-feedback">نام کاربری الزامی است.</div>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">ایمیل <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required
                           value="<?= e(old('email', $member['email'])) ?>" dir="ltr">
                    <div class="invalid-feedback">ایمیل معتبر وارد کنید.</div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">رمز عبور جدید</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="خالی بگذارید برای حفظ رمز فعلی" minlength="8">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">در صورت خالی گذاشتن، رمز عبور قبلی حفظ می‌شود.</div>
                </div>

                <div class="col-md-4">
                    <label for="role" class="form-label">نقش <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="reporter" <?= old('role', $member['role']) === 'reporter' ? 'selected' : '' ?>>خبرنگار</option>
                        <option value="photographer" <?= old('role', $member['role']) === 'photographer' ? 'selected' : '' ?>>عکاس</option>
                    </select>
                    <div class="invalid-feedback">انتخاب نقش الزامی است.</div>
                </div>

                <div class="col-md-4">
                    <label for="position" class="form-label">سمت <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="position" name="position" required
                           value="<?= e(old('position', $member['position'] ?? '')) ?>">
                    <div class="invalid-feedback">سمت الزامی است.</div>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">وضعیت <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active" <?= old('status', $member['status']) === 'active' ? 'selected' : '' ?>>فعال</option>
                        <option value="inactive" <?= old('status', $member['status']) === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>ذخیره تغییرات
                </button>
                <a href="<?= url('admin/team') ?>" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('teamForm').addEventListener('submit', function(e) {
    let valid = true;
    const fields = ['name', 'username', 'email', 'role', 'position'];

    fields.forEach(function(id) {
        const el = document.getElementById(id);
        if (!el.value.trim()) {
            el.classList.add('is-invalid');
            valid = false;
        } else {
            el.classList.remove('is-invalid');
            el.classList.add('is-valid');
        }
    });

    const pwd = document.getElementById('password');
    if (pwd.value && pwd.value.length < 8) {
        pwd.classList.add('is-invalid');
        valid = false;
    } else {
        pwd.classList.remove('is-invalid');
    }

    if (!valid) e.preventDefault();
});

function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
