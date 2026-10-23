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
require_once __DIR__ . '/functions/user_validation.php';
require_once __DIR__ . '/functions/user_save.php';

$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);

if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

$editedUserId = 0;
$editedUser = null;
$formTitle = 'Thêm mới người dùng';
if (isset($_GET['id'])) {
    $editedUserId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($editedUserId === false || $editedUserId < 1) {
        redirectToError(400);
    }
    $editedUser = findUserById($db, $editedUserId);
    if (!$editedUser) {
        redirectToError(404);
    }
    if (!canManageUser($editedUser, $currentUser)) {
        redirectToError(403);
    }
    $formTitle = 'Sửa người dùng';
} elseif ((int) $currentUser['user_type'] !== USER_TYPE_MANAGER
    && (int) $currentUser['user_type'] !== USER_TYPE_ADMIN) {
    redirectToError(403);
}
$isAdminAccount = $editedUser && (int) $editedUser['user_type'] === USER_TYPE_ADMIN;
$options = userCreateOptions($currentUser, $userTypes, $departments, $editedUser);
$newUserTypes = $options['user_types'];
$newUserDepartments = $options['departments'];
$departmentCodes = array_keys($newUserDepartments);

$defaultValues = [
    'username' => '',
    'full_name' => '',
    'password' => '',
    'email' => '',
    'birth_date' => '',
    'user_type' => (string) USER_TYPE_USER,
    'department' => (string) $departmentCodes[0],
];
if ($editedUser) {
    $defaultValues['username'] = $editedUser['username'];
}
if ($isAdminAccount) {
    $defaultValues['user_type'] = (string) USER_TYPE_ADMIN;
}
$initialValues = $defaultValues;
if ($editedUser) {
    foreach (array_keys($initialValues) as $field) {
        if ($field !== 'password') {
            $initialValues[$field] = (string) $editedUser[$field];
        }
    }
}
$showExitPopup = false;
$showSavePopup = false;
$exitDestination = '';
$exitMessage = MSG_CONFIRM_EXIT;

if ($requestMethod === 'GET' && empty($_SESSION['user_create_resume'])) {
    unset($_SESSION['user_create_draft'], $_SESSION['user_create_exit'], $_SESSION['user_create_save_pending']);
}
unset($_SESSION['user_create_resume']);

if (isset($_SESSION['user_create_draft'])
    && ($_SESSION['user_create_draft']['user_id'] !== $currentUserId
        || !isset($_SESSION['user_create_draft']['edited_user_id'])
        || $_SESSION['user_create_draft']['edited_user_id'] !== $editedUserId)) {
    unset($_SESSION['user_create_draft'], $_SESSION['user_create_exit'], $_SESSION['user_create_save_pending']);
}
if (!isset($_SESSION['user_create_draft'])) {
    $_SESSION['user_create_draft'] = [
        'user_id' => $currentUserId,
        'edited_user_id' => $editedUserId,
        'initial_values' => $initialValues,
        'values' => $initialValues,
        'confirmed' => false,
        'errors' => [],
    ];
}

$draft = $_SESSION['user_create_draft'];
$values = $draft['values'];
$errors = $draft['errors'];
$isConfirmation = $draft['confirmed'];
$initialValues = $draft['initial_values'];

if ($requestMethod !== 'POST') {
    return;
}

$action = 'validate';
if (isset($_POST['action'])) {
    if (!is_string($_POST['action'])) {
        redirectToError(400);
    }
    $action = $_POST['action'];
}

if ($action !== 'save' && $action !== 'confirm_save') {
    unset($_SESSION['user_create_save_pending']);
}

$exitActions = [
    'exit_dashboard' => 'dashboard.php',
    'exit_users' => 'user_list.php',
    'exit_list' => 'leave_list.php',
    'exit_logout' => 'logout',
];

if ($action === 'confirm_exit' || $action === 'cancel_exit') {
    if (!isset($_SESSION['user_create_exit'])
        || ($action === 'confirm_exit' && $_SESSION['user_create_exit'] === 'logout')) {
        redirectToError(400);
    }
    $exitDestination = $_SESSION['user_create_exit'];
    unset($_SESSION['user_create_exit']);
    if ($action === 'cancel_exit') {
        return;
    }
    unset($_SESSION['user_create_draft']);
    header('Location: ../views/' . $exitDestination, true, 303);
    exit;
}

