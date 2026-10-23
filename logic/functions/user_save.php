<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/user_validation.php';

// Kiểm tra trùng tên đăng nhập và email khi từng trường chưa có lỗi.
function findUserDuplicateErrors($db, $values, $editedUserId = 0, $errors = []) {
    if ($editedUserId === 0 && !isset($errors['username'])) {
        $existingUser = findUserForLogin($db, $values['username']);
        if ($existingUser) {
            $errors['username'] = MSG_USERNAME_EXISTS;
        }
    }
    if (!isset($errors['email']) && $values['email'] !== '') {
        $existingUser = findUserByEmail($db, $values['email']);
        if ($existingUser && (int) $existingUser['id'] !== $editedUserId) {
            $errors['email'] = MSG_USER_EMAIL_EXISTS;
        }
    }
    return $errors;
}

// Thêm hoặc sửa người dùng sau khi khóa và kiểm tra quyền trong transaction.
function saveUser($db, $currentUserId, $values, $userTypes, $departments, $editedUserId = 0) {
    $result = ['errors' => [], 'options' => null];
    try {
        $db->begin_transaction();
        $currentUser = findUserById($db, $currentUserId, true);
        if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
            $db->rollback();
            $result['errors'] = ['form' => MSG_ACCOUNT_INACTIVE];
            return $result;
        }

        $editedUser = null;
        if ($editedUserId > 0) {
            $editedUser = $currentUser;
            if ($editedUserId !== $currentUserId) {
                $editedUser = findUserById($db, $editedUserId, true);
            }
            if (!$editedUser) {
                $db->rollback();
                $result['errors'] = ['form' => MSG_USER_NOT_FOUND];
                return $result;
            }
            if (!canManageUser($editedUser, $currentUser)) {
                $db->rollback();
                $result['errors'] = ['form' => MSG_USER_EDIT_FORBIDDEN];
                return $result;
            }
            $values['username'] = $editedUser['username'];
            if ((int) $editedUser['user_type'] === USER_TYPE_ADMIN) {
                $values['user_type'] = (string) USER_TYPE_ADMIN;
            }
        }

        $result['options'] = userCreateOptions($currentUser, $userTypes, $departments, $editedUser);
        $result['errors'] = validateUserFields(
            $values, $result['options']['user_types'], $result['options']['departments'], [], $editedUserId > 0
        );
        if ($result['errors']) {
            $db->rollback();
            return $result;
        }

        $result['errors'] = findUserDuplicateErrors($db, $values, $editedUserId);
        if ($result['errors']) {
            $db->rollback();
            return $result;
        }

        date_default_timezone_set(APP_TIMEZONE);
        $savedAt = date(APP_DATETIME_FORMAT);
        if ($editedUserId > 0) {
            updateUser($db, $editedUserId, $values, $savedAt);
        } else {
            $passwordHash = md5($values['password']);
            insertUser($db, $values, $passwordHash, $savedAt);
        }
        $db->commit();
        return $result;
    } catch (Throwable $error) {
        $result['errors'] = ['form' => MSG_USER_SAVE_FAILED];
        try {
            $db->rollback();
        } catch (Throwable $recoveryError) {
            return $result;
        }
        return $result;
    }
}

// Đổi trạng thái các tài khoản trong cùng một transaction sau khi kiểm tra quyền.
function changeUsersStatus($db, $currentUserId, $targetUserIds, $status) {
    try {
        $db->begin_transaction();
        $currentUser = findUserById($db, $currentUserId, true);
        if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
            $db->rollback();
            return MSG_ACCOUNT_INACTIVE;
        }

        foreach ($targetUserIds as $targetUserId) {
            $targetUser = $currentUser;
            if ($targetUserId !== $currentUserId) {
                $targetUser = findUserById($db, $targetUserId, true);
            }
            if (!$targetUser) {
                $db->rollback();
                return MSG_USER_STATUS_NOT_FOUND;
            }
            if (!canManageUser($targetUser, $currentUser)) {
                $db->rollback();
                return MSG_USER_STATUS_FORBIDDEN;
            }
            updateUserStatus($db, $targetUserId, $status);
        }

        $db->commit();
        return '';
    } catch (Throwable $error) {
        try {
            $db->rollback();
        } catch (Throwable $rollbackError) {
            return MSG_USER_STATUS_FAILED;
        }
        return MSG_USER_STATUS_FAILED;
    }
}
