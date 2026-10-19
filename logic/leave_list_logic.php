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
if ($requestMethod !== 'GET' && $requestMethod !== 'HEAD' && $requestMethod !== 'POST') {
    header('Allow: GET, HEAD, POST');
    http_response_code(405);
    exit(MSG_METHOD_NOT_ALLOWED);
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/catalogs.php';
require_once __DIR__ . '/functions/leave_process.php';

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

$processPopup = '';
$processLeaveId = 0;
$processMessage = MSG_CONFIRM_APPROVE;
$cancelReason = '';
$processErrors = [];

if ($requestMethod === 'POST') {
    $processing = handleLeaveProcessingPost($db, $currentUser);
    $processPopup = $processing['popup'];
    $processLeaveId = $processing['leave_id'];
    $cancelReason = $processing['reason'];
    $processErrors = $processing['errors'];
    if (!$processErrors && $processPopup === '') {
        header('Location: ../views/' . pageUrl($page, $search), true, 303);
        exit;
    }
}

$searchWhere = leaveSearchWhere($db, $search, $leaveTypes);
$totalRows = countLeaves($db, $currentUser, $search, $searchWhere);
$perPage = LIST_PER_PAGE;
$totalPages = (int) ceil($totalRows / $perPage);
if ($totalPages < 1) {
    $totalPages = 1;
}
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$leaves = findLeaves($db, $currentUser, $search, $searchWhere, $perPage, $offset);

foreach ($leaves as $index => $leave) {
    $leaves[$index]['number'] = $offset + $index + 1;
    $leaves[$index]['type_name'] = $leaveTypes[$leave['leave_type']];
    $leaves[$index]['status_name'] = $leaveStatuses[$leave['status']];
    $leaves[$index]['created_date'] = displayDate($leave['created_at']);
    $leaves[$index]['show_actions'] = canProcessLeave($leave, $currentUser);
    $leaves[$index]['show_edit'] = canEditLeave($leave, $currentUser);
}

$pagination = buildPagination($page, $totalPages, $search, 'leave_list.php');

$previousPageUrl = null;
if ($page > 1) {
    $previousPageUrl = pageUrl($page - 1, $search);
}
$nextPageUrl = null;
if ($page < $totalPages) {
    $nextPageUrl = pageUrl($page + 1, $search);
}
