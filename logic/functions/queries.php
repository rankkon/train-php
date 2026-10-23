<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';

// Đọc câu SQL theo nhãn tên.
function readSql($name) {
    $sql = '';
    $reading = false;

    foreach (file(__DIR__ . '/queries.sql') as $line) {
        if (strpos($line, '-- name: ') === 0) {
            if ($reading) {
                break;
            }
            $queryName = trim(substr($line, 9));
            if ($queryName === $name) {
                $reading = true;
            }
        } elseif ($reading) {
            $sql .= $line;
        }
    }

    if (!$reading) {
        throw new InvalidArgumentException(sprintf(MSG_SQL_NOT_FOUND, $name));
    }

    return trim($sql);
}

// Lấy tài khoản theo tên đăng nhập.
function findUserForLogin($db, $username) {
    $sql = readSql('login_user');
    $statement = $db->prepare($sql);
    $statement->bind_param('s', $username);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result->fetch_assoc();
    $statement->close();
    return $user;
}

// Lấy tài khoản theo email.
function findUserByEmail($db, $email) {
    $statement = $db->prepare(readSql('user_by_email'));
    $statement->bind_param('s', $email);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result->fetch_assoc();
    $statement->close();
    return $user;
}

// Lấy thông tin tài khoản và khóa dòng khi cần.
function findUserById($db, $userId, $forUpdate = false) {
    $sql = readSql('current_user');
    if ($forUpdate) {
        $sql .= "\n" . readSql('lock_row');
    }
    $statement = $db->prepare($sql);
    $statement->bind_param('i', $userId);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result->fetch_assoc();
    $statement->close();
    return $user;
}

// Thêm người dùng mới với các trường tùy chọn có thể để NULL.
function insertUser($db, $values, $passwordHash, $createdAt) {
    $statement = $db->prepare(readSql('user_insert'));
    $email = null;
    if ($values['email'] !== '') {
        $email = $values['email'];
    }
    $birthDate = null;
    if ($values['birth_date'] !== '') {
        $birthDate = $values['birth_date'];
    }
    $userType = (int) $values['user_type'];
    $department = (int) $values['department'];
    $status = USER_STATUS_ACTIVE;
    $statement->bind_param(
        'sssssiiis',
        $values['username'], $values['full_name'], $passwordHash, $email, $birthDate,
        $userType, $department, $status, $createdAt
    );
    $statement->execute();
    $statement->close();
}

// Cập nhật tài khoản và chỉ đổi mật khẩu khi có nhập mới.
function updateUser($db, $userId, $values, $updatedAt) {
    $email = null;
    if ($values['email'] !== '') {
        $email = $values['email'];
    }
    $birthDate = null;
    if ($values['birth_date'] !== '') {
        $birthDate = $values['birth_date'];
    }
    $userType = (int) $values['user_type'];
    $department = (int) $values['department'];
    $statement = $db->prepare(readSql('user_update'));
    $statement->bind_param(
        'sssiisi',
        $values['full_name'], $email, $birthDate,
        $userType, $department, $updatedAt, $userId
    );
    $statement->execute();
    $statement->close();

    if ($values['password'] !== '') {
        $passwordHash = md5($values['password']);
        $statement = $db->prepare(readSql('user_password_update'));
        $statement->bind_param('si', $passwordHash, $userId);
        $statement->execute();
        $statement->close();
    }
}

// Cập nhật trạng thái hoạt động của tài khoản.
function updateUserStatus($db, $userId, $status) {
    $statement = $db->prepare(readSql('user_status_update'));
    $statement->bind_param('ii', $status, $userId);
    $statement->execute();
    $statement->close();
}

// Chuẩn bị từ khóa tìm kiếm cho LIKE.
function searchPattern($search) {
    $pattern = str_replace('!', '!!', $search);
    $pattern = str_replace('%', '!%', $pattern);
    $pattern = str_replace('_', '!_', $pattern);
    return '%' . $pattern . '%';
}

