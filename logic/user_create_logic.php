<?php
require_once __DIR__ . '/functions/const.php';
require_once __DIR__ . '/functions/message.php';

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
    http_response_code(405);
    exit(MSG_METHOD_NOT_ALLOWED);
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';
require_once __DIR__ . '/functions/helpers.php';
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

$options = userCreateOptions($currentUser, $userTypes, $departments);
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
$showExitPopup = false;
$showSavePopup = false;
$exitDestination = '';
$exitMessage = MSG_CONFIRM_EXIT;

if ($requestMethod === 'GET') {
    unset($_SESSION['user_create_draft'], $_SESSION['user_create_exit'], $_SESSION['user_create_save_pending']);
}

if (isset($_SESSION['user_create_draft'])
    && $_SESSION['user_create_draft']['user_id'] !== $currentUserId) {
    unset($_SESSION['user_create_draft'], $_SESSION['user_create_exit'], $_SESSION['user_create_save_pending']);
}
if (!isset($_SESSION['user_create_draft'])) {
    $_SESSION['user_create_draft'] = [
        'user_id' => $currentUserId,
        'values' => $defaultValues,
        'confirmed' => false,
        'errors' => [],
    ];
}

$draft = $_SESSION['user_create_draft'];
$values = $draft['values'];
$errors = $draft['errors'];
$isConfirmation = $draft['confirmed'];

if ($requestMethod !== 'POST') {
    return;
}

$action = 'validate';
if (isset($_POST['action'])) {
    if (!is_string($_POST['action'])) {
        unset($_SESSION['user_create_save_pending']);
        http_response_code(400);
        $errors['form'] = MSG_INVALID_ACTION;
        return;
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
        http_response_code(400);
        $errors['form'] = MSG_INVALID_ACTION;
        return;
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
        unset($_SESSION['user_create_save_pending']);
        http_response_code(400);
        $errors['form'] = MSG_USER_CONFIRMATION_INVALID;
        return;
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
        http_response_code(400);
        $errors['form'] = MSG_USER_SAVE_CONFIRMATION_REQUIRED;
        return;
    }

    unset($_SESSION['user_create_save_pending']);
    $saving = saveUser($db, $currentUserId, $values, $userTypes, $departments);
    $errors = $saving['errors'];
    if ($saving['options'] !== null) {
        $newUserTypes = $saving['options']['user_types'];
        $newUserDepartments = $saving['options']['departments'];
    }
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
    unset($_SESSION['user_create_draft']);
    header('Location: ../views/user_create.php', true, 303);
    exit;
}

if ($action === 'back') {
    if (!$isConfirmation) {
        http_response_code(400);
        $errors['form'] = MSG_USER_CONFIRMATION_INVALID;
        return;
    }
    $isConfirmation = false;
    $_SESSION['user_create_draft']['confirmed'] = false;
    return;
}

if ($action !== 'validate' && !isset($exitActions[$action])) {
    http_response_code(400);
    $errors['form'] = MSG_INVALID_ACTION;
    return;
}

if ($action === 'validate' || !$isConfirmation) {
    $errors = [];
    foreach (array_keys($values) as $field) {
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
    $errors = validateUserFields($values, $newUserTypes, $newUserDepartments, $errors);
    if (!isset($errors['username'])) {
        $existingUser = findUserForLogin($db, $values['username']);
        if ($existingUser) {
            $errors['username'] = MSG_USERNAME_EXISTS;
        }
    }
    if (!$errors) {
        $isConfirmation = true;
    }
}

$_SESSION['user_create_draft'] = [
    'user_id' => $currentUserId,
    'values' => $values,
    'confirmed' => $isConfirmation,
    'errors' => $errors,
];

if (isset($exitActions[$action])) {
    $exitDestination = $exitActions[$action];
    $hasChanges = $values !== $defaultValues || !empty($errors);
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
