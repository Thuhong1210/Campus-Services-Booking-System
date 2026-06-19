-- ============================================================
-- IS-VNU Realistic Seed Data – Part 2: Bookings & Transactions
-- ============================================================

USE `campus_services_booking`;

-- ─── Bookings ────────────────────────────────────────────────
-- user_id reference: 9=student(VanAn), 10=BichNgoc, 11=LeHoangMinh,
-- 12=ThuyDung, 13=QuangHuy, 14=HaLinh, 15=ManhCuong,
-- 16=AnhTuan, 17=ThanhThao, 18=DucLong, 19=PhuongThao, 20=MinhKhoa
-- resource_id: 1-4=study, 5-7=lab, 8-11=sports, 12-14=meeting,
--              15-17=club, 18-19=studio, 20-21=pc

INSERT INTO bookings (booking_reference, user_id, resource_id, start_datetime, end_datetime, purpose, additional_notes, status, requires_approval) VALUES

-- ── Upcoming / active bookings ──
('BK-2026-0001', 9,  1, DATE_ADD(CURDATE(), INTERVAL  1 DAY) + INTERVAL  8 HOUR, DATE_ADD(CURDATE(), INTERVAL  1 DAY) + INTERVAL 10 HOUR, 'Họp nhóm đề án Hệ thống Thông tin', 'Cần bảng trắng và marker', 'approved', 0),
('BK-2026-0002', 10, 3, DATE_ADD(CURDATE(), INTERVAL  1 DAY) + INTERVAL 13 HOUR, DATE_ADD(CURDATE(), INTERVAL  1 DAY) + INTERVAL 15 HOUR, 'Ôn thi giữa kỳ môn Giải tích', NULL, 'approved', 0),
('BK-2026-0003', 11, 5, DATE_ADD(CURDATE(), INTERVAL  2 DAY) + INTERVAL  8 HOUR, DATE_ADD(CURDATE(), INTERVAL  2 DAY) + INTERVAL 10 HOUR, 'Thực hành lập trình Python – INS3041', 'Cần cài Anaconda sẵn', 'pending', 1),
('BK-2026-0004', 12, 8, DATE_ADD(CURDATE(), INTERVAL  2 DAY) + INTERVAL 17 HOUR, DATE_ADD(CURDATE(), INTERVAL  2 DAY) + INTERVAL 19 HOUR, 'Tập bóng rổ CLB Sao Đỏ IS-VNU', NULL, 'approved', 0),
('BK-2026-0005', 13, 18,DATE_ADD(CURDATE(), INTERVAL  3 DAY) + INTERVAL  9 HOUR, DATE_ADD(CURDATE(), INTERVAL  3 DAY) + INTERVAL 11 HOUR, 'Quay video thuyết trình Marketing', 'Cần phông xanh và đèn ring', 'pending', 1),
('BK-2026-0006', 14, 12,DATE_ADD(CURDATE(), INTERVAL  3 DAY) + INTERVAL 13 HOUR, DATE_ADD(CURDATE(), INTERVAL  3 DAY) + INTERVAL 16 HOUR, 'Hội thảo CLB Tiếng Anh – Debating', 'Mời khách: ThS. Phạm Anh Khoa', 'approved', 0),
('BK-2026-0007', 15, 2, DATE_ADD(CURDATE(), INTERVAL  4 DAY) + INTERVAL  9 HOUR, DATE_ADD(CURDATE(), INTERVAL  4 DAY) + INTERVAL 11 HOUR, 'Học nhóm Business Law', NULL, 'approved', 0),
('BK-2026-0008', 16, 9, DATE_ADD(CURDATE(), INTERVAL  4 DAY) + INTERVAL 17 HOUR, DATE_ADD(CURDATE(), INTERVAL  4 DAY) + INTERVAL 19 HOUR, 'Tập cầu lông – giải IS-VNU Open', NULL, 'approved', 0),
('BK-2026-0009', 17, 6, DATE_ADD(CURDATE(), INTERVAL  5 DAY) + INTERVAL  8 HOUR, DATE_ADD(CURDATE(), INTERVAL  5 DAY) + INTERVAL 10 HOUR, 'Thực hành cấu hình Router Cisco', 'Đội 4 người, cần 4 bộ switch', 'pending', 1),
('BK-2026-0010', 18, 15,DATE_ADD(CURDATE(), INTERVAL  5 DAY) + INTERVAL 14 HOUR, DATE_ADD(CURDATE(), INTERVAL  5 DAY) + INTERVAL 17 HOUR, 'Sinh hoạt CLB English Speaking Union', NULL, 'approved', 0),
('BK-2026-0011', 19, 4, DATE_ADD(CURDATE(), INTERVAL  6 DAY) + INTERVAL  9 HOUR, DATE_ADD(CURDATE(), INTERVAL  6 DAY) + INTERVAL 11 HOUR, 'Nghiên cứu nhóm – đề tài NCKH', NULL, 'approved', 0),
('BK-2026-0012', 20, 13,DATE_ADD(CURDATE(), INTERVAL  7 DAY) + INTERVAL 13 HOUR, DATE_ADD(CURDATE(), INTERVAL  7 DAY) + INTERVAL 15 HOUR, 'Họp Ban chấp hành CLB Khởi nghiệp', 'Chuẩn bị Demo Day tháng 7', 'approved', 0),
('BK-2026-0013',  9, 19,DATE_ADD(CURDATE(), INTERVAL  7 DAY) + INTERVAL  9 HOUR, DATE_ADD(CURDATE(), INTERVAL  7 DAY) + INTERVAL 11 HOUR, 'Thu âm podcast "IT Talks IS-VNU"', 'Tập 3 – Khách mời TS. Trần Đức Minh', 'pending', 1),
('BK-2026-0014', 10, 1, DATE_ADD(CURDATE(), INTERVAL  8 DAY) + INTERVAL  7 HOUR + INTERVAL 30 MINUTE, DATE_ADD(CURDATE(), INTERVAL 8 DAY) + INTERVAL 9 HOUR + INTERVAL 30 MINUTE, 'Ôn luyện IELTS nhóm', NULL, 'approved', 0),
('BK-2026-0015', 11, 20,DATE_ADD(CURDATE(), INTERVAL  1 DAY) + INTERVAL 13 HOUR, DATE_ADD(CURDATE(), INTERVAL  1 DAY) + INTERVAL 15 HOUR, 'Thực hành đồ án môn Web Development', 'Cần cài Node.js và VS Code', 'approved', 0),

