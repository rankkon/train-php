<nav class="leave-pagination">
    <?php if ($previousPageUrl !== null): ?>
        <a href="<?= escapeHtml($previousPageUrl) ?>" class="leave-page-previous">← Previous</a>
    <?php else: ?>
        <span class="leave-page-previous leave-page-disabled">← Previous</span>
    <?php endif; ?>

    <?php foreach ($pagination as $link): ?>
        <?php if ($link['show_gap']): ?>
            <span class="leave-page-gap">…</span>
        <?php endif; ?>
        <?php if ($link['current']): ?>
            <span class="leave-page-current"><?= escapeHtml($link['number']) ?></span>
        <?php else: ?>
            <a href="<?= escapeHtml($link['url']) ?>"><?= escapeHtml($link['number']) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($nextPageUrl !== null): ?>
        <a href="<?= escapeHtml($nextPageUrl) ?>" class="leave-page-next">Next →</a>
    <?php else: ?>
        <span class="leave-page-next leave-page-disabled">Next →</span>
    <?php endif; ?>
</nav>
