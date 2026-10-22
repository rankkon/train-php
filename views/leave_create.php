<?php

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

    <main class="leave-list leave-create">
        <h1 class="leave-form-title"><?= escapeHtml($formTitle) ?></h1>

        <form id="leave-form" class="leave-form<?php if ($isConfirmation): ?> leave-form-confirm<?php endif; ?>" method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= escapeHtml($maxAttachmentSize) ?>">

            <div class="leave-form-fields">
                <?php if (isset($errors['form'])): ?>
                    <p class="leave-form-error"><?= escapeHtml($errors['form']) ?></p>
                <?php endif; ?>

                <div class="leave-form-field">
                    <label for="leave-title">Tiêu đề <span class="required-marker">*</span></label>
                    <input type="text" id="leave-title" name="title" maxlength="<?= escapeHtml(LEAVE_TITLE_MAX_LENGTH) ?>" value="<?= escapeHtml($values['title']) ?>" required
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['title'])): ?>class="leave-input-error"<?php endif; ?>>
                    <?php if (isset($errors['title'])): ?>
                        <p class="leave-form-error"><?= escapeHtml($errors['title']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="leave-form-field leave-form-content">
                    <label for="leave-content">Nội dung</label>
                    <textarea id="leave-content" name="content"
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['content'])): ?>class="leave-input-error"<?php endif; ?>><?= escapeHtml($values['content']) ?></textarea>
                    <?php if (isset($errors['content'])): ?>
                        <p class="leave-form-error"><?= escapeHtml($errors['content']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="leave-form-field">
                    <label for="leave-approver">Người duyệt <span class="required-marker">*</span></label>
                    <div class="leave-select-control">
                        <select id="leave-approver" name="approver_id" required
                            <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                            <?php if (isset($errors['approver_id'])): ?>class="leave-input-error"<?php endif; ?>>
                            <?php foreach ($approvers as $approver): ?>
                                <option value="<?= escapeHtml((int) $approver['id']) ?>" <?php if ($values['approver_id'] === (string) $approver['id']): ?>selected<?php endif; ?>><?= escapeHtml($approver['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isset($errors['approver_id'])): ?>
                        <p class="leave-form-error"><?= escapeHtml($errors['approver_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="leave-form-field leave-form-type">
                    <label for="leave-type">Loại đơn <span class="required-marker">*</span></label>
                    <div class="leave-select-control">
                        <select id="leave-type" name="leave_type" required
                            <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                            <?php if (isset($errors['leave_type'])): ?>class="leave-input-error"<?php endif; ?>>
                            <?php foreach ($leaveTypes as $typeCode => $typeName): ?>
                                <option value="<?= escapeHtml($typeCode) ?>" <?php if ($values['leave_type'] === (string) $typeCode): ?>selected<?php endif; ?>><?= escapeHtml($typeName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isset($errors['leave_type'])): ?>
                        <p class="leave-form-error"><?= escapeHtml($errors['leave_type']) ?></p>
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
                        <p class="leave-form-error"><?= escapeHtml($errors['start_date']) ?></p>
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
                        <p class="leave-form-error"><?= escapeHtml($errors['end_date']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="leave-form-field">
                    <label<?php if (!$isConfirmation): ?> for="leave-attachment"<?php endif; ?>>Đính kèm <span class="required-marker">*</span></label>
                    <?php if ($isConfirmation): ?>
                        <span id="leave-attachment" class="leave-confirm-attachment" title="<?= escapeHtml($attachment['name']) ?>"><?= escapeHtml($attachment['name']) ?></span>
                    <?php else: ?>
                        <div class="leave-file-control">
                            <input type="file" id="leave-attachment" name="attachment" accept="<?= escapeHtml($attachmentAccept) ?>" required
                                title="<?= escapeHtml(sprintf(MSG_ATTACHMENT_HINT, ATTACHMENT_MAX_MB)) ?>"
                                <?php if (isset($errors['attachment'])): ?>class="leave-input-error"<?php endif; ?>>
                            <?php if ($attachment): ?>
                                <span class="leave-file-name" title="<?= escapeHtml($attachment['name']) ?>"><?= escapeHtml($attachment['name']) ?></span>
                            <?php endif; ?>
                            <span class="leave-upload-icon"></span>
                        </div>
                        <?php if (isset($errors['attachment'])): ?>
                            <p class="leave-form-error"><?= escapeHtml($errors['attachment']) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="leave-form-actions">
                <?php if ($isConfirmation): ?>
                    <button name="action" value="confirm_save" class="leave-form-next">Lưu lại</button>
                    <button name="action" value="back" class="leave-form-clear">Quay lại</button>
                <?php else: ?>
                    <button name="action" value="validate" class="leave-form-next">Tiếp theo</button>
                    <button name="action" value="clear" class="leave-form-clear">Xóa trống</button>
                <?php endif; ?>
            </div>

            <?php if ($showSavePopup): ?>
                <div class="popup-overlay">
                    <section class="popup-dialog">
                        <header class="popup-header">
                            <h2 class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
                            <button name="action" value="cancel_save" class="popup-close"></button>
                        </header>
                        <p class="popup-message"><?= escapeHtml(MSG_CONFIRM_SAVE) ?></p>
                        <div class="popup-actions">
                            <button name="action" value="save" class="popup-button popup-confirm">OK</button>
                            <button name="action" value="cancel_save" class="popup-button popup-cancel" autofocus>Cancel</button>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
        </form>
    </main>
    <?php if ($showExitPopup): ?>
        <div class="popup-overlay">
            <section class="popup-dialog">
                <header class="popup-header">
                    <h2 class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
                    <button form="leave-form" name="action" value="cancel_exit" class="popup-close" title="Đóng"></button>
                </header>
                <p class="popup-message"><?= escapeHtml($exitMessage) ?></p>
                <div class="popup-actions">
                    <?php if ($exitDestination === 'logout'): ?>
                        <button form="leave-form" formaction="../logic/logout.php" class="popup-button popup-confirm">Đồng ý</button>
                    <?php else: ?>
                        <button form="leave-form" name="action" value="confirm_exit" class="popup-button popup-confirm">Đồng ý</button>
                    <?php endif; ?>
                    <button form="leave-form" name="action" value="cancel_exit" class="popup-button popup-cancel" autofocus>Không</button>
                </div>
            </section>
        </div>
    <?php endif; ?>
    <?php require __DIR__ . '/sidebar.php'; ?>
</body>
</html>
