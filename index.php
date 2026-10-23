<?php
require_once __DIR__ . '/logic/functions/helpers.php';

set_exception_handler('handleUnexpectedError');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

    $db = new mysqli($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    redirectToError();
}
