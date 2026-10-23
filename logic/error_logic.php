<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/message.php';

$errorCode = 500;
if (isset($_GET['code']) && is_string($_GET['code'])
    && in_array($_GET['code'], ['400', '403', '404', '405', '409', '500'], true)) {
    $errorCode = (int) $_GET['code'];
}
http_response_code($errorCode);
header('Content-Type: text/html; charset=UTF-8');
if ($errorCode === 405) {
    header('Allow: GET, HEAD, POST');
}
header('Cache-Control: no-store');
