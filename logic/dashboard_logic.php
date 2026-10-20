<?php
require_once __DIR__ . '/functions/const.php';
require_once __DIR__ . '/functions/message.php';
require_once __DIR__ . '/functions/helpers.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit;
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';
require_once __DIR__ . '/functions/catalogs.php';

$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);
if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

$page = 1;
if (isset($_GET['page'])) {
    $page = filter_var($_GET['page'], FILTER_VALIDATE_INT);
}
if ($page === false || $page < 1) {
    $page = 1;
}

$totalRows = countLeaves($db, $currentUser, '', '');
$perPage = LEAVE_LIST_PER_PAGE;
$totalPages = (int) ceil($totalRows / $perPage);
if ($totalPages < 1) {
    $totalPages = 1;
}
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$leaves = findDashboardLeaves($db, $currentUser, $perPage, $offset);
foreach ($leaves as $index => $leave) {
    $leaves[$index]['number'] = $offset + $index + 1;
    $leaves[$index]['type_name'] = $leaveTypes[$leave['leave_type']];
    $leaves[$index]['status_name'] = $leaveStatuses[$leave['status']];
    $leaves[$index]['created_date'] = displayDate($leave['created_at']);
}

$pagination = buildPagination($page, $totalPages, '', 'dashboard.php');

$previousPageUrl = null;
if ($page > 1) {
    $previousPageUrl = pageUrl($page - 1, '', 'dashboard.php');
}
$nextPageUrl = null;
if ($page < $totalPages) {
    $nextPageUrl = pageUrl($page + 1, '', 'dashboard.php');
}
