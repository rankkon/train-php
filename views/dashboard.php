<?php
// Đọc các đơn trong phạm vi được phép xem và chuẩn bị từng hàng.
require dirname(__DIR__) . '/logic/dashboard_logic.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/common.css')) ?>">
    <link rel="stylesheet" href="../css/dashboard.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/dashboard.css')) ?>">
</head>
<body class="dashboard-page">
    <div class="dashboard-surface">
        <header class="site-header">
            <span class="site-brand">Sanshin</span>
            <span class="site-description">Hệ thống quản lý đơn</span>
        </header>
    </div>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="dashboard-list">
        <!-- Tiêu đề và dữ liệu được in lần lượt từ các mảng do logic chuẩn bị. -->
        <div class="dashboard-table-container">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <?php foreach ($dashboardHeaders as $heading): ?>
                            <th scope="col">
                                <span class="dashboard-cell-text"><?= escapeHtml($heading) ?></span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dashboardRows as $dashboardRow): ?>
                        <tr>
                            <?php foreach ($dashboardRow as $value): ?>
                                <td>
                                    <span class="dashboard-cell-text"><?= escapeHtml($value) ?></span>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
