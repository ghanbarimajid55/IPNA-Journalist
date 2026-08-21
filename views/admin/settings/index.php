<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">تنظیمات سیستم</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('admin/settings') ?>" id="settingsForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="site_name" class="form-label">نام سایت <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="site_name" name="site_name" required
                           value="<?= e($settings['site_name'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label for="site_version" class="form-label">نسخه سایت</label>
                    <input type="text" class="form-control" id="site_version" name="site_version"
                           value="<?= e($settings['site_version'] ?? '1.0.0') ?>" dir="ltr">
                </div>

                <div class="col-md-6">
                    <label for="admin_email" class="form-label">ایمیل مدیر</label>
                    <input type="email" class="form-control" id="admin_email" name="admin_email"
                           value="<?= e($settings['admin_email'] ?? '') ?>" dir="ltr">
                </div>

                <div class="col-md-6">
                    <label for="default_monthly_target" class="form-label">هدف پیش‌فرض ماهانه</label>
                    <input type="number" class="form-control" id="default_monthly_target" name="default_monthly_target"
                           value="<?= e($settings['default_monthly_target'] ?? 1000) ?>" min="0">
                    <div class="form-text">امتیاز پیش‌فرض هدف ماهانه برای هر عضو تیم</div>
                </div>

                <div class="col-md-6">
                    <label for="news_score" class="form-label">امتیاز هر خبر</label>
                    <input type="number" class="form-control" id="news_score" name="news_score"
                           value="<?= e($settings['news_score'] ?? 25) ?>" min="0">
                </div>

                <div class="col-md-6">
                    <label for="exclusive_score" class="form-label">امتیاز هر خبر اختصاصی</label>
                    <input type="number" class="form-control" id="exclusive_score" name="exclusive_score"
                           value="<?= e($settings['exclusive_score'] ?? 50) ?>" min="0">
                </div>

                <div class="col-md-6">
                    <label for="interview_score" class="form-label">امتیاز هر مصاحبه</label>
                    <input type="number" class="form-control" id="interview_score" name="interview_score"
                           value="<?= e($settings['interview_score'] ?? 40) ?>" min="0">
                </div>

                <div class="col-md-6">
                    <label for="special_report_score" class="form-label">امتیاز هر گزارش ویژه</label>
                    <input type="number" class="form-control" id="special_report_score" name="special_report_score"
                           value="<?= e($settings['special_report_score'] ?? 75) ?>" min="0">
                </div>

                <div class="col-md-6">
                    <label for="photo_score" class="form-label">امتیاز هر عکس</label>
                    <input type="number" class="form-control" id="photo_score" name="photo_score"
                           value="<?= e($settings['photo_score'] ?? 10) ?>" min="0">
                </div>

                <div class="col-md-6">
                    <label for="gallery_score" class="form-label">امتیاز هر گالری</label>
                    <input type="number" class="form-control" id="gallery_score" name="gallery_score"
                           value="<?= e($settings['gallery_score'] ?? 30) ?>" min="0">
                </div>

                <div class="col-md-6">
                    <label for="video_score" class="form-label">امتیاز هر ویدئو</label>
                    <input type="number" class="form-control" id="video_score" name="video_score"
                           value="<?= e($settings['video_score'] ?? 60) ?>" min="0">
                </div>

                <div class="col-md-6">
                    <label for="mission_bonus" class="form-label">پاداش مأموریت ویژه (%)</label>
                    <input type="number" class="form-control" id="mission_bonus" name="mission_bonus"
                           value="<?= e($settings['mission_bonus'] ?? 20) ?>" min="0" max="100">
                    <div class="form-text">درصد امتیاز اضافه برای فعالیت‌های مأموریتی</div>
                </div>

                <div class="col-12">
                    <hr>
                    <h6 class="text-muted mb-3">تنظیمات نمایش</h6>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="show_ranking" name="show_ranking" value="1"
                               <?= ($settings['show_ranking'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_ranking">نمایش رتبه‌بندی</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="show_scores" name="show_scores" value="1"
                               <?= ($settings['show_scores'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_scores">نمایش امتیازات</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enable_notifications" name="enable_notifications" value="1"
                               <?= ($settings['enable_notifications'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_notifications">فعال‌سازی اعلان‌ها</label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>ذخیره تنظیمات
                </button>
                <a href="<?= url('admin') ?>" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>
