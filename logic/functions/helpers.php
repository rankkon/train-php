<?php
function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function canShowButtons($leave, $user) {
    if ((int) $leave['status'] !== 0 || (int) $user['status'] !== 1) {
        return false;
    }

    if ((int) $user['user_type'] === 3) {
        return in_array((int) $leave['owner_type'], [1, 2], true)
            || ((int) $leave['owner_type'] === 3
                && (int) $leave['user_id'] === (int) $user['id']);
    }

    if ((int) $user['user_type'] === 2) {
        return (int) $leave['approver_id'] === (int) $user['id']
            && (int) $leave['owner_type'] === 1
            && (int) $leave['owner_department'] === (int) $user['department'];
    }

    return false;
}

function pageUrl($pageNumber, $search) {
    return 'leave_list.php?' . http_build_query(['q' => $search, 'page' => $pageNumber]);
}

function isValidDate($value) {
    if (!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $value)) {
        return false;
    }

    $year = (int) substr($value, 0, 4);
    $month = (int) substr($value, 5, 2);
    $day = (int) substr($value, 8, 2);
    return checkdate($month, $day, $year);
}
