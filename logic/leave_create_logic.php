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
require_once __DIR__ . '/functions/leave_validation.php';
require_once __DIR__ . '/functions/leave_form.php';
require_once __DIR__ . '/functions/leave_save.php';

$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);

if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

$leaveId = 0;
$leave = null;
$formTitle = 'Thêm mới đơn';
if (isset($_GET['id'])) {
    $leaveId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($leaveId === false || $leaveId < 1) {
        redirectToError(400);
    }
    $leave = findLeaveById($db, $leaveId);
    if (!$leave) {
        redirectToError(404);
    }
    if (!canEditLeave($leave, $currentUser)) {
        redirectToError(403);
    }
    $formTitle = 'Sửa đơn';
}

$approvers = findApprovers($db, $currentUser);

date_default_timezone_set(APP_TIMEZONE);
$minimumDate = date(APP_DATE_FORMAT);
if ($leaveId > 0) {
    $minimumDate = '';
}
$maxAttachmentSize = ATTACHMENT_MAX_BYTES;
$attachmentAccept = attachmentAccept();
$defaultValues = emptyLeaveValues();
if ($leaveId === 0) {
    $defaultValues['start_date'] = $minimumDate;
    $defaultValues['end_date'] = $minimumDate;
}
if ($approvers) {
    $defaultValues['approver_id'] = (string) $approvers[0]['id'];
}
$leaveTypeCodes = array_keys($leaveTypes);
$defaultValues['leave_type'] = (string) $leaveTypeCodes[0];
$values = $defaultValues;
$errors = [];
$isConfirmation = false;
$showSavePopup = false;
$showExitPopup = false;
$exitDestination = '';
$exitMessage = MSG_CONFIRM_EXIT;

if ($requestMethod === 'GET' && empty($_SESSION['leave_create_resume'])) {
    clearLeaveDraft();
}
unset($_SESSION['leave_create_resume']);

$draft = null;
if (isset($_SESSION['leave_create_draft'])) {
    $draft = $_SESSION['leave_create_draft'];
}
if ($draft && (int) $draft['user_id'] === $currentUserId
    && isset($draft['leave_id']) && (int) $draft['leave_id'] === $leaveId
    && isset($_SESSION['leave_create_initial'])) {
    $values = $draft['values'];
    if (isset($draft['errors'])) {
        $errors = $draft['errors'];
    }
} else {
    clearLeaveDraft();
    $draft = null;
    if ($leave) {
        foreach (array_keys($values) as $field) {
            $values[$field] = (string) $leave[$field];
        }
        $selectedApproverId = $defaultValues['approver_id'];
        foreach ($approvers as $approver) {
            if ($values['approver_id'] === (string) $approver['id']) {
                $selectedApproverId = $values['approver_id'];
                break;
            }
        }
        $values['approver_id'] = $selectedApproverId;
        $savedAttachment = readSavedLeaveAttachment($leave, $currentUserId);

        if ($savedAttachment) {
            $_SESSION['leave_create_attachment'] = $savedAttachment;
        }
    }
}

$attachment = null;
if (isset($_SESSION['leave_create_attachment'])) {
    $attachment = $_SESSION['leave_create_attachment'];
}
if ($attachment && (int) $attachment['user_id'] !== $currentUserId) {
    unset($_SESSION['leave_create_attachment']);
    $attachment = null;
}

if (!isset($_SESSION['leave_create_initial'])) {
    $_SESSION['leave_create_initial'] = [
        'values' => $values,
        'attachment' => leaveAttachmentFingerprint($attachment),
    ];
    $draft = [
        'user_id' => $currentUserId,
        'leave_id' => $leaveId,
        'values' => $values,
        'confirmed' => false,
        'errors' => [],
    ];
    $_SESSION['leave_create_draft'] = $draft;
}
$initialForm = $_SESSION['leave_create_initial'];

if ($requestMethod !== 'POST') {
    return;
}

$action = 'validate';
if (isset($_POST['action'])) {
    if (is_string($_POST['action'])) {
        $action = $_POST['action'];
    } else {
        $action = '';
    }
}

$contentLength = 0;
if (isset($_SERVER['CONTENT_LENGTH'])) {
    $contentLength = (int) $_SERVER['CONTENT_LENGTH'];
}
if (!$_POST && !$_FILES && $contentLength > 0) {
    http_response_code(400);
    $errors['form'] = sprintf(MSG_POST_DATA_UNREADABLE, ATTACHMENT_MAX_MB);
    return;
}

$exitActions = [
    'exit_dashboard' => 'dashboard.php',
    'exit_users' => 'user_list.php',
    'exit_list' => 'leave_list.php',
    'exit_logout' => 'logout',
];

