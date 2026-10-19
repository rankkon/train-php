<?php
require_once __DIR__ . '/functions/const.php';
require_once __DIR__ . '/functions/helpers.php';

// Kiểm tra đăng nhập trước khi đọc dữ liệu Dashboard.
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit;
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';
require_once __DIR__ . '/functions/catalogs.php';

// Check người dùng hiện tại còn hoạt động hay không.
$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);
if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

// Tiêu đề các cột trong bảng.
$dashboardHeaders = [
    'STT',
    'Người dùng',
    'Loại đơn',
    'Ngày lập',
    'Trạng thái',
    'Ngày duty',
    'Mô tả',
];

// Chuẩn bị từng hàng dữ liệu theo đúng thứ tự cột phía trên.
$leaves = findDashboardLeaves($db, $currentUser);
$dashboardRows = [];
foreach ($leaves as $rowIndex => $leave) {
    $dashboardRows[] = [
        (string) ($rowIndex + 1),
        $leave['full_name'],
        $leaveTypes[$leave['leave_type']],
        displayDate($leave['created_at']),
        $leaveStatuses[$leave['status']],
        $leave['end_date'],
        $leave['title'],
    ];
}