-- ── Past / completed bookings ──
('BK-2026-0016',  9, 1, DATE_ADD(CURDATE(), INTERVAL  -7 DAY) + INTERVAL  8 HOUR, DATE_ADD(CURDATE(), INTERVAL -7 DAY) + INTERVAL 10 HOUR, 'Họp nhóm đề cương đồ án', NULL, 'completed', 0),
('BK-2026-0017', 12, 8, DATE_ADD(CURDATE(), INTERVAL  -5 DAY) + INTERVAL 17 HOUR, DATE_ADD(CURDATE(), INTERVAL -5 DAY) + INTERVAL 19 HOUR, 'Thi đấu giao hữu bóng rổ', NULL, 'completed', 0),
('BK-2026-0018', 14, 5, DATE_ADD(CURDATE(), INTERVAL  -4 DAY) + INTERVAL  8 HOUR, DATE_ADD(CURDATE(), INTERVAL -4 DAY) + INTERVAL 10 HOUR, 'Thực hành AI lab – môn Machine Learning', NULL, 'completed', 1),
('BK-2026-0019', 16, 12,DATE_ADD(CURDATE(), INTERVAL  -3 DAY) + INTERVAL 13 HOUR, DATE_ADD(CURDATE(), INTERVAL -3 DAY) + INTERVAL 16 HOUR, 'Hội thảo "AI và tương lai nghề nghiệp"', '50 người tham dự', 'completed', 0),
('BK-2026-0020', 13, 18,DATE_ADD(CURDATE(), INTERVAL -10 DAY) + INTERVAL  9 HOUR, DATE_ADD(CURDATE(), INTERVAL-10 DAY) + INTERVAL 11 HOUR, 'Quay clip giới thiệu khoa', NULL, 'completed', 1),
('BK-2026-0021', 15, 9, DATE_ADD(CURDATE(), INTERVAL  -6 DAY) + INTERVAL 17 HOUR, DATE_ADD(CURDATE(), INTERVAL -6 DAY) + INTERVAL 19 HOUR, 'Tập cầu lông buổi chiều', NULL, 'completed', 0),
('BK-2026-0022', 17, 3, DATE_ADD(CURDATE(), INTERVAL  -8 DAY) + INTERVAL  7 HOUR + INTERVAL 30 MINUTE, DATE_ADD(CURDATE(), INTERVAL -8 DAY) + INTERVAL 10 HOUR, 'Thực hành lab CSDL Oracle', NULL, 'completed', 1),
('BK-2026-0023', 19, 15,DATE_ADD(CURDATE(), INTERVAL  -2 DAY) + INTERVAL 14 HOUR, DATE_ADD(CURDATE(), INTERVAL -2 DAY) + INTERVAL 17 HOUR, 'Sinh hoạt cuối kỳ CLB English Speaking', NULL, 'completed', 0),

