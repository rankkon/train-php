<?php
// Mã quyền và trạng thái phải khớp với schema.sql và các câu SQL đang dùng.
const USER_TYPE_USER = 1;
const USER_TYPE_MANAGER = 2;
const USER_TYPE_ADMIN = 3;

const USER_STATUS_LOCKED = 0;
const USER_STATUS_ACTIVE = 1;

const LEAVE_STATUS_NEW = 0;
const LEAVE_STATUS_APPROVED = 1;
const LEAVE_STATUS_CANCELLED = 2;

// Mã loại đơn, dùng làm khóa trong mảng tên hiển thị ở catalogs.php.
const LEAVE_TYPE_TIME_OFF = 1;
const LEAVE_TYPE_SUPPLIES = 2;
const LEAVE_TYPE_WORK_HOURS = 3;
const LEAVE_TYPE_TRAVEL_EXPENSE = 4;

// Các giá trị dùng chung khi hiển thị danh sách và kiểm tra form.
const APP_TIMEZONE = 'Asia/Bangkok';
const APP_DATE_FORMAT = 'Y-m-d';
const APP_DATETIME_FORMAT = 'Y-m-d H:i:s';
const LIST_PER_PAGE = 20;
const PAGINATION_NEARBY_PAGES = 2;
const USER_CODE_LENGTH = 4;
const LEAVE_TITLE_MAX_LENGTH = 255;
const LEAVE_CONTENT_MAX_BYTES = 65535;

// Giới hạn file tính bằng MB để hiển thị, bằng byte để kiểm tra dung lượng.
const ATTACHMENT_MAX_MB = 2;
const ATTACHMENT_MAX_BYTES = ATTACHMENT_MAX_MB * 1024 * 1024;
const ATTACHMENT_NAME_MAX_LENGTH = 255;
// Số byte ngẫu nhiên dùng để tạo mã form và tên file.
const FORM_TOKEN_BYTES = 32;
const ATTACHMENT_RANDOM_BYTES = 16;

const ATTACHMENT_ALLOWED_TYPES = [
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
    'gif' => ['image/gif'],
    'webp' => ['image/webp'],
    'bmp' => ['image/bmp', 'image/x-ms-bmp'],
    'pdf' => ['application/pdf'],
];
