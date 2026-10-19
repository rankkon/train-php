<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/leave_validation.php';
require_once __DIR__ . '/leave_form.php';

// Xóa file cũ sau commit nếu không còn đơn dùng.
function removeOldLeaveAttachment($db, $path) {
    try {
        if (countAttachmentUsage($db, $path) > 0) {
            return;
        }
        $fullPath = leaveAttachmentFullPath($path);
        if ($fullPath && !@unlink($fullPath)) {
            error_log(MSG_OLD_ATTACHMENT_DELETE_FAILED);
        }
    } catch (Throwable $error) {

        error_log(MSG_OLD_ATTACHMENT_DELETE_FAILED);
    }
}

// Thêm hoặc sửa đơn cùng file trong transaction.
function saveLeave($db, $userId, $values, $attachment, $leaveTypes, $maxAttachmentSize, $leaveId = 0) {
    $fullPath = null;
    $leave = null;
    $ownerId = $userId;

    try {

        $db->begin_transaction();
        if ($leaveId > 0) {
            $leave = findLeaveById($db, $leaveId, true);
            if (!$leave) {
                $db->rollback();
                return ['form' => MSG_LEAVE_NOT_FOUND];
            }
            $ownerId = (int) $leave['user_id'];
        }

        $users = lockLeaveUsers($db, $userId, $ownerId, (int) $values['approver_id']);
        $currentUser = null;
        foreach ($users as $user) {
            if ((int) $user['id'] === $userId) {
                $currentUser = $user;
            }
        }
        if (!$currentUser || (int) $currentUser['status'] !== USER_STATUS_ACTIVE) {
            $db->rollback();
            return ['form' => MSG_ACCOUNT_INACTIVE];
        }
        if ($leave && !canEditLeave($leave, $currentUser)) {
            $db->rollback();
            return ['form' => MSG_LEAVE_EDIT_FORBIDDEN];
        }

        date_default_timezone_set(APP_TIMEZONE);
        $minimumDate = '';
        if ($leaveId === 0) {
            $minimumDate = date(APP_DATE_FORMAT);
        }
        $approvers = findApprovers($db, $currentUser);
        $errors = validateLeaveFields($values, $approvers, $leaveTypes, $minimumDate);
        if (!$attachment || (int) $attachment['user_id'] !== $userId) {
            $fileError = MSG_ATTACHMENT_SELECT_AGAIN;
        } else {
            $fileError = validateLeaveAttachment($attachment['name'], $attachment['data'], $maxAttachmentSize);
        }
        if ($fileError !== '') {
            $errors['attachment'] = $fileError;
        }
        if ($errors) {
            $db->rollback();
            return $errors;
        }

        $reuseAttachment = false;
        if ($leave && isset($attachment['path']) && $attachment['path'] === $leave['attachment_path']) {
            $savedAttachment = readSavedLeaveAttachment($leave, $userId);
            $reuseAttachment = $savedAttachment
                && $savedAttachment['name'] === $attachment['name']
                && $savedAttachment['data'] === $attachment['data'];
        }
        if ($reuseAttachment) {
            $attachmentPath = $leave['attachment_path'];
        } else {
            $projectRoot = dirname(__DIR__, 2);
            if (!is_dir($projectRoot . '/uploads')) {
                throw new RuntimeException(MSG_UPLOAD_DIRECTORY_MISSING);
            }

            $extension = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
            $attachmentPath = 'uploads/' . bin2hex(random_bytes(ATTACHMENT_RANDOM_BYTES)) . '.' . $extension;
            $fullPath = $projectRoot . '/' . $attachmentPath;
            $writtenBytes = @file_put_contents($fullPath, $attachment['data'], LOCK_EX);
            if ($writtenBytes !== strlen($attachment['data'])) {
                throw new RuntimeException(MSG_ATTACHMENT_WRITE_FAILED);
            }
        }

        if ($leaveId > 0) {
            updateLeave($db, $leaveId, $values, $attachment['name'], $attachmentPath);
        } else {
            $createdAt = date(APP_DATETIME_FORMAT);
            insertLeave($db, $userId, $values, $createdAt, $attachment['name'], $attachmentPath);
        }
        $db->commit();
    } catch (Throwable $error) {

        try {
            $db->rollback();
        } catch (Throwable $rollbackError) {
            error_log(MSG_ROLLBACK_FAILED);
        }
        if ($fullPath !== null && is_file($fullPath) && !@unlink($fullPath)) {
            error_log(MSG_FAILED_ATTACHMENT_DELETE);
        }
        error_log(sprintf(MSG_SAVE_FAILED_LOG, $error->getMessage()));
        return ['form' => MSG_SAVE_FAILED];
    }

    if ($leave && $leave['attachment_path'] !== $attachmentPath) {
        removeOldLeaveAttachment($db, $leave['attachment_path']);
    }
    return [];
}
