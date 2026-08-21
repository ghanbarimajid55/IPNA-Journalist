<?php
if ($totalPages <= 1) return;
?>
<nav aria-label="navigation">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $currentPage - 1 ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $currentPage + 1 ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
    </ul>
</nav>
