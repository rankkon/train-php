<?php
// Xử lý form và chuẩn bị dữ liệu trước khi hiển thị HTML.
require dirname(__DIR__) . '/logic/leave_create_logic.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeHtml($formTitle) ?> | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/common.css')) ?>">
    <link rel="stylesheet" href="../css/leave_form.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/leave_form.css')) ?>">
</head>
<body class="leave-list-page leave-create-page">
    <header class="site-header">
        <span class="site-brand">Sanshin</span>
        <span class="site-description">Hệ thống quản lý đơn</span>
    </header>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="leave-list leave-create">
        <h1 class="leave-form-title"><?= escapeHtml($formTitle) ?></h1>

        <!-- Form nhập và form xác nhận dùng chung các ô -->
        <form class="leave-form<?php if ($isConfirmation): ?> leave-form-confirm<?php endif; ?>" method="post" action="<?= escapeHtml($formAction) ?>" enctype="multipart/form-data" novalidate>
            <!-- Các ô ẩn để xác thực CSRF, giới hạn kích thước file và lưu mã xác nhận. -->
            <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= escapeHtml($maxAttachmentSize) ?>">
            <?php if ($isConfirmation): ?>
                <input type="hidden" name="save_token" value="<?= escapeHtml($saveToken) ?>">
            <?php endif; ?>

            <?php if (isset($errors['form'])): ?>
                <p class="leave-form-error"><?= escapeHtml($errors['form']) ?></p>
            <?php endif; ?>

            <!-- Thông tin đơn và thông báo lỗi của từng ô nhập. -->
            <div class="leave-form-field">
                <label for="leave-title">Tiêu đề <span class="required-marker">*</span></label>
                <input type="text" id="leave-title" name="title" maxlength="<?= escapeHtml(LEAVE_TITLE_MAX_LENGTH) ?>" value="<?= escapeHtml($values['title']) ?>" required
                    <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                    <?php if (isset($errors['title'])): ?>class="leave-input-error"<?php endif; ?>>
                <?php if (isset($errors['title'])): ?>
                    <p id="leave-title-error" class="leave-form-error"><?= escapeHtml($errors['title']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-content">
                <label for="leave-content">Nội dung</label>
                <textarea id="leave-content" name="content"
                    <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                    <?php if (isset($errors['content'])): ?>class="leave-input-error"<?php endif; ?>><?= escapeHtml($values['content']) ?></textarea>
                <?php if (isset($errors['content'])): ?>
                    <p id="leave-content-error" class="leave-form-error"><?= escapeHtml($errors['content']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-select">
                <label for="leave-approver">Người duyệt <span class="required-marker">*</span></label>
                <div class="leave-select-control">
                    <select id="leave-approver" name="approver_id" required
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['approver_id'])): ?>class="leave-input-error"<?php endif; ?>>
                        <option value="">Value</option>
                        <?php foreach ($approvers as $approver): ?>
                            <option value="<?= escapeHtml((int) $approver['id']) ?>" <?php if ($values['approver_id'] === (string) $approver['id']): ?>selected<?php endif; ?>><?= escapeHtml($approver['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (isset($errors['approver_id'])): ?>
                    <p id="leave-approver-error" class="leave-form-error"><?= escapeHtml($errors['approver_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-select">
                <label for="leave-type">Loại đơn <span class="required-marker">*</span></label>
                <div class="leave-select-control">
                    <select id="leave-type" name="leave_type" required
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['leave_type'])): ?>class="leave-input-error"<?php endif; ?>>
                        <option value="">Value</option>
                        <?php foreach ($leaveTypes as $typeCode => $typeName): ?>
                            <option value="<?= escapeHtml($typeCode) ?>" <?php if ($values['leave_type'] === (string) $typeCode): ?>selected<?php endif; ?>><?= escapeHtml($typeName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (isset($errors['leave_type'])): ?>
                    <p id="leave-type-error" class="leave-form-error"><?= escapeHtml($errors['leave_type']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-date">
                <label for="leave-start-date">Ngày bắt đầu <span class="required-marker">*</span></label>
                <div class="leave-date-control">
                    <input type="date" id="leave-start-date" name="start_date" value="<?= escapeHtml($values['start_date']) ?>" <?php if ($minimumDate !== ''): ?>min="<?= escapeHtml($minimumDate) ?>"<?php endif; ?> required
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['start_date'])): ?>class="leave-input-error"<?php endif; ?>>
                    <?php if ($isConfirmation): ?>
                        <span class="leave-calendar-icon"></span>
                    <?php endif; ?>
                </div>
                <?php if (isset($errors['start_date'])): ?>
                    <p id="leave-start-date-error" class="leave-form-error"><?= escapeHtml($errors['start_date']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-date">
                <label for="leave-end-date">Ngày kết thúc <span class="required-marker">*</span></label>
                <div class="leave-date-control">
                    <input type="date" id="leave-end-date" name="end_date" value="<?= escapeHtml($values['end_date']) ?>" <?php if ($minimumDate !== ''): ?>min="<?= escapeHtml($minimumDate) ?>"<?php endif; ?> required
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['end_date'])): ?>class="leave-input-error"<?php endif; ?>>
                    <?php if ($isConfirmation): ?>
                        <span class="leave-calendar-icon"></span>
                    <?php endif; ?>
                </div>
                <?php if (isset($errors['end_date'])): ?>
                    <p id="leave-end-date-error" class="leave-form-error"><?= escapeHtml($errors['end_date']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Khi xác nhận chỉ hiển thị tên file đã kiểm tra trong session. -->
            <div class="leave-form-field leave-form-attachment">
                <label<?php if (!$isConfirmation): ?> for="leave-attachment"<?php endif; ?>>Đính kèm <span class="required-marker">*</span></label>
                <?php if ($isConfirmation): ?>
                    <span id="leave-attachment" class="leave-confirm-attachment" title="<?= escapeHtml($attachment['name']) ?>"><?= escapeHtml($attachment['name']) ?></span>
                <?php else: ?>
                    <div class="leave-file-control">
                        <input type="file" id="leave-attachment" name="attachment" accept="<?= escapeHtml($attachmentAccept) ?>" required
                            title="<?= escapeHtml(sprintf(MSG_ATTACHMENT_HINT, ATTACHMENT_MAX_MB)) ?>"
                            <?php if (isset($errors['attachment'])): ?>class="leave-input-error"<?php endif; ?>>
                        <?php if ($attachment): ?>
                            <span id="leave-file-name" class="leave-file-name" title="<?= escapeHtml($attachment['name']) ?>"><?= escapeHtml($attachment['name']) ?></span>
                        <?php else: ?>
                            <span id="leave-file-name" class="leave-file-name" title=""></span>
                        <?php endif; ?>
                        <span class="leave-upload-icon"></span>
                    </div>
                    <?php if (isset($errors['attachment'])): ?>
                        <p id="leave-attachment-error" class="leave-form-error"><?= escapeHtml($errors['attachment']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Đổi nhóm nút theo bước đang xem. -->
            <div class="leave-form-actions">
                <?php if ($isConfirmation): ?>
                    <button type="submit" name="action" value="confirm_save" class="leave-form-next">Lưu lại</button>
                    <button type="submit" name="action" value="back" class="leave-form-clear" formnovalidate>Quay lại</button>
                <?php else: ?>
                    <button type="submit" name="action" value="validate" class="leave-form-next">Tiếp theo</button>
                    <button type="submit" name="action" value="clear" class="leave-form-clear" formnovalidate>Xóa trống</button>
                <?php endif; ?>
            </div>

            <!-- Popup được hiển thị sau khi bấm Lưu lại. -->
            <?php if ($showSavePopup): ?>
                <div class="popup-overlay">
                    <section class="popup-dialog">
                        <header class="popup-header">
                            <h2 id="leave-save-title" class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
                            <button type="submit" name="action" value="cancel_save" class="popup-close"></button>
                        </header>
                        <p id="leave-save-message" class="popup-message"><?= escapeHtml(MSG_CONFIRM_SAVE) ?></p>
                        <div class="popup-actions">
                            <button type="submit" name="action" value="save" class="popup-button popup-confirm">OK</button>
                            <button type="submit" name="action" value="cancel_save" class="popup-button popup-cancel" autofocus>Cancel</button>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>
