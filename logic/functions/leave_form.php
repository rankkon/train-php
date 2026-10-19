<?php
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/leave_validation.php';

// Tạo các giá trị trống cho form đơn.
function emptyLeaveValues() {
    return [
        'title' => '',
        'content' => '',
        'approver_id' => '',
        'leave_type' => '',
        'start_date' => '',
        'end_date' => '',
    ];
}

// Lấy đường dẫn file hợp lệ trong uploads.
function leaveAttachmentFullPath($path) {
    $projectRoot = dirname(__DIR__, 2);
    $uploadDirectory = realpath($projectRoot . '/uploads');
    $fullPath = realpath($projectRoot . '/' . $path);
    if (!$uploadDirectory || !$fullPath || !is_file($fullPath)) {
        return null;
    }
    if (strpos($fullPath, $uploadDirectory . DIRECTORY_SEPARATOR) !== 0) {
        return null;
    }
    return $fullPath;
}

// Đọc dữ liệu file đính kèm đã lưu.
function readSavedLeaveAttachment($leave, $userId) {
    $fullPath = leaveAttachmentFullPath($leave['attachment_path']);
    if (!$fullPath) {
        return null;
    }
    $data = file_get_contents($fullPath, false, null, 0, ATTACHMENT_MAX_BYTES + 1);
    if ($data === false) {
        return null;
    }
    return [
        'user_id' => $userId,
        'name' => $leave['attachment_original_name'],
        'data' => $data,
        'path' => $leave['attachment_path'],
    ];
}

// Đọc giá trị và lỗi kiểu dữ liệu từ form.
function readLeaveValues($post) {
    $values = emptyLeaveValues();
    $errors = [];
    foreach (array_keys($values) as $field) {
        if (!isset($post[$field])) {
            continue;
        }
        if (!is_string($post[$field])) {
            $errors[$field] = MSG_INVALID_FORM_VALUE;
            continue;
        }
        $values[$field] = $post[$field];
    }
    $values['title'] = trim($values['title']);
    return ['values' => $values, 'errors' => $errors];
}

// Đọc file mới hoặc giữ file trong session.
function readLeaveUpload($files, $attachment, $userId, $maxSize) {
    $result = ['attachment' => $attachment, 'error' => ''];
    if (count($files) > 1) {
        $result['error'] = MSG_ATTACHMENT_SINGLE_ONLY;
        return $result;
    }

    $file = null;
    if (isset($files['attachment'])) {
        $file = $files['attachment'];
    }
    if ($file !== null) {
        $result['error'] = validateLeaveUpload($file, $maxSize);
        if ($result['error'] !== '') {
            return $result;
        }

        if ($file['error'] === UPLOAD_ERR_OK) {
            $name = basename(str_replace('\\', '/', $file['name']));
            $data = file_get_contents($file['tmp_name'], false, null, 0, $maxSize + 1);
            if ($data === false) {
                $result['error'] = MSG_ATTACHMENT_READ_FAILED;
                return $result;
            }
            $result['error'] = validateLeaveAttachment($name, $data, $maxSize);
            if ($result['error'] !== '') {
                return $result;
            }
            $result['attachment'] = ['user_id' => $userId, 'name' => $name, 'data' => $data];
            return $result;
        }
    }

    if (!$result['attachment']) {
        $result['error'] = MSG_ATTACHMENT_REQUIRED;
    }
    return $result;
}

// Hủy bước xác nhận và giữ bản nháp.
function resetLeaveConfirmation() {
    unset($_SESSION['leave_create_save_pending']);
    if (!empty($_SESSION['leave_create_draft'])) {
        $_SESSION['leave_create_draft']['confirmed'] = false;
    }
}

// Xóa bản nháp, file tạm và trạng thái xác nhận.
function clearLeaveDraft() {
    unset($_SESSION['leave_create_draft'], $_SESSION['leave_create_attachment'],
        $_SESSION['leave_create_save_pending']);
}
