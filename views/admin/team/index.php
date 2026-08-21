<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">مدیریت تیم</h4>
    <a href="<?= url('admin/team/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>افزودن عضو جدید
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>
                        <a href="?sort=name&dir=<?= ($sort ?? '') === 'name' && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc' ?>" class="text-decoration-none text-dark">
                            نام <i class="bi bi-arrow-down-up small"></i>
                        </a>
                    </th>
                    <th>سمت</th>
                    <th class="text-center">
                        <a href="?sort=activity_count&dir=<?= ($sort ?? '') === 'activity_count' && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc' ?>" class="text-decoration-none text-dark">
                            تعداد فعالیت <i class="bi bi-arrow-down-up small"></i>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="?sort=month_score&dir=<?= ($sort ?? '') === 'month_score' && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc' ?>" class="text-decoration-none text-dark">
                            امتیاز ماه <i class="bi bi-arrow-down-up small"></i>
                        </a>
                    </th>
                    <th class="text-center">هدف</th>
                    <th style="min-width: 180px;">درصد تحقق</th>
                    <th class="text-center" style="width: 120px;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-people fs-1 d-block mb-2"></i>
                            هیچ عضوی یافت نشد.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($members as $index => $member): ?>
                        <?php
                        $target = $member['target'] ?? 0;
                        $score = $member['month_score'] ?? 0;
                        $percentage = $target > 0 ? min(100, round(($score / $target) * 100)) : 0;

                        if ($percentage >= 100) {
                            $progressClass = 'bg-success';
                        } elseif ($percentage >= 60) {
                            $progressClass = 'bg-warning';
                        } else {
                            $progressClass = 'bg-danger';
                        }
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td class="fw-semibold">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-size:0.8rem;">
                                        <?= mb_substr($member['name'], 0, 1) ?>
                                    </div>
                                    <?= e($member['name']) ?>
                                </div>
                            </td>
                            <td><?= e($member['position'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?= $member['activity_count'] ?? 0 ?></span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-primary"><?= number_format($score) ?></span>
                            </td>
                            <td class="text-center">
                                <?= $target > 0 ? number_format($target) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td>
                                <?php if ($target > 0): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar <?= $progressClass ?>" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <small class="text-nowrap <?= $percentage >= 100 ? 'text-success fw-bold' : ($percentage >= 60 ? 'text-warning' : 'text-danger') ?>">
                                            <?= $percentage ?>%
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">هدف تعیین نشده</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('admin/team/' . $member['id'] . '/edit') ?>" class="btn btn-outline-primary" title="ویرایش">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
<nav class="mt-4 d-flex justify-content-center">
    <ul class="pagination">
        <li class="page-item <?= $pagination['currentPage'] <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $pagination['currentPage'] - 1 ?>"><i class="bi bi-chevron-right"></i></a>
        </li>
        <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
            <li class="page-item <?= $i === $pagination['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $pagination['currentPage'] >= $pagination['totalPages'] ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $pagination['currentPage'] + 1 ?>"><i class="bi bi-chevron-left"></i></a>
        </li>
    </ul>
</nav>
<?php endif; ?>