// Đếm người dùng theo phòng ban, từ khóa và trạng thái.
function countUsers($db, $currentUser, $search, $userStatusFilter = '') {
    $where = '';
    if ($search !== '') {
        $where = readSql('user_search');
    }
    $sql = str_replace('{{where}}', $where, readSql('user_count'));
    $statement = $db->prepare($sql);
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    if ($search !== '') {
        $pattern = searchPattern($search);
        $statement->bind_param('iissss', $userType, $department, $userStatusFilter, $userStatusFilter, $pattern, $pattern);
    } else {
        $statement->bind_param('iiss', $userType, $department, $userStatusFilter, $userStatusFilter);
    }
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $statement->close();
    return (int) $row['total'];
}

// Lấy người dùng theo phòng ban, từ khóa, trạng thái và trang.
function findUsers($db, $currentUser, $search, $perPage, $offset, $userStatusFilter = '') {
    $where = '';
    if ($search !== '') {
        $where = readSql('user_search');
    }
    $sql = str_replace('{{where}}', $where, readSql('user_list'));
    $statement = $db->prepare($sql);
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    if ($search !== '') {
        $pattern = searchPattern($search);
        $statement->bind_param('iissssii', $userType, $department, $userStatusFilter, $userStatusFilter, $pattern, $pattern, $perPage, $offset);
    } else {
        $statement->bind_param('iissii', $userType, $department, $userStatusFilter, $userStatusFilter, $perPage, $offset);
    }
    $statement->execute();
    $result = $statement->get_result();
    $users = [];
    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }
    $statement->close();
    return $users;
}

// Lấy người duyệt phù hợp với quyền và phòng ban.
function findApprovers($db, $user) {
    if ((int) $user['user_type'] === USER_TYPE_USER) {
        $statement = $db->prepare(readSql('approvers_user'));
        $department = (int) $user['department'];
        $statement->bind_param('i', $department);
    } elseif ((int) $user['user_type'] === USER_TYPE_MANAGER) {
        $statement = $db->prepare(readSql('approvers_manager'));
    } else {
        $statement = $db->prepare(readSql('approvers_admin'));
        $userId = (int) $user['id'];
        $statement->bind_param('i', $userId);
    }

    $statement->execute();
    $result = $statement->get_result();
    $approvers = [];
    while ($approver = $result->fetch_assoc()) {
        $approvers[] = $approver;
    }
    $statement->close();
    return $approvers;
}

// Khóa các tài khoản liên quan trong transaction.
function lockLeaveUsers($db, $userId, $ownerId, $approverId) {
    $statement = $db->prepare(readSql('leave_save_users'));
    $statement->bind_param('iii', $userId, $ownerId, $approverId);
    $statement->execute();
    $result = $statement->get_result();
    $users = [];
    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }
    $statement->close();
    return $users;
}

// Thêm đơn mới và thông tin file đính kèm.
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

// Lấy đơn theo ID và khóa dòng khi cần.
function findLeaveById($db, $leaveId, $forUpdate = false) {
    $sql = readSql('leave_detail');
    if ($forUpdate) {
        $sql .= "\n" . readSql('lock_row');
    }
    $statement = $db->prepare($sql);
    $statement->bind_param('i', $leaveId);
    $statement->execute();
    $result = $statement->get_result();
    $leave = $result->fetch_assoc();
    $statement->close();
    return $leave;
}

// Cập nhật nội dung và file của đơn.
function updateLeave($db, $leaveId, $values, $attachmentName, $attachmentPath) {
    $statement = $db->prepare(readSql('leave_update'));
    $approverId = (int) $values['approver_id'];
    $leaveType = (int) $values['leave_type'];
    $statement->bind_param(
        'ssiissssi',
        $values['title'], $values['content'], $approverId, $leaveType,
        $values['start_date'], $values['end_date'], $attachmentName, $attachmentPath, $leaveId
    );
    $statement->execute();
    $statement->close();
}

