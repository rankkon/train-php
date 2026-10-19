<?php
require_once __DIR__ . '/logic/functions/message.php';

// Báo lỗi MySQL để xử lý lỗi kết nối và hoàn tác khi lưu thất bại.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Lấy cấu hình trên máy đang chạy.
$dbHost = getenv('DB_HOST');
if (!$dbHost) {
    $dbHost = '127.0.0.1';
}

$dbUser = getenv('DB_USER');
if (!$dbUser) {
    $dbUser = 'root';
}

$dbName = getenv('DB_NAME');
if (!$dbName) {
    $dbName = 'train_php';
}

$dbPort = getenv('DB_PORT');
if (!$dbPort) {
    $dbPort = 3306;
}
$dbPort = (int) $dbPort;

$dbPassword = getenv('DB_PASSWORD');

if ($dbPassword === false) {
    $dbPassword = '';
}

try {
    // Kết nối database và dùng UTF-8 để đọc, ghi tiếng Việt.
    $db = new mysqli($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    http_response_code(500);
    exit(MSG_DATABASE_CONNECTION_FAILED);
}
