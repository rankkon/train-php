<?php
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/message.php';

// Đọc từng dòng trong queries.sql và lấy đúng đoạn có nhãn -- name: cần dùng.
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

// prepare tạo câu truy vấn; bind_param điền dữ liệu vào dấu ? một cách an toàn.
// Trong bind_param: s là chuỗi, i là số nguyên.
// Tìm tài khoản theo tên đăng nhập để kiểm tra mật khẩu và trạng thái hoạt động.
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

// Đọc lại tài khoản từ database để dùng quyền và trạng thái hiện tại.
function findUserById($db, $userId) {
    $sql = readSql('current_user');
    $statement = $db->prepare($sql);
    $statement->bind_param('i', $userId);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result->fetch_assoc();
    $statement->close();
    return $user;
}

// Tìm % và _ như ký tự bình thường, không để SQL hiểu thành ký tự đại diện.
function searchPattern($search) {
    $pattern = str_replace('!', '!!', $search);
    $pattern = str_replace('%', '!%', $pattern);
    $pattern = str_replace('_', '!_', $pattern);
    return '%' . $pattern . '%';
}

// Đếm số người dùng phù hợp để tính số trang.
function countUsers($db, $search) {
    $where = '';
    if ($search !== '') {
        $where = readSql('user_search');
    }
    $sql = str_replace('{{where}}', $where, readSql('user_count'));
    $statement = $db->prepare($sql);
    if ($search !== '') {
        $pattern = searchPattern($search);
        $statement->bind_param('ss', $pattern, $pattern);
    }
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $total = (int) $row['total'];
    $statement->close();
    return $total;
}

// Lấy người dùng trong trang hiện tại, sắp xếp mới nhất trước.
function findUsers($db, $search, $perPage, $offset) {
    $where = '';
    if ($search !== '') {
        $where = readSql('user_search');
    }
    $sql = str_replace('{{where}}', $where, readSql('user_list'));
    $statement = $db->prepare($sql);
    if ($search !== '') {
        $pattern = searchPattern($search);
        $statement->bind_param('ssii', $pattern, $pattern, $perPage, $offset);
    } else {
        $statement->bind_param('ii', $perPage, $offset);
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

// User chọn manager cùng phòng; manager chọn admin; admin chọn chính mình.
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

// FOR UPDATE giữ người thao tác, chủ đơn và người duyệt ổn định trong lúc lưu.
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

// Thêm một đơn mới cùng tên và đường dẫn file; chạy trong transaction của saveLeave.
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

// Đọc một đơn theo ID; chỉ khóa dòng khi đang lưu trong transaction.
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

// Sửa đơn đã có; hàm saveLeave chịu trách nhiệm mở và kết thúc transaction.
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

// Đếm số đơn còn dùng file cũ trước khi xóa file sau một lần sửa thành công.
function countAttachmentUsage($db, $attachmentPath) {
    $statement = $db->prepare(readSql('attachment_usage'));
    $statement->bind_param('s', $attachmentPath);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $statement->close();
    return (int) $row['total'];
}

// Lấy tối đa 30 đơn mới nhất trong phạm vi phòng ban của tài khoản.
function findDashboardLeaves($db, $currentUser) {
    $statement = $db->prepare(readSql('dashboard_leaves'));
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    $statement->bind_param('ii', $userType, $department);
    $statement->execute();
    $result = $statement->get_result();
    $leaves = [];
    while ($leave = $result->fetch_assoc()) {
        $leaves[] = $leave;
    }
    $statement->close();
    return $leaves;
}

// Tìm tên loại đơn bằng cùng LIKE/collation với các cột văn bản, rồi tạo điều kiện tìm kiếm.
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

    // Chỉ đưa mã số từ truy vấn cố định vào SQL; mã 0 dành cho trường hợp không khớp loại nào.
    $typeCodes = [0];
    while ($type = $result->fetch_assoc()) {
        $typeCodes[] = (int) $type['leave_type'];
    }
    $statement->close();

    return str_replace('{{type_codes}}', implode(',', $typeCodes), readSql('leave_search'));
}

// Đếm đơn theo cùng điều kiện tìm kiếm và phân quyền với danh sách.
function countLeaves($db, $currentUser, $search, $searchWhere) {
    $sql = str_replace('{{where}}', $searchWhere, readSql('leave_count'));
    $statement = $db->prepare($sql);
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    if ($search !== '') {
        $pattern = searchPattern($search);
        $statement->bind_param('iissss', $userType, $department, $pattern, $pattern, $pattern, $pattern);
    } else {
        $statement->bind_param('ii', $userType, $department);
    }
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $total = (int) $row['total'];
    $statement->close();
    return $total;
}

// Lấy đơn của trang hiện tại. LIMIT là số dòng, OFFSET là số dòng bỏ qua.
function findLeaves($db, $currentUser, $search, $searchWhere, $perPage, $offset) {
    $sql = str_replace('{{where}}', $searchWhere, readSql('leave_list'));
    $statement = $db->prepare($sql);
    $userType = (int) $currentUser['user_type'];
    $department = (int) $currentUser['department'];
    if ($search !== '') {
        $pattern = searchPattern($search);
        $statement->bind_param('iissssii', $userType, $department, $pattern, $pattern, $pattern, $pattern, $perPage, $offset);
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
