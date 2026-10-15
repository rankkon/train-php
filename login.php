<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/index.php';

$username = '';
$password = '';
$usernameError = false;
$passwordError = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        header('Location: login.php', true, 303);
        exit;
    }

    if (isset($_POST['username']) && is_string($_POST['username'])) {
        $username = trim($_POST['username']);
    }

    if (isset($_POST['password']) && is_string($_POST['password'])) {
        $password = $_POST['password'];
    }


    $usernameError = $username === '';
    $passwordError = $password === '';

    if (!$usernameError && !$passwordError) {
        $statement = $db->prepare('SELECT id, password_hash, status FROM users WHERE username = ? LIMIT 1');
        $statement->bind_param('s', $username);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$user || (int) $user['status'] !== 1) {
            $usernameError = true;
        } elseif (!hash_equals($user['password_hash'], md5($password))) {
            $passwordError = true;
        } else {

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            header('Location: dashboard.php', true, 303);
            exit;
        }
    }
}

$showLoginError = $usernameError || $passwordError;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Sanshin</title>
    <link rel="stylesheet" href="css/common.css?v=<?= filemtime(__DIR__ . '/css/common.css') ?>">
    <link rel="stylesheet" href="css/login.css">
</head>
<body class="login-page">
    <div class="login-surface">
        <header class="site-header">
            <span class="site-brand">Sanshin</span>
            <span class="site-description">Hệ thống quản lý đơn</span>
        </header>

        <main class="login-container">
            <h1 class="login-title">Sanshin IT Solution</h1>

            <form class="login-form" method="post" action="login.php" novalidate>
                <div class="login-field login-username">
                    <label for="username">Tên đăng nhập</label>
                    <span class="required-marker">*</span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $usernameError ? 'class="login-input-error" autofocus' : '' ?>
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
                        <?= $passwordError ? 'class="login-input-error"' : '' ?>
                        <?= $passwordError && !$usernameError ? 'autofocus' : '' ?>
                        placeholder=" "
                        required
                    >
                    <span class="login-placeholder">Mật khẩu</span>
                </div>

                <div class="login-actions">
                    <button type="submit" class="button-login">
                        <span class="button-text">Login</span>
                    </button>
                    <button type="submit" class="button-clear" name="action" value="clear" formnovalidate>
                        <span class="button-text">Clear</span>
                    </button>
                </div>

                <?php if ($showLoginError): ?>
                    <p class="login-error">※Tên đăng nhập hoặc mật khẩu lỗi. Vui lòng thử lại！</p>
                <?php endif; ?>
            </form>
        </main>
    </div>
</body>
</html>
