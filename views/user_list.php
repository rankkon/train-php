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
    <link rel="stylesheet" href="../css/leave_list.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/leave_list.css')) ?>">
    <link rel="stylesheet" href="../css/user_list.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/user_list.css')) ?>">
</head>
<body class="leave-list-page leave-list-screen">
    <header class="site-header">
        <span class="site-brand">Sanshin</span>
        <span class="site-description">Hệ thống quản lý đơn</span>
    </header>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="leave-list">
        <div class="leave-list-toolbar">
            <form method="get" class="leave-search user-search">
                <label for="user-search">Mã/Tên user</label>
                <input type="text" id="user-search" name="q" value="<?= escapeHtml($search) ?>">
                <button class="leave-search-button">Tìm kiếm</button>
            </form>
            <?php if ($showUserListActions): ?>
                <div class="user-list-actions">
                    <a href="user_create.php" class="leave-add">Thêm mới</a>
                    <button type="button" class="leave-add leave-cancel">Xóa nhiều</button>
                </div>
            <?php endif; ?>
        </div>

        <div class="leave-table-container">
            <table class="leave-table user-table">
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
                                <?php if ($user['show_actions']): ?>
                                    <div class="leave-actions user-row-actions">
                                        <button type="button" class="leave-edit" title="Sửa">✎</button>
                                        <button type="button" class="leave-cancel" title="Xóa">×</button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php require __DIR__ . '/pagination.php'; ?>
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
