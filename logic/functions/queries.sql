-- name: login_user
SELECT id, password_hash, status
FROM users
WHERE username = ?
LIMIT 1

-- name: current_user
SELECT id, user_type, department, status
FROM users
WHERE id = ?

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
       `leave`.created_at, `leave`.status, `leave`.start_date,
       `leave`.end_date, `leave`.title
FROM `leave`
JOIN users ON users.id = `leave`.user_id
WHERE (? = 3 OR users.department = ?)
ORDER BY `leave`.created_at DESC, `leave`.id DESC
LIMIT 30

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
SELECT `leave`.id, `leave`.user_id, `leave`.approver_id, `leave`.leave_type,
       `leave`.created_at, `leave`.status, `leave`.end_date, `leave`.title,
       users.full_name, users.user_type AS owner_type, users.department AS owner_department
FROM `leave`
JOIN users ON users.id = `leave`.user_id
WHERE (? = 3 OR users.department = ?)
{{where}}
ORDER BY `leave`.created_at DESC, `leave`.id DESC
LIMIT ? OFFSET ?

-- name: leave_creation_users
SELECT id, user_type, department, status
FROM users
WHERE id IN (?, ?)
ORDER BY id
FOR UPDATE

-- name: leave_insert
INSERT INTO `leave` (
    user_id, title, content, approver_id, leave_type, start_date, end_date,
    status, created_at, attachment_original_name, attachment_path
)
VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)
