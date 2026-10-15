<?php $sidebarPage = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>
<aside class="sidebar">
    <nav class="menu-list" aria-label="Menu chính">
        <a href="dashboard.php" class="menu-item<?= $sidebarPage === 'dashboard.php' ? ' menu-active' : '' ?>"<?= $sidebarPage === 'dashboard.php' ? ' aria-current="page"' : '' ?>>
            <span class="menu-label">Dashboard</span>
        </a>
        <div class="menu-item">
            <span class="menu-label">Quản lý người dùng</span>
        </div>
        <a href="leave_list.php" class="menu-item<?= $sidebarPage === 'leave_list.php' ? ' menu-active' : '' ?>"<?= $sidebarPage === 'leave_list.php' ? ' aria-current="page"' : '' ?>>
            <span class="menu-label">Quản lý đơn</span>
        </a>
        <form method="post" action="logout.php">
            <button type="submit" class="menu-item">
                <span class="menu-label">Đăng xuất</span>
            </button>
        </form>
    </nav>
</aside>