-- ── Cancelled bookings ──
('BK-2026-0024', 10, 6, DATE_ADD(CURDATE(), INTERVAL -12 DAY) + INTERVAL 8 HOUR,  DATE_ADD(CURDATE(), INTERVAL-12 DAY) + INTERVAL 10 HOUR, 'Thực hành cấu hình mạng', NULL, 'cancelled', 1),
('BK-2026-0025', 20, 2, DATE_ADD(CURDATE(), INTERVAL  -9 DAY) + INTERVAL 13 HOUR, DATE_ADD(CURDATE(), INTERVAL -9 DAY) + INTERVAL 15 HOUR, 'Học nhóm Kinh tế vĩ mô', NULL, 'cancelled', 0),
('BK-2026-0026', 18, 9, DATE_ADD(CURDATE(), INTERVAL  -4 DAY) + INTERVAL 17 HOUR, DATE_ADD(CURDATE(), INTERVAL -4 DAY) + INTERVAL 19 HOUR, 'Luyện cầu lông', NULL, 'cancelled', 0),

-- ── Rejected bookings ──
('BK-2026-0027', 11, 18,DATE_ADD(CURDATE(), INTERVAL -14 DAY) + INTERVAL 8 HOUR,  DATE_ADD(CURDATE(), INTERVAL-14 DAY) + INTERVAL 11 HOUR, 'Quay MV nhạc sinh viên', 'Yêu cầu 3 giờ liên tục', 'rejected', 1),
('BK-2026-0028', 13, 5, DATE_ADD(CURDATE(), INTERVAL -11 DAY) + INTERVAL 8 HOUR,  DATE_ADD(CURDATE(), INTERVAL-11 DAY) + INTERVAL 11 HOUR, 'Thực hành Lab A.I nhóm lớp K23', NULL, 'rejected', 1);

