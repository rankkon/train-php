<?php

require dirname(__DIR__) . '/logic/user_create_logic.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeHtml($formTitle) ?> | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/common.css')) ?>">
    <link rel="stylesheet" href="../css/user_form.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/user_form.css')) ?>">
</head>
<body class="user-create-page">
    <header class="site-header">
        <span class="site-brand">Sanshin</span>
        <span class="site-description">Hệ thống quản lý đơn</span>
    </header>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="user-create">
        <h1 class="user-form-title"><?= escapeHtml($formTitle) ?></h1>

        <form method="post" id="user-form" class="user-form<?php if ($isConfirmation): ?> user-form-confirm<?php endif; ?>" novalidate>
            <div class="user-form-fields">
                <?php if (isset($errors['form'])): ?>
                    <p class="user-field-error"><?= escapeHtml($errors['form']) ?></p>
                <?php endif; ?>

                <div class="user-form-field">
                    <label for="username">Tên đăng nhập <span class="required-marker">*</span></label>
                    <input type="text" id="username" name="username" value="<?= escapeHtml($values['username']) ?>" required
                        <?php if ($isConfirmation || $editedUserId > 0): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['username'])): ?>class="user-input-error"<?php endif; ?>>
                    <?php if (isset($errors['username'])): ?>
                        <p class="user-field-error"><?= escapeHtml($errors['username']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="user-form-field">
                    <label for="full-name">Tên người dùng <span class="required-marker">*</span></label>
                    <input type="text" id="full-name" name="full_name" value="<?= escapeHtml($values['full_name']) ?>" required
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['full_name'])): ?>class="user-input-error"<?php endif; ?>>
                    <?php if (isset($errors['full_name'])): ?>
                        <p class="user-field-error"><?= escapeHtml($errors['full_name']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="user-form-field">
                    <label for="password">Mật khẩu<?php if ($editedUserId > 0): ?> mới<?php else: ?> <span class="required-marker">*</span><?php endif; ?></label>
                    <input type="password" id="password" name="password" value="<?= escapeHtml($values['password']) ?>"
                        <?php if ($editedUserId === 0): ?>required<?php endif; ?>
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['password'])): ?>class="user-input-error"<?php endif; ?>>
                    <?php if (isset($errors['password'])): ?>
                        <p class="user-field-error"><?= escapeHtml($errors['password']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="user-form-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= escapeHtml($values['email']) ?>"
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['email'])): ?>class="user-input-error"<?php endif; ?>>
                    <?php if (isset($errors['email'])): ?>
                        <p class="user-field-error"><?= escapeHtml($errors['email']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="user-form-field user-form-date">
                    <label for="birth-date">Ngày sinh</label>
                    <input type="date" id="birth-date" name="birth_date" value="<?= escapeHtml($values['birth_date']) ?>"
                        <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                        <?php if (isset($errors['birth_date'])): ?>class="user-input-error"<?php endif; ?>>
                    <?php if (isset($errors['birth_date'])): ?>
                        <p class="user-field-error"><?= escapeHtml($errors['birth_date']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="user-form-field">
                    <label for="user-type">Loại người dùng <span class="required-marker">*</span></label>
                    <div class="user-select-control">
                        <select id="user-type" name="user_type" required
                            <?php if ($isConfirmation || $isAdminAccount): ?>disabled<?php endif; ?>
                            <?php if (isset($errors['user_type'])): ?>class="user-input-error"<?php endif; ?>>
                            <?php foreach ($newUserTypes as $code => $name): ?>
                                <option value="<?= escapeHtml($code) ?>" <?php if ($values['user_type'] === (string) $code): ?>selected<?php endif; ?>><?= escapeHtml($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isset($errors['user_type'])): ?>
                        <p class="user-field-error"><?= escapeHtml($errors['user_type']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="user-form-field">
                    <label for="department">Phòng ban <span class="required-marker">*</span></label>
                    <div class="user-select-control">
                        <select id="department" name="department" required
                            <?php if ($isConfirmation): ?>disabled<?php endif; ?>
                            <?php if (isset($errors['department'])): ?>class="user-input-error"<?php endif; ?>>
                            <?php foreach ($newUserDepartments as $code => $name): ?>
                                <option value="<?= escapeHtml($code) ?>" <?php if ($values['department'] === (string) $code): ?>selected<?php endif; ?>><?= escapeHtml($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isset($errors['department'])): ?>
                        <p class="user-field-error"><?= escapeHtml($errors['department']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="user-form-actions">
                <?php if ($isConfirmation): ?>
                    <button name="action" value="confirm_save" class="user-form-next">Lưu lại</button>
                    <button name="action" value="back" class="user-form-clear">Quay lại</button>
                <?php else: ?>
                    <button name="action" value="validate" class="user-form-next">Tiếp theo</button>
                    <button name="action" value="clear" class="user-form-clear">Xóa trống</button>
                <?php endif; ?>
            </div>
        </form>
    </main>
    <?php if ($showSavePopup): ?>
        <div class="popup-overlay">
            <section class="popup-dialog">
                <header class="popup-header">
                    <h2 class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
                    <button form="user-form" name="action" value="cancel_save" class="popup-close" title="Đóng"></button>
                </header>
                <p class="popup-message"><?= escapeHtml(MSG_CONFIRM_SAVE) ?></p>
                <div class="popup-actions">
                    <button form="user-form" name="action" value="save" class="popup-button popup-confirm">Đồng ý</button>
                    <button form="user-form" name="action" value="cancel_save" class="popup-button popup-cancel" autofocus>Không</button>
                </div>
            </section>
        </div>
    <?php endif; ?>
    <?php if ($showExitPopup): ?>
        <div class="popup-overlay">
            <section class="popup-dialog">
                <header class="popup-header">
                    <h2 class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
                    <button form="user-form" name="action" value="cancel_exit" class="popup-close" title="Đóng"></button>
                </header>
                <p class="popup-message"><?= escapeHtml($exitMessage) ?></p>
                <div class="popup-actions">
                    <?php if ($exitDestination === 'logout'): ?>
                        <button form="user-form" formaction="../logic/logout.php" class="popup-button popup-confirm">Đồng ý</button>
                    <?php else: ?>
                        <button form="user-form" name="action" value="confirm_exit" class="popup-button popup-confirm">Đồng ý</button>
                    <?php endif; ?>
                    <button form="user-form" name="action" value="cancel_exit" class="popup-button popup-cancel" autofocus>Không</button>
                </div>
            </section>
        </div>
    <?php endif; ?>
</body>
</html>
