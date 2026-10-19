<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/leave_validation.php';
require_once __DIR__ . '/leave_form.php';

// Xóa file cũ sau commit; không xóa file đang được đơn khác dùng hoặc file ngoài uploads.
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
        // Đơn đã lưu thành công nên lỗi dọn file không được làm rollback hay báo lưu thất bại.
        error_log(MSG_OLD_ATTACHMENT_DELETE_FAILED);
    }
}

// Thêm hoặc sửa đơn cùng file trong transaction.
function saveLeave($db, $userId, $values, $attachment, $leaveTypes, $today, $maxAttachmentSize, $leaveId = 0) {
    $fullPath = null;
    $leave = null;
    $ownerId = $userId;
    $minimumDate = $today;

    try {
        // Khóa đơn đang sửa trước khi đọc lại chủ đơn và kiểm tra quyền.
        $db->begin_transaction();
        if ($leaveId > 0) {
            $leave = findLeaveById($db, $leaveId, true);
            if (!$leave) {
                $db->rollback();
                return ['form' => MSG_LEAVE_NOT_FOUND];
            }
            $ownerId = (int) $leave['user_id'];
            $minimumDate = '';
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

        // Dùng lại file cũ nếu tên, đường dẫn và nội dung không thay đổi.
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
            // Tên ngẫu nhiên tránh trùng file; tên gốc vẫn được lưu trong database.
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
            date_default_timezone_set(APP_TIMEZONE);
            $createdAt = date(APP_DATETIME_FORMAT);
            insertLeave($db, $userId, $values, $createdAt, $attachment['name'], $attachmentPath);
        }
        $db->commit();
    } catch (Throwable $error) {
        // Vẫn phải thử xóa file kể cả khi việc rollback cũng gặp lỗi.
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

    // Chỉ dọn file cũ sau khi UPDATE đã commit, giữ file cũ nguyên vẹn nếu lưu thất bại.
    if ($leave && $leave['attachment_path'] !== $attachmentPath) {
        removeOldLeaveAttachment($db, $leave['attachment_path']);
    }
    return [];
}