-- ─── Approvals ───────────────────────────────────────────────
INSERT INTO approvals (booking_id, approver_id, decision, comment, decided_at) VALUES
(3,  2, 'approved', 'Chấp nhận – phù hợp kế hoạch môn học INS3041',              NOW() - INTERVAL 2 DAY),
(9,  2, 'approved', 'Chấp nhận – bài thực hành mạng nằm trong chương trình',     NOW() - INTERVAL 1 DAY),
(13, 6, 'approved', 'Chấp nhận – nội dung podcast học thuật phù hợp',            NOW() - INTERVAL 1 DAY),
(18, 2, 'approved', 'Chấp nhận – thực hành AI lab trong kế hoạch',               NOW() - INTERVAL 5 DAY),
(20, 6, 'approved', 'Chấp nhận – sử dụng studio cho truyền thông nhà trường',    NOW() - INTERVAL 11 DAY),
(22, 2, 'approved', 'Chấp nhận – thực hành bắt buộc môn CSDL',                  NOW() - INTERVAL 9 DAY),
(24, 2, 'rejected', 'Từ chối – phòng đang được bảo trì thiết bị mạng',           NOW() - INTERVAL 13 DAY),
(27, 6, 'rejected', 'Từ chối – vượt quá 2 giờ/lần, không phải mục đích học thuật', NOW() - INTERVAL 15 DAY),
(28, 2, 'rejected', 'Từ chối – slot đã được đặt bởi lớp học chính thức',         NOW() - INTERVAL 12 DAY);

-- Update statuses
UPDATE bookings SET status='approved'  WHERE id IN (3,9,13,18,20,22);
UPDATE bookings SET status='rejected'  WHERE id IN (27,28);

-- ─── Cancellations ───────────────────────────────────────────
INSERT INTO cancellations (booking_id, cancelled_by, reason, cancelled_at) VALUES
(24, 10, 'Lịch thi đột xuất trùng giờ, không thể tham gia', NOW() - INTERVAL 13 DAY),
(25, 20, 'Nhóm tự giải tán, không cần phòng nữa',           NOW() - INTERVAL 10 DAY),
(26, 18, 'Đối tác tập cùng bận, hoãn lịch tập',             NOW() - INTERVAL 5 DAY);

-- ─── Notifications ───────────────────────────────────────────
INSERT INTO notifications (user_id, booking_id, title, message, type, is_read) VALUES
(9,  1,  'Đặt phòng được xác nhận',  'Đặt phòng BK-2026-0001 cho Phòng Tự học A.101 đã được duyệt.', 'booking_approved', 1),
(11, 3,  'Đang chờ phê duyệt',       'Đặt phòng BK-2026-0003 đang chờ giảng viên phê duyệt.', 'pending_approval', 0),
(2,  3,  'Yêu cầu phê duyệt mới',   'Sinh viên Lê Hoàng Minh đặt Lab C.201 – cần xét duyệt.', 'pending_approval', 0),
(13, 5,  'Đang chờ phê duyệt',       'Đặt phòng BK-2026-0005 Studio G.001 đang chờ phê duyệt.', 'pending_approval', 0),
(6,  5,  'Yêu cầu phê duyệt mới',   'Đỗ Quang Huy yêu cầu đặt Studio Ghi hình – cần xét duyệt.', 'pending_approval', 0),
(14, 6,  'Đặt phòng được xác nhận',  'BK-2026-0006 Phòng Hội thảo E.301 đã được xác nhận.', 'booking_approved', 1),
(9,  13, 'Đang chờ phê duyệt',       'BK-2026-0013 Studio Podcast đang chờ phê duyệt.', 'pending_approval', 0),
(6,  13, 'Yêu cầu phê duyệt mới',   'Nguyễn Văn An yêu cầu Studio Podcast G.002 – cần xét duyệt.', 'pending_approval', 0),
(10, 24, 'Đặt phòng bị từ chối',     'BK-2026-0024 bị từ chối: phòng đang bảo trì thiết bị mạng.', 'booking_rejected', 1),
(11, 27, 'Đặt phòng bị từ chối',     'BK-2026-0027 bị từ chối: vượt quá thời gian và không đúng mục đích.', 'booking_rejected', 1),
(18, 26, 'Đặt phòng đã huỷ',        'BK-2026-0026 sân cầu lông đã được huỷ thành công.', 'booking_cancelled', 1),
(1,  NULL,'Thông báo hệ thống',     'Hệ thống đặt phòng IS-VNU đã cập nhật lên phiên bản mới.', 'system', 0),
(9,  16, 'Đặt phòng hoàn thành',    'BK-2026-0016 đã kết thúc. Cảm ơn bạn đã sử dụng dịch vụ.', 'booking_approved', 1),
(14, 18, 'Đặt phòng hoàn thành',    'BK-2026-0018 Lab AI đã hoàn thành thành công.', 'booking_approved', 1);

