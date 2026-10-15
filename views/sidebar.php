<?php require dirname(__DIR__) . '/logic/sidebar_logic.php'; ?>
<aside class="sidebar">
    <nav class="menu-list" aria-label="Menu chính">
        <a href="dashboard.php" class="menu-item<?= $sidebarPage === 'dashboard.php' ? ' menu-active' : '' ?>"<?= $sidebarPage === 'dashboard.php' ? ' aria-current="page"' : '' ?>>
            <span class="menu-label">Dashboard</span>
        </a>
        <div class="menu-item">
            <span class="menu-label">Quản lý người dùng</span>
        </div>
        <a href="leave_list.php" class="menu-item<?= $sidebarLeavePage ? ' menu-active' : '' ?>"<?= $sidebarLeavePage ? ' aria-current="page"' : '' ?>>
            <span class="menu-label">Quản lý đơn</span>
        </a>
        <form method="post" action="../logic/logout.php">
            <button type="submit" class="menu-item">
                <span class="menu-label">Đăng xuất</span>
            </button>
        </form>
    </nav>
</aside>
