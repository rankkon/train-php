<?php

require dirname(__DIR__) . '/logic/sidebar_logic.php';
?>
<?php if (!$sidebarConfirmLeave): ?>
    <input type="checkbox" id="logout-confirm-toggle" class="logout-confirm-toggle" hidden>
<?php endif; ?>
<aside class="sidebar">
    <nav class="menu-list">
        <?php if ($sidebarConfirmLeave): ?>
            <button type="submit" form="leave-form" name="action" value="exit_dashboard" class="menu-item" formnovalidate>
                <span class="menu-label">Dashboard</span>
            </button>
            <button type="submit" form="leave-form" name="action" value="exit_users" class="menu-item" formnovalidate>
                <span class="menu-label">Quản lý người dùng</span>
            </button>
            <button type="submit" form="leave-form" name="action" value="exit_list" class="menu-item menu-active" formnovalidate>
                <span class="menu-label">Quản lý đơn</span>
            </button>
        <?php else: ?>
            <a href="dashboard.php" class="menu-item<?php if ($sidebarPage === 'dashboard.php'): ?> menu-active<?php endif; ?>">
                <span class="menu-label">Dashboard</span>
            </a>
            <a href="user_list.php" class="menu-item<?php if ($sidebarPage === 'user_list.php'): ?> menu-active<?php endif; ?>">
                <span class="menu-label">Quản lý người dùng</span>
            </a>
            <a href="leave_list.php" class="menu-item<?php if ($sidebarLeavePage): ?> menu-active<?php endif; ?>">
                <span class="menu-label">Quản lý đơn</span>
            </a>
        <?php endif; ?>
        <?php if ($sidebarConfirmLeave): ?>
            <button type="submit" form="leave-form" name="action" value="exit_logout" class="menu-item" formnovalidate>
                <span class="menu-label">Đăng xuất</span>
            </button>
        <?php else: ?>
            <label for="logout-confirm-toggle" class="menu-item">
                <span class="menu-label">Đăng xuất</span>
            </label>
        <?php endif; ?>
    </nav>
</aside>
<?php if (!$sidebarConfirmLeave): ?>
<div class="popup-overlay logout-confirm-popup">
    <form method="post" action="../logic/logout.php" class="popup-dialog">
        <header class="popup-header">
            <h2 class="popup-title"><?= escapeHtml(MSG_CONFIRMATION_TITLE) ?></h2>
            <label for="logout-confirm-toggle" class="popup-close" title="Đóng"></label>
        </header>
        <p class="popup-message"><?= escapeHtml(MSG_CONFIRM_LOGOUT) ?></p>
        <div class="popup-actions">
            <button type="submit" class="popup-button popup-confirm">OK</button>
            <label for="logout-confirm-toggle" class="popup-button popup-cancel">Cancel</label>
        </div>
    </form>
</div>
<?php endif; ?>
