<?php
// Dựa vào tên trang đang mở để tô màu mục tương ứng trong sidebar.
$sidebarPage = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $sidebarPage = basename($_SERVER['SCRIPT_NAME']);
}
$sidebarLeavePage = false;
if ($sidebarPage === 'leave_list.php' || $sidebarPage === 'leave_create.php') {
    $sidebarLeavePage = true;
}
