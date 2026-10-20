<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/message.php';

$sidebarPage = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $sidebarPage = basename($_SERVER['SCRIPT_NAME']);
}
$sidebarLeavePage = false;
if ($sidebarPage === 'leave_list.php' || $sidebarPage === 'leave_create.php') {
    $sidebarLeavePage = true;
}
