SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS train_php
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE train_php;

CREATE TABLE user_types (
    id TINYINT UNSIGNED NOT NULL,
    name VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_types_name (name)
);

CREATE TABLE departments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departments_name (name)
);

CREATE TABLE request_types (
    id TINYINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_request_types_name (name)
);

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    password_hash CHAR(32) NOT NULL,
    email VARCHAR(254) NULL,
    birth_date DATE NULL,
    user_type_id TINYINT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    status TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    CONSTRAINT fk_users_user_type FOREIGN KEY (user_type_id)
        REFERENCES user_types (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_users_department FOREIGN KEY (department_id)
        REFERENCES departments (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT ck_users_status CHECK (status IN (0, 1)),
    CONSTRAINT ck_users_admin_account CHECK (
        (user_type_id = 3 AND username = 'admin')
        OR (user_type_id IN (1, 2) AND username <> 'admin')
    )
);

CREATE TABLE requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    approver_id INT UNSIGNED NOT NULL,
    request_type_id TINYINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    attachment_original_name VARCHAR(255) NOT NULL,
    attachment_path VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_requests_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_requests_approver FOREIGN KEY (approver_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_requests_request_type FOREIGN KEY (request_type_id)
        REFERENCES request_types (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT ck_requests_status CHECK (status IN (0, 1, 2)),
    CONSTRAINT ck_requests_dates CHECK (start_date <= end_date)
);

START TRANSACTION;

INSERT INTO user_types (id, name) VALUES
    (1, 'user'),
    (2, 'manager'),
    (3, 'admin');

INSERT INTO departments (id, name) VALUES
    (1, 'Ban quản lý'),
    (2, 'Nhân sự'),
    (3, 'Kế toán'),
    (4, 'Kỹ thuật'),
    (5, 'Sản xuất');

INSERT INTO request_types (id, name) VALUES
    (1, 'Đơn nghỉ phép'),
    (2, 'Đơn cấp vật tư máy móc'),
    (3, 'Đơn thay đổi giờ làm'),
    (4, 'Đơn xin thanh toán công tác phí');

INSERT INTO users (username, full_name, password_hash, user_type_id, department_id)
VALUES ('admin', 'Admin', MD5('123456'), 3, 1);

COMMIT;
