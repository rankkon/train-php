<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($requestMethod, ['GET', 'HEAD', 'POST'], true)) {
    header('Allow: GET, HEAD, POST');
    http_response_code(405);
    exit('Phương thức không được hỗ trợ.');
}

require_once dirname(__DIR__) . '/index.php';
require_once __DIR__ . '/functions/queries.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/catalogs.php';
require_once __DIR__ . '/functions/leave_validation.php';
require_once __DIR__ . '/functions/leave_save.php';

$currentUserId = (int) $_SESSION['user_id'];
$currentUser = findUserById($db, $currentUserId);

if (!$currentUser || (int) $currentUser['status'] !== 1) {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php');
    exit;
}

$approvers = findApprovers($db, $currentUser);

$today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Bangkok')))->format('Y-m-d');
$maxAttachmentSize = 2 * 1024 * 1024;
$values = ['title' => '', 'content' => '', 'approver_id' => '', 'leave_type' => '', 'start_date' => '', 'end_date' => ''];
$errors = [];
$isConfirmation = false;
$showSavePopup = false;

// Giữ dữ liệu ở server để Quay lại không cần gửi lại các ô đã khóa.
$draft = $_SESSION['leave_create_draft'] ?? null;
if ($draft && (int) $draft['user_id'] === $currentUserId) {
    $values = $draft['values'];
} else {
    unset($_SESSION['leave_create_draft']);
    $draft = null;
}
$saveToken = $_SESSION['leave_create_save_token'] ?? '';

