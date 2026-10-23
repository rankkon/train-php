<?php
require_once __DIR__ . '/const.php';

// Chuyển đến trang lỗi chung với mã HTTP tương ứng.
function redirectToError($statusCode = 500) {
    if (isset($_SESSION['user_create_draft'])) {
        $_SESSION['user_create_resume'] = true;
        $_SESSION['user_create_draft']['confirmed'] = false;
        unset($_SESSION['user_create_save_pending']);
    }
    if (isset($_SESSION['leave_create_draft'])) {
        $_SESSION['leave_create_resume'] = true;
        $_SESSION['leave_create_draft']['confirmed'] = false;
        unset($_SESSION['leave_create_save_pending']);
    }
    $errorUrl = '../views/error.php?code=' . $statusCode;
    if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'index.php') {
        $errorUrl = 'views/error.php?code=' . $statusCode;
    }
    header('Location: ' . $errorUrl, true, 303);
    exit;
}

// Đưa tài khoản không còn đăng nhập hợp lệ về trang login.
function redirectToLogin() {
    unset($_SESSION['user_id']);
    header('Location: ../views/login.php', true, 303);
    exit;
}

// Ghi lỗi ngoài dự kiến và chuyển đến trang lỗi chung.
function handleUnexpectedError($error) {
    error_log($error->getMessage());
    redirectToError();
}

// Escape dữ liệu trước khi hiển thị HTML.
function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Lấy phần ngày từ thời gian đã lưu.
function displayDate($value) {
    return substr((string) $value, 0, 10);
}

// Tạo danh sách định dạng file cho ô upload.
function attachmentAccept() {
    $extensions = [];
    foreach (array_keys(ATTACHMENT_ALLOWED_TYPES) as $extension) {
        $extensions[] = '.' . $extension;
    }
    return implode(',', $extensions);
}

// Kiểm tra quyền duyệt hoặc hủy đơn mới.
function canProcessLeave($leave, $user) {
    if ((int) $leave['status'] !== LEAVE_STATUS_NEW || (int) $user['status'] !== USER_STATUS_ACTIVE) {
        return false;
    }

    if ((int) $user['user_type'] === USER_TYPE_ADMIN) {
        return true;
    }

    if ((int) $user['user_type'] !== USER_TYPE_MANAGER
        || (int) $leave['approver_id'] !== (int) $user['id']
        || (int) $leave['owner_type'] !== USER_TYPE_USER
        || (int) $leave['owner_department'] !== (int) $user['department']) {
        return false;
    }
    return true;
}

// Kiểm tra chủ đơn đang hoạt động được sửa đơn mới.
function canEditLeave($leave, $user) {
    if ((int) $user['status'] !== USER_STATUS_ACTIVE
        || (int) $leave['user_id'] !== (int) $user['id']) {
        return false;
    }
    return (int) $leave['status'] === LEAVE_STATUS_NEW;
}

// Kiểm tra quyền hiển thị nút sửa và khóa tài khoản.
function canManageUser($targetUser, $currentUser) {
    if ((int) $currentUser['user_type'] === USER_TYPE_ADMIN) {
        return true;
    }
    if ((int) $currentUser['user_type'] !== USER_TYPE_MANAGER
        || (int) $targetUser['user_type'] !== USER_TYPE_USER
        || (int) $targetUser['department'] !== (int) $currentUser['department']) {
        return false;
    }
    return true;
}

// Tạo link phân trang kèm từ khóa và bộ lọc trạng thái.
function pageUrl($pageNumber, $search, $pageFile = 'leave_list.php', $userStatusFilter = '') {
    $parameters = ['q' => $search, 'page' => $pageNumber];
    if ($userStatusFilter !== '') {
        $parameters['status'] = $userStatusFilter;
    }
    return $pageFile . '?' . http_build_query($parameters);
}

// Tạo danh sách các link phân trang.
function buildPagination($page, $totalPages, $search, $pageFile, $userStatusFilter = '') {
    $pageNumbers = [1];
    $startPage = max(2, $page - PAGINATION_NEARBY_PAGES);
    $endPage = min($totalPages - 1, $page + PAGINATION_NEARBY_PAGES);
    for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++) {
        $pageNumbers[] = $pageNumber;
    }
    if ($totalPages > 1) {
        $pageNumbers[] = $totalPages;
    }

    $pagination = [];
    $previousPage = 0;
    foreach ($pageNumbers as $pageNumber) {
        $pagination[] = [
            'number' => $pageNumber,
            'url' => pageUrl($pageNumber, $search, $pageFile, $userStatusFilter),
            'current' => $pageNumber === $page,
            'show_gap' => $pageNumber > $previousPage + 1,
        ];
        $previousPage = $pageNumber;
    }
    return $pagination;
}

// Kiểm tra ngày YYYY-MM-DD hợp lệ.
function isValidDate($value) {
    $parts = explode('-', $value);

    if (count($parts) !== 3
        || strlen($parts[0]) !== 4
        || strlen($parts[1]) !== 2
        || strlen($parts[2]) !== 2
        || !ctype_digit($parts[0])
        || !ctype_digit($parts[1])
        || !ctype_digit($parts[2])) {
        return false;
    }

    $year = (int) $parts[0];
    $month = (int) $parts[1];
    $day = (int) $parts[2];
    return checkdate($month, $day, $year);
}