// Cập nhật trạng thái và thời gian duyệt đơn.
function approveLeave($db, $leaveId, $approvedTime, $approvedDate) {
    $statement = $db->prepare(readSql('leave_approve'));
    $statement->bind_param('ssi', $approvedTime, $approvedDate, $leaveId);
    $statement->execute();
    $statement->close();
}

// Cập nhật trạng thái, thời gian và lý do hủy.
function cancelLeave($db, $leaveId, $cancelledTime, $cancelReason) {
    $statement = $db->prepare(readSql('leave_cancel'));
    $statement->bind_param('ssi', $cancelledTime, $cancelReason, $leaveId);
    $statement->execute();
    $statement->close();
}

// Đếm số đơn đang dùng một file.
function countAttachmentUsage($db, $attachmentPath) {
    $statement = $db->prepare(readSql('attachment_usage'));
    $statement->bind_param('s', $attachmentPath);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $statement->close();
    return (int) $row['total'];
}

// Lấy đơn trên dashboard theo phạm vi và phân trang.
function findDashboardLeaves($db, $currentUser, $perPage, $offset) {
    $statement = $db->prepare(readSql('dashboard_leaves'));
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    $statement->bind_param('iiii', $userType, $department, $perPage, $offset);
    $statement->execute();
    $result = $statement->get_result();
    $leaves = [];
    while ($leave = $result->fetch_assoc()) {
        $leaves[] = $leave;
    }
    $statement->close();
    return $leaves;
}

// Tạo điều kiện tìm kiếm đơn theo từ khóa.
function leaveSearchWhere($db, $search, $leaveTypes) {
    if ($search === '') {
        return '';
    }

    $statement = $db->prepare(readSql('leave_type_search'));
    $timeOffName = $leaveTypes[LEAVE_TYPE_TIME_OFF];
    $suppliesName = $leaveTypes[LEAVE_TYPE_SUPPLIES];
    $workHoursName = $leaveTypes[LEAVE_TYPE_WORK_HOURS];
    $travelExpenseName = $leaveTypes[LEAVE_TYPE_TRAVEL_EXPENSE];
    $pattern = searchPattern($search);
    $statement->bind_param('sssss', $timeOffName, $suppliesName, $workHoursName, $travelExpenseName, $pattern);
    $statement->execute();
    $result = $statement->get_result();

    $typeCodes = [0];
    while ($type = $result->fetch_assoc()) {
        $typeCodes[] = (int) $type['leave_type'];
    }
    $statement->close();

    return str_replace('{{type_codes}}', implode(',', $typeCodes), readSql('leave_search'));
}

// Đếm đơn theo phạm vi và từ khóa.
function countLeaves($db, $currentUser, $search, $searchWhere) {
    $sql = str_replace('{{where}}', $searchWhere, readSql('leave_count'));
    $statement = $db->prepare($sql);
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    if ($search !== '') {
        $pattern = searchPattern($search);
        $statement->bind_param('iiss', $userType, $department, $pattern, $pattern);
    } else {
        $statement->bind_param('ii', $userType, $department);
    }
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $statement->close();
    return (int) $row['total'];
}

// Lấy danh sách đơn theo phạm vi và phân trang.
function findLeaves($db, $currentUser, $search, $searchWhere, $perPage, $offset) {
    $sql = str_replace('{{where}}', $searchWhere, readSql('leave_list'));
    $statement = $db->prepare($sql);
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    if ($search !== '') {
        $pattern = searchPattern($search);
        $statement->bind_param('iissii', $userType, $department, $pattern, $pattern, $perPage, $offset);
    } else {
        $statement->bind_param('iiii', $userType, $department, $perPage, $offset);
    }
    $statement->execute();
    $result = $statement->get_result();
    $leaves = [];
    while ($leave = $result->fetch_assoc()) {
        $leaves[] = $leave;
    }
    $statement->close();
    return $leaves;
}
