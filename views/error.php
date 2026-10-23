<?php
require dirname(__DIR__) . '/logic/error_logic.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= escapeHtml(APP_BASE_PATH . '/views/') ?>">
    <title><?= escapeHtml(MSG_PAGE_ERROR) ?> | Sanshin</title>
    <link rel="stylesheet" href="../css/common.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/common.css')) ?>">
    <link rel="stylesheet" href="../css/error.css?v=<?= escapeHtml(filemtime(dirname(__DIR__) . '/css/error.css')) ?>">
</head>
<body class="error-page">
    <header class="site-header">
        <span class="site-brand">Sanshin</span>
        <span class="site-description">Hệ thống quản lý đơn</span>
    </header>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="error-content">
        <h1><?= escapeHtml(MSG_PAGE_ERROR) ?></h1>
    </main>
</body>
</html>
