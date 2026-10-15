<?php require dirname(__DIR__) . '/logic/leave_list_logic.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách đơn | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= filemtime(dirname(__DIR__) . '/css/common.css') ?>">
    <link rel="stylesheet" href="../css/leave_list.css?v=<?= filemtime(dirname(__DIR__) . '/css/leave_list.css') ?>">
</head>
<body class="leave-list-page">
    <header class="site-header">
        <span class="site-brand">Sanshin</span>
        <span class="site-description">Hệ thống quản lý đơn</span>
    </header>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="leave-list">
        <div class="leave-list-toolbar">
            <form method="get" action="leave_list.php" class="leave-search">
                <label for="leave-search">Tên user/Loại đơn/Nội dung</label>
                <input type="text" id="leave-search" name="q" value="<?= escapeHtml($search) ?>" placeholder="Value">
                <button type="submit" class="leave-search-button">Tìm kiếm</button>
            </form>
            <a href="leave_create.php" class="leave-add">Thêm mới đơn</a>
        </div>

        <div class="leave-table-container">
            <table class="leave-table" aria-label="Danh sách đơn">
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
                        <tr><td colspan="7">Không có đơn phù hợp.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr class="leave-row leave-status-<?= (int) $leave['status'] ?>">
                            <td><?= $leave['number'] ?></td>
                            <td><?= escapeHtml($leave['full_name']) ?></td>
                            <td><?= escapeHtml($leave['type_name']) ?></td>
                            <td><?= escapeHtml($leave['created_date']) ?></td>
                            <td><?= escapeHtml($leave['status_name']) ?></td>
                            <td><?= escapeHtml($leave['end_date']) ?></td>
                            <td>
                                <div class="leave-description">
                                    <span class="leave-title" title="<?= escapeHtml($leave['title']) ?>"><?= escapeHtml($leave['title']) ?></span>
                                    <?php if ($leave['show_actions']): ?>
                                        <div class="leave-actions">
                                            <button type="button" class="leave-approve">Duyệt</button>
                                            <button type="button" class="leave-cancel">Hủy</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="leave-pagination" aria-label="Phân trang danh sách đơn">
            <?php if ($previousPageUrl !== null): ?>
                <a href="<?= escapeHtml($previousPageUrl) ?>" class="leave-page-previous" aria-label="Trang trước">← Previous</a>
            <?php else: ?>
                <span class="leave-page-previous" aria-disabled="true">← Previous</span>
            <?php endif; ?>

            <?php foreach ($pagination as $link): ?>
                <?php if ($link['show_gap']): ?>
                    <span class="leave-page-gap">…</span>
                <?php endif; ?>
                <?php if ($link['current']): ?>
                    <span aria-current="page"><?= $link['number'] ?></span>
                <?php else: ?>
                    <a href="<?= escapeHtml($link['url']) ?>"><?= $link['number'] ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($nextPageUrl !== null): ?>
                <a href="<?= escapeHtml($nextPageUrl) ?>" class="leave-page-next" aria-label="Trang sau">Next →</a>
            <?php else: ?>
                <span class="leave-page-next" aria-disabled="true">Next →</span>
            <?php endif; ?>
        </nav>
    </main>
</body>
</html>
