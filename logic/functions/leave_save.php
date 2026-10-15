<?php
// Trả về danh sách lỗi; mảng rỗng nghĩa là đã lưu thành công.
function saveLeave($db, $userId, $values, $attachment, $leaveTypes, $today, $maxAttachmentSize) {
    $fullPath = null;

    try {
        $db->begin_transaction();

        // Khóa hai tài khoản để quyền/phòng ban không đổi trong lúc lưu.
        $users = lockLeaveCreationUsers($db, $userId, (int) $values['approver_id']);
        $currentUser = null;
        foreach ($users as $user) {
            if ((int) $user['id'] === $userId) {
                $currentUser = $user;
            }
        }
        if (!$currentUser || (int) $currentUser['status'] !== 1) {
            $db->rollback();
            return ['form' => 'Tài khoản không còn hoạt động. Vui lòng đăng nhập lại.'];
        }

        $errors = validateLeaveFields($values, findApprovers($db, $currentUser), $leaveTypes, $today);
        $fileError = !$attachment || (int) $attachment['user_id'] !== $userId
            ? 'Vui lòng chọn lại tệp đính kèm.'
            : validateLeaveAttachment($attachment['name'], $attachment['data'], $maxAttachmentSize);
        if ($fileError !== '') {
            $errors['attachment'] = $fileError;
        }
        if ($errors) {
            $db->rollback();
            return $errors;
        }

        $projectRoot = dirname(__DIR__, 2);
        $uploadDirectory = $projectRoot . '/uploads';
        if (!is_dir($uploadDirectory)) {
            throw new RuntimeException('Không tìm thấy thư mục lưu tệp.');
        }
        $extension = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
        $attachmentPath = 'uploads/' . bin2hex(random_bytes(16)) . '.' . $extension;
        $fullPath = $projectRoot . '/' . $attachmentPath;
        if (@file_put_contents($fullPath, $attachment['data'], LOCK_EX) !== strlen($attachment['data'])) {
            throw new RuntimeException('Không ghi được tệp đính kèm.');
        }

        $createdAt = (new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s');
        insertLeave($db, $userId, $values, $createdAt, $attachment['name'], $attachmentPath);
        $db->commit();
        return [];
    } catch (Throwable $error) {
        try {
            $db->rollback();
        } catch (Throwable $rollbackError) {
            error_log('Không thể rollback giao dịch tạo đơn.');
        }
        if ($fullPath !== null && is_file($fullPath)) {
            if (!@unlink($fullPath)) {
                error_log('Không thể xóa tệp của giao dịch tạo đơn thất bại.');
            }
        }
        error_log('Lưu đơn thất bại: ' . $error->getMessage());
        return ['form' => 'Không thể lưu đơn. Dữ liệu và tệp vẫn được giữ để bạn thử lại.'];
    }
}
