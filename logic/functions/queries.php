<?php
// Các hàm ở đây lấy câu SQL theo nhãn trong queries.sql và truyền dữ liệu qua bind_param.
function readSql($name) {
    $sql = '';
    $reading = false;

    foreach (file(__DIR__ . '/queries.sql') as $line) {
        if (strpos($line, '-- name: ') === 0) {
            if ($reading) {
                break;
            }
            $reading = trim(substr($line, 9)) === $name;
        } elseif ($reading) {
            $sql .= $line;
        }
    }

    if (!$reading) {
        throw new InvalidArgumentException('Không tìm thấy câu SQL: ' . $name);
    }

    return trim($sql);
}

function findUserForLogin($db, $username) {
    $statement = $db->prepare(readSql('login_user'));
    $statement->bind_param('s', $username);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $user;
}

function findUserById($db, $userId) {
    $statement = $db->prepare(readSql('current_user'));
    $statement->bind_param('i', $userId);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $user;
}

function findApprovers($db, $user) {
    if ((int) $user['user_type'] === 1) {
        $statement = $db->prepare(readSql('approvers_user'));
        $department = (int) $user['department'];
        $statement->bind_param('i', $department);
    } elseif ((int) $user['user_type'] === 2) {
        $statement = $db->prepare(readSql('approvers_manager'));
    } else {
        $statement = $db->prepare(readSql('approvers_admin'));
        $userId = (int) $user['id'];
        $statement->bind_param('i', $userId);
    }

    $statement->execute();
    $approvers = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $approvers;
}

function lockLeaveCreationUsers($db, $userId, $approverId) {
    $statement = $db->prepare(readSql('leave_creation_users'));
    $statement->bind_param('ii', $userId, $approverId);
    $statement->execute();
    $users = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $users;
}

function insertLeave($db, $userId, $values, $createdAt, $attachmentName, $attachmentPath) {
    $statement = $db->prepare(readSql('leave_insert'));
    $approverId = (int) $values['approver_id'];
    $leaveType = (int) $values['leave_type'];
    $statement->bind_param(
        'issiisssss',
        $userId, $values['title'], $values['content'], $approverId, $leaveType,
        $values['start_date'], $values['end_date'], $createdAt, $attachmentName, $attachmentPath
    );
    $statement->execute();
    $statement->close();
}

function findDashboardLeaves($db, $currentUser) {
    $statement = $db->prepare(readSql('dashboard_leaves'));
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    $statement->bind_param('ii', $userType, $department);
    $statement->execute();
    $leaves = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $leaves;
}

function leaveSearchWhere($search, $leaveTypes) {
    if ($search === '') {
        return '';
    }

    // Mã 0 không khớp loại đơn nào; các mã còn lại lấy từ danh sách cố định.
    $typeCodes = [0];
    foreach ($leaveTypes as $code => $name) {
        if (mb_stripos($name, $search, 0, 'UTF-8') !== false) {
            $typeCodes[] = $code;
        }
    }

    return str_replace('{{type_codes}}', implode(',', $typeCodes), readSql('leave_search'));
}

function countLeaves($db, $currentUser, $search, $leaveTypes) {
    $where = leaveSearchWhere($search, $leaveTypes);
    $statement = $db->prepare(str_replace('{{where}}', $where, readSql('leave_count')));
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    if ($search !== '') {
        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
        $statement->bind_param('iissss', $userType, $department, $pattern, $pattern, $pattern, $pattern);
    } else {
        $statement->bind_param('ii', $userType, $department);
    }
    $statement->execute();
    $total = (int) $statement->get_result()->fetch_assoc()['total'];
    $statement->close();
    return $total;
}

function findLeaves($db, $currentUser, $search, $leaveTypes, $perPage, $offset) {
    $where = leaveSearchWhere($search, $leaveTypes);
    $statement = $db->prepare(str_replace('{{where}}', $where, readSql('leave_list')));
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    if ($search !== '') {
        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
        $statement->bind_param('iissssii', $userType, $department, $pattern, $pattern, $pattern, $pattern, $perPage, $offset);
    } else {
        $statement->bind_param('iiii', $userType, $department, $perPage, $offset);
    }
    $statement->execute();
    $leaves = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $leaves;
}
