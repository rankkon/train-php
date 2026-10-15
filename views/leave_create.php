<?php require dirname(__DIR__) . '/logic/leave_create_logic.php'; /** @var array<int, string> $leaveTypes */ ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm mới đơn | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= filemtime(dirname(__DIR__) . '/css/common.css') ?>">
    <link rel="stylesheet" href="../css/leave_form.css?v=<?= filemtime(dirname(__DIR__) . '/css/leave_form.css') ?>">
</head>
<body class="leave-list-page leave-create-page">
    <header class="site-header">
        <span class="site-brand">Sanshin</span>
        <span class="site-description">Hệ thống quản lý đơn</span>
    </header>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="leave-list leave-create">
        <h1 class="leave-form-title">Thêm mới đơn</h1>

        <form class="leave-form<?= $isConfirmation ? ' leave-form-confirm' : '' ?>" method="post" action="leave_create.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= $maxAttachmentSize ?>">
            <?php if ($isConfirmation): ?>
                <input type="hidden" name="save_token" value="<?= escapeHtml($saveToken) ?>">
            <?php endif; ?>

            <?php if (isset($errors['form'])): ?>
                <p class="leave-form-error" role="alert"><?= escapeHtml($errors['form']) ?></p>
            <?php endif; ?>

            <div class="leave-form-field">
                <label for="leave-title">Tiêu đề <span class="required-marker" aria-hidden="true">*</span></label>
                <input type="text" id="leave-title" name="title" maxlength="255" value="<?= escapeHtml($values['title']) ?>" required <?= $isConfirmation ? 'disabled' : '' ?>
                    <?= isset($errors['title']) ? 'class="leave-input-error" aria-invalid="true" aria-describedby="leave-title-error"' : '' ?>>
                <?php if (isset($errors['title'])): ?>
                    <p id="leave-title-error" class="leave-form-error" role="alert"><?= escapeHtml($errors['title']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-content">
                <label for="leave-content">Nội dung</label>
                <textarea id="leave-content" name="content" <?= $isConfirmation ? 'disabled' : '' ?> <?= isset($errors['content']) ? 'class="leave-input-error" aria-invalid="true" aria-describedby="leave-content-error"' : '' ?>><?= escapeHtml($values['content']) ?></textarea>
                <?php if (isset($errors['content'])): ?>
                    <p id="leave-content-error" class="leave-form-error" role="alert"><?= escapeHtml($errors['content']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-select">
                <label for="leave-approver">Người duyệt <span class="required-marker" aria-hidden="true">*</span></label>
                <div class="leave-select-control">
                    <select id="leave-approver" name="approver_id" required <?= $isConfirmation ? 'disabled' : '' ?>
                        <?= isset($errors['approver_id']) ? 'class="leave-input-error" aria-invalid="true" aria-describedby="leave-approver-error"' : '' ?>>
                        <option value="">Value</option>
                        <?php foreach ($approvers as $approver): ?>
                            <option value="<?= (int) $approver['id'] ?>" <?= $values['approver_id'] === (string) $approver['id'] ? 'selected' : '' ?>><?= escapeHtml($approver['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (isset($errors['approver_id'])): ?>
                    <p id="leave-approver-error" class="leave-form-error" role="alert"><?= escapeHtml($errors['approver_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-select">
                <label for="leave-type">Loại đơn <span class="required-marker" aria-hidden="true">*</span></label>
                <div class="leave-select-control">
                    <select id="leave-type" name="leave_type" required <?= $isConfirmation ? 'disabled' : '' ?>
                        <?= isset($errors['leave_type']) ? 'class="leave-input-error" aria-invalid="true" aria-describedby="leave-type-error"' : '' ?>>
                        <option value="">Value</option>
                        <?php foreach ($leaveTypes as $typeCode => $typeName): ?>
                            <option value="<?= $typeCode ?>" <?= $values['leave_type'] === (string) $typeCode ? 'selected' : '' ?>><?= escapeHtml($typeName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (isset($errors['leave_type'])): ?>
                    <p id="leave-type-error" class="leave-form-error" role="alert"><?= escapeHtml($errors['leave_type']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-date">
                <label for="leave-start-date">Ngày bắt đầu <span class="required-marker" aria-hidden="true">*</span></label>
                <div class="leave-date-control">
                    <input type="date" id="leave-start-date" name="start_date" value="<?= escapeHtml($values['start_date']) ?>" min="<?= $today ?>" required <?= $isConfirmation ? 'disabled' : '' ?>
                        <?= isset($errors['start_date']) ? 'class="leave-input-error" aria-invalid="true" aria-describedby="leave-start-date-error"' : '' ?>>
                    <?php if ($isConfirmation): ?>
                        <span class="leave-calendar-icon" aria-hidden="true"></span>
                    <?php endif; ?>
                </div>
                <?php if (isset($errors['start_date'])): ?>
                    <p id="leave-start-date-error" class="leave-form-error" role="alert"><?= escapeHtml($errors['start_date']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-date">
                <label for="leave-end-date">Ngày kết thúc <span class="required-marker" aria-hidden="true">*</span></label>
                <div class="leave-date-control">
                    <input type="date" id="leave-end-date" name="end_date" value="<?= escapeHtml($values['end_date']) ?>" min="<?= $today ?>" required <?= $isConfirmation ? 'disabled' : '' ?>
                        <?= isset($errors['end_date']) ? 'class="leave-input-error" aria-invalid="true" aria-describedby="leave-end-date-error"' : '' ?>>
                    <?php if ($isConfirmation): ?>
                        <span class="leave-calendar-icon" aria-hidden="true"></span>
                    <?php endif; ?>
                </div>
                <?php if (isset($errors['end_date'])): ?>
                    <p id="leave-end-date-error" class="leave-form-error" role="alert"><?= escapeHtml($errors['end_date']) ?></p>
                <?php endif; ?>
            </div>

            <div class="leave-form-field leave-form-attachment">
                <label<?= $isConfirmation ? '' : ' for="leave-attachment"' ?>>Đính kèm <span class="required-marker" aria-hidden="true">*</span></label>
                <?php if ($isConfirmation): ?>
                    <span id="leave-attachment" class="leave-confirm-attachment" title="<?= escapeHtml($attachment['name']) ?>"><?= escapeHtml($attachment['name']) ?></span>
                <?php else: ?>
                    <div class="leave-file-control">
                        <input type="file" id="leave-attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf" required
                            title="JPG/JPEG, PNG, GIF, WebP, BMP hoặc PDF. Tối đa 2 MB."
                            aria-describedby="leave-file-name<?= isset($errors['attachment']) ? ' leave-attachment-error' : '' ?>"
                            <?= isset($errors['attachment']) ? 'class="leave-input-error" aria-invalid="true"' : '' ?>>
                        <span id="leave-file-name" class="leave-file-name" title="<?= $attachment ? escapeHtml($attachment['name']) : '' ?>"><?= $attachment ? escapeHtml($attachment['name']) : '' ?></span>
                        <span class="leave-upload-icon" aria-hidden="true"></span>
                    </div>
                    <?php if (isset($errors['attachment'])): ?>
                        <p id="leave-attachment-error" class="leave-form-error" role="alert"><?= escapeHtml($errors['attachment']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="leave-form-actions">
                <?php if ($isConfirmation): ?>
                    <button type="submit" name="action" value="confirm_save" class="leave-form-next">Lưu lại</button>
                    <button type="submit" name="action" value="back" class="leave-form-clear" formnovalidate>Quay lại</button>
                <?php else: ?>
                    <button type="submit" name="action" value="validate" class="leave-form-next">Tiếp theo</button>
                    <button type="submit" name="action" value="clear" class="leave-form-clear" formnovalidate>Xóa trống</button>
                <?php endif; ?>
            </div>

            <?php if ($showSavePopup): ?>
                <div class="leave-save-overlay">
                    <section class="leave-save-dialog" role="dialog" aria-modal="true" aria-labelledby="leave-save-title">
                        <h2 id="leave-save-title">Xác nhận lưu đơn</h2>
                        <p>Bạn có muốn tạo đơn này không?</p>
                        <div class="leave-save-buttons">
                            <button type="submit" name="action" value="save" class="leave-form-next">Đồng ý</button>
                            <button type="submit" name="action" value="cancel_save" class="leave-form-clear" autofocus>Đóng</button>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>
