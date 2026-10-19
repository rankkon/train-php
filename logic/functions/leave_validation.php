<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/helpers.php';

// Kiểm tra các trường và thứ tự ngày của đơn.
function validateLeaveFields($values, $approvers, $leaveTypes, $minimumDate) {
    $messages = [
        'title' => validateLeaveTitle($values['title']),
        'content' => validateLeaveContent($values['content']),
        'approver_id' => validateLeaveApprover($values['approver_id'], $approvers),
        'leave_type' => validateLeaveType($values['leave_type'], $leaveTypes),
        'start_date' => validateLeaveDate($values['start_date'], 'Ngày bắt đầu', $minimumDate),
        'end_date' => validateLeaveDate($values['end_date'], 'Ngày kết thúc', $minimumDate),
    ];
    $errors = [];
    foreach ($messages as $field => $message) {
        if ($message !== '') {
            $errors[$field] = $message;
        }
    }
    if (!isset($errors['start_date']) && !isset($errors['end_date'])
        && $values['start_date'] > $values['end_date']) {
        $errors['end_date'] = MSG_END_DATE_BEFORE_START;
    }
    return $errors;
}

// Kiểm tra tiêu đề bắt buộc và giới hạn ký tự.
function validateLeaveTitle($title) {

    if (!mb_check_encoding($title, 'UTF-8')) {
        return MSG_LEAVE_TITLE_ENCODING;
    }
    if (!preg_match('/[^\s\p{Z}]/u', $title)) {
        return MSG_LEAVE_TITLE_REQUIRED;
    }
    if (mb_strlen($title, 'UTF-8') > LEAVE_TITLE_MAX_LENGTH) {
        return sprintf(MSG_LEAVE_TITLE_TOO_LONG, LEAVE_TITLE_MAX_LENGTH);
    }
    return '';
}

// Kiểm tra định dạng và dung lượng nội dung.
function validateLeaveContent($content) {
    if (!mb_check_encoding($content, 'UTF-8')) {
        return MSG_LEAVE_CONTENT_ENCODING;
    }
    if (strlen($content) > LEAVE_CONTENT_MAX_BYTES) {
        return MSG_LEAVE_CONTENT_TOO_LONG;
    }
    return '';
}

// Kiểm tra lý do hủy bắt buộc và giới hạn dung lượng.
function validateLeaveCancelReason($reason) {
    if (!mb_check_encoding($reason, 'UTF-8')) {
        return MSG_CANCEL_REASON_ENCODING;
    }

    if (!preg_match('/[^\s\p{Z}]/u', $reason)) {
        return MSG_CANCEL_REASON_REQUIRED;
    }
    if (strlen($reason) > LEAVE_CANCEL_REASON_MAX_BYTES) {
        return MSG_CANCEL_REASON_TOO_LONG;
    }
    return '';
}

// Kiểm tra người duyệt thuộc danh sách được chọn.
function validateLeaveApprover($value, $approvers) {
    if (!$approvers) {
        return MSG_APPROVER_UNAVAILABLE;
    }

    $approverId = filter_var($value, FILTER_VALIDATE_INT);
    if ($approverId === false || $approverId < 1) {
        return MSG_APPROVER_REQUIRED;
    }
    foreach ($approvers as $approver) {
        if ((int) $approver['id'] === $approverId) {
            return '';
        }
    }
    return MSG_APPROVER_REQUIRED;
}

// Kiểm tra mã loại đơn hợp lệ.
function validateLeaveType($value, $leaveTypes) {
    foreach ($leaveTypes as $code => $name) {
        if ($value === (string) $code) {
            return '';
        }
    }
    return MSG_LEAVE_TYPE_REQUIRED;
}

// Kiểm tra ngày hợp lệ và ngày tối thiểu.
function validateLeaveDate($value, $label, $minimumDate) {
    if ($value === '') {
        return sprintf(MSG_DATE_REQUIRED, mb_strtolower($label, 'UTF-8'));
    }
    if (!isValidDate($value)) {
        return sprintf(MSG_DATE_INVALID, $label);
    }
    if ($minimumDate !== '' && $value < $minimumDate) {
        return sprintf(MSG_DATE_IN_PAST, $label);
    }
    return '';
}

// Kiểm tra thông tin file PHP nhận từ form.
function validateLeaveUpload($file, $maxSize) {
    if (!is_array($file) || !isset($file['name'], $file['error'], $file['tmp_name'], $file['size'])
        || !is_string($file['name']) || !is_string($file['tmp_name'])
        || !is_int($file['error']) || !is_int($file['size'])) {
        return MSG_ATTACHMENT_SINGLE_ONLY;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || $file['size'] > $maxSize) {
        return sprintf(MSG_ATTACHMENT_TOO_LARGE, ATTACHMENT_MAX_MB);
    }
    if ($file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        return MSG_ATTACHMENT_UPLOAD_FAILED;
    }
    if ($file['error'] === UPLOAD_ERR_OK && !is_uploaded_file($file['tmp_name'])) {
        return MSG_ATTACHMENT_UPLOAD_INVALID;
    }
    return '';
}

// Kiểm tra tên, dung lượng và nội dung thật của file.
function validateLeaveAttachment($name, $data, $maxSize) {

    if ($name === '' || !mb_check_encoding($name, 'UTF-8')
        || mb_strlen($name, 'UTF-8') > ATTACHMENT_NAME_MAX_LENGTH || preg_match('/[\x00-\x1F\x7F]/', $name)) {
        return sprintf(MSG_ATTACHMENT_NAME_INVALID, ATTACHMENT_NAME_MAX_LENGTH);
    }
    if ($data === '') {
        return MSG_ATTACHMENT_EMPTY;
    }
    if (strlen($data) > $maxSize) {
        return sprintf(MSG_ATTACHMENT_TOO_LARGE, ATTACHMENT_MAX_MB);
    }

    $allowedTypes = ATTACHMENT_ALLOWED_TYPES;
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!isset($allowedTypes[$extension])) {
        return MSG_ATTACHMENT_TYPE_INVALID;
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->buffer($data);
    if (!in_array($mime, $allowedTypes[$extension], true)) {
        return MSG_ATTACHMENT_CONTENT_INVALID;
    }
    if ($extension !== 'pdf' && @getimagesizefromstring($data) === false) {
        return MSG_ATTACHMENT_IMAGE_INVALID;
    }
    if ($extension === 'pdf' && substr($data, 0, 5) !== '%PDF-') {
        return MSG_ATTACHMENT_PDF_INVALID;
    }

    return '';
}
