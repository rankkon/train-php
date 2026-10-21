<?php
require_once __DIR__ . '/functions/const.php';
require_once __DIR__ . '/functions/message.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit;
}

$requestMethod = 'GET';
if (isset($_SERVER['REQUEST_METHOD'])) {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
}
if ($requestMethod !== 'GET' && $requestMethod !== 'HEAD') {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit(MSG_LIST_READ_ONLY);
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/catalogs.php';

$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);

if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

$search = '';
if (isset($_GET['q']) && is_string($_GET['q'])) {
    $search = trim($_GET['q']);
}

$page = 1;
if (isset($_GET['page'])) {
    $page = filter_var($_GET['page'], FILTER_VALIDATE_INT);
}
if ($page === false || $page < 1) {
    $page = 1;
}

$totalRows = countUsers($db, $currentUser, $search);
$perPage = LIST_PER_PAGE;
$totalPages = (int) ceil($totalRows / $perPage);
if ($totalPages < 1) {
    $totalPages = 1;
}
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$users = findUsers($db, $currentUser, $search, $perPage, $offset);

foreach ($users as $index => $user) {
    $users[$index]['code'] = str_pad((string) $user['id'], USER_CODE_LENGTH, '0', STR_PAD_LEFT);
    $users[$index]['created_date'] = displayDate($user['created_at']);
    $users[$index]['status_name'] = $userStatuses[$user['status']];
    $users[$index]['show_actions'] = canManageUser($user, $currentUser);
}

$pagination = buildPagination($page, $totalPages, $search, 'user_list.php');

$previousPageUrl = null;
if ($page > 1) {
    $previousPageUrl = pageUrl($page - 1, $search, 'user_list.php');
}
$nextPageUrl = null;
if ($page < $totalPages) {
    $nextPageUrl = pageUrl($page + 1, $search, 'user_list.php');
}
