<?php

require dirname(__DIR__) . '/logic/sidebar_logic.php';
?>
<aside class="sidebar">
    <nav class="menu-list">
        <a href="dashboard.php" class="menu-item<?php if ($sidebarPage === 'dashboard.php'): ?> menu-active<?php endif; ?>">
            <span class="menu-label">Dashboard</span>
        </a>
        <a href="user_list.php" class="menu-item<?php if ($sidebarPage === 'user_list.php'): ?> menu-active<?php endif; ?>">
            <span class="menu-label">Quản lý người dùng</span>
        </a>
        <a href="leave_list.php" class="menu-item<?php if ($sidebarLeavePage): ?> menu-active<?php endif; ?>">
            <span class="menu-label">Quản lý đơn</span>
        </a>
        <form method="post" action="../logic/logout.php">
            <button type="submit" class="menu-item">
                <span class="menu-label">Đăng xuất</span>
            </button>
        </form>
    </nav>
</aside>
