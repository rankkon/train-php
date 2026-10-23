<?php

require dirname(__DIR__) . '/logic/leave_list_logic.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách đơn | Sanshin</title>
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
        <div class="leave-list-toolbar">
            <form method="get" class="leave-search">
                <label for="leave-search">Tên user/Loại đơn/Nội dung</label>
                <input type="text" id="leave-search" name="q" value="<?= escapeHtml($search) ?>">
                <button class="leave-search-button">Tìm kiếm</button>
            </form>
            <a href="leave_create.php" class="leave-add">Thêm mới đơn</a>
        </div>

        <div class="leave-table-container">
            <table class="leave-table leave-management-table">
                <thead>
                    <tr>
                        <th scope="col">STT</th>
                        <th scope="col">Người dùng</th>
                        <th scope="col">Loại đơn</th>
                        <th scope="col">Ngày lập</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Ngày duty</th>
                        <th scope="col">Mô tả</th>
                        <th scope="col">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$leaves): ?>
                        <tr><td colspan="8"><?= escapeHtml(MSG_NO_LEAVES_FOUND) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr class="leave-row leave-status-<?= escapeHtml((int) $leave['status']) ?>">
                            <td><?= escapeHtml($leave['number']) ?></td>
                            <td><?= escapeHtml($leave['full_name']) ?></td>
                            <td><?= escapeHtml($leave['type_name']) ?></td>
                            <td><?= escapeHtml($leave['created_date']) ?></td>
                            <td><?= escapeHtml($leave['status_name']) ?></td>
                            <td><?= escapeHtml($leave['end_date']) ?></td>
                            <td title="<?= escapeHtml($leave['title']) ?>">
                                <span class="leave-title"><?= escapeHtml($leave['title']) ?></span>
                            </td>
                            <td>
                                <?php if ($leave['show_actions'] || $leave['show_edit']): ?>
                                    <form method="post" class="leave-actions">
                                        <?php if ($leave['show_actions']): ?>
                                            <input type="hidden" name="leave_id" value="<?= escapeHtml($leave['id']) ?>">
                                            <button name="action" value="open_approve" class="leave-approve" title="Duyệt">✓</button>
                                        <?php endif; ?>
                                        <?php if ($leave['show_edit']): ?>
                                            <a href="leave_create.php?id=<?= escapeHtml($leave['id']) ?>" class="leave-edit" title="Sửa">✎</a>
                                        <?php endif; ?>
                                        <?php if ($leave['show_actions']): ?>
                                            <button name="action" value="open_cancel" class="leave-cancel" title="Hủy">×</button>
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

    <?php if ($processPopup === 'approve'): ?>
        <div class="popup-overlay">
            <form method="post" class="popup-dialog">
                <input type="hidden" name="leave_id" value="<?= escapeHtml($processLeaveId) ?>">
                <header class="popup-header">
                    <h2 class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
                    <button name="action" value="close_popup" class="popup-close" title="Đóng" formnovalidate></button>
                </header>
                <p class="popup-message"><?= escapeHtml(MSG_CONFIRM_APPROVE) ?></p>
                <div class="popup-actions">
                    <button name="action" value="confirm_approve" class="popup-button popup-confirm">OK</button>
                    <button name="action" value="close_popup" class="popup-button popup-cancel" formnovalidate>Cancel</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($processPopup === 'cancel'): ?>
        <div class="popup-overlay">
            <form method="post" class="popup-dialog leave-cancel-dialog" novalidate>
                <input type="hidden" name="leave_id" value="<?= escapeHtml($processLeaveId) ?>">
                <header class="popup-header">
                    <h2 class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
                    <button name="action" value="close_popup" class="popup-close" title="Đóng"></button>
                </header>
                <div class="leave-cancel-fields">
                    <label for="cancel-reason">Lý do hủy đơn <span class="leave-cancel-required">*</span></label>
                    <textarea id="cancel-reason" name="cancel_reason" class="leave-cancel-reason<?php if (!empty($processErrors['cancel_reason'])): ?> leave-field-error<?php endif; ?>" required><?= escapeHtml($cancelReason) ?></textarea>
                    <?php if (!empty($processErrors['cancel_reason'])): ?>
                        <p class="leave-process-error"><?= escapeHtml($processErrors['cancel_reason']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="popup-actions leave-cancel-actions">
                    <button name="action" value="confirm_cancel" class="popup-button popup-confirm">OK</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
