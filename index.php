<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbName = getenv('DB_NAME') ?: 'train_php';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);
$dbPassword = getenv('DB_PASSWORD');

if ($dbPassword === false) {
    $dbPassword = '';
}

try {
    $db = new mysqli($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}
