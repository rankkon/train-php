<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/leave_validation.php';

// Duyệt hoặc hủy đơn trong transaction.
function processLeave($db, $userId, $leaveId, $action, $cancelReason) {
    try {
        $db->begin_transaction();
        $leave = findLeaveById($db, $leaveId, true);
        if (!$leave) {
            $db->rollback();
            return ['error' => MSG_LEAVE_PROCESS_NOT_FOUND, 'retry' => false];
        }

        $users = lockLeaveUsers($db, $userId, (int) $leave['user_id'], (int) $leave['approver_id']);
        $currentUser = null;
        $owner = null;
        foreach ($users as $user) {
            if ((int) $user['id'] === $userId) {
                $currentUser = $user;
            }
            if ((int) $user['id'] === (int) $leave['user_id']) {
                $owner = $user;
            }
        }
        if (!$currentUser) {
            $db->rollback();
            return ['error' => MSG_ACCOUNT_INACTIVE, 'retry' => false];
        }
        if (!$owner) {
            $db->rollback();
            return ['error' => MSG_LEAVE_PROCESS_NOT_FOUND, 'retry' => false];
        }
        $leave['owner_type'] = $owner['user_type'];
        $leave['owner_department'] = $owner['department'];
        if (!canProcessLeave($leave, $currentUser)) {
            $db->rollback();
            return ['error' => MSG_LEAVE_PROCESS_FORBIDDEN, 'retry' => false];
        }

        date_default_timezone_set(APP_TIMEZONE);
        $processedAt = date(APP_DATETIME_FORMAT);
        if ($action === 'approve') {
            $affectedRows = approveLeave($db, $leaveId, $processedAt, displayDate($processedAt));
        } else {
            $affectedRows = cancelLeave($db, $leaveId, $processedAt, $cancelReason);
        }
        if ($affectedRows !== 1) {
            $db->rollback();
            return ['error' => MSG_LEAVE_PROCESS_FORBIDDEN, 'retry' => false];
        }
        $db->commit();
        return ['error' => '', 'retry' => false];
    } catch (Throwable $error) {
        try {
            $db->rollback();
        } catch (Throwable $rollbackError) {
            error_log(MSG_ROLLBACK_FAILED);
        }
        error_log(sprintf(MSG_LEAVE_PROCESS_FAILED_LOG, $error->getMessage()));
        return ['error' => MSG_LEAVE_PROCESS_FAILED, 'retry' => true];
    }
}

// Xử lý action và chuẩn bị popup duyệt/hủy.
function handleLeaveProcessingPost($db, $currentUser) {
    $result = ['popup' => '', 'leave_id' => 0, 'reason' => '', 'errors' => []];
    $action = '';
    if (isset($_POST['action']) && is_string($_POST['action'])) {
        $action = $_POST['action'];
    }
    if (!in_array($action, ['open_approve', 'open_cancel', 'close_popup', 'confirm_approve', 'confirm_cancel'], true)) {
        http_response_code(400);
        $result['errors']['general'] = MSG_INVALID_ACTION;
        return $result;
    }

    if ($action === 'close_popup') {
        return $result;
    }

    $leaveId = false;
    if (isset($_POST['leave_id']) && is_string($_POST['leave_id'])) {
        $leaveId = filter_var($_POST['leave_id'], FILTER_VALIDATE_INT);
    }
    if ($leaveId === false || $leaveId < 1) {
        http_response_code(400);
        $result['errors']['general'] = MSG_INVALID_FORM_VALUE;
        return $result;
    }
    $result['leave_id'] = $leaveId;

    if ($action === 'open_approve' || $action === 'open_cancel') {
        $leave = findLeaveById($db, $leaveId);
        if (!$leave) {
            http_response_code(404);
            $result['errors']['general'] = MSG_LEAVE_PROCESS_NOT_FOUND;
            return $result;
        }
        $owner = findUserById($db, (int) $leave['user_id']);
        if (!$owner) {
            http_response_code(404);
            $result['errors']['general'] = MSG_LEAVE_PROCESS_NOT_FOUND;
            return $result;
        }
        $leave['owner_type'] = $owner['user_type'];
        $leave['owner_department'] = $owner['department'];
        if (!canProcessLeave($leave, $currentUser)) {
            http_response_code(403);
            $result['errors']['general'] = MSG_LEAVE_PROCESS_FORBIDDEN;
            return $result;
        }

        $result['popup'] = 'approve';
        if ($action === 'open_cancel') {
            $result['popup'] = 'cancel';
        }
        return $result;
    }

    $result['popup'] = 'approve';
    if ($action === 'confirm_cancel') {
        $result['popup'] = 'cancel';

        if (isset($_POST['cancel_reason']) && !is_string($_POST['cancel_reason'])) {
            http_response_code(400);
            $result['errors']['cancel_reason'] = MSG_INVALID_FORM_VALUE;
            return $result;
        }
        if (isset($_POST['cancel_reason'])) {
            $result['reason'] = trim($_POST['cancel_reason']);
        }
        $reasonError = validateLeaveCancelReason($result['reason']);
        if ($reasonError !== '') {
            $result['errors']['cancel_reason'] = $reasonError;
            return $result;
        }
    }

    $processed = processLeave($db, (int) $currentUser['id'], $leaveId, $result['popup'], $result['reason']);
    if ($processed['error'] !== '') {
        $result['errors']['general'] = $processed['error'];
        http_response_code(409);
        if ($processed['retry']) {
            http_response_code(500);
        }
    }

    if (!$processed['retry']) {
        $result['popup'] = '';
        $result['leave_id'] = 0;
    }
    return $result;
}