if (isset($exitActions[$action])) {
    $exitDestination = $exitActions[$action];
    $isConfirmation = !empty($draft['confirmed']);
    unset($_SESSION['leave_create_save_pending'], $_SESSION['leave_create_exit']);
    $uploadError = '';
    $hasInvalidValues = false;

    if (!$isConfirmation) {
        $form = readLeaveValues($_POST);
        $values = $form['values'];
        $hasInvalidValues = !empty($form['errors']);
        foreach ($form['errors'] as $field => $message) {
            $errors[$field] = $message;
        }

        $upload = readLeaveUpload($_FILES, $attachment, $currentUserId, $maxAttachmentSize, true);
        $attachment = $upload['attachment'];
        $uploadError = $upload['error'];
        if ($attachment) {
            $_SESSION['leave_create_attachment'] = $attachment;
        } else {
            unset($_SESSION['leave_create_attachment']);
        }
        if ($uploadError !== '') {
            $errors['attachment'] = $uploadError;
        }
        $_SESSION['leave_create_draft'] = [
            'user_id' => $currentUserId,
            'leave_id' => $leaveId,
            'values' => $values,
            'confirmed' => false,
            'errors' => $errors,
        ];
    }

    $hasChanges = hasLeaveFormChanges($values, $attachment, $initialForm);
    if ($hasChanges || $uploadError !== '' || $hasInvalidValues) {
        $_SESSION['leave_create_exit'] = $exitDestination;
        $showExitPopup = true;
        if ($exitDestination === 'logout') {
            $exitMessage = MSG_CONFIRM_LOGOUT_WITH_CHANGES;
        }
        return;
    }
    if ($exitDestination === 'logout') {
        $_SESSION['leave_create_exit'] = $exitDestination;
        $showExitPopup = true;
        $exitMessage = MSG_CONFIRM_LOGOUT;
        return;
    }
    clearLeaveDraft();
    header('Location: ../views/' . $exitDestination, true, 303);
    exit;
}

if ($action === 'confirm_exit' || $action === 'cancel_exit') {
    $isConfirmation = !empty($draft['confirmed']);
    if (!isset($_SESSION['leave_create_exit'])
        || ($action === 'confirm_exit' && $_SESSION['leave_create_exit'] === 'logout')) {
        redirectToError(400);
    }
    $exitDestination = $_SESSION['leave_create_exit'];
    unset($_SESSION['leave_create_exit']);
    if ($action === 'cancel_exit') {
        return;
    }
    clearLeaveDraft();
    header('Location: ../views/' . $exitDestination, true, 303);
    exit;
}

unset($_SESSION['leave_create_exit']);

if ($action === 'clear') {
    clearLeaveDraft();
    $_SESSION['leave_create_initial'] = $initialForm;
    $values = $defaultValues;
    $attachment = null;
    $errors = [];

    $_SESSION['leave_create_draft'] = [
        'user_id' => $currentUserId,
        'leave_id' => $leaveId,
        'values' => $values,
        'confirmed' => false,
    ];
    if ($leaveId > 0) {

        return;
    }
    header('Location: ../views/leave_create.php', true, 303);
    exit;
}

if ($action === 'back') {
    resetLeaveConfirmation();
    return;
}

if ($action === 'validate') {
    unset($_SESSION['leave_create_save_pending']);
    $form = readLeaveValues($_POST);
    $values = $form['values'];
    $values['title'] = trim($values['title']);
    $errors = $form['errors'];

    $fieldErrors = validateLeaveFields($values, $approvers, $leaveTypes, $minimumDate);
    foreach ($fieldErrors as $field => $message) {
        if (!isset($errors[$field])) {
            $errors[$field] = $message;
        }
    }

    $upload = readLeaveUpload($_FILES, $attachment, $currentUserId, $maxAttachmentSize);
    $attachment = $upload['attachment'];
    if ($attachment) {
        $_SESSION['leave_create_attachment'] = $attachment;
    } else {
        unset($_SESSION['leave_create_attachment']);
    }
    if ($upload['error'] !== '') {
        $errors['attachment'] = $upload['error'];
    }

    if (!$errors) {
        $isConfirmation = true;
    }
    $_SESSION['leave_create_draft'] = [
        'user_id' => $currentUserId,
        'leave_id' => $leaveId,
        'values' => $values,
        'confirmed' => $isConfirmation,
        'errors' => $errors,
    ];
    return;
}

if ($action !== 'confirm_save' && $action !== 'cancel_save' && $action !== 'save') {
    redirectToError(400);
}

if (!$draft || empty($draft['confirmed'])) {
    redirectToError(400);
}

if ($action === 'cancel_save') {
    $isConfirmation = true;
    unset($_SESSION['leave_create_save_pending']);
    return;
}

if ($action === 'confirm_save') {
    $isConfirmation = true;
    $_SESSION['leave_create_save_pending'] = true;
    $showSavePopup = true;
    return;
}

if (empty($_SESSION['leave_create_save_pending'])) {
    redirectToError(400);
}

$errors = saveLeave($db, $currentUserId, $values, $attachment, $leaveTypes, $maxAttachmentSize, $leaveId);
if ($errors) {
    resetLeaveConfirmation();
    return;
}

clearLeaveDraft();
header('Location: ../views/leave_list.php', true, 303);
exit;
