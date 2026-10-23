<?php

require dirname(__DIR__) . '/logic/login_logic.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/common.css')) ?>">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body class="login-page">
    <div class="login-surface">
        <header class="site-header">
            <span class="site-brand">Sanshin</span>
            <span class="site-description">Hệ thống quản lý đơn</span>
        </header>

        <main class="login-container">
            <h1 class="login-title">Sanshin IT Solution</h1>

            <form method="post" novalidate>
                <div class="login-field login-username">
                    <label for="username">Tên đăng nhập</label>
                    <span class="required-marker">*</span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= escapeHtml($username) ?>"
                        <?php if ($usernameError): ?>class="login-input-error" autofocus<?php endif; ?>
                        placeholder=" "
                        required
                    >
                    <span class="login-placeholder">Tên đăng nhập</span>
                </div>

                <div class="login-field login-password">
                    <label for="password">Mật khẩu</label>
                    <span class="required-marker">*</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        <?php if ($passwordError): ?>class="login-input-error"<?php endif; ?>
                        <?php if ($passwordError && !$usernameError): ?>autofocus<?php endif; ?>
                        placeholder=" "
                        required
                    >
                    <span class="login-placeholder">Mật khẩu</span>
                </div>

                <div class="login-actions">
                    <button class="button-login">
                        <span class="button-text">Login</span>
                    </button>
                    <button class="button-clear" name="action" value="clear">
                        <span class="button-text">Clear</span>
                    </button>
                </div>

                <?php if ($showLoginError): ?>
                    <p class="login-error"><?= escapeHtml($loginErrorMessage) ?></p>
                <?php endif; ?>
            </form>
        </main>
    </div>
</body>
</html>
