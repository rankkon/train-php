<?php
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
if (!$currentUser || (int) $currentUser['status'] !== 1) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

$dashboardHeaders = [
    'STT',
    'Người dùng',
    'Loại đơn',
    'Ngày lập',
    'Trạng thái',
    'Ngày duty',
    'Mô tả',
];

$leaves = findDashboardLeaves($db, $currentUser);
$dashboardRows = [];
foreach ($leaves as $rowIndex => $leave) {
    $dashboardRows[] = [
        (string) ($rowIndex + 1),
        $leave['full_name'],
        $leaveTypes[$leave['leave_type']],
        substr($leave['created_at'], 0, 10),
        $leaveStatuses[$leave['status']],
        $leave['end_date'],
        $leave['title'],
    ];
}
