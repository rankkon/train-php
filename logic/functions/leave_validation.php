<?php
function validateLeaveFields($values, $approvers, $leaveTypes, $today) {
    $errors = [];

    if (!mb_check_encoding($values['title'], 'UTF-8')) {
        $errors['title'] = 'Tiêu đề phải là văn bản hợp lệ.';
    } elseif (!preg_match('/[^\s\p{Z}]/u', $values['title'])) {
        $errors['title'] = 'Vui lòng nhập tiêu đề.';
    } elseif (mb_strlen($values['title'], 'UTF-8') > 255) {
        $errors['title'] = 'Tiêu đề không được quá 255 ký tự.';
    }

    if (!mb_check_encoding($values['content'], 'UTF-8')) {
        $errors['content'] = 'Nội dung phải là văn bản hợp lệ.';
    } elseif (strlen($values['content']) > 65535) {
        $errors['content'] = 'Nội dung vượt giới hạn lưu trữ.';
    }

    $approverId = filter_var($values['approver_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $validApprover = false;
    foreach ($approvers as $approver) {
        if ($approverId !== false && (int) $approver['id'] === $approverId) {
            $validApprover = true;
        }
    }
    if (!$validApprover) {
        $errors['approver_id'] = $approvers
            ? 'Vui lòng chọn người duyệt đang hoạt động.'
            : 'Chưa có người duyệt đang hoạt động phù hợp với tài khoản của bạn.';
    }

    if (!in_array($values['leave_type'], array_map('strval', array_keys($leaveTypes)), true)) {
        $errors['leave_type'] = 'Vui lòng chọn loại đơn.';
    }

    foreach (['start_date' => 'Ngày bắt đầu', 'end_date' => 'Ngày kết thúc'] as $field => $label) {
        if ($values[$field] === '') {
            $errors[$field] = 'Vui lòng chọn ' . mb_strtolower($label, 'UTF-8') . '.';
        } elseif (!isValidDate($values[$field])) {
            $errors[$field] = $label . ' không hợp lệ. Định dạng yêu cầu: YYYY-MM-DD.';
        } elseif ($values[$field] < $today) {
            $errors[$field] = $label . ' không hợp lệ. Không thể chọn ngày trong quá khứ.';
        }
    }

    if (!isset($errors['start_date']) && !isset($errors['end_date'])
        && $values['start_date'] > $values['end_date']) {
        $errors['end_date'] = 'Ngày kết thúc phải cùng hoặc sau ngày bắt đầu.';
    }

    return $errors;
}

function validateLeaveAttachment($name, $data, $maxSize) {
    if ($name === '' || !mb_check_encoding($name, 'UTF-8')
        || mb_strlen($name, 'UTF-8') > 255 || preg_match('/[\x00-\x1F\x7F]/', $name)) {
        return 'Tên tệp không hợp lệ hoặc dài quá 255 ký tự.';
    }
    if ($data === '') {
        return 'Tệp đính kèm không được rỗng.';
    }
    if (strlen($data) > $maxSize) {
        return 'Tệp đính kèm không được quá 2 MB.';
    }

    $allowedTypes = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'bmp' => ['image/bmp', 'image/x-ms-bmp'],
        'pdf' => ['application/pdf'],
    ];
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!isset($allowedTypes[$extension])) {
        return 'Chỉ chấp nhận JPG/JPEG, PNG, GIF, WebP, BMP hoặc PDF.';
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->buffer($data);
    if (!in_array($mime, $allowedTypes[$extension], true)) {
        return 'Nội dung tệp không đúng với định dạng ảnh hoặc PDF đã chọn.';
    }
    if ($extension !== 'pdf' && @getimagesizefromstring($data) === false) {
        return 'Tệp ảnh bị lỗi hoặc không hợp lệ.';
    }
    if ($extension === 'pdf' && strncmp($data, '%PDF-', 5) !== 0) {
        return 'Tệp PDF không hợp lệ.';
    }

    return '';
}
