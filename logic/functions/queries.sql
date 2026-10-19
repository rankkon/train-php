-- name: login_user
-- Tìm tài khoản theo tên đăng nhập; phần PHP sẽ kiểm tra mật khẩu.
SELECT id, password_hash, status
FROM users
WHERE username = ?
LIMIT 1

-- name: current_user
-- Lấy quyền, phòng ban và trạng thái mới nhất của tài khoản.
SELECT id, user_type, department, status
FROM users
WHERE id = ?

-- name: user_search
-- LPAD thêm số 0 cho mã ngắn. Mã từ 1000 trở lên giữ nguyên, không bị cắt.
WHERE (CASE WHEN id < 1000 THEN LPAD(id, 4, '0') ELSE CAST(id AS CHAR) END LIKE ? ESCAPE '!'
    OR full_name LIKE ? ESCAPE '!')

-- name: user_count
-- Điều kiện WHERE được thêm từ đoạn tìm kiếm cố định phía trên hoặc để trống.
SELECT COUNT(*) AS total
FROM users
{{where}}

-- name: user_list
-- Lấy dữ liệu để hiện bảng, không lấy mật khẩu.
SELECT id, full_name, created_at, status
FROM users
{{where}}
ORDER BY created_at DESC, id DESC
LIMIT ? OFFSET ?

-- name: approvers_user
-- User chỉ chọn manager đang hoạt động trong cùng phòng.
SELECT id, full_name
FROM users
WHERE user_type = 2 AND department = ? AND status = 1
ORDER BY id

-- name: approvers_manager
-- Manager chọn admin đang hoạt động, không giới hạn phòng ban.
SELECT id, full_name
FROM users
WHERE user_type = 3 AND status = 1
ORDER BY id

-- name: approvers_admin
-- Admin chọn chính mình làm người duyệt.
SELECT id, full_name
FROM users
WHERE id = ? AND user_type = 3 AND status = 1

-- name: dashboard_leaves
-- Quyền 3 xem mọi phòng; các quyền còn lại chỉ xem đơn trong phòng mình.
SELECT users.full_name, `leave`.leave_type,
       `leave`.created_at, `leave`.status, `leave`.start_date,
       `leave`.end_date, `leave`.title
FROM `leave`
JOIN users ON users.id = `leave`.user_id
WHERE (? = 3 OR users.department = ?)
ORDER BY `leave`.created_at DESC, `leave`.id DESC
LIMIT 30

-- name: leave_type_search
SELECT leave_type
FROM (
    SELECT 1 AS leave_type, ? AS type_name
    UNION ALL SELECT 2, ?
    UNION ALL SELECT 3, ?
    UNION ALL SELECT 4, ?
) AS leave_types
WHERE CONVERT(type_name USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ? ESCAPE '!'

-- name: leave_search
AND (users.username LIKE ? ESCAPE '!'
    OR users.full_name LIKE ? ESCAPE '!'
    OR `leave`.title LIKE ? ESCAPE '!'
    OR `leave`.content LIKE ? ESCAPE '!'
    OR `leave`.leave_type IN ({{type_codes}}))

-- name: leave_count
SELECT COUNT(*) AS total
FROM `leave`
JOIN users ON users.id = `leave`.user_id
WHERE (? = 3 OR users.department = ?)
{{where}}

-- name: leave_list
-- Lọc theo quyền trước, sau đó sắp xếp và lấy số dòng của một trang.
-- Trạng thái 0 (mới), 1 (đã duyệt), 2 (đã hủy); cùng trạng thái thì đơn tạo mới đứng trước.
SELECT `leave`.id, `leave`.user_id, `leave`.approver_id, `leave`.leave_type,
       `leave`.created_at, `leave`.status, `leave`.end_date, `leave`.title,
       users.full_name, users.user_type AS owner_type, users.department AS owner_department
FROM `leave`
JOIN users ON users.id = `leave`.user_id
WHERE (? = 3 OR users.department = ?)
{{where}}
ORDER BY `leave`.status ASC, `leave`.created_at DESC, `leave`.id DESC
LIMIT ? OFFSET ?

-- name: leave_save_users
-- Khóa người thao tác, chủ đơn và người duyệt trong transaction lưu đơn.
SELECT id, user_type, department, status
FROM users
WHERE id IN (?, ?, ?)
ORDER BY id
FOR UPDATE

-- name: leave_insert
-- Đơn mới có trạng thái 0; ngày duyệt và ngày hủy để NULL theo mặc định của bảng.
INSERT INTO `leave` (
    user_id, title, content, approver_id, leave_type, start_date, end_date,
    status, created_at, attachment_original_name, attachment_path
)
VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)

-- name: leave_detail
-- Đọc dữ liệu gốc để điền vào form sửa; PHP kiểm tra quyền trước khi hiển thị.
SELECT id, user_id, title, content, approver_id, leave_type, start_date, end_date,
       status, created_at, approved_at, update_at, delete_at,
       attachment_original_name, attachment_path
FROM `leave`
WHERE id = ?

-- name: lock_row
FOR UPDATE

-- name: leave_update
-- Chỉ cập nhật nội dung nhập; giữ nguyên chủ đơn, trạng thái và các mốc thời gian.
UPDATE `leave`
SET title = ?, content = ?, approver_id = ?, leave_type = ?,
    start_date = ?, end_date = ?, attachment_original_name = ?, attachment_path = ?
WHERE id = ?

-- name: attachment_usage
-- Chỉ xóa file cũ khi không còn đơn nào dùng đường dẫn đó.
SELECT COUNT(*) AS total
FROM `leave`
WHERE attachment_path = ?
