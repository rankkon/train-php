-- Chỉ chạy một lần để khởi tạo database mới, không chạy lại trên dữ liệu đang dùng.
SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS train_php
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE train_php;

-- Tài khoản: quyền 1 = user, 2 = manager, 3 = admin; trạng thái 0 là đã khóa.
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    password_hash CHAR(32) NOT NULL,
    email VARCHAR(254) NULL,
    birth_date DATE NULL,
    user_type TINYINT UNSIGNED NOT NULL,
    department TINYINT UNSIGNED NOT NULL,
    status TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    CONSTRAINT ck_users_user_type CHECK (user_type IN (1, 2, 3)),
    CONSTRAINT ck_users_department CHECK (department IN (1, 2, 3, 4, 5)),
    CONSTRAINT ck_users_status CHECK (status IN (0, 1)),
    CONSTRAINT ck_users_admin_account CHECK (
        (user_type = 3 AND username = 'admin')
        OR (user_type IN (1, 2) AND username <> 'admin')
    )
);

-- Đơn: tên bảng leave phải có dấu backtick vì trùng từ khóa SQL.
CREATE TABLE `leave` (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    approver_id INT UNSIGNED NOT NULL,
    leave_type TINYINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATE NULL,
    delete_at DATETIME NULL,
    update_at DATETIME NULL,
    attachment_original_name VARCHAR(255) NOT NULL,
    attachment_path VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_requests_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_requests_approver FOREIGN KEY (approver_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT ck_requests_status CHECK (status IN (0, 1, 2)),
    CONSTRAINT ck_leave_type CHECK (leave_type IN (1, 2, 3, 4)),
    CONSTRAINT ck_requests_dates CHECK (start_date <= end_date)
);

-- Tạo tài khoản admin ban đầu trong transaction, không đổi mật khẩu tài khoản đã có.
START TRANSACTION;

INSERT INTO users (username, full_name, password_hash, user_type, department)
VALUES ('admin', 'Admin', MD5('123456'), 3, 1);

COMMIT;