if (!isset($_SESSION['leave_create_csrf'])) {
    $_SESSION['leave_create_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['leave_create_csrf'];

// Giữ tạm nội dung tệp trong session; không lưu đường dẫn do trình duyệt gửi lên.
$attachment = $_SESSION['leave_create_attachment'] ?? null;
if ($attachment && (int) $attachment['user_id'] !== $currentUserId) {
    unset($_SESSION['leave_create_attachment']);
    $attachment = null;
}

if ($requestMethod === 'POST') {
    $csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $action = $_POST['action'] ?? 'validate';
    if (!is_string($action)) {
        $action = '';
    }

    if (!$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(400);
        $errors['form'] = 'Không đọc được dữ liệu gửi lên. Vui lòng kiểm tra tệp đính kèm, tối đa 2 MB.';
    } elseif (!hash_equals($_SESSION['leave_create_csrf'], $csrf)) {
        http_response_code(403);
        $errors['form'] = 'Phiên gửi form không hợp lệ. Vui lòng thử lại.';
    } elseif ($action === 'clear') {
        unset($_SESSION['leave_create_draft'], $_SESSION['leave_create_attachment'],
            $_SESSION['leave_create_save_token'], $_SESSION['leave_create_save_pending']);
        header('Location: ../views/leave_create.php', true, 303);
        exit;
    } elseif ($action === 'back') {
        unset($_SESSION['leave_create_save_token'], $_SESSION['leave_create_save_pending']);
        if ($draft) {
            $_SESSION['leave_create_draft']['confirmed'] = false;
        }
    } elseif (in_array($action, ['confirm_save', 'cancel_save', 'save'], true)) {
        $postedSaveToken = isset($_POST['save_token']) && is_string($_POST['save_token']) ? $_POST['save_token'] : '';
        if (!$draft || empty($draft['confirmed']) || $saveToken === '' || !hash_equals($saveToken, $postedSaveToken)) {
            http_response_code(400);
            $errors['form'] = 'Phiên xác nhận không còn hợp lệ. Vui lòng nhấn Tiếp theo để kiểm tra lại đơn.';
        } else {
            // Chỉ sử dụng bản đã kiểm tra trong session, bỏ qua các ô gửi thêm từ trình duyệt.
            $errors = validateLeaveFields($values, $approvers, $leaveTypes, $today);
            $fileError = $attachment
                ? validateLeaveAttachment($attachment['name'], $attachment['data'], $maxAttachmentSize)
                : 'Vui lòng chọn lại tệp đính kèm.';
            if ($fileError !== '') {
                $errors['attachment'] = $fileError;
            }

            if (!$errors) {
                $isConfirmation = true;
                if ($action === 'confirm_save') {
                    $_SESSION['leave_create_save_pending'] = $saveToken;
                    $showSavePopup = true;
                } elseif ($action === 'cancel_save') {
                    unset($_SESSION['leave_create_save_pending']);
                } elseif (!hash_equals($_SESSION['leave_create_save_pending'] ?? '', $saveToken)) {
                    http_response_code(400);
                    $errors['form'] = 'Vui lòng nhấn Lưu lại và xác nhận trước khi tạo đơn.';
                } else {
                    $errors = saveLeave($db, $currentUserId, $values, $attachment, $leaveTypes, $today, $maxAttachmentSize);
                    if (!$errors) {
                        unset($_SESSION['leave_create_draft'], $_SESSION['leave_create_attachment'],
                            $_SESSION['leave_create_save_token'], $_SESSION['leave_create_save_pending'],
                            $_SESSION['leave_create_csrf']);
                        header('Location: ../views/leave_list.php', true, 303);
                        exit;
                    }
                }
            }
        }

        if ($errors) {
            $isConfirmation = false;
            unset($_SESSION['leave_create_save_token'], $_SESSION['leave_create_save_pending']);
            if ($draft) {
                $_SESSION['leave_create_draft']['confirmed'] = false;
            }
        }
    } elseif ($action !== 'validate') {
        http_response_code(400);
        $errors['form'] = 'Thao tác không hợp lệ.';
    } elseif ($action === 'validate') {
        unset($_SESSION['leave_create_save_token'], $_SESSION['leave_create_save_pending']);
        foreach ($values as $field => $defaultValue) {
            $values[$field] = '';
            if (isset($_POST[$field])) {
                if (is_string($_POST[$field])) {
                    $values[$field] = $_POST[$field];
                } else {
                    $errors[$field] = 'Giá trị gửi lên không hợp lệ.';
                }
            }
        }
        $values['title'] = trim($values['title']);
        $errors = array_merge(validateLeaveFields($values, $approvers, $leaveTypes, $today), $errors);

        $file = $_FILES['attachment'] ?? null;
        if (count($_FILES) > 1) {
            $errors['attachment'] = 'Chỉ được chọn một tệp đính kèm.';
        } elseif ($file !== null) {
            if (!is_array($file) || !isset($file['name'], $file['error'], $file['tmp_name'], $file['size'])
                || !is_string($file['name']) || !is_string($file['tmp_name'])
                || !is_int($file['error']) || !is_int($file['size'])) {
                $errors['attachment'] = 'Chỉ được chọn một tệp đính kèm.';
            } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
                || $file['size'] > $maxAttachmentSize) {
                $errors['attachment'] = 'Tệp đính kèm không được quá 2 MB.';
            } elseif ($file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors['attachment'] = 'Tải tệp không thành công. Vui lòng chọn lại tệp.';
            } elseif ($file['error'] === UPLOAD_ERR_OK) {
                if (!is_uploaded_file($file['tmp_name'])) {
                    $errors['attachment'] = 'Tệp tải lên không hợp lệ.';
                } else {
                    $name = basename(str_replace('\\', '/', $file['name']));
                    $data = file_get_contents($file['tmp_name'], false, null, 0, $maxAttachmentSize + 1);
                    $fileError = $data === false ? 'Không đọc được tệp đính kèm.' : validateLeaveAttachment($name, $data, $maxAttachmentSize);
                    if ($fileError !== '') {
                        $errors['attachment'] = $fileError;
                    } else {
                        $attachment = ['user_id' => $currentUserId, 'name' => $name, 'data' => $data];
                        $_SESSION['leave_create_attachment'] = $attachment;
                    }
                }
            }
        }

        if (!$attachment && !isset($errors['attachment'])) {
            $errors['attachment'] = 'Vui lòng chọn một tệp ảnh hoặc PDF.';
        }
        if ($attachment && !isset($errors['attachment'])) {
            $fileError = validateLeaveAttachment($attachment['name'], $attachment['data'], $maxAttachmentSize);
            if ($fileError !== '') {
                $errors['attachment'] = $fileError;
                unset($_SESSION['leave_create_attachment']);
                $attachment = null;
            }
        }

        $isConfirmation = !$errors;
        $_SESSION['leave_create_draft'] = ['user_id' => $currentUserId, 'values' => $values, 'confirmed' => $isConfirmation];
        if ($isConfirmation) {
            $saveToken = bin2hex(random_bytes(32));
            $_SESSION['leave_create_save_token'] = $saveToken;
        }
    }
}
