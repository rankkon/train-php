<?php
require_once __DIR__ . '/functions/const.php';
require_once __DIR__ . '/functions/message.php';

// Chỉ cho tài khoản đã đăng nhập mở trang danh sách.
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

// Lấy quyền và phòng ban hiện tại từ database.
$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);

if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

// Nhận từ khóa tìm kiếm và số trang từ URL.
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

// Đếm và lấy dữ liệu theo cùng từ khóa, phòng ban rồi mới phân trang.
// Tạo điều kiện một lần để truy vấn đếm và lấy đơn luôn dùng cùng danh sách loại phù hợp.
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

// Đổi mã số thành tên hiển thị và kiểm tra có hiện nút Duyệt/Hủy hay không.
foreach ($leaves as $index => $leave) {
    $leaves[$index]['number'] = $offset + $index + 1;
    $leaves[$index]['type_name'] = $leaveTypes[$leave['leave_type']];
    $leaves[$index]['status_name'] = $leaveStatuses[$leave['status']];
    $leaves[$index]['created_date'] = displayDate($leave['created_at']);
    $leaves[$index]['show_actions'] = canShowLeaveProcessingButtons($leave, $currentUser);
    $leaves[$index]['show_edit'] = canEditLeave($leave, $currentUser);
}

// Dùng chung cách dựng phân trang; giữ từ khóa và đường dẫn của màn hình này.
$pagination = buildPagination($page, $totalPages, $search, 'leave_list.php');

$previousPageUrl = null;
if ($page > 1) {
    $previousPageUrl = pageUrl($page - 1, $search);
}
$nextPageUrl = null;
if ($page < $totalPages) {
    $nextPageUrl = pageUrl($page + 1, $search);
}
