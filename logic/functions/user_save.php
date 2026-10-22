<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/user_validation.php';

// Kiểm tra tên đăng nhập và email đã được sử dụng.
function findUserDuplicateErrors($db, $values) {
    $errors = [];
    if (findUserForLogin($db, $values['username'])) {
        $errors['username'] = MSG_USERNAME_EXISTS;
    }
    if ($values['email'] !== '' && findUserByEmail($db, $values['email'])) {
        $errors['email'] = MSG_USER_EMAIL_EXISTS;
    }
    return $errors;
}

// Lưu người dùng trong transaction và trả lỗi cùng lựa chọn theo tài khoản đã khóa.
function saveUser($db, $currentUserId, $values, $userTypes, $departments) {
    $result = ['errors' => [], 'options' => null];
    try {
        $db->begin_transaction();
        $currentUser = findUserById($db, $currentUserId, true);
        if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
            $db->rollback();
            $result['errors'] = ['form' => MSG_ACCOUNT_INACTIVE];
            return $result;
        }

        $result['options'] = userCreateOptions($currentUser, $userTypes, $departments);
        $result['errors'] = validateUserFields(
            $values, $result['options']['user_types'], $result['options']['departments'], []
        );
        if ($result['errors']) {
            $db->rollback();
            return $result;
        }

        $result['errors'] = findUserDuplicateErrors($db, $values);
        if ($result['errors']) {
            $db->rollback();
            return $result;
        }

        date_default_timezone_set(APP_TIMEZONE);
        $createdAt = date(APP_DATETIME_FORMAT);
        $passwordHash = md5($values['password']);
        insertUser($db, $values, $passwordHash, $createdAt);
        $db->commit();
        return $result;
    } catch (Throwable $error) {
        try {
            $db->rollback();
        } catch (Throwable $recoveryError) {
            $result['errors'] = ['form' => MSG_USER_SAVE_FAILED];
            return $result;
        }
        $result['errors'] = ['form' => MSG_USER_SAVE_FAILED];
        return $result;
    }
}
