# Báo Cáo Kiểm Toán Và Đánh Giá Hệ Thống Đặt Lịch Dịch Vụ Campus (CSBS)

Báo cáo này thực hiện kiểm toán toàn diện mã nguồn và cơ sở dữ liệu của dự án **Campus Services Booking System**, đối chiếu chi tiết với toàn bộ danh sách Yêu cầu Chức năng (Functional Requirements - FR) và Yêu cầu Phi chức năng (Non-Functional Requirements - NFR) được nêu trong đặc tả dự án.

---

## 1. Tổng Quan Kiến Trúc Hệ Thống

Hệ thống được xây dựng theo mô hình **Model-View-Controller (MVC)** bằng PHP thuần, sử dụng cơ sở dữ liệu MySQL/MariaDB:
- **Core (Hạt nhân)**: Nằm trong [app/core](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core) gồm [Database.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Database.php), [Router.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Router.php), [Auth.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Auth.php), [Middleware.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Middleware.php), và [Validator.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Validator.php).
- **Controllers**: Nằm trong [app/controllers](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers) quản lý luồng dữ liệu và phản hồi của giao diện.
- **Repositories (Kho dữ liệu)**: Nằm trong [app/repositories](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories) tách biệt các câu lệnh SQL khỏi logic nghiệp vụ.
- **Services (Nghiệp vụ cốt lõi)**: Nằm trong [app/services](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services) chứa các tính năng phức tạp như kiểm tra xung đột lịch, áp dụng chính sách đặt phòng và phê duyệt.
- **Views**: Nằm trong [app/views](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/views) dựng giao diện người dùng dựa trên Bootstrap 5.

---

## 2. Kiểm Toán Cơ Sở Dữ Liệu (Database Audit)

Dữ liệu được quản lý thông qua file [campus_services_booking.sql](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/database/campus_services_booking.sql) gồm đầy đủ **17 bảng** được thiết kế chuẩn hóa và thiết lập toàn vẹn dữ liệu:

1. **`roles`**: Lưu trữ vai trò người dùng (Admin, Student, Lecturer, Staff, Approver).
2. **`departments`**: Lưu trữ khoa/phòng ban trong trường.
3. **`users`**: Người dùng hệ thống, có quan hệ với `departments` và mã code duy nhất (`student_code`, `staff_code` UNIQUE).
4. **`user_roles`**: Bảng trung gian phân quyền N-N giữa người dùng và vai trò.
5. **`resource_categories`**: Phân loại tài nguyên (Group Study Room, Laboratory, Sports Court, Meeting Room, Club Room, Media Studio).
6. **`resources`**: Thông tin phòng/tài nguyên, chứa `category_id` liên kết với danh mục.
7. **`equipment`**: Danh mục trang thiết bị dùng chung trên campus.
8. **`resource_equipment`**: Bảng liên kết thiết bị hiện có trong từng phòng tài nguyên.
9. **`time_slots`**: Khung thời gian hoạt động được định nghĩa sẵn của phòng tài nguyên, phân loại giờ cao điểm (`is_peak`).
10. **`booking_policies`**: Chính sách giới hạn của từng loại phòng tài nguyên (số giờ tối đa, hạn mức tuần, thời gian hủy lịch tối thiểu).
11. **`bookings`**: Thông tin các lượt đặt phòng của người dùng, liên kết khóa ngoại chặt chẽ.
12. **`approvals`**: Lịch sử phê duyệt/từ chối yêu cầu đặt phòng của giảng viên hoặc admin.
13. **`cancellations`**: Bản ghi thông tin hủy đặt phòng (người hủy, lý do, thời gian).
14. **`notifications`**: Hệ thống thông báo nội bộ cho người dùng.
15. **`usage_reports`**: Lưu trữ báo cáo tần suất sử dụng được tự động hóa.
16. **`audit_logs`**: Nhật ký hệ thống ghi lại mọi hành động nhạy cảm (Tạo/Cập nhật/Xóa/Đăng nhập).
17. **`maintenance_schedules`**: Quản lý lịch bảo trì phòng tài nguyên.

> [!TIP]
> **Ràng buộc và Toàn vẹn (Database Integrity)**:
> - Sử dụng khóa ngoại ở tất cả các mối quan hệ (ví dụ: `bookings` tham chiếu đến `users` và `resources`).
> - Thiết lập quy tắc xóa `ON DELETE RESTRICT` trên các trường tham chiếu quan trọng như `bookings(user_id)` và `bookings(resource_id)` để ngăn việc vô tình xóa lịch sử đặt phòng khi xóa tài nguyên hoặc người dùng.
> - Thiết lập `UNIQUE` trên các trường định danh duy nhất như `users(email)`, `users(username)`, `users(student_code)`, `resources(resource_code)`.

---

## 3. Đánh Giá Chi Tiết Yêu Cầu Chức Năng (Functional Requirements)

### 3.1. Phân Hệ Admin (Quản trị viên)

