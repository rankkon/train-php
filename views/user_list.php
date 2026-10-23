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
                <button type="submit" name="status" value="<?= escapeHtml($userStatusFilter) ?>" class="leave-search-button">Tìm kiếm</button>
                <div class="user-status-filters">
                    <button type="submit" name="status" value="<?= escapeHtml($activeStatusValue) ?>"
                        class="user-status-filter<?php if ($userStatusFilter === (string) USER_STATUS_ACTIVE): ?> user-status-filter-selected<?php endif; ?>">Đang hoạt động</button>
                    <button type="submit" name="status" value="<?= escapeHtml($lockedStatusValue) ?>"
                        class="user-status-filter<?php if ($userStatusFilter === (string) USER_STATUS_LOCKED): ?> user-status-filter-selected<?php endif; ?>">Đã khóa</button>
                </div>
            </form>
            <?php if ($showUserListActions): ?>
                <div class="user-list-actions">
                    <a href="user_create.php" class="leave-add">Thêm mới</a>
                    <form method="post" id="user-bulk-form">
                        <button name="action" value="open_bulk_lock" class="leave-add leave-cancel">Khóa nhiều</button>
                    </form>
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
                            <td>
                                <input type="checkbox" name="user_ids[]" value="<?= escapeHtml($user['id']) ?>" form="user-bulk-form"
                                    <?php if (!$user['show_actions']): ?>disabled<?php endif; ?>
                                    <?php if ($user['show_actions'] && in_array((int) $user['id'], $selectedUserIds, true)): ?>checked<?php endif; ?>>
                            </td>
                            <td><?= escapeHtml($user['code']) ?></td>
                            <td><?= escapeHtml($user['full_name']) ?></td>
                            <td><?= escapeHtml($user['created_date']) ?></td>
                            <td><?= escapeHtml($user['status_name']) ?></td>
                            <td>
                                <?php if ($user['show_actions']): ?>
                                    <form method="post" class="user-row-actions">
                                        <input type="hidden" name="user_id" value="<?= escapeHtml($user['id']) ?>">
                                        <a href="user_create.php?id=<?= escapeHtml($user['id']) ?>" class="user-edit" title="Sửa">✎</a>
                                        <?php if ((int) $user['status'] === USER_STATUS_ACTIVE): ?>
                                            <button type="submit" name="action" value="open_lock" class="user-cancel" title="Khóa">
                                                <svg class="user-status-icon" viewBox="0 0 24 24">
                                                    <rect x="4" y="11" width="16" height="10" rx="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="open_unlock" class="user-activate" title="Mở khóa">
                                                <svg class="user-status-icon" viewBox="0 0 24 24">
                                                    <rect x="4" y="11" width="16" height="10" rx="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php require __DIR__ . '/pagination.php'; ?>
    </main>
    <?php if ($statusPopup !== ''): ?>
        <div class="popup-overlay">
            <form method="post" class="popup-dialog">
                <?php if ($statusPopup !== 'bulk_lock'): ?>
                    <input type="hidden" name="user_id" value="<?= escapeHtml($statusUserId) ?>">
                <?php endif; ?>
                <header class="popup-header">
                    <h2 class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
                    <button name="action" value="<?= escapeHtml($statusCloseAction) ?>" class="popup-close" title="Đóng"></button>
                </header>
                <p class="popup-message"><?= escapeHtml($statusMessage) ?></p>
                <div class="popup-actions">
                    <button name="action" value="confirm_<?= escapeHtml($statusPopup) ?>" class="popup-button popup-confirm">Đồng ý</button>
                    <button name="action" value="<?= escapeHtml($statusCloseAction) ?>" class="popup-button popup-cancel" autofocus>Không</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    <script>
        const selectAllUsers = document.getElementById('select-all-users');
        const userCheckboxes = document.querySelectorAll('.user-table tbody input[type="checkbox"]:not(:disabled)');

        // Chọn hoặc bỏ chọn tất cả người dùng.
        selectAllUsers.addEventListener('change', function () {
            for (const checkbox of userCheckboxes) {
                checkbox.checked = selectAllUsers.checked;
            }
        });
    </script>
</body>
</html>
