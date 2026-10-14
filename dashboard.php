<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/index.php';

$dashboardHeaders = [
    'STT',
    'Người dùng',
    'Loại đơn',
    'Ngày lập',
    'Trạng thái',
    'Ngày duty',
    'Mô tả',
];

$leaveStatuses = [
    0 => 'Đơn mới',
    1 => 'Đã duyệt',
    2 => 'Đã hủy',
];

$leaveTypes = [
    1 => 'Đơn nghỉ phép',
    2 => 'Đơn cấp vật tư máy móc',
    3 => 'Đơn thay đổi giờ làm',
    4 => 'Đơn xin thanh toán công tác phí',
];

$leaves = $db->query('
    SELECT users.full_name, `leave`.leave_type,
           `leave`.created_at, `leave`.status, `leave`.start_date,
           `leave`.end_date, `leave`.title
    FROM `leave`
    JOIN users ON users.id = `leave`.user_id
    ORDER BY `leave`.created_at DESC, `leave`.id DESC
    LIMIT 30
')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sanshin</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="dashboard-page">
    <div class="dashboard-surface">
        <header class="dashboard-header">
            <span class="dashboard-brand">Sanshin</span>
            <span class="dashboard-description">Hệ thống quản lý đơn</span>
        </header>
    </div>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="dashboard-list">
        <div class="dashboard-table">
            <div class="dashboard-row dashboard-table-heading">
                <?php foreach ($dashboardHeaders as $columnIndex => $heading): ?>
                    <div class="dashboard-cell dashboard-cell-<?= $columnIndex ?>">
                        <div class="dashboard-cell-content">
                            <span class="dashboard-cell-text"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($leaves as $rowIndex => $leave): ?>
                <?php
                $dashboardRow = [
                    (string) ($rowIndex + 1),
                    $leave['full_name'],
                    $leaveTypes[$leave['leave_type']],
                    substr($leave['created_at'], 0, 10),
                    $leaveStatuses[$leave['status']],
                    $leave['end_date'],
                    $leave['title'],
                ];
                ?>
                <div class="dashboard-row">
                    <?php foreach ($dashboardRow as $columnIndex => $value): ?>
                        <div class="dashboard-cell dashboard-cell-<?= $columnIndex ?>">
                            <div class="dashboard-cell-content">
                                <span class="dashboard-cell-text"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
