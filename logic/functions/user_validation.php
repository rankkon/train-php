<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/helpers.php';

// Lấy loại người dùng và phòng ban được phép chọn khi thêm tài khoản.
function userCreateOptions($currentUser, $userTypes, $departments) {
    $newUserTypes = [
        USER_TYPE_USER => $userTypes[USER_TYPE_USER],
        USER_TYPE_MANAGER => $userTypes[USER_TYPE_MANAGER],
    ];
    $newUserDepartments = $departments;
    if ((int) $currentUser['user_type'] === USER_TYPE_MANAGER) {
        $newUserTypes = [USER_TYPE_USER => $userTypes[USER_TYPE_USER]];
        $departmentCode = (int) $currentUser['department'];
        $newUserDepartments = [$departmentCode => $departments[$departmentCode]];
    }
    return ['user_types' => $newUserTypes, 'departments' => $newUserDepartments];
}

// Kiểm tra tên đăng nhập bắt buộc, định dạng và độ dài.
function validateUsername($username) {
    if ($username === '') {
        return MSG_USERNAME_REQUIRED;
    }
    if (!preg_match('/\A[A-Za-z0-9]+\z/', $username)) {
        return MSG_USERNAME_FORMAT;
    }
    if (strlen($username) > USERNAME_MAX_LENGTH) {
        return sprintf(MSG_USERNAME_TOO_LONG, USERNAME_MAX_LENGTH);
    }
    return '';
}

// Kiểm tra tên người dùng gồm chữ Unicode và khoảng trắng.
function validateUserFullName($fullName) {
    if (trim($fullName, ' ') === '') {
        return MSG_USER_FULL_NAME_REQUIRED;
    }
    if (!preg_match('/\A(?:\p{L}\p{M}*| )+\z/u', $fullName)) {
        return MSG_USER_FULL_NAME_FORMAT;
    }
    if (mb_strlen($fullName, 'UTF-8') > USER_FULL_NAME_MAX_LENGTH) {
        return sprintf(MSG_USER_FULL_NAME_TOO_LONG, USER_FULL_NAME_MAX_LENGTH);
    }
    return '';
}

// Kiểm tra mật khẩu đủ độ dài, chữ cái, số và ký tự đặc biệt.
function validateUserPassword($password) {
    if ($password === '') {
        return MSG_USER_PASSWORD_REQUIRED;
    }
    if (!mb_check_encoding($password, 'UTF-8')
        || mb_strlen($password, 'UTF-8') < USER_PASSWORD_MIN_LENGTH
        || !preg_match('/\p{L}/u', $password)
        || !preg_match('/[0-9]/', $password)
        || !preg_match('/[\p{P}\p{S}]/u', $password)) {
        return sprintf(MSG_USER_PASSWORD_FORMAT, USER_PASSWORD_MIN_LENGTH);
    }
    return '';
}

// Kiểm tra email tùy chọn đúng định dạng và độ dài.
function validateUserEmail($email) {
    if ($email === '') {
        return '';
    }
    if (strlen($email) > USER_EMAIL_MAX_LENGTH) {
        return sprintf(MSG_USER_EMAIL_TOO_LONG, USER_EMAIL_MAX_LENGTH);
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return MSG_USER_EMAIL_FORMAT;
    }
    return '';
}

// Kiểm tra ngày sinh tùy chọn đúng định dạng và tồn tại trong lịch.
function validateUserBirthDate($birthDate) {
    if ($birthDate !== '' && !isValidDate($birthDate)) {
        return sprintf(MSG_DATE_INVALID, 'Ngày sinh');
    }
    return '';
}

// Kiểm tra từng trường người dùng chưa có lỗi kiểu dữ liệu.
function validateUserFields($values, $newUserTypes, $newUserDepartments, $errors) {
    foreach ($values as $field => $value) {
        if (isset($errors[$field])) {
            continue;
        }
        $message = '';
        if ($field === 'username') {
            $message = validateUsername($value);
        } elseif ($field === 'full_name') {
            $message = validateUserFullName($value);
        } elseif ($field === 'password') {
            $message = validateUserPassword($value);
        } elseif ($field === 'email') {
            $message = validateUserEmail($value);
        } elseif ($field === 'birth_date') {
            $message = validateUserBirthDate($value);
        } elseif ($field === 'user_type' && !isset($newUserTypes[$value])) {
            $message = MSG_USER_TYPE_INVALID;
        } elseif ($field === 'department' && !isset($newUserDepartments[$value])) {
            $message = MSG_USER_DEPARTMENT_INVALID;
        }
        if ($message !== '') {
            $errors[$field] = $message;
        }
    }
    return $errors;
}
