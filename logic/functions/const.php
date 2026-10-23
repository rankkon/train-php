<?php

const USER_TYPE_USER = 1;
const USER_TYPE_MANAGER = 2;
const USER_TYPE_ADMIN = 3;

const DEPARTMENT_MANAGEMENT = 1;
const DEPARTMENT_HUMAN_RESOURCES = 2;
const DEPARTMENT_ACCOUNTING = 3;
const DEPARTMENT_TECHNICAL = 4;
const DEPARTMENT_PRODUCTION = 5;

const USER_STATUS_LOCKED = 0;
const USER_STATUS_ACTIVE = 1;

const LEAVE_STATUS_NEW = 0;
const LEAVE_STATUS_APPROVED = 1;
const LEAVE_STATUS_CANCELLED = 2;

const LEAVE_TYPE_TIME_OFF = 1;
const LEAVE_TYPE_SUPPLIES = 2;
const LEAVE_TYPE_WORK_HOURS = 3;
const LEAVE_TYPE_TRAVEL_EXPENSE = 4;

const APP_TIMEZONE = 'Asia/Bangkok';
const APP_BASE_PATH = '/train-php';
const APP_DATE_FORMAT = 'Y-m-d';
const APP_DATETIME_FORMAT = 'Y-m-d H:i:s';
const LIST_PER_PAGE = 15;
const LEAVE_LIST_PER_PAGE = 15;
const PAGINATION_NEARBY_PAGES = 2;
const USER_CODE_LENGTH = 4;
const USERNAME_MAX_LENGTH = 50;
const USER_FULL_NAME_MAX_LENGTH = 100;
const USER_PASSWORD_MIN_LENGTH = 8;
const USER_EMAIL_MAX_LENGTH = 254;
const LEAVE_TITLE_MAX_LENGTH = 255;
const LEAVE_CONTENT_MAX_BYTES = 65535;
const LEAVE_CANCEL_REASON_MAX_BYTES = 65535;

const ATTACHMENT_MAX_MB = 10;
const ATTACHMENT_MAX_BYTES = ATTACHMENT_MAX_MB * 1024 * 1024;
const ATTACHMENT_NAME_MAX_LENGTH = 255;

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
