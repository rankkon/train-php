<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Sanshin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-surface">
        <header class="site-header">
            <span class="header-text" aria-hidden="true"> </span>
            <span class="site-brand">Sanshin</span>
            <span class="site-description">Hệ thống quản lý đơn</span>
        </header>

        <main class="login-container">
            <h1 id="login-title" class="login-title">Sanshin IT Solution</h1>

            <form class="login-form" aria-labelledby="login-title">
                <div class="login-field login-username">
                    <label for="username">Tên đăng nhập</label>
                    <span class="required-marker" aria-hidden="true">*</span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder=" "
                        required
                    >
                    <span class="login-placeholder" aria-hidden="true">Tên đăng nhập</span>
                </div>

                <div class="login-field login-password">
                    <label for="password">Mật khẩu</label>
                    <span class="required-marker" aria-hidden="true">*</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder=" "
                        required
                    >
                    <span class="login-placeholder" aria-hidden="true">Mật khẩu</span>
                </div>

                <div class="login-actions">
                    <button type="button" class="button-login">
                        <span class="button-text">Login</span>
                    </button>
                    <button type="reset" class="button-clear">
                        <span class="button-text">CLear</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
    <div class="login-frame" aria-hidden="true"></div>
</body>
</html>
