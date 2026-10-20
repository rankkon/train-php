<?php

const MSG_DATABASE_CONNECTION_FAILED = 'Không thể kết nối cơ sở dữ liệu.';
const MSG_METHOD_NOT_ALLOWED = 'Phương thức không được hỗ trợ.';
const MSG_LIST_READ_ONLY = 'Trang danh sách hiện chỉ hỗ trợ xem và tìm kiếm.';
const MSG_LOGIN_FAILED = '※Tên đăng nhập hoặc mật khẩu lỗi. Vui lòng thử lại！';
const MSG_ACCOUNT_INACTIVE = 'Tài khoản không còn hoạt động. Vui lòng đăng nhập lại.';
const MSG_INVALID_FORM_VALUE = 'Giá trị gửi lên không hợp lệ.';
const MSG_INVALID_ACTION = 'Thao tác không hợp lệ.';
const MSG_SQL_NOT_FOUND = 'Không tìm thấy câu SQL: %s';
const MSG_LEAVE_NOT_FOUND = 'Không tìm thấy đơn cần sửa.';
const MSG_LEAVE_EDIT_FORBIDDEN = 'Chỉ chủ đơn được sửa khi đơn còn ở trạng thái Đơn mới.';

const MSG_LEAVE_TITLE_ENCODING = 'Tiêu đề phải là văn bản hợp lệ.';
const MSG_LEAVE_TITLE_REQUIRED = 'Vui lòng nhập tiêu đề.';
const MSG_LEAVE_TITLE_TOO_LONG = 'Tiêu đề không được quá %d ký tự.';
const MSG_LEAVE_CONTENT_ENCODING = 'Nội dung phải là văn bản hợp lệ.';
const MSG_LEAVE_CONTENT_TOO_LONG = 'Nội dung vượt giới hạn lưu trữ.';
const MSG_APPROVER_REQUIRED = 'Vui lòng chọn người duyệt đang hoạt động.';
const MSG_APPROVER_UNAVAILABLE = 'Chưa có người duyệt đang hoạt động phù hợp với tài khoản của bạn.';
const MSG_LEAVE_TYPE_REQUIRED = 'Vui lòng chọn loại đơn.';
const MSG_DATE_REQUIRED = 'Vui lòng chọn %s.';
const MSG_DATE_INVALID = '%s không hợp lệ. Định dạng yêu cầu: YYYY-MM-DD.';
const MSG_DATE_IN_PAST = '%s không hợp lệ. Không thể chọn ngày trong quá khứ.';
const MSG_END_DATE_BEFORE_START = 'Ngày kết thúc phải cùng hoặc sau ngày bắt đầu.';

const MSG_ATTACHMENT_SINGLE_ONLY = 'Chỉ được chọn một tệp đính kèm.';
const MSG_ATTACHMENT_TOO_LARGE = 'Tệp đính kèm không được quá %d MB.';
const MSG_ATTACHMENT_UPLOAD_FAILED = 'Tải tệp không thành công. Vui lòng chọn lại tệp.';
const MSG_ATTACHMENT_UPLOAD_INVALID = 'Tệp tải lên không hợp lệ.';
const MSG_ATTACHMENT_READ_FAILED = 'Không đọc được tệp đính kèm.';
const MSG_ATTACHMENT_REQUIRED = 'Vui lòng chọn một tệp ảnh hoặc PDF.';
const MSG_ATTACHMENT_SELECT_AGAIN = 'Vui lòng chọn lại tệp đính kèm.';
const MSG_ATTACHMENT_NAME_INVALID = 'Tên tệp không hợp lệ hoặc dài quá %d ký tự.';
const MSG_ATTACHMENT_EMPTY = 'Tệp đính kèm không được rỗng.';
const MSG_ATTACHMENT_TYPE_INVALID = 'Chỉ chấp nhận JPG/JPEG, PNG, GIF, WebP, BMP hoặc PDF.';
const MSG_ATTACHMENT_CONTENT_INVALID = 'Nội dung tệp không đúng với định dạng ảnh hoặc PDF đã chọn.';
const MSG_ATTACHMENT_IMAGE_INVALID = 'Tệp ảnh bị lỗi hoặc không hợp lệ.';
const MSG_ATTACHMENT_PDF_INVALID = 'Tệp PDF không hợp lệ.';
const MSG_ATTACHMENT_HINT = 'JPG/JPEG, PNG, GIF, WebP, BMP hoặc PDF. Tối đa %d MB.';

const MSG_POST_DATA_UNREADABLE = 'Không đọc được dữ liệu gửi lên. Vui lòng kiểm tra tệp đính kèm, tối đa %d MB.';
const MSG_CONFIRMATION_INVALID = 'Vui lòng nhấn Tiếp theo để kiểm tra đơn trước khi lưu.';
const MSG_SAVE_CONFIRMATION_REQUIRED = 'Vui lòng nhấn Lưu lại và xác nhận trước khi lưu đơn.';
const MSG_CONFIRMATION_TITLE = 'Thông báo';
const MSG_CONFIRM_LOGOUT = 'Bạn có chắc chắn muốn đăng xuất?';
const MSG_CONFIRM_LEAVE_EXIT = 'Bạn có chắc chắn hủy các thay đổi và thoát khỏi trang?';
const MSG_CONFIRM_LEAVE_LOGOUT = 'Bạn có chắc chắn hủy các thay đổi và đăng xuất?';
const MSG_CONFIRM_SAVE = 'Bạn có chắc chắn lưu lại thay đổi ?';
const MSG_SAVE_FAILED = 'Không thể lưu đơn. Dữ liệu và tệp vẫn được giữ để bạn thử lại.';

const MSG_CONFIRM_APPROVE = 'Bạn có chắc chắn duyệt đơn này?';
const MSG_LEAVE_PROCESS_NOT_FOUND = 'Không tìm thấy đơn cần xử lý.';
const MSG_LEAVE_PROCESS_FORBIDDEN = 'Bạn không có quyền xử lý đơn này hoặc đơn không còn ở trạng thái Đơn mới.';
const MSG_CANCEL_REASON_REQUIRED = 'Vui lòng nhập lý do hủy đơn.';
const MSG_CANCEL_REASON_ENCODING = 'Lý do hủy phải là văn bản hợp lệ.';
const MSG_CANCEL_REASON_TOO_LONG = 'Lý do hủy vượt giới hạn lưu trữ.';
const MSG_LEAVE_PROCESS_FAILED = 'Không thể xử lý đơn. Vui lòng thử lại.';
const MSG_LEAVE_PROCESS_FAILED_LOG = 'Duyệt/hủy đơn thất bại: %s';

const MSG_NO_USERS_FOUND = 'Không có người dùng phù hợp.';
const MSG_NO_LEAVES_FOUND = 'Không có đơn phù hợp.';
const MSG_UPLOAD_DIRECTORY_MISSING = 'Không tìm thấy thư mục lưu tệp.';
const MSG_ATTACHMENT_WRITE_FAILED = 'Không ghi được tệp đính kèm.';
const MSG_ROLLBACK_FAILED = 'Không thể rollback giao dịch lưu đơn.';
const MSG_FAILED_ATTACHMENT_DELETE = 'Không thể xóa tệp của giao dịch lưu đơn thất bại.';
const MSG_OLD_ATTACHMENT_DELETE_FAILED = 'Không thể xóa tệp cũ sau khi sửa đơn.';
const MSG_SAVE_FAILED_LOG = 'Lưu đơn thất bại: %s';
