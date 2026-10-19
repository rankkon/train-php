<?php

require dirname(__DIR__) . '/logic/user_list_logic.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách người dùng | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/common.css')) ?>">
    <link rel="stylesheet" href="../css/user_list.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/user_list.css')) ?>">
</head>
<body class="user-list-page">
    <header class="site-header">
        <span class="site-brand">Sanshin</span>
        <span class="site-description">Hệ thống quản lý đơn</span>
    </header>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="user-list">
        <div class="user-list-toolbar">
            <form method="get" action="user_list.php" class="user-search">
                <label for="user-search">Mã/Tên user</label>
                <input type="text" id="user-search" name="q" value="<?= escapeHtml($search) ?>" placeholder="Value">
                <button type="submit" class="user-search-button">Tìm kiếm</button>
            </form>
            <div class="user-list-actions">
                <button type="button" class="user-add">Thêm mới</button>
                <button type="button" class="user-delete">Xóa nhiều</button>
            </div>
        </div>

        <div class="user-table-container">
            <table class="user-table">
                <thead>
                    <tr>
                        <th scope="col"><input type="checkbox" id="select-all-users"></th>
                        <th scope="col">Mã người dùng</th>
                        <th scope="col">Tên người dùng</th>
                        <th scope="col">Ngày lập</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="6"><?= escapeHtml(MSG_NO_USERS_FOUND) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><?= escapeHtml($user['code']) ?></td>
                            <td><?= escapeHtml($user['full_name']) ?></td>
                            <td><?= escapeHtml($user['created_date']) ?></td>
                            <td><?= escapeHtml($user['status_name']) ?></td>
                            <td>
                                <div class="user-row-actions">
                                    <button type="button" class="user-edit">Sửa</button>
                                    <button type="button" class="user-delete">Xóa</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="user-pagination">
            <?php if ($previousPageUrl !== null): ?>
                <a href="<?= escapeHtml($previousPageUrl) ?>" class="user-page-previous">← Previous</a>
            <?php else: ?>
                <span class="user-page-previous user-page-disabled">← Previous</span>
            <?php endif; ?>

            <?php foreach ($pagination as $link): ?>
                <?php if ($link['show_gap']): ?>
                    <span class="user-page-gap">…</span>
                <?php endif; ?>
                <?php if ($link['current']): ?>
                    <span class="user-page-current"><?= escapeHtml($link['number']) ?></span>
                <?php else: ?>
                    <a href="<?= escapeHtml($link['url']) ?>"><?= escapeHtml($link['number']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($nextPageUrl !== null): ?>
                <a href="<?= escapeHtml($nextPageUrl) ?>" class="user-page-next">Next →</a>
            <?php else: ?>
                <span class="user-page-next user-page-disabled">Next →</span>
            <?php endif; ?>
        </nav>
    </main>
    <script>
        const selectAllUsers = document.getElementById('select-all-users');
        const userCheckboxes = document.querySelectorAll('.user-table tbody input[type="checkbox"]');

        // Chọn hoặc bỏ chọn tất cả người dùng.
        selectAllUsers.addEventListener('change', function () {
            for (const checkbox of userCheckboxes) {
                checkbox.checked = selectAllUsers.checked;
            }
        });
    </script>
</body>
</html>
