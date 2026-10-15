<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit('Trang danh sách hiện chỉ hỗ trợ xem và tìm kiếm.');
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/catalogs.php';

$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);

if (!$currentUser || (int) $currentUser['status'] !== 1) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($page === false) {
    $page = 1;
}

$totalRows = countLeaves($db, $currentUser, $search, $leaveTypes);
$perPage = 20;
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$leaves = findLeaves($db, $currentUser, $search, $leaveTypes, $perPage, $offset);

foreach ($leaves as $index => $leave) {
    $leaves[$index]['number'] = $offset + $index + 1;
    $leaves[$index]['type_name'] = $leaveTypes[$leave['leave_type']];
    $leaves[$index]['status_name'] = $leaveStatuses[$leave['status']];
    $leaves[$index]['created_date'] = substr($leave['created_at'], 0, 10);
    $leaves[$index]['show_actions'] = canShowButtons($leave, $currentUser);
}

$pageNumbers = [1];
$startPage = max(2, $page - 2);
$endPage = min($totalPages - 1, $page + 2);
for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++) {
    $pageNumbers[] = $pageNumber;
}
if ($totalPages > 1) {
    $pageNumbers[] = $totalPages;
}

$pagination = [];
$previousPage = 0;
foreach ($pageNumbers as $pageNumber) {
    $pagination[] = [
        'number' => $pageNumber,
        'url' => pageUrl($pageNumber, $search),
        'current' => $pageNumber === $page,
        'show_gap' => $pageNumber > $previousPage + 1,
    ];
    $previousPage = $pageNumber;
}

$previousPageUrl = $page > 1 ? pageUrl($page - 1, $search) : null;
$nextPageUrl = $page < $totalPages ? pageUrl($page + 1, $search) : null;
