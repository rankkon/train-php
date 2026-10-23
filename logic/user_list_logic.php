<?php
require_once __DIR__ . '/functions/const.php';
require_once __DIR__ . '/functions/message.php';
require_once __DIR__ . '/functions/helpers.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit;
}

$requestMethod = 'GET';
if (isset($_SERVER['REQUEST_METHOD'])) {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
}
if ($requestMethod !== 'GET' && $requestMethod !== 'HEAD' && $requestMethod !== 'POST') {
    header('Allow: GET, HEAD, POST');
    redirectToError(405);
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';
require_once __DIR__ . '/functions/catalogs.php';
require_once __DIR__ . '/functions/user_save.php';

$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);

if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

$showUserListActions = (int) $currentUser['user_type'] === USER_TYPE_MANAGER
    || (int) $currentUser['user_type'] === USER_TYPE_ADMIN;

$search = '';
if (isset($_GET['q']) && is_string($_GET['q'])) {
    $search = trim($_GET['q']);
}

$userStatusFilter = '';
if (isset($_GET['status']) && is_string($_GET['status'])
    && in_array($_GET['status'], [(string) USER_STATUS_ACTIVE, (string) USER_STATUS_LOCKED], true)) {
    $userStatusFilter = $_GET['status'];
}
$activeStatusValue = (string) USER_STATUS_ACTIVE;
$lockedStatusValue = (string) USER_STATUS_LOCKED;
if ($userStatusFilter === $activeStatusValue) {
    $activeStatusValue = '';
}
if ($userStatusFilter === $lockedStatusValue) {
    $lockedStatusValue = '';
}

$page = 1;
if (isset($_GET['page'])) {
    $page = filter_var($_GET['page'], FILTER_VALIDATE_INT);
}
if ($page === false || $page < 1) {
    $page = 1;
}
$userListUrl = pageUrl($page, $search, 'user_list.php', $userStatusFilter);

$statusPopup = '';
$statusUserId = 0;
$statusMessage = '';
$statusCloseAction = 'close_popup';
$selectedUserIds = [];

if ($requestMethod === 'GET') {
    unset($_SESSION['user_bulk_lock_ids']);
}

if ($requestMethod === 'POST') {
    $action = '';
    if (isset($_POST['action']) && is_string($_POST['action'])) {
        $action = $_POST['action'];
    }
    if ($action !== 'confirm_bulk_lock' && $action !== 'close_bulk_popup') {
        unset($_SESSION['user_bulk_lock_ids']);
    }
    if ($action === 'close_popup') {
        header('Location: ../views/' . $userListUrl, true, 303);
        exit;
    }
    if ($action === 'open_bulk_lock') {
        if (isset($_POST['user_ids'])) {
            if (!is_array($_POST['user_ids'])) {
                redirectToError(400);
            } else {
                foreach ($_POST['user_ids'] as $value) {
                    $userId = false;
                    if (is_string($value)) {
                        $userId = filter_var($value, FILTER_VALIDATE_INT);
                    }
                    if ($userId === false || $userId < 1) {
                        redirectToError(400);
                    }
                    $selectedUserIds[] = $userId;
                }
            }
        }
        if ($selectedUserIds) {
            if (!$showUserListActions) {
                redirectToError(403);
            } else {
                $_SESSION['user_bulk_lock_ids'] = $selectedUserIds;
                $statusPopup = 'bulk_lock';
                $statusMessage = MSG_CONFIRM_USERS_LOCK;
                $statusCloseAction = 'close_bulk_popup';
            }
        }
    } elseif ($action === 'confirm_bulk_lock' || $action === 'close_bulk_popup') {
        if (isset($_SESSION['user_bulk_lock_ids'])) {
            $selectedUserIds = $_SESSION['user_bulk_lock_ids'];
        }
        unset($_SESSION['user_bulk_lock_ids']);
        if ($action === 'confirm_bulk_lock' && $selectedUserIds) {
            changeUsersStatus($db, $currentUserId, $selectedUserIds, USER_STATUS_LOCKED);
            header('Location: ../views/' . $userListUrl, true, 303);
            exit;
        }
    } elseif (!in_array($action, ['open_lock', 'open_unlock', 'confirm_lock', 'confirm_unlock'], true)) {
        redirectToError(400);
    } else {
        if (isset($_POST['user_id']) && is_string($_POST['user_id'])) {
            $statusUserId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        }
        if ($statusUserId === false || $statusUserId < 1) {
            redirectToError(400);
        } elseif ($action === 'confirm_lock' || $action === 'confirm_unlock') {
            $status = USER_STATUS_LOCKED;
            if ($action === 'confirm_unlock') {
                $status = USER_STATUS_ACTIVE;
            }
            changeUsersStatus($db, $currentUserId, [$statusUserId], $status);
            header('Location: ../views/' . $userListUrl, true, 303);
            exit;
        } else {
            $targetUser = findUserById($db, $statusUserId);
            if (!$targetUser) {
                redirectToError(404);
            } elseif (!canManageUser($targetUser, $currentUser)) {
                redirectToError(403);
            } else {
                $statusPopup = 'lock';
                $statusMessage = MSG_CONFIRM_USER_LOCK;
                if ($action === 'open_unlock') {
                    $statusPopup = 'unlock';
                    $statusMessage = MSG_CONFIRM_USER_UNLOCK;
                }
            }
        }
    }
}

$totalRows = countUsers($db, $currentUser, $search, $userStatusFilter);
$perPage = LIST_PER_PAGE;
$totalPages = (int) ceil($totalRows / $perPage);
if ($totalPages < 1) {
    $totalPages = 1;
}
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$users = findUsers($db, $currentUser, $search, $perPage, $offset, $userStatusFilter);

foreach ($users as $index => $user) {
    $users[$index]['code'] = str_pad((string) $user['id'], USER_CODE_LENGTH, '0', STR_PAD_LEFT);
    $users[$index]['created_date'] = displayDate($user['created_at']);
    $users[$index]['status_name'] = $userStatuses[$user['status']];
    $users[$index]['show_actions'] = canManageUser($user, $currentUser);
}

$pagination = buildPagination($page, $totalPages, $search, 'user_list.php', $userStatusFilter);

$previousPageUrl = null;
if ($page > 1) {
    $previousPageUrl = pageUrl($page - 1, $search, 'user_list.php', $userStatusFilter);
}
$nextPageUrl = null;
if ($page < $totalPages) {
    $nextPageUrl = pageUrl($page + 1, $search, 'user_list.php', $userStatusFilter);
}