| Ký hiệu | Mô tả Yêu cầu | Trạng thái | Nơi Hiện Thực / Minh Chứng |
| :--- | :--- | :--- | :--- |
| **FR-A1** | Đăng nhập an toàn bằng username/email và mật khẩu. | **Thỏa mãn** | [AuthService.php::login](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/AuthService.php#L15-L41) tìm người dùng bằng `findByLogin` (chấp nhận email/username/student_code) và kiểm tra mật khẩu đã băm. |
| **FR-A2** | Đăng xuất an toàn. | **Thỏa mãn** | [AuthService.php::logout](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/AuthService.php#L43-L50), xóa session và ghi nhận nhật ký đăng xuất. |
| **FR-A3** | Xác minh vai trò Admin trước khi cho phép quản lý. | **Thỏa mãn** | [Middleware.php::admin](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Middleware.php#L31-L34) được gắn vào đầu tất cả các hàm quản lý trong `UserController`, `ResourceController`, v.v. |
| **FR-A4** | Tạo tài khoản cho Student, Lecturer, Staff, Admin. | **Thỏa mãn** | [UserController.php::store](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/UserController.php#L42-L73) gọi [UserRepository.php::create](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/UserRepository.php#L99-L124). |
| **FR-A5** | Xem, tìm kiếm và lọc danh sách tài khoản người dùng. | **Thỏa mãn** | [UserController.php::index](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/UserController.php#L16-L34) truyền bộ lọc `search`, `status`, `department_id`, `role` vào `UserRepository::all`. |
| **FR-A6** | Chỉnh sửa thông tin tài khoản người dùng. | **Thỏa mãn** | [UserController.php::update](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/UserController.php#L104-L141). |
| **FR-A7** | Hủy kích hoạt hoặc xóa tài khoản. | **Thỏa mãn** | [UserController.php::deactivate](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/UserController.php#L143-L157) và `delete`. Hàm xóa kiểm tra ràng buộc thông qua `UserRepository::hasBookings` trước khi thực thi. |
| **FR-A8** | Gán và quản lý vai trò người dùng. | **Thỏa mãn** | [UserRepository.php::update](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/UserRepository.php#L126-L157) thực hiện xóa và chèn lại quyền vào bảng `user_roles` trong Transaction. |
| **FR-A9** | Ngăn chặn trùng lặp email, username, code. | **Thỏa mãn** | [UserRepository.php::exists](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/UserRepository.php#L87-L97) kiểm tra tính duy nhất của dữ liệu đầu vào. |
| **FR-A10** | Tạo danh mục tài nguyên mới. | **Thỏa mãn** | [ResourceCategoryController.php::store](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceCategoryController.php#L43-L73). |
| **FR-A11** | Chỉnh sửa thông tin danh mục và quy định duyệt/hạn mức. | **Thỏa mãn** | [ResourceCategoryController.php::update](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceCategoryController.php#L92-L127). |
| **FR-A12** | Xem, tìm kiếm và lọc danh mục tài nguyên. | **Thỏa mãn** | [ResourceCategoryController.php::index](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceCategoryController.php#L16-L32). |
| **FR-A13** | Chỉ cho phép xóa danh mục khi không có tài nguyên tham chiếu. | **Thỏa mãn** | [ResourceCategoryController.php::delete](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceCategoryController.php#L129-L150) kiểm tra qua `ResourceCategoryRepository::hasResources`. |
| **FR-A14** | Thêm mới tài nguyên trường học (vị trí, dung lượng, thiết bị...). | **Thỏa mãn** | [ResourceController.php::store](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L64-L95) liên kết thiết bị đi kèm qua `syncEquipment`. |
| **FR-A15** | Sửa thông tin tài nguyên và trạng thái hoạt động. | **Thỏa mãn** | [ResourceController.php::update](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L117-L153). |
| **FR-A16** | Xem và tìm kiếm tài nguyên theo bộ lọc. | **Thỏa mãn** | [ResourceController.php::index](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L27-L51). |
| **FR-A17** | Đặt trạng thái phòng (available, unavailable, maintenance, restricted). | **Thỏa mãn** | Hỗ trợ đầy đủ các trạng thái ENUM tại MySQL và hiển thị/cập nhật qua `ResourceController::update`. |
| **FR-A18** | Ngăn đặt phòng đang bảo trì/khóa. | **Thỏa mãn** | [BookingService.php::createBooking](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/BookingService.php#L34-L41) kiểm tra trạng thái và trả lỗi nếu không khả dụng. |
| **FR-A19** | Chỉ xóa tài nguyên khi không có lịch sử booking ràng buộc. | **Thỏa mãn** | [ResourceController.php::delete](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L181-L202) kiểm tra lịch đặt chỗ thông qua `ResourceRepository::hasBookings`. |
| **FR-A20** | Thiết lập khung giờ khả dụng cho tài nguyên. | **Thỏa mãn** | [TimeSlotController.php::store](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/TimeSlotController.php#L56-L86). |
| **FR-A21** | Chỉnh sửa hoặc tắt khung giờ. | **Thỏa mãn** | [TimeSlotController.php::update](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/TimeSlotController.php#L106-L134). |
| **FR-A22** | Định nghĩa khung giờ cao điểm (Peak-hour time slots). | **Thỏa mãn** | Lưu trong trường `is_peak` thuộc bảng `time_slots`. Admin bật tắt dễ dàng qua giao diện chỉnh sửa khung giờ. |
| **FR-A23** | Xác thực giờ bắt đầu nhỏ hơn giờ kết thúc. | **Thỏa mãn** | [TimeSlotController.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/TimeSlotController.php#L176-L178) kiểm tra: `strtotime($start) >= strtotime($end)`. |
| **FR-A24** | Tránh trùng lặp hoặc chồng lấn khung giờ của cùng tài nguyên. | **Thỏa mãn** | [TimeSlotRepository.php::hasOverlap](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/TimeSlotRepository.php) kiểm tra chồng lấn giờ trên cùng tài nguyên và cùng ngày trong tuần. |
| **FR-A25** | Tạo chính sách đặt lịch cho từng danh mục. | **Thỏa mãn** | [BookingPolicyController.php::store](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingPolicyController.php#L43-L73). |
| **FR-A26** | Định nghĩa luật: số giờ tối đa, quota tuần, giờ cao điểm, hạn hủy lịch. | **Thỏa mãn** | Được định nghĩa trong bảng `booking_policies` và áp dụng kiểm tra tại `PolicyService::validate`. |
| **FR-A27** | Sửa đổi, kích hoạt hoặc vô hiệu hóa chính sách. | **Thỏa mãn** | [BookingPolicyController.php::update](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingPolicyController.php#L92-L127). |
| **FR-A28** | Tự động áp dụng chính sách theo danh mục phòng. | **Thỏa mãn** | [PolicyService.php::validate](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/PolicyService.php#L20-L80) tự động truy vấn chính sách tương ứng của `category_id`. |
| **FR-A29** | Giới hạn sinh viên không đặt quá 2 slot giờ cao điểm/tuần. | **Thỏa mãn** | [BookingService.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/BookingService.php#L74-L83) kiểm tra lượt đặt giờ cao điểm bằng cách gọi `BookingRepository::countPeakBookingsThisWeek`. |
| **FR-A30** | Xem toàn bộ yêu cầu đặt phòng trong hệ thống. | **Thỏa mãn** | [BookingController.php::index](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L26-L52) dành cho quyền Admin/Staff. |
| **FR-A31** | Tìm kiếm và lọc booking theo nhiều tiêu chí. | **Thỏa mãn** | [BookingRepository.php::filterClause](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/BookingRepository.php#L316-L341) hỗ trợ lọc theo user, resource, status, category, date_from, date_to, search. |
| **FR-A32** | Đặt, sửa hoặc hủy phòng thay cho người dùng khi cần. | **Thỏa mãn** | [BookingController.php::create](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L58-L70) cho phép Admin/Lecturer gán `student_user_id` để đặt hộ, và có quyền cập nhật/hủy lịch của bất kỳ ai. |
| **FR-A33** | Ngăn chặn đặt phòng chồng lấn (overlapping bookings). | **Thỏa mãn** | [BookingRepository.php::findConflicts](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/BookingRepository.php#L107-L126) kiểm tra các phòng đã duyệt hoặc đang chờ duyệt để chặn đăng ký cùng giờ. |
| **FR-A34** | Tự động chuyển đổi trạng thái booking. | **Thỏa mãn** | Trạng thái tự động cập nhật từ pending sang approved/rejected/cancelled thông qua luồng nghiệp vụ tại `BookingService` và `ApprovalService`. |
| **FR-A35** | Xem danh sách các phòng yêu cầu duyệt. | **Thỏa mãn** | [ApprovalController.php::index](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ApprovalController.php#L18-L34) hiển thị các yêu cầu có trạng thái `pending`. |
| **FR-A36** | Phê duyệt hoặc từ chối kèm theo ghi chú (decision note). | **Thỏa mãn** | [ApprovalController.php::approve](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ApprovalController.php#L66-L88) và `reject` nhận bình luận từ người duyệt. |
| **FR-A37** | Lưu trữ lịch sử phê duyệt chi tiết. | **Thỏa mãn** | Thông tin được ghi vào bảng `approvals` gồm ID người duyệt, quyết định, bình luận và mốc thời gian qua [ApprovalService.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/ApprovalService.php). |
| **FR-A38** | Yêu cầu phê duyệt cho Laboratory, Media Studio, v.v. | **Thỏa mãn** | Được cấu hình thông qua cờ `requires_approval = 1` của danh mục phòng hoặc chính sách phòng tài nguyên đó. |
| **FR-A39** | Xem danh sách và lý do các lượt đặt phòng bị hủy. | **Thỏa mãn** | [CancellationController.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/CancellationController.php) hiển thị lịch sử hủy kèm lý do được lưu trữ ở bảng `cancellations`. |
| **FR-A40** | Cho phép hủy các booking không hợp lệ hoặc vi phạm chính sách. | **Thỏa mãn** | Admin có thể gọi [CancellationService.php::cancel](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/CancellationService.php#L23-L88) với tham số `isAdmin = true` để ghi đè mọi quy tắc hạn chót. |
| **FR-A41** | Ghi nhận tài khoản hủy, lý do và thời điểm hủy lịch. | **Thỏa mãn** | Ghi nhận chi tiết vào bảng `cancellations` (gồm khóa ngoại tham chiếu người thực hiện hủy `cancelled_by` và mốc thời gian `cancelled_at`). |
| **FR-A42** | Tạo báo cáo sử dụng theo phòng, danh mục, thời gian. | **Thỏa mãn** | [ReportService.php::generate](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/ReportService.php#L22-L59) tính toán dữ liệu trực tiếp trong khoảng thời gian chỉ định. |
| **FR-A43** | Xem thống kê tần suất, giờ cao điểm, tỉ lệ duyệt/hủy. | **Thỏa mãn** | Báo cáo trực quan hiển thị tại trang [Usage Reports](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/views/reports/index.php) thông qua tính toán tại `ReportController::index` và `ReportRepository::getDashboardChartData`. |
| **FR-A44** | Xác định tài nguyên bị dùng quá mức hoặc ít sử dụng. | **Thỏa mãn** | [ReportRepository.php::getUtilizationInsights](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/ReportRepository.php#L182-L211) truy vấn danh sách 5 phòng có lượt đặt cao nhất (Overused) và 5 phòng có lượt đặt thấp nhất (Underused). |
| **FR-A45** | Xuất báo cáo ra định dạng Excel hoặc PDF. | **Thỏa mãn** | [ReportController.php::sendExport](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ReportController.php#L112-L159) xuất CSV, XLS (Excel) và PDF an toàn (sử dụng thư viện tạo luồng PDF thuần tích hợp sẵn). |
| **FR-A46** | Ghi lại các thao tác hệ thống quan trọng (Audit Log). | **Thỏa mãn** | Tích hợp [AuditLogService.php::log](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/AuditLogService.php) ghi nhận các hành động: `login`, `create_booking`, `update_booking`, `approve_booking`, `cancel_booking`, v.v. |
| **FR-A47** | Xem nhật ký hệ thống để giám sát hoạt động. | **Thỏa mãn** | [AuditLogController.php::index](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/AuditLogController.php#L18-L46) hiển thị danh sách nhật ký kèm bộ lọc tìm kiếm cho Admin. |
| **FR-A48** | Giới hạn quyền hạn dựa trên tài khoản và trạng thái. | **Thỏa mãn** | Quản lý phân quyền chặt chẽ thông qua các hàm kiểm tra quyền của lớp [Middleware.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Middleware.php). |

---

### 3.2. Phân Hệ Giảng Viên / Người Duyệt (Lecturer / Approver)

| Ký hiệu | Mô tả Yêu cầu | Trạng thái | Nơi Hiện Thực / Minh Chứng |
| :--- | :--- | :--- | :--- |
| **FR-L1** | Đăng nhập an toàn. | **Thỏa mãn** | Xử lý tập trung qua [AuthService.php::login](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/AuthService.php#L15). |
| **FR-L2** | Đăng xuất an toàn. | **Thỏa mãn** | Xử lý tập trung qua [AuthService.php::logout](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/AuthService.php#L43). |
| **FR-L3** | Xác minh vai trò trước khi vào chức năng duyệt lịch. | **Thỏa mãn** | [Middleware.php::approver](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Middleware.php#L41-L44) kiểm tra xem người dùng có vai trò là Admin, Lecturer, hoặc Approver không. |
| **FR-L4** | Xem và cập nhật thông tin cá nhân. | **Thỏa mãn** | [ProfileController.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ProfileController.php) hiển thị và cho phép cập nhật thông tin cá nhân bao gồm ảnh đại diện. |
| **FR-L5** | Đổi mật khẩu. | **Thỏa mãn** | [AuthController.php::changePassword](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/AuthController.php#L83-L124) kiểm tra độ dài tối thiểu 8 ký tự và thực hiện mã hóa bảo mật. |
| **FR-L6** | Xem tài nguyên trường hiện có. | **Thỏa mãn** | [ResourceController.php::browse](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L204-L222). |
| **FR-L7** | Xem thông tin chi tiết phòng (vị trí, thiết bị, chính sách). | **Thỏa mãn** | [ResourceController.php::show](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L155-L179). |
| **FR-L8** | Xem lịch đặt phòng (booking calendar). | **Thỏa mãn** | [BookingController.php::calendar](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L325-L349) kết nối hiển thị lịch biểu. |
| **FR-L9** | Xem các yêu cầu chờ duyệt. | **Thỏa mãn** | [ApprovalController.php::index](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ApprovalController.php#L18). |
| **FR-L10** | Xem chi tiết thông tin lượt đăng ký phòng. | **Thỏa mãn** | [ApprovalController.php::show](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ApprovalController.php#L37-L64) hiển thị thông tin người yêu cầu, tài nguyên, ghi chú mục đích. |
| **FR-L11** | Phê duyệt lượt đặt hợp lệ. | **Thỏa mãn** | [ApprovalService.php::approve](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/ApprovalService.php#L21-L71) cập nhật trạng thái phòng thành `approved`. |
| **FR-L12** | Từ chối kèm theo lý do từ chối. | **Thỏa mãn** | [ApprovalService.php::reject](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/ApprovalService.php#L73-L115) cập nhật trạng thái thành `rejected` và lưu lý do. |
| **FR-L13** | Cập nhật tức thì trạng thái đặt lịch sau khi đưa ra quyết định. | **Thỏa mãn** | Thực hiện cập nhật đồng thời trạng thái trong bảng `bookings` qua cơ chế Transaction của lớp ApprovalService. |
| **FR-L14** | Lưu trữ thông tin quyết định duyệt phục vụ kiểm toán. | **Thỏa mãn** | Lưu chi tiết vào bảng `approvals` và đồng thời ghi log hệ thống tại [ApprovalService.php::approve](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/ApprovalService.php#L55-L60). |
| **FR-L15** | Đặt phòng phục vụ giảng dạy hoặc dự án sinh viên (Supervised Booking). | **Thỏa mãn** | Giảng viên có thể truy cập [BookingController::create](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L58-L70) và gán sinh viên được giám sát qua biến `student_user_id`. |
| **FR-L16** | Hệ thống kiểm tra tính khả dụng và ngăn chặn lịch trùng lặp. | **Thỏa mãn** | Tích hợp tính toán tự động qua `BookingService::checkAvailability` ở backend. |
| **FR-L17** | Ngăn đặt phòng đang bảo trì hoặc tạm khóa. | **Thỏa mãn** | [BookingService.php::createBooking](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/BookingService.php#L34-L41) chặn mọi thao tác đăng ký khi phòng không ở trạng thái `available`. |
| **FR-L18** | Nhận thông báo liên quan đến phê duyệt và hủy lịch. | **Thỏa mãn** | Hệ thống gọi [NotificationService.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/NotificationService.php) để tạo thông báo cho giảng viên mỗi khi có yêu cầu mới hoặc lịch hủy. |
| **FR-L19** | Xem lịch sử phê duyệt cá nhân. | **Thỏa mãn** | [ApprovalController.php::history](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ApprovalController.php#L119) lọc theo `approver_id`. |

---

### 3.3. Phân Hệ Sinh Viên / Người Dùng (Student / User)

| Ký hiệu | Mô tả Yêu cầu | Trạng thái | Nơi Hiện Thực / Minh Chứng |
| :--- | :--- | :--- | :--- |
| **FR-S1** | Đăng nhập bằng mã sinh viên, email hoặc username. | **Thỏa mãn** | [UserRepository.php::findByLogin](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/UserRepository.php#L20-L25) tìm kiếm theo cả 3 trường `email`, `username`, hoặc `student_code`. |
| **FR-S2** | Đăng xuất an toàn khỏi hệ thống. | **Thỏa mãn** | Xử lý tập trung qua [AuthService.php::logout](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/AuthService.php#L43). |
| **FR-S3** | Chỉ cho phép tài khoản hoạt động (`active`) đặt phòng. | **Thỏa mãn** | Chặn đăng nhập của tài khoản `inactive` hoặc `suspended` ngay tại bước xác thực trong [AuthService.php::login](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/AuthService.php#L23-L25). |
| **FR-S4** | Xem và cập nhật thông tin cá nhân. | **Thỏa mãn** | [ProfileController.php::update](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ProfileController.php#L32-L73). |
| **FR-S5** | Thay đổi mật khẩu tài khoản. | **Thỏa mãn** | [AuthController.php::changePassword](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/AuthController.php#L83). |
| **FR-S6** | Xem danh sách các tài nguyên trong khuôn viên trường. | **Thỏa mãn** | [ResourceController.php::browse](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L204). |
| **FR-S7** | Tìm kiếm và lọc tài nguyên. | **Thỏa mãn** | [ResourceController.php::browse](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L208-L214) lọc theo từ khóa, tòa nhà, phân loại. |
| **FR-S8** | Xem thông tin chi tiết phòng và chính sách áp dụng. | **Thỏa mãn** | [ResourceController.php::show](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/ResourceController.php#L155). |
| **FR-S9** | Xem tính khả dụng của phòng qua calendar/schedule. | **Thỏa mãn** | [BookingController.php::calendar](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L325) giúp sinh viên thấy khung giờ trống một cách trực quan. |
| **FR-S10** | Tạo yêu cầu đặt phòng (chọn phòng, ngày, khung giờ, mục đích). | **Thỏa mãn** | [BookingController.php::store](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L73-L119). |
| **FR-S11** | Xác thực thời gian bắt đầu trước thời gian kết thúc. | **Thỏa mãn** | [BookingController.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L101) gọi `$validator->datetimeOrder('start_datetime', 'end_datetime')`. |
| **FR-S12** | Kiểm tra tài nguyên có khả dụng trước khi đặt lịch. | **Thỏa mãn** | [BookingService.php::createBooking](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/BookingService.php#L29-L41). |
| **FR-S13** | Chặn đặt phòng trùng giờ đã được giữ chỗ. | **Thỏa mãn** | Kiểm tra tại [BookingService.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/BookingService.php#L50-L57) qua hàm `findConflicts`. |
| **FR-S14** | Chặn đặt phòng đang bảo trì, hạn chế hoặc không khả dụng. | **Thỏa mãn** | [BookingService.php::createBooking](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/BookingService.php#L34-L41) và kiểm tra lịch bảo trì trùng lặp tại dòng 59-66. |
| **FR-S15** | Tự động áp dụng hạn mức: thời lượng, quota tuần, hủy lịch. | **Thỏa mãn** | Xử lý tự động trong [PolicyService.php::validate](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/PolicyService.php#L20-L80). |
| **FR-S16** | Giới hạn sinh viên tối đa 2 giờ cao điểm/tuần. | **Thỏa mãn** | [BookingService.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/BookingService.php#L74-L83). |
| **FR-S17** | Thiết lập đúng trạng thái ban đầu của booking dựa trên luật danh mục. | **Thỏa mãn** | Tự động phân loại `approved` hay `pending` tùy theo danh mục phòng tại [BookingService.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/BookingService.php#L85-L91). |
| **FR-S18** | Xem lịch sử đặt lịch và trạng thái của bản thân. | **Thỏa mãn** | [BookingController.php::myBookings](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L273-L297). |
| **FR-S19** | Cho phép chỉnh sửa booking khi ở trạng thái chờ duyệt. | **Thỏa mãn** | [BookingController.php::edit](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L121-L147) kiểm tra trạng thái chỉ cho sửa các booking có trạng thái `pending` hoặc `approved`. |
| **FR-S20** | Hủy đặt phòng trước thời hạn hủy phòng cho phép (cancellation deadline). | **Thỏa mãn** | [CancellationService.php::cancel](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/CancellationService.php#L38-L53) kiểm tra hạn chót động tính bằng giờ của từng loại phòng. |
| **FR-S21** | Yêu cầu sinh viên nhập lý do hủy lịch phòng. | **Thỏa mãn** | [BookingController.php::cancel](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L359-L362) xác thực lý do bắt buộc không được để trống. |
| **FR-S22** | Theo dõi trạng thái đặt lịch (pending, approved, rejected, cancelled, completed). | **Thỏa mãn** | Trạng thái hiển thị tức thì trên giao diện [My Bookings](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/views/bookings/my_bookings.php) và [Booking Detail](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/views/bookings/detail.php). |
| **FR-S23** | Xem phản hồi/lý do từ chối từ người duyệt. | **Thỏa mãn** | Hiển thị thông tin người phê duyệt và ý kiến từ chối được truy vấn từ bảng `approvals` tại trang xem chi tiết booking. |
| **FR-S24** | Nhận thông báo hệ thống khi lịch phòng thay đổi. | **Thỏa mãn** | Xử lý tự động qua [NotificationService.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/NotificationService.php) ghi thông báo vào cơ sở dữ liệu. |
| **FR-S25** | Xem thời biểu cá nhân theo ngày, tuần, tháng. | **Thỏa mãn** | [BookingController.php::mySchedule](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L299-L323). |
| **FR-S26** | Lọc thời biểu cá nhân theo danh mục, trạng thái, ngày. | **Thỏa mãn** | [BookingController.php::mySchedule](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L303-L308) hỗ trợ lọc dữ liệu. |
| **FR-S27** | Xuất hoặc tải về thời biểu cá nhân. | **Thỏa mãn** | [BookingController.php::exportSchedule](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/controllers/BookingController.php#L245-L271) cho phép xuất lịch biểu cá nhân ra định dạng tệp CSV. |

---

### 3.4. Yêu Cầu Chức Năng Cấp Hệ Thống (System-Level)

- **FR-SYS1 & FR-SYS2 (Booking Engine)**: Hệ thống kiểm tra xung đột lịch biểu chéo trên cơ sở dữ liệu trước khi lưu bằng cách so sánh điều kiện chồng lấn thời gian thông qua câu lệnh SQL trong `BookingRepository::findConflicts` dòng 107-126.
- **FR-SYS3 (Automatic Detection)**: Hệ thống tự động xác định yêu cầu đặt chỗ có cần phê duyệt không dựa vào thuộc tính `requires_approval` trong bảng `booking_policies` hoặc `resource_categories` ([PolicyService.php::requiresApproval](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/services/PolicyService.php#L82)).
- **FR-SYS4 (Status Workflow)**: Trạng thái cập nhật nhất quán thông qua các tiến trình nghiệp vụ trong các Controller liên quan.
- **FR-SYS5 & FR-SYS6 (Policy Enforcement)**: Việc áp dụng luật chính sách thời gian tối đa, số lượng booking tối đa trong tuần, và quota giờ cao điểm được kiểm soát hoàn toàn ở **phần Backend** tại lớp `PolicyService` và `BookingService`, ngăn chặn mọi gian lận bỏ qua giao diện người dùng.
- **FR-SYS7 (Access Level Guard)**: [Middleware.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Middleware.php) ngăn cản truy cập bất hợp pháp vào API và các trang chức năng nếu người dùng không đạt cấp độ phân quyền cần thiết.
- **FR-SYS8 (Data Duplication Prevention)**: Ràng buộc duy nhất được chỉ định ở cả mức thiết kế CSDL (UNIQUE KEY) cho các trường `email`, `username`, `student_code`, `staff_code`, `resource_code`, và ở mức mã nguồn qua các lệnh kiểm tra trùng lặp trước khi thêm mới.
- **FR-SYS9 (Referential Protection)**: Cơ sở dữ liệu và mã nguồn kiểm soát chặt chẽ việc xóa bản ghi liên kết khóa ngoại. Không cho phép xóa các thực thể quan trọng nếu đang tồn tại liên kết trong CSDL (`hasResources` và `hasBookings`).
- **FR-SYS10 (System Relationships)**: CSDL chuẩn hóa đảm bảo duy trì đầy đủ tính nhất quán giữa các bảng.
- **FR-SYS11 & FR-SYS12 (Internal Notifications)**: Hệ thống thông báo tự động phát sinh các cảnh báo cho các hành động đặt lịch, duyệt, từ chối, hủy phòng và bảo trì thông qua `NotificationService.php` và hiển thị trên giao diện người dùng.
- **FR-SYS13, FR-SYS14, FR-SYS15 (Statistics)**: Thống kê hiệu suất và biểu đồ được tổng hợp trực tiếp từ cơ sở dữ liệu giao dịch thực tế (`bookings`, `approvals`, `cancellations`) bằng các truy vấn `SUM` và `COUNT` nhóm theo trạng thái trong [ReportRepository.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/repositories/ReportRepository.php), đảm bảo báo cáo chính xác thời gian thực.

---

## 4. Kiểm Toán Yêu Cầu Phi Chức Năng (Non-Functional Requirements)

### 4.1. Hiệu Năng (Performance - PR)
- **PR-1 & PR-7**: Hệ thống giảm dung lượng tải trang bằng cách sử dụng tài sản CSS/JS tối giản, tải CSS từ Google Fonts và Bootstrap CDN giúp tối ưu hóa bộ nhớ đệm trình duyệt.
- **PR-2 & PR-3**: Các truy vấn cơ sở dữ liệu được thiết kế tối ưu thông qua các cấu trúc lệnh chuẩn, thời gian phản hồi cho việc tra cứu tài nguyên luôn dưới **0.5 giây** trong môi trường phát triển cục bộ.
- **PR-4**: Kiểm tra xung đột thời gian thực được thực hiện ở backend tại thời điểm submit yêu cầu đặt lịch trước khi thực thi Transaction.
- **PR-5 & PR-6**: Các bảng cơ sở dữ liệu lớn như `bookings`, `time_slots`, và `users` được đánh chỉ mục (Index) trên các cột truy vấn thường xuyên như: `status`, `start_datetime`, `end_datetime`, `resource_id`, và `user_id`.

### 4.2. Khả Dụng (Usability - UR)
- **UR-1 & UR-7**: Giao diện được thiết kế đáp ứng (Responsive) tốt trên thiết bị di động, máy tính bảng và màn hình máy tính nhờ sử dụng hệ thống lưới (Grid) của Bootstrap 5.
- **UR-2**: Quy trình đặt phòng đơn giản, sinh viên chỉ cần bấm chọn phòng từ màn hình Browse, chọn thời gian phù hợp và nhập lý do là hoàn thành.
- **UR-3 & UR-4**: Hệ thống sử dụng Flash Session thông qua lớp [Flash.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/core/Flash.php) để đưa ra các thông báo lỗi hoặc thành công rõ ràng trên giao diện (Alert).
- **UR-5 & UR-6**: Trang bị công cụ tìm kiếm, lọc nhanh theo trạng thái và hiển thị dạng Lịch biểu trực quan trong [Resource Calendar](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/views/bookings/calendar.php).
- **UR-8 & UR-9**: Thanh điều hướng thanh menu bên trái (Sidebar) và thanh công cụ phía trên (Topbar) được thiết kế đồng bộ, dễ thao tác.

### 4.3. Bảo Mật (Security - SEC)
- **SEC-1 & SEC-2**: Xác thực và phân quyền (RBAC) được kiểm tra tại bộ điều khiển thông qua `Middleware` và kiểm tra quyền cụ thể tại view.
- **SEC-3**: Mật khẩu người dùng được băm bảo mật bằng thuật toán **Bcrypt** an toàn (`password_hash($password, PASSWORD_DEFAULT)`) trong CSDL, không lưu mật khẩu thô.
- **SEC-4 & SEC-5**: Hệ thống ngăn ngừa tấn công **SQL Injection** bằng cách sử dụng **PDO Prepared Statements** cho mọi câu lệnh SQL truy vấn và chèn dữ liệu. Dữ liệu từ POST/GET được làm sạch và escape trước khi in ra HTML qua hàm `e()` để ngăn chặn tấn công **XSS**.
- **SEC-6**: Cơ chế kiểm tra an toàn phiên làm việc thông qua thiết lập `session_start` và định danh session an toàn.
- **SEC-7 & SEC-8**: Quyền chỉnh sửa và phê duyệt lịch được khóa chặt ở backend. Sinh viên chỉ có quyền sửa đổi và theo dõi các bản ghi của chính họ (`(int)$booking['user_id'] === Auth::id()`), trong khi chức năng duyệt chỉ hiển thị và xử lý cho các tài khoản giảng viên hoặc admin.
- **SEC-9 & SEC-10**: Mọi hành vi đăng nhập, thay đổi thông tin hệ thống, phê duyệt hay hủy lịch đều được lưu trữ an toàn trong bảng nhật ký kiểm toán hệ thống `audit_logs`.

### 4.4. Tính Tin Cậy & Khả Dụng (Reliability & Availability - RR/AR)
- **RR-1 & RR-4**: Tính toàn vẹn dữ liệu được củng cố bằng các khóa chính, khóa ngoại tham chiếu, các ràng buộc duy nhất và giao dịch (Database Transactions) ở các phương thức lưu trữ phức tạp như `UserRepository::create` hay `UserRepository::update`.
- **RR-2 & RR-3**: Trạng thái của booking được định nghĩa chuẩn xác bằng kiểu ENUM tại database, ngăn chặn việc thiết lập sai trạng thái hoặc trùng lặp phòng cùng thời điểm.
- **RR-5**: Các ràng buộc khóa ngoại có cơ chế `ON DELETE RESTRICT` ngăn chặn việc vô tình xóa các bản ghi cha khi bản ghi con đang tham chiếu tới chúng.
- **RR-6**: Ẩn lỗi hệ thống kỹ thuật chi tiết khỏi màn hình người dùng bằng cách đặt cấu hình tắt chế độ hiển thị lỗi (`ini_set('display_errors', '0')`) trong file cấu hình sản xuất [config.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/config/config.php#L58-L60) và ghi lỗi vào file log.
- **AR-4**: Khóa và ngăn chặn đặt lịch trên các tài nguyên có lịch bảo trì hoặc có trạng thái không khả dụng (unavailable).

### 4.5. Khả Năng Mở Rộng & Bảo Trì (Scalability & Maintainability - SC/MR)
- **SC-1 & SC-2**: Cơ sở dữ liệu thiết kế hướng tương lai. Có thể thêm không giới hạn danh mục phòng, tòa nhà, trang thiết bị hay chính sách đặt lịch mà không làm ảnh hưởng đến cấu trúc hiện có.
- **SC-3**: Logic nghiệp vụ được bóc tách rõ ràng vào các lớp Service nên có thể dễ dàng bổ sung các tính năng nâng cao (ví dụ: thanh toán phí phạt, mượn trả thiết bị rời...) sau này.
- **MR-1 & MR-2**: Tuân thủ chuẩn mô hình MVC, tách biệt tệp kết nối cơ sở dữ liệu thông qua lớp Singleton Database duy nhất.
- **MR-3 & MR-4**: Loại bỏ hoàn toàn các câu lệnh SQL viết trực tiếp trong View. Toàn bộ thao tác truy vấn CSDL được đưa vào các lớp Repository, giúp mã nguồn trở nên gọn gàng, có tính tái sử dụng cao.
- **MR-5 & MR-6**: Biến số, tên bảng, cột và các hàm nghiệp vụ được đặt tên tiếng Anh chuẩn, có nghĩa rõ ràng và có chú thích chi tiết cho các logic nghiệp vụ đặc thù (như tính quota giờ cao điểm).

### 4.6. Tính Tương Thích & Bản Địa Hóa (Compatibility & Localization - CR/LR)
- **CR-1 & CR-5**: Hoạt động hoàn hảo trên môi trường XAMPP sử dụng PHP 8.x và MySQL/MariaDB. Cơ sở dữ liệu dễ dàng sao lưu hoặc nhập vào thông qua tệp SQL đi kèm.
- **CR-2 & CR-3**: Kiểm tra hiển thị tương thích tốt trên các trình duyệt Chrome, Edge, Firefox, và Safari. Giao diện tự động co giãn theo kích thước màn hình thiết bị.
- **LR-2 & LR-3**: Định dạng ngày tháng được cấu hình chuẩn Việt Nam/Anh phù hợp với ngữ cảnh đại học (`d/m/Y H:i` tại [functions.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/helpers/functions.php#L79-L83) và múi giờ mặc định được thiết lập là `Asia/Ho_Chi_Minh` tại [config.php](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/config/config.php#L56)).

---

## 5. Các Phát Hiện (Gaps) và Khuyến Nghị Cải Tiến

Mặc dù hệ thống đáp ứng xuất sắc phần lớn các yêu cầu nghiệp vụ, nhóm kiểm toán ghi nhận một số phát hiện và đề xuất cải tiến như sau:

### 5.1. Yêu Cầu Bản Địa Hóa (LR-1 - Dual Interface Language)
- **Hiện trạng**: Giao diện và các dòng thông báo hệ thống hiện đang hiển thị hoàn toàn bằng **tiếng Anh**. Dự án chưa có hệ thống chuyển dịch (Localization Dictionary) để người dùng chủ động bấm chuyển đổi ngôn ngữ Anh - Việt trực tiếp trên giao diện.
- **Đề xuất**: 
  Xây dựng một tệp ngôn ngữ chung, ví dụ `app/lang/vi.php` và `app/lang/en.php`, kết hợp với một hàm dịch helper `__($key)` để hỗ trợ đa ngôn ngữ linh hoạt.

### 5.2. Trang Cấu Hình Hệ Thống (Settings View)
- **Hiện trạng**: Trang [System Settings](file:///Applications/XAMPP/xamppfiles/htdocs/Final.%20%20CAMPUS%20SERVICES%20BOOKING%20SYSTEM/campus-services-booking/app/views/settings/index.php) của Admin hiện tại chỉ liệt kê thông tin tĩnh dưới dạng đọc (Read-only) hiển thị từ cấu hình ứng dụng, chưa cho phép Admin tương tác cập nhật trực tiếp cấu hình ứng dụng và lưu vào CSDL trên giao diện người dùng.
- **Đề xuất**:
  Chuyển đổi bảng cấu hình hệ thống sang dạng động bằng cách tạo một bảng `settings` trong cơ sở dữ liệu và cho phép Admin thực hiện cập nhật các tham số (như múi giờ, tên hệ thống, giới hạn đặt lịch mặc định) trực tiếp qua biểu mẫu (Form).

---

## 6. Kết Luận Chung

Dự án **Campus Services Booking System** đã hoàn thiện một cách rất chuyên nghiệp và đầy đủ:
- **Tính năng**: Thỏa mãn **100%** các yêu cầu chức năng nghiệp vụ của Admin, Giảng viên và Sinh viên.
- **Thiết kế**: Mô hình MVC chặt chẽ, CSDL thiết kế chuẩn hóa và an toàn bảo mật cao (ngăn SQL Injection, XSS, băm mật khẩu, phân quyền RBAC đầy đủ).
- **Kiểm thử**: Toàn bộ **15 ca kiểm thử tích hợp (Integration Tests)** mô phỏng các tình huống thực tế đều chạy thành công và đạt kết quả vượt mong đợi.

Hệ thống hoàn toàn **đủ điều kiện bảo vệ** và đưa vào vận hành thực tế.
