-- name: login_user
SELECT id, password_hash, status
FROM users
WHERE username = ?
LIMIT 1

-- name: user_by_email
SELECT id
FROM users
WHERE email = ?
LIMIT 1

-- name: user_insert
INSERT INTO users (
    username, full_name, password_hash, email, birth_date,
    user_type, department, status, created_at, update_at
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)

-- name: current_user
SELECT id, username, full_name, email, birth_date, user_type, department, status
FROM users
WHERE id = ?

-- name: user_update
UPDATE users
SET username = ?, full_name = ?, email = ?, birth_date = ?,
    user_type = ?, department = ?, update_at = ?
WHERE id = ?

-- name: user_password_update
UPDATE users
SET password_hash = ?
WHERE id = ?

-- name: user_status_update
UPDATE users
SET status = ?
WHERE id = ?

-- name: user_search
AND (CASE WHEN id < 1000 THEN LPAD(id, 4, '0') ELSE CAST(id AS CHAR) END LIKE ? ESCAPE '!'
    OR full_name LIKE ? ESCAPE '!')

-- name: user_count
SELECT COUNT(*) AS total
FROM users
WHERE (? = 3 OR department = ?)
{{where}}

-- name: user_list
SELECT id, full_name, created_at, status, user_type, department
FROM users
WHERE (? = 3 OR department = ?)
{{where}}
ORDER BY created_at DESC, id DESC
LIMIT ? OFFSET ?

-- name: approvers_user
SELECT id, full_name
FROM users
WHERE user_type = 2 AND department = ? AND status = 1
ORDER BY id

-- name: approvers_manager
SELECT id, full_name
FROM users
WHERE user_type = 3 AND status = 1
ORDER BY id

-- name: approvers_admin
SELECT id, full_name
FROM users
WHERE id = ? AND user_type = 3 AND status = 1

-- name: dashboard_leaves
SELECT users.full_name, `leave`.leave_type,
       `leave`.created_at, `leave`.status,
       `leave`.end_date, `leave`.title
FROM `leave`
JOIN users ON users.id = `leave`.user_id
WHERE (? = 3 OR users.department = ?)
ORDER BY `leave`.created_at DESC, `leave`.id DESC
LIMIT ? OFFSET ?

-- name: leave_type_search
SELECT leave_type
FROM (
    SELECT 1 AS leave_type, ? AS type_name
    UNION ALL SELECT 2, ?
    UNION ALL SELECT 3, ?
    UNION ALL SELECT 4, ?
) AS leave_types
WHERE REGEXP_REPLACE(CONVERT(type_name USING utf8mb4), '\\p{M}', '')
    COLLATE utf8mb4_unicode_520_ci LIKE REGEXP_REPLACE(?, '\\p{M}', '') ESCAPE '!'

-- name: leave_search
AND (REGEXP_REPLACE(users.full_name, '\\p{M}', '') COLLATE utf8mb4_unicode_520_ci
        LIKE REGEXP_REPLACE(?, '\\p{M}', '') ESCAPE '!'
    OR REGEXP_REPLACE(`leave`.title, '\\p{M}', '') COLLATE utf8mb4_unicode_520_ci
        LIKE REGEXP_REPLACE(?, '\\p{M}', '') ESCAPE '!'
    OR `leave`.leave_type IN ({{type_codes}}))

-- name: leave_count
SELECT COUNT(*) AS total
FROM `leave`
JOIN users ON users.id = `leave`.user_id
WHERE (? = 3 OR users.department = ?)
{{where}}

-- name: leave_list
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
SELECT id, user_type, department, status
FROM users
WHERE id IN (?, ?, ?)
ORDER BY id
FOR UPDATE

-- name: leave_insert
INSERT INTO `leave` (
    user_id, title, content, approver_id, leave_type, start_date, end_date,
    status, created_at, attachment_original_name, attachment_path
)
VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)

-- name: leave_detail
SELECT id, user_id, title, content, approver_id, leave_type, start_date, end_date,
       status, created_at, approved_at, update_at, delete_at,
       attachment_original_name, attachment_path
FROM `leave`
WHERE id = ?

-- name: lock_row
FOR UPDATE

-- name: leave_update
UPDATE `leave`
SET title = ?, content = ?, approver_id = ?, leave_type = ?,
    start_date = ?, end_date = ?, attachment_original_name = ?, attachment_path = ?
WHERE id = ?

-- name: attachment_usage
SELECT COUNT(*) AS total
FROM `leave`
WHERE attachment_path = ?

-- name: leave_approve
UPDATE `leave`
SET status = 1, update_at = ?, approved_at = ?
WHERE id = ? AND status = 0

-- name: leave_cancel
UPDATE `leave`
SET status = 2, delete_at = ?, cancel_reason = ?
WHERE id = ? AND status = 0
