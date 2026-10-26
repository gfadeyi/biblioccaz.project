<?php
$pagesVisibles = [];
foreach (range(1, $nbPages) as $numero) {
    if ($numero === 1 || $numero === $nbPages || abs($numero - $page) <= 1) {
        if ($pagesVisibles && end($pagesVisibles) !== null && $numero - end($pagesVisibles) > 1) {
            $pagesVisibles[] = null;
        }
        $pagesVisibles[] = $numero;
    }
}
?>
<nav class="pagination-site <?= $classePagination ?? 'mt-4' ?>" aria-label="Pages">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($lienPage(max(1, $page - 1))) ?>" aria-label="Page précédente" <?= $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
        </li>
        <?php foreach ($pagesVisibles as $numero): ?>
            <?php if ($numero === null): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php else: ?>
                <li class="page-item <?= $numero === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($lienPage($numero)) ?>" <?= $numero === $page ? 'aria-current="page"' : '' ?>><?= $numero ?></a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $nbPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($lienPage(min($nbPages, $page + 1))) ?>" aria-label="Page suivante" <?= $page >= $nbPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        </li>
    </ul>
</nav>
