<?php
require_once __DIR__ . '/const.php';

// Đổi ký tự HTML thành văn bản để dữ liệu nhập vào không trở thành thẻ HTML.
function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Lấy phần ngày từ timestamp đã lưu, giữ nguyên ngày và không đổi múi giờ.
function displayDate($value) {
    return substr((string) $value, 0, 10);
}

// Tạo thuộc tính accept của ô file từ cùng danh sách định dạng mà server kiểm tra.
function attachmentAccept() {
    $extensions = [];
    foreach (array_keys(ATTACHMENT_ALLOWED_TYPES) as $extension) {
        $extensions[] = '.' . $extension;
    }
    return implode(',', $extensions);
}

// Chỉ hiện nút Duyệt/Hủy cho đúng người và khi đơn còn mới.
function canShowLeaveProcessingButtons($leave, $user) {
    if ((int) $leave['status'] !== LEAVE_STATUS_NEW || (int) $user['status'] !== USER_STATUS_ACTIVE) {
        return false;
    }

    // Admin được xử lý mọi đơn mới, theo quy tắc đã được xác nhận.
    if ((int) $user['user_type'] === USER_TYPE_ADMIN) {
        return true;
    }

    if ((int) $user['user_type'] !== USER_TYPE_MANAGER) {
        return false;
    }

    // Manager phải được chỉ định duyệt đơn của user cùng phòng ban.
    if ((int) $leave['approver_id'] !== (int) $user['id']) {
        return false;
    }
    if ((int) $leave['owner_type'] !== USER_TYPE_USER) {
        return false;
    }
    if ((int) $leave['owner_department'] !== (int) $user['department']) {
        return false;
    }
    return true;
}

// Chỉ chủ đơn đang hoạt động được sửa khi đơn còn mới, kể cả tài khoản admin.
function canEditLeave($leave, $user) {
    if ((int) $user['status'] !== USER_STATUS_ACTIVE) {
        return false;
    }
    if ((int) $leave['user_id'] !== (int) $user['id']) {
        return false;
    }
    return (int) $leave['status'] === LEAVE_STATUS_NEW;
}

// Tạo link chuyển trang và giữ lại từ khóa tìm kiếm.
function pageUrl($pageNumber, $search, $pageFile = 'leave_list.php') {
    $parameters = ['q' => $search, 'page' => $pageNumber];
    return $pageFile . '?' . http_build_query($parameters);
}

// Tạo link trang đầu, trang cuối và các trang lân cận cho cả hai màn hình danh sách.
function buildPagination($page, $totalPages, $search, $pageFile) {
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
            'url' => pageUrl($pageNumber, $search, $pageFile),
            'current' => $pageNumber === $page,
            'show_gap' => $pageNumber > $previousPage + 1,
        ];
        $previousPage = $pageNumber;
    }
    return $pagination;
}

// Kiểm tra đúng dạng YYYY-MM-DD, sau đó kiểm tra ngày có thật trên lịch.
function isValidDate($value) {
    $parts = explode('-', $value);
    if (count($parts) !== 3) {
        return false;
    }
    if (strlen($parts[0]) !== 4 || strlen($parts[1]) !== 2 || strlen($parts[2]) !== 2) {
        return false;
    }
    if (!ctype_digit($parts[0]) || !ctype_digit($parts[1]) || !ctype_digit($parts[2])) {
        return false;
    }

    $year = (int) $parts[0];
    $month = (int) $parts[1];
    $day = (int) $parts[2];
    return checkdate($month, $day, $year);
}
