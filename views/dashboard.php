<?php

require dirname(__DIR__) . '/logic/dashboard_logic.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/common.css')) ?>">
    <link rel="stylesheet" href="../css/leave_list.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/leave_list.css')) ?>">
</head>
<body class="leave-list-page leave-list-screen">
    <header class="site-header">
        <span class="site-brand">Sanshin</span>
        <span class="site-description">Hệ thống quản lý đơn</span>
    </header>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="leave-list">
        <div class="leave-list-toolbar"></div>

        <div class="leave-table-container">
            <table class="leave-table">
                <thead>
                    <tr>
                        <th scope="col">STT</th>
                        <th scope="col">Người dùng</th>
                        <th scope="col">Loại đơn</th>
                        <th scope="col">Ngày lập</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Ngày duty</th>
                        <th scope="col">Mô tả</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$leaves): ?>
                        <tr><td colspan="7"><?= escapeHtml(MSG_NO_LEAVES_FOUND) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr class="leave-row leave-status-<?= escapeHtml((int) $leave['status']) ?>">
                            <td><?= escapeHtml($leave['number']) ?></td>
                            <td><?= escapeHtml($leave['full_name']) ?></td>
                            <td><?= escapeHtml($leave['type_name']) ?></td>
                            <td><?= escapeHtml($leave['created_date']) ?></td>
                            <td><?= escapeHtml($leave['status_name']) ?></td>
                            <td><?= escapeHtml($leave['end_date']) ?></td>
                            <td>
                                <div class="leave-description">
                                    <span class="leave-title" title="<?= escapeHtml($leave['title']) ?>"><?= escapeHtml($leave['title']) ?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

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
    </main>
</body>
</html>
