<?php
require_once __DIR__ . '/const.php';

$userTypes = [
    USER_TYPE_USER => 'User',
    USER_TYPE_MANAGER => 'Manager',
    USER_TYPE_ADMIN => 'Admin',
];

$departments = [
    DEPARTMENT_MANAGEMENT => 'Ban quản lý',
    DEPARTMENT_HUMAN_RESOURCES => 'Nhân sự',
    DEPARTMENT_ACCOUNTING => 'Kế toán',
    DEPARTMENT_TECHNICAL => 'Kỹ thuật',
    DEPARTMENT_PRODUCTION => 'Sản xuất',
];

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
