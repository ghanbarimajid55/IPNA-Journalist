<div class="container-fluid px-2 py-3" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">ویرایش فعالیت</h5>
        <a href="<?= BASE_URL ?>/photographer/activities" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-right"></i> بازگشت
        </a>
    </div>

    <form id="activityForm" action="<?= BASE_URL ?>/photographer/activities/<?= $activity['id'] ?>/update" method="POST" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label for="date" class="form-label fw-bold">تاریخ</label>
                    <input type="text" class="form-control form-control-lg" id="date" name="date"
                           value="<?= e(old('date', $activity['date'])) ?>" required
                           placeholder="مثلاً ۱۴۰۵/۰۶/۰۱" style="font-size:1.1rem;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">نوع فعالیت</label>
                    <div id="activityTypeContainer">
                        <?php if (!empty($activityTypes)): ?>
                            <?php foreach ($activityTypes as $type): ?>
                                <?php
                                    $name = strtolower($type['name'] ?? '');
                                    $isPhotoVideo = in_array($name, ['عکس', 'photo', 'تصویر', 'ویدیو', 'video', 'فیلم']);
                                ?>
                                <div class="form-check form-check-inline-block mb-2" style="min-width:calc(50% - 0.5rem);display:inline-block;">
                                    <input class="form-check-input activity-type-radio" type="radio"
                                           name="activity_type_id" id="type_<?= $type['id'] ?>"
                                           value="<?= $type['id'] ?>"
                                           data-score="<?= $type['score_per_unit'] ?? 0 ?>"
                                           data-name="<?= e($type['name']) ?>"
                                           <?= old('activity_type_id', $activity['activity_type_id']) == $type['id'] ? 'checked' : '' ?>
                                           required>
                                    <label class="form-check-label w-100 d-block p-3 border rounded-3 text-center activity-type-label <?= $isPhotoVideo ? 'border-success border-2' : '' ?>"
                                           for="type_<?= $type['id'] ?>"
                                           style="cursor:pointer;min-height:60px;display:flex;flex-direction:column;justify-content:center;transition:all 0.2s;">
                                        <span class="fw-bold" style="font-size:1.05rem;"><?= e($type['name']) ?></span>
                                        <small class="text-muted"><?= $type['score_per_unit'] ?? 0 ?> امتیاز/واحد</small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="invalid-feedback">لطفاً نوع فعالیت را انتخاب کنید</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="quantity" class="form-label fw-bold">تعداد</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" id="qtyMinus" style="min-width:48px;min-height:48px;font-size:1.3rem;">−</button>
                            <input type="number" class="form-control form-control-lg text-center" id="quantity" name="quantity"
                                   value="<?= e(old('quantity', $activity['quantity'])) ?>" min="1" max="999" required style="font-size:1.3rem;font-weight:bold;">
                            <button class="btn btn-outline-secondary" type="button" id="qtyPlus" style="min-width:48px;min-height:48px;font-size:1.3rem;">+</button>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">امتیاز</label>
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3 text-center" id="scoreDisplay">
                            <span id="calculatedScore" class="fw-bold text-primary" style="font-size:1.8rem;"><?= $activity['score'] ?></span>
                            <div class="text-muted small">امتیاز</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">عنوان</label>
                    <input type="text" class="form-control form-control-lg" id="title" name="title"
                           value="<?= e(old('title', $activity['title'])) ?>"
                           required minlength="3" maxlength="255"
                           placeholder="حداقل ۳ کاراکتر" style="font-size:1.05rem;">
                    <div class="invalid-feedback">عنوان حداقل باید ۳ کاراکتر باشد</div>
                </div>

                <div class="mb-3">
                    <label for="link" class="form-label fw-bold">لینک <small class="text-muted">(اختیاری)</small></label>
                    <input type="url" class="form-control form-control-lg" id="link" name="link"
                           value="<?= e(old('link', $activity['link'] ?? '')) ?>"
                           placeholder="https://..." dir="ltr" style="font-size:1rem;">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 bg-light">
                        <div>
                            <span class="fw-bold" style="font-size:1.05rem;">مأموریت</span>
                            <small class="text-muted d-block">آیا این فعالیت مأموریتی بوده؟</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_mission" name="is_mission" value="1"
                                   <?= old('is_mission', $activity['is_mission']) ? 'checked' : '' ?>
                                   style="width:3.5rem;height:1.8rem;cursor:pointer;">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-bold">توضیحات <small class="text-muted">(اختیاری)</small></label>
                    <textarea class="form-control" id="description" name="description" rows="3"
                              placeholder="توضیحات تکمیلی..." style="font-size:1rem;"><?= e(old('description', $activity['description'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="scoreSummaryCard">
            <div class="card-body text-center py-3">
                <div class="mb-1 text-muted">خلاصه امتیاز</div>
                <div id="scoreSummaryText" class="fw-bold text-primary" style="font-size:1.2rem;"></div>
            </div>
        </div>

        <div class="d-grid gap-2 mb-4">
            <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold" id="submitBtn" style="font-size:1.2rem;min-height:56px;">
                <i class="bi bi-check-circle me-2"></i> ذخیره تغییرات
            </button>
        </div>
    </form>
</div>

<style>
    .activity-type-radio:checked + .activity-type-label {
        background-color: var(--bs-primary) !important;
        color: #fff !important;
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.25);
    }
    .activity-type-radio:checked + .activity-type-label small {
        color: rgba(255,255,255,0.8) !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('.activity-type-radio');
    const quantityInput = document.getElementById('quantity');
    const calculatedScore = document.getElementById('calculatedScore');
    const scoreSummaryCard = document.getElementById('scoreSummaryCard');
    const scoreSummaryText = document.getElementById('scoreSummaryText');

    function updateScore() {
        const selected = document.querySelector('.activity-type-radio:checked');
        if (!selected) {
            calculatedScore.textContent = '0';
            scoreSummaryCard.style.display = 'none';
            return;
        }
        const scorePerUnit = parseFloat(selected.dataset.score) || 0;
        const qty = parseInt(quantityInput.value) || 1;
        const total = scorePerUnit * qty;
        calculatedScore.textContent = total;
        scoreSummaryText.textContent = selected.dataset.name + ' × ' + qty + ' = ' + total + ' امتیاز';
        scoreSummaryCard.style.display = 'block';
    }

    radios.forEach(function(radio) {
        radio.addEventListener('change', updateScore);
    });

    quantityInput.addEventListener('input', updateScore);

    document.getElementById('qtyPlus').addEventListener('click', function() {
        let v = parseInt(quantityInput.value) || 1;
        if (v < 999) quantityInput.value = v + 1;
        updateScore();
    });

    document.getElementById('qtyMinus').addEventListener('click', function() {
        let v = parseInt(quantityInput.value) || 1;
        if (v > 1) quantityInput.value = v - 1;
        updateScore();
    });

    updateScore();

    document.getElementById('activityForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> در حال ذخیره...';
    });
});
</script>
