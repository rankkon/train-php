<?php
// Đăng xuất phải được gửi từ nút trong form bằng POST.
$requestMethod = 'GET';
if (isset($_SERVER['REQUEST_METHOD'])) {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
}
if ($requestMethod !== 'POST') {
    header('Location: ../views/login.php');
    exit;
}

session_start();
// Xóa dữ liệu đăng nhập và cookie session trên trình duyệt.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $cookie['path'],
        $cookie['domain'],
        $cookie['secure'],
        $cookie['httponly']
    );
}

// Kết thúc session rồi quay về trang đăng nhập.
session_destroy();
header('Location: ../views/login.php', true, 303);
exit;
