<?php
require_once __DIR__ . '/functions/const.php';
require_once __DIR__ . '/functions/message.php';

// Form thêm và sửa dùng chung các bước nhập, xác nhận và lưu.
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

// Đọc lại quyền, phòng ban và danh sách người duyệt từ database.
$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);

if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

// Có ID trên URL thì sửa đơn đã có; kiểm tra quyền ngay trên server.
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
$today = date(APP_DATE_FORMAT);
$minimumDate = $today;
if ($leaveId > 0) {
    $minimumDate = '';
}
$maxAttachmentSize = ATTACHMENT_MAX_BYTES;
$attachmentAccept = attachmentAccept();
$values = emptyLeaveValues();
$errors = [];
$isConfirmation = false;
$showSavePopup = false;

// Mở sửa bằng GET lấy dữ liệu từ database; các bước POST vẫn tiếp tục dùng bản nháp.
if ($leaveId > 0 && $requestMethod === 'GET') {
    clearLeaveDraft();
}

// Bản nháp phải khớp cả người thao tác và ID đơn, tránh lưu nhầm khi đổi trang.
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
        if ($savedAttachment) {
            $_SESSION['leave_create_attachment'] = $savedAttachment;
        }
    }
}
$saveToken = '';
if (isset($_SESSION['leave_create_save_token'])) {
    $saveToken = $_SESSION['leave_create_save_token'];
}

// Mã CSRF giúp kiểm tra form có được gửi từ phiên làm việc hiện tại hay không.
if (!isset($_SESSION['leave_create_csrf'])) {
    $_SESSION['leave_create_csrf'] = bin2hex(random_bytes(FORM_TOKEN_BYTES));
}
$csrfToken = $_SESSION['leave_create_csrf'];

// File đã kiểm tra được giữ trong session để không phải chọn lại khi Quay lại.
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

$csrf = '';
if (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
    $csrf = $_POST['csrf_token'];
}
$action = 'validate';
if (isset($_POST['action'])) {
    if (is_string($_POST['action'])) {
        $action = $_POST['action'];
    } else {
        $action = '';
    }
}

// PHP có thể bỏ toàn bộ POST và FILES khi request vượt giới hạn post_max_size.
$contentLength = 0;
if (isset($_SERVER['CONTENT_LENGTH'])) {
    $contentLength = (int) $_SERVER['CONTENT_LENGTH'];
}
if (!$_POST && !$_FILES && $contentLength > 0) {
    http_response_code(400);
    $errors['form'] = sprintf(MSG_POST_DATA_UNREADABLE, ATTACHMENT_MAX_MB);
    return;
}
if (!hash_equals($csrfToken, $csrf)) {
    http_response_code(403);
    $errors['form'] = MSG_CSRF_INVALID;
    return;
}

// Xóa trống: bỏ cả bản nháp, file tạm và trạng thái xác nhận.
if ($action === 'clear') {
    clearLeaveDraft();
    $values = emptyLeaveValues();
    $attachment = null;
    // Giữ nháp rỗng để các POST tiếp theo không tự nạp lại dữ liệu và file cũ.
    $_SESSION['leave_create_draft'] = [
        'user_id' => $currentUserId,
        'leave_id' => $leaveId,
        'values' => $values,
        'confirmed' => false,
    ];
    if ($leaveId > 0) {
        // Hiện form sửa trống ngay; redirect GET sẽ nạp lại dữ liệu gốc.
        return;
    }
    header('Location: ../views/' . $formAction, true, 303);
    exit;
}

// Quay lại: giữ dữ liệu và file, chỉ bỏ bước xác nhận để sửa tiếp.
if ($action === 'back') {
    resetLeaveConfirmation();
    return;
}

// Tiếp theo: đọc ô nhập và file, kiểm tra rồi giữ bản nháp trong session.
if ($action === 'validate') {
    unset($_SESSION['leave_create_save_token'], $_SESSION['leave_create_save_pending']);
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
        $saveToken = bin2hex(random_bytes(FORM_TOKEN_BYTES));
        $_SESSION['leave_create_save_token'] = $saveToken;
    }
    $_SESSION['leave_create_draft'] = [
        'user_id' => $currentUserId,
        'leave_id' => $leaveId,
        'values' => $values,
        'confirmed' => $isConfirmation,
    ];
    return;
}

// Từ đây chỉ còn ba thao tác ở màn hình xác nhận: hiện popup, hủy popup hoặc lưu dữ liệu.
if ($action !== 'confirm_save' && $action !== 'cancel_save' && $action !== 'save') {
    http_response_code(400);
    $errors['form'] = MSG_INVALID_ACTION;
    return;
}

// Kiểm tra mã xác nhận trước khi dùng dữ liệu bản nháp trong session.
$postedSaveToken = '';
if (isset($_POST['save_token']) && is_string($_POST['save_token'])) {
    $postedSaveToken = $_POST['save_token'];
}
$confirmationError = validateLeaveConfirmation($draft, $saveToken, $postedSaveToken);
if ($confirmationError !== '') {
    http_response_code(400);
    $errors['form'] = $confirmationError;
    resetLeaveConfirmation();
    return;
}

// Đóng popup chỉ kiểm tra phiên và token ở trên, không yêu cầu dữ liệu còn đủ điều kiện lưu.
if ($action === 'cancel_save') {
    $isConfirmation = true;
    unset($_SESSION['leave_create_save_pending']);
    return;
}

// Lưu lại: kiểm tra ngày và người duyệt vì có thể thay đổi từ lần Tiếp theo.
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
    $_SESSION['leave_create_save_pending'] = $saveToken;
    $showSavePopup = true;
    return;
}

// Chỉ lưu sau khi đã mở popup bằng đúng mã của lần xác nhận này.
$pendingSaveToken = '';
if (isset($_SESSION['leave_create_save_pending'])) {
    $pendingSaveToken = $_SESSION['leave_create_save_pending'];
}
if (!hash_equals($pendingSaveToken, $saveToken)) {
    http_response_code(400);
    $errors['form'] = MSG_SAVE_CONFIRMATION_REQUIRED;
    resetLeaveConfirmation();
    return;
}

// Chỉ lấy dữ liệu từ nháp trong session; kiểm tra đầy đủ một lần trong transaction của hàm lưu.
$errors = saveLeave($db, $currentUserId, $values, $attachment, $leaveTypes, $today, $maxAttachmentSize, $leaveId);
if ($errors) {
    resetLeaveConfirmation();
    return;
}

// Lưu thành công: bỏ bản nháp rồi tải lại danh sách từ database.
clearLeaveDraft();
unset($_SESSION['leave_create_csrf']);
header('Location: ../views/leave_list.php', true, 303);
exit;