unset($_SESSION['user_create_exit']);

if ($action === 'confirm_save' || $action === 'cancel_save' || $action === 'save') {
    if (!$isConfirmation) {
        redirectToError(400);
    }
    if ($action === 'cancel_save') {
        return;
    }
    if ($action === 'confirm_save') {
        $_SESSION['user_create_save_pending'] = true;
        $showSavePopup = true;
        return;
    }
    if (!isset($_SESSION['user_create_save_pending']) || $_SESSION['user_create_save_pending'] !== true) {
        redirectToError(400);
    }

    unset($_SESSION['user_create_save_pending']);
    $saving = saveUser($db, $currentUserId, $values, $userTypes, $departments, $editedUserId);
    $errors = $saving['errors'];
    $newUserTypes = $saving['options']['user_types'];
    $newUserDepartments = $saving['options']['departments'];
    if ($errors) {
        $isConfirmation = false;
        $_SESSION['user_create_draft']['confirmed'] = false;
        $_SESSION['user_create_draft']['errors'] = $errors;
        return;
    }

    unset($_SESSION['user_create_draft'], $_SESSION['user_create_exit']);
    header('Location: ../views/user_list.php', true, 303);
    exit;
}

if ($action === 'clear') {
    if ($editedUserId > 0) {
        $values = $defaultValues;
        $errors = [];
        $isConfirmation = false;
        $_SESSION['user_create_draft']['values'] = $values;
        $_SESSION['user_create_draft']['errors'] = [];
        $_SESSION['user_create_draft']['confirmed'] = false;
        return;
    }
    unset($_SESSION['user_create_draft']);
    header('Location: ../views/user_create.php', true, 303);
    exit;
}

if ($action === 'back') {
    if (!$isConfirmation) {
        redirectToError(400);
    }
    $isConfirmation = false;
    $_SESSION['user_create_draft']['confirmed'] = false;
    return;
}

if ($action !== 'validate' && !isset($exitActions[$action])) {
    redirectToError(400);
}

if ($action === 'validate' || !$isConfirmation) {
    $errors = [];
    foreach (array_keys($values) as $field) {
        if (($editedUserId > 0 && $field === 'username')
            || ($isAdminAccount && $field === 'user_type')) {
            $values[$field] = (string) $editedUser[$field];
            continue;
        }
        $values[$field] = '';
        if (isset($_POST[$field])) {
            if (is_string($_POST[$field])) {
                $values[$field] = $_POST[$field];
            } else {
                $errors[$field] = MSG_INVALID_FORM_VALUE;
            }
        }
        if ($action !== 'validate' && $values[$field] === $draft['values'][$field]
            && isset($draft['errors'][$field])) {
            $errors[$field] = $draft['errors'][$field];
        }
    }
}

if ($action === 'validate') {
    $isConfirmation = false;
    $errors = validateUserFields($values, $newUserTypes, $newUserDepartments, $errors, $editedUserId > 0);
    $errors = findUserDuplicateErrors($db, $values, $editedUserId, $errors);
    if (!$errors) {
        $isConfirmation = true;
    }
}

$_SESSION['user_create_draft'] = [
    'user_id' => $currentUserId,
    'edited_user_id' => $editedUserId,
    'initial_values' => $initialValues,
    'values' => $values,
    'confirmed' => $isConfirmation,
    'errors' => $errors,
];

if (isset($exitActions[$action])) {
    $exitDestination = $exitActions[$action];
    $hasChanges = $values !== $initialValues || !empty($errors);
    if ($hasChanges || $exitDestination === 'logout') {
        $_SESSION['user_create_exit'] = $exitDestination;
        $showExitPopup = true;
        if ($exitDestination === 'logout') {
            $exitMessage = MSG_CONFIRM_LOGOUT;
            if ($hasChanges) {
                $exitMessage = MSG_CONFIRM_LOGOUT_WITH_CHANGES;
            }
        }
        return;
    }
    unset($_SESSION['user_create_draft']);
    header('Location: ../views/' . $exitDestination, true, 303);
    exit;
}
