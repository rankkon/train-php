<?php
require_once __DIR__ . '/const.php';

// Database chỉ lưu mã số; các mảng này dùng để đổi mã thành tên trên giao diện.
$userStatuses = [
    USER_STATUS_LOCKED => 'Đã khóa',
    USER_STATUS_ACTIVE => 'Đang hoạt động',
];

$leaveStatuses = [
    LEAVE_STATUS_NEW => 'Đơn mới',
    LEAVE_STATUS_APPROVED => 'Đã duyệt',
    LEAVE_STATUS_CANCELLED => 'Đã hủy',
];

$leaveTypes = [
    LEAVE_TYPE_TIME_OFF => 'Đơn nghỉ phép',
    LEAVE_TYPE_SUPPLIES => 'Đơn cấp vật tư máy móc',
    LEAVE_TYPE_WORK_HOURS => 'Đơn thay đổi giờ làm',
    LEAVE_TYPE_TRAVEL_EXPENSE => 'Đơn xin thanh toán công tác phí',
];