-- ─── Maintenance Schedules ───────────────────────────────────
INSERT INTO maintenance_schedules (resource_id, maintenance_start, maintenance_end, reason, status, created_by) VALUES
(7,  DATE_ADD(CURDATE(), INTERVAL -5 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Bảo dưỡng thiết bị oscilloscope và thay mới linh kiện', 'in_progress', 1),
(21, DATE_ADD(CURDATE(), INTERVAL  7 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), 'Nâng cấp RAM và SSD cho 40 máy tính phòng H.102', 'scheduled',   1),
(10, DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY),'Sơn lại mặt sân và thay lưới cầu lông 2', 'scheduled', 1);

-- ─── Usage Reports ───────────────────────────────────────────
INSERT INTO usage_reports (resource_id, report_type, period_start, period_end, total_bookings, total_approved, total_rejected, total_cancelled, total_hours, peak_hour_bookings, utilization_rate, generated_at) VALUES
(1,  'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01'), LAST_DAY(CURDATE()), 22, 18, 1, 3, 44.0, 10, 52.4, NOW()),
(5,  'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01'), LAST_DAY(CURDATE()), 18, 14, 2, 2, 45.0, 12, 49.8, NOW()),
(8,  'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01'), LAST_DAY(CURDATE()), 15, 13, 0, 2, 30.0, 10, 41.2, NOW()),
(12, 'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01'), LAST_DAY(CURDATE()), 10,  9, 0, 1, 28.0,  6, 37.5, NOW()),
(18, 'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01'), LAST_DAY(CURDATE()),  8,  5, 2, 1, 16.0,  6, 28.0, NOW());

-- ─── Audit Logs ──────────────────────────────────────────────
INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address, created_at) VALUES
(1, 'login',          'users',    1,  NULL, '{"email":"admin@is.vnu.edu.vn"}',         '127.0.0.1', NOW() - INTERVAL 30 MINUTE),
(9, 'login',          'users',    9,  NULL, '{"email":"student@is.vnu.edu.vn"}',       '192.168.1.10', NOW() - INTERVAL 2 HOUR),
(9, 'create_booking', 'bookings', 1,  NULL, '{"reference":"BK-2026-0001","status":"pending"}', '192.168.1.10', NOW() - INTERVAL 3 DAY),
(2, 'approve_booking','bookings', 3,  '{"status":"pending"}','{"status":"approved"}',  '10.0.0.5', NOW() - INTERVAL 2 DAY),
(6, 'approve_booking','bookings', 13, '{"status":"pending"}','{"status":"approved"}',  '10.0.0.6', NOW() - INTERVAL 1 DAY),
(2, 'reject_booking', 'bookings', 27, '{"status":"pending"}','{"status":"rejected"}',  '10.0.0.5', NOW() - INTERVAL 15 DAY),
(10,'cancel_booking', 'bookings', 24, '{"status":"pending"}','{"status":"cancelled"}', '192.168.1.11', NOW() - INTERVAL 13 DAY),
(1, 'create_resource','resources',20, NULL, '{"code":"PC-H101","name":"Phòng Máy tính H.101"}', '127.0.0.1', NOW() - INTERVAL 30 DAY),
(1, 'update_policy',  'booking_policies',2, '{"requires_approval":0}','{"requires_approval":1}', '127.0.0.1', NOW() - INTERVAL 20 DAY),
(7, 'create_maintenance','maintenance_schedules',1,NULL,'{"resource_id":7,"reason":"Bảo dưỡng oscilloscope"}','127.0.0.1', NOW() - INTERVAL 5 DAY);
