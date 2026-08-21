<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">افزودن عضو تیم جدید</h4>
    <a href="<?= url('admin/team') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right me-1"></i>بازگشت
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('admin/team/store') ?>" id="teamForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="name" class="form-label">نام کامل <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required
                           placeholder="نام و نام خانوادگی" value="<?= e(old('name')) ?>">
                    <div class="invalid-feedback">نام الزامی است.</div>
                </div>

                <div class="col-md-6">
                    <label for="username" class="form-label">نام کاربری <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" required
                           placeholder="نام کاربری (انگلیسی)" value="<?= e(old('username')) ?>"
                           pattern="[a-zA-Z0-9_]+" dir="ltr">
                    <div class="invalid-feedback">نام کاربری الزامی است.</div>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">ایمیل <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required
                           placeholder="example@email.com" value="<?= e(old('email')) ?>" dir="ltr">
                    <div class="invalid-feedback">ایمیل معتبر وارد کنید.</div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">رمز عبور <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="حداقل ۸ کاراکتر" minlength="8">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">رمز عبور الزامی است.</div>
                </div>

                <div class="col-md-4">
                    <label for="role" class="form-label">نقش <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">انتخاب نقش...</option>
                        <option value="reporter" <?= old('role') === 'reporter' ? 'selected' : '' ?>>خبرنگار</option>
                        <option value="photographer" <?= old('role') === 'photographer' ? 'selected' : '' ?>>عکاس</option>
                    </select>
                    <div class="invalid-feedback">انتخاب نقش الزامی است.</div>
                </div>

                <div class="col-md-4">
                    <label for="position" class="form-label">سمت <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="position" name="position" required
                           placeholder="مثلاً: خبرنگار ارشد" value="<?= e(old('position')) ?>">
                    <div class="invalid-feedback">سمت الزامی است.</div>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">وضعیت <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>فعال</option>
                        <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>ذخیره عضو تیم
                </button>
                <a href="<?= url('admin/team') ?>" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('teamForm').addEventListener('submit', function(e) {
    let valid = true;
    const fields = ['name', 'username', 'email', 'password', 'role', 'position'];

    fields.forEach(function(id) {
        const el = document.getElementById(id);
        if (!el.value.trim() || (id === 'password' && el.value.length < 8)) {
            el.classList.add('is-invalid');
            valid = false;
        } else {
            el.classList.remove('is-invalid');
            el.classList.add('is-valid');
        }
    });

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
