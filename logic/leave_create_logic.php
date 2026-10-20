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
$formAction = 'leave_create.php';
if (isset($_GET['id'])) {
    $leaveId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($leaveId === false || $leaveId < 1) {
        http_response_code(400);
        exit(MSG_INVALID_FORM_VALUE);
    }
    $leave = findLeaveById($db, $leaveId);
    if (!$leave) {
        http_response_code(404);
        exit(MSG_LEAVE_NOT_FOUND);
    }
    if (!canEditLeave($leave, $currentUser)) {
        http_response_code(403);
        exit(MSG_LEAVE_EDIT_FORBIDDEN);
    }
    $formTitle = 'Sửa đơn';
    $formAction .= '?id=' . $leaveId;
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

if ($requestMethod === 'GET') {
    clearLeaveDraft();
}

$draft = null;
if (isset($_SESSION['leave_create_draft'])) {
    $draft = $_SESSION['leave_create_draft'];
}
if ($draft && (int) $draft['user_id'] === $currentUserId
    && isset($draft['leave_id']) && (int) $draft['leave_id'] === $leaveId) {
    $values = $draft['values'];
} else {
    clearLeaveDraft();
    $draft = null;
    if ($leave) {
        foreach ($values as $field => $value) {
            $values[$field] = (string) $leave[$field];
        }
        $savedAttachment = readSavedLeaveAttachment($leave, $currentUserId);

        if ($savedAttachment && validateLeaveAttachment(
            $savedAttachment['name'], $savedAttachment['data'], $maxAttachmentSize
        ) === '') {
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

if ($action === 'clear') {
    clearLeaveDraft();
    $values = $defaultValues;
    $attachment = null;

    $_SESSION['leave_create_draft'] = [
        'user_id' => $currentUserId,
        'leave_id' => $leaveId,
        'values' => $values,
        'confirmed' => false,
    ];
    if ($leaveId > 0) {

        return;
    }
    header('Location: ../views/' . $formAction, true, 303);
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
    ];
    return;
}

if ($action !== 'confirm_save' && $action !== 'cancel_save' && $action !== 'save') {
    http_response_code(400);
    $errors['form'] = MSG_INVALID_ACTION;
    return;
}

if (!$draft || empty($draft['confirmed'])) {
    http_response_code(400);
    $errors['form'] = MSG_CONFIRMATION_INVALID;
    resetLeaveConfirmation();
    return;
}

if ($action === 'cancel_save') {
    $isConfirmation = true;
    unset($_SESSION['leave_create_save_pending']);
    return;
}

if ($action === 'confirm_save') {
    $messages = [
        'approver_id' => validateLeaveApprover($values['approver_id'], $approvers),
        'start_date' => validateLeaveDate($values['start_date'], 'Ngày bắt đầu', $minimumDate),
        'end_date' => validateLeaveDate($values['end_date'], 'Ngày kết thúc', $minimumDate),
    ];
    foreach ($messages as $field => $message) {
        if ($message !== '') {
            $errors[$field] = $message;
        }
    }
    if ($errors) {
        resetLeaveConfirmation();
        return;
    }

    $isConfirmation = true;
    $_SESSION['leave_create_save_pending'] = true;
    $showSavePopup = true;
    return;
}

if (empty($_SESSION['leave_create_save_pending'])) {
    http_response_code(400);
    $errors['form'] = MSG_SAVE_CONFIRMATION_REQUIRED;
    resetLeaveConfirmation();
    return;
}

$errors = saveLeave($db, $currentUserId, $values, $attachment, $leaveTypes, $maxAttachmentSize, $leaveId);
if ($errors) {
    resetLeaveConfirmation();
    return;
}

clearLeaveDraft();
header('Location: ../views/leave_list.php', true, 303);
exit;
