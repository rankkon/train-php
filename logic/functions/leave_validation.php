<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/helpers.php';

// Gọi từng hàm kiểm tra ô nhập, gom lỗi và kiểm tra thứ tự hai ngày.
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

// Kiểm tra tiêu đề có chữ, đúng UTF-8 và không quá 255 ký tự tiếng Việt.
function validateLeaveTitle($title) {
    // mb_* dùng cho tiếng Việt; biểu thức bên dưới nhận biết tiêu đề chỉ có khoảng trắng.
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

// Nội dung được để trống nhưng phải đúng UTF-8 và vừa giới hạn TEXT của database.
function validateLeaveContent($content) {
    if (!mb_check_encoding($content, 'UTF-8')) {
        return MSG_LEAVE_CONTENT_ENCODING;
    }
    if (strlen($content) > LEAVE_CONTENT_MAX_BYTES) {
        return MSG_LEAVE_CONTENT_TOO_LONG;
    }
    return '';
}

// Kiểm tra mã người duyệt có nằm trong danh sách được phép chọn hay không.
function validateLeaveApprover($value, $approvers) {
    // Chỉ nhận mã số nguyên và phải có trong danh sách người duyệt được phép chọn.
    $approverId = filter_var($value, FILTER_VALIDATE_INT);
    foreach ($approvers as $approver) {
        if ($approverId !== false && $approverId > 0 && (int) $approver['id'] === $approverId) {
            return '';
        }
    }
    if (!$approvers) {
        return MSG_APPROVER_UNAVAILABLE;
    }
    return MSG_APPROVER_REQUIRED;
}

// Chỉ nhận mã loại đơn có trong danh sách cố định.
function validateLeaveType($value, $leaveTypes) {
    foreach ($leaveTypes as $code => $name) {
        if ($value === (string) $code) {
            return '';
        }
    }
    return MSG_LEAVE_TYPE_REQUIRED;
}

// Kiểm tra ngày có thật; chỉ áp dụng ngày tối thiểu khi tạo đơn mới.
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

// Kiểm tra bản nháp đã qua Tiếp theo và mã xác nhận chưa bị thay hoặc hết hiệu lực.
function validateLeaveConfirmation($draft, $saveToken, $postedSaveToken) {
    if (!$draft || empty($draft['confirmed']) || $saveToken === '' || !hash_equals($saveToken, $postedSaveToken)) {
        return MSG_CONFIRMATION_INVALID;
    }
    return '';
}

// Kiểm tra dữ liệu PHP nhận từ ô file trước khi đọc nội dung file tạm.
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

// Kiểm tra tên, dung lượng, phần mở rộng và nội dung thật của file.
function validateLeaveAttachment($name, $data, $maxSize) {
    // Không nhận ký tự điều khiển như xuống dòng hoặc ký tự rỗng trong tên file.
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

    // finfo đọc định dạng thật của file, không chỉ dựa vào đuôi tên file.
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
