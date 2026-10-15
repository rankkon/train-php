<?php require dirname(__DIR__) . '/logic/dashboard_logic.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= filemtime(dirname(__DIR__) . '/css/common.css') ?>">
    <link rel="stylesheet" href="../css/dashboard.css?v=<?= filemtime(dirname(__DIR__) . '/css/dashboard.css') ?>">
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
        <div class="dashboard-table" role="table" aria-label="Danh sách đơn">
            <div class="dashboard-row dashboard-table-heading" role="row">
                <?php foreach ($dashboardHeaders as $columnIndex => $heading): ?>
                    <div class="dashboard-cell dashboard-cell-<?= $columnIndex ?>" role="columnheader">
                        <div class="dashboard-cell-content">
                            <span class="dashboard-cell-text"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($dashboardRows as $dashboardRow): ?>
                <div class="dashboard-row" role="row">
                    <?php foreach ($dashboardRow as $columnIndex => $value): ?>
                        <div class="dashboard-cell dashboard-cell-<?= $columnIndex ?>" role="cell">
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
