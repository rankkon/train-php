<?php
$sidebarPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$sidebarLeavePage = in_array($sidebarPage, ['leave_list.php', 'leave_create.php'], true);
