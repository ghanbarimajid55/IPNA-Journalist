<div class="container-fluid px-2 py-3" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">فعالیت‌های من</h5>
        <a href="<?= BASE_URL ?>/photographer/activities/create" class="btn btn-primary btn-lg fw-bold px-4">
            <i class="bi bi-plus-circle me-1"></i> ثبت فعالیت جدید
        </a>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body text-center py-3">
                    <div class="fw-bold text-primary" style="font-size:1.6rem;"><?= $monthActivityCount ?? 0 ?></div>
                    <div class="text-muted small">فعالیت این ماه</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body text-center py-3">
                    <div class="fw-bold text-success" style="font-size:1.6rem;"><?= $monthScore ?? 0 ?></div>
                    <div class="text-muted small">امتیاز این ماه</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>/photographer/activities" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold">از تاریخ</label>
                    <input type="text" class="form-control form-control-sm" name="date_from"
                           value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" placeholder="۱۴۰۵/۰۶/۰۱">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold">تا تاریخ</label>
                    <input type="text" class="form-control form-control-sm" name="date_to"
                           value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" placeholder="۱۴۰۵/۰۶/۳۱">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold">نوع فعالیت</label>
                    <select class="form-select form-select-sm" name="activity_type_id">
                        <option value="">همه</option>
                        <?php if (!empty($activityTypes)): ?>
                            <?php foreach ($activityTypes as $type): ?>
                                <option value="<?= $type['id'] ?>" <?= ($filters['activity_type_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 d-grid">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-search me-1"></i> فیلتر
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($activities)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 bg-white shadow-sm rounded-3 overflow-hidden">
                <thead class="table-light">
                    <tr>
                        <th>تاریخ</th>
                        <th>نوع فعالیت</th>
                        <th class="text-center">تعداد</th>
                        <th>عنوان</th>
                        <th class="text-center">امتیاز</th>
                        <th class="text-center">مأموریت</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($activity['date']) ?></td>
                            <td>
                                <?php
                                    $isPhoto = in_array(strtolower($activity['activity_type_name'] ?? ''), ['عکس', 'photo', 'تصویر']);
                                    $isVideo = in_array(strtolower($activity['activity_type_name'] ?? ''), ['ویدیو', 'video', 'فیلم']);
                                    $badgeClass = $isPhoto ? 'bg-success' : ($isVideo ? 'bg-danger' : 'bg-primary');
                                ?>
                                <span class="badge <?= $badgeClass ?> bg-opacity-10 <?= $badgeClass ?>">
                                    <?= htmlspecialchars($activity['activity_type_name'] ?? '') ?>
                                </span>
                            </td>
                            <td class="text-center fw-bold"><?= $activity['quantity'] ?></td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width:200px;" title="<?= htmlspecialchars($activity['title']) ?>">
                                    <?= htmlspecialchars($activity['title']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success fs-6"><?= $activity['score'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($activity['is_mission'])): ?>
                                    <span class="badge bg-warning text-dark">بله</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/photographer/activities/<?= $activity['id'] ?>/edit" class="btn btn-outline-primary" title="ویرایش">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>/photographer/activities/<?= $activity['id'] ?>/delete" class="d-inline"
                                          onsubmit="return confirm('آیا از حذف این فعالیت مطمئن هستید؟');">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
            <nav class="mt-3 d-flex justify-content-center">
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i == ($pagination['current_page'] ?? 1) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= !empty($filters) ? '&' . http_build_query(array_filter($filters)) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-camera text-muted" style="font-size:3rem;"></i>
                <h6 class="text-muted mt-3">فعالیتی ثبت نشده است</h6>
                <p class="text-muted small">اولین فعالیت خود را ثبت کنید</p>
                <a href="<?= BASE_URL ?>/photographer/activities/create" class="btn btn-primary btn-lg mt-2 px-4">
                    <i class="bi bi-plus-circle me-1"></i> ثبت فعالیت جدید
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
