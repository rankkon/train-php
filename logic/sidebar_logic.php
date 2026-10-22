<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/message.php';

$sidebarPage = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $sidebarPage = basename($_SERVER['SCRIPT_NAME']);
}
$sidebarUserPage = $sidebarPage === 'user_list.php' || $sidebarPage === 'user_create.php';

$sidebarLeavePage = $sidebarPage === 'leave_list.php' || $sidebarPage === 'leave_create.php';

$sidebarFormId = '';
if ($sidebarPage === 'leave_create.php') {
    $sidebarFormId = 'leave-form';
} elseif ($sidebarPage === 'user_create.php') {
    $sidebarFormId = 'user-form';
}
