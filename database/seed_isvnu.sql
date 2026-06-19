-- ============================================================
-- IS-VNU Realistic Seed Data
-- Trường Quốc Tế - Đại học Quốc gia Hà Nội
-- Run AFTER campus_services_booking.sql
-- ============================================================

USE `campus_services_booking`;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Clear existing seed data ────────────────────────────────
TRUNCATE audit_logs;
TRUNCATE usage_reports;
TRUNCATE notifications;
TRUNCATE cancellations;
TRUNCATE approvals;
TRUNCATE bookings;
TRUNCATE maintenance_schedules;
TRUNCATE booking_policies;
TRUNCATE time_slots;
TRUNCATE resource_equipment;
TRUNCATE equipment;
TRUNCATE resources;
TRUNCATE resource_categories;
TRUNCATE user_roles;
TRUNCATE users;
TRUNCATE departments;
TRUNCATE roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ─── Roles ───────────────────────────────────────────────────
INSERT INTO roles (role_name, description) VALUES
('Admin',    'Quản trị viên hệ thống'),
('Student',  'Sinh viên có thể đặt tài nguyên'),
('Lecturer', 'Giảng viên phê duyệt và đặt lịch'),
('Staff',    'Nhân viên hành chính'),
('Approver', 'Người duyệt lịch chuyên trách');

-- ─── Departments (IS-VNU actual) ─────────────────────────────
INSERT INTO departments (department_name, description) VALUES
('Phòng Đào tạo & Khảo thí',        'Quản lý chương trình đào tạo và thi cử'),
('Phòng Công nghệ Thông tin',        'Hạ tầng CNTT và phòng máy tính'),
('Khoa Quản trị Kinh doanh',         'Chương trình BBA, MBA quốc tế'),
('Khoa Công nghệ Thông tin & TT',    'Chương trình IT, Data Science'),
('Khoa Khoa học Xã hội & Nhân văn',  'Ngôn ngữ, Quan hệ Quốc tế'),
('Phòng Công tác Sinh viên',         'Hỗ trợ sinh viên và ngoại khoá'),
('Phòng Cơ sở Vật chất',             'Quản lý cơ sở vật chất trường'),
('Trung tâm Nghiên cứu & Sáng tạo', 'Nghiên cứu khoa học và khởi nghiệp'),
('Khoa Ngôn ngữ Anh',               'Chương trình tiếng Anh và dịch thuật'),
('Ban Giám hiệu',                    'Lãnh đạo nhà trường');

-- ─── Users (password: admin123 / student123 / lecturer123) ───
-- Hash: $2y$12$xQl8WWuJsOfrXZXIFY6wt.ZNnIyiz01MALkDLiAFlmqVt1j5UZ0Gq = admin123
-- Hash: $2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO = student123
-- Hash: $2y$12$QktMJ5qJLmCnIXObxaGZEOD.oWwUUVwuWG2XT1aksDKGVhigqO1Oe = lecturer123

INSERT INTO users (department_id, full_name, username, email, password_hash, phone, student_code, staff_code, status) VALUES
-- Admin
(7,  'Nguyễn Thị Hương Giang',  'admin',       'admin@is.vnu.edu.vn',         '$2y$12$xQl8WWuJsOfrXZXIFY6wt.ZNnIyiz01MALkDLiAFlmqVt1j5UZ0Gq', '0243.7547.823', NULL, 'ADM001', 'active'),
-- Lecturers
(4,  'TS. Trần Đức Minh',       'lecturer',    'lecturer@is.vnu.edu.vn',      '$2y$12$QktMJ5qJLmCnIXObxaGZEOD.oWwUUVwuWG2XT1aksDKGVhigqO1Oe', '0912.334.567', NULL, 'GV001',  'active'),
(3,  'PGS.TS. Lê Thị Thu Hà',  'lethiha',     'le.thiha@is.vnu.edu.vn',      '$2y$12$QktMJ5qJLmCnIXObxaGZEOD.oWwUUVwuWG2XT1aksDKGVhigqO1Oe', '0904.112.889', NULL, 'GV002',  'active'),
(9,  'ThS. Phạm Anh Khoa',     'phamanh',     'pham.anhkhoa@is.vnu.edu.vn',  '$2y$12$QktMJ5qJLmCnIXObxaGZEOD.oWwUUVwuWG2XT1aksDKGVhigqO1Oe', '0978.220.341', NULL, 'GV003',  'active'),
(5,  'TS. Vũ Thị Lan Phương',  'vulanphuong', 'vu.lanphuong@is.vnu.edu.vn',  '$2y$12$QktMJ5qJLmCnIXObxaGZEOD.oWwUUVwuWG2XT1aksDKGVhigqO1Oe', '0965.445.778', NULL, 'GV004',  'active'),
-- Approver
(6,  'Bùi Thanh Sơn',          'approver',    'approver@is.vnu.edu.vn',      '$2y$12$6pFSYdbevPsvksEXAl3dTOdjGuGmmsferhqkvIVFx07eBuNmI.VUq',  '0243.7547.900', NULL, 'APP001', 'active'),
-- Staff
(7,  'Hoàng Thị Kim Oanh',     'staff',       'staff@is.vnu.edu.vn',         '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0243.7547.801', NULL, 'NV001',  'active'),
(2,  'Nguyễn Văn Tú',         'nguyentu',    'nguyen.vantu@is.vnu.edu.vn',  '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0243.7547.802', NULL, 'NV002',  'active'),
-- Students
(4,  'Nguyễn Văn An',          'student',     'student@is.vnu.edu.vn',       '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0966.112.233', '21020001', NULL, 'active'),
(4,  'Trần Thị Bích Ngọc',    'bichngoc',    'tran.bichngoc@st.is.vnu.vn',  '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0977.334.455', '21020002', NULL, 'active'),
(3,  'Lê Hoàng Minh',         'lehminh',     'le.hoangminh@st.is.vnu.vn',   '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0988.556.677', '21020003', NULL, 'active'),
(3,  'Phạm Thùy Dung',        'thuydung',    'pham.thuydung@st.is.vnu.vn',  '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0911.778.899', '21020004', NULL, 'active'),
(9,  'Đỗ Quang Huy',          'quanghuy',    'do.quanghuy@st.is.vnu.vn',    '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0922.990.112', '22020005', NULL, 'active'),
(4,  'Nguyễn Thị Hà Linh',   'halinh',      'nguyen.halinh@st.is.vnu.vn',  '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0933.221.334', '22020006', NULL, 'active'),
(5,  'Vũ Mạnh Cường',        'manhcuong',   'vu.manhcuong@st.is.vnu.vn',   '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0944.443.556', '22020007', NULL, 'active'),
(3,  'Hoàng Anh Tuấn',        'anhtuanhi',   'hoang.anhtuan@st.is.vnu.vn',  '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0955.667.889', '22020008', NULL, 'active'),
(4,  'Bùi Thị Thanh Thảo',   'thanhthao',   'bui.thanhthao@st.is.vnu.vn',  '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0966.889.001', '23020009', NULL, 'active'),
(9,  'Phan Đức Long',         'duclong',     'phan.duclong@st.is.vnu.vn',   '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0977.112.334', '23020010', NULL, 'active'),
(5,  'Đinh Thị Phương Thảo',  'phuongthao',  'dinh.phuongthao@st.is.vnu.vn','$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0988.334.556', '23020011', NULL, 'active'),
(3,  'Trịnh Minh Khoa',       'minhkhoa',    'trinh.minhkhoa@st.is.vnu.vn', '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0999.556.778', '23020012', NULL, 'active');

-- ─── User Roles ──────────────────────────────────────────────
INSERT INTO user_roles (user_id, role_id) VALUES
(1,1),   -- admin → Admin
(2,3),   -- lecturer → Lecturer
(3,3),   -- lethiha → Lecturer
(4,3),   -- phamanh → Lecturer
(5,3),   -- vulanphuong → Lecturer
(6,5),   -- approver → Approver
(7,4),   -- staff → Staff
(8,4),   -- nguyentu → Staff
(9,2),   -- student → Student
(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2);

-- ─── Resource Categories ─────────────────────────────────────
INSERT INTO resource_categories (category_name, description, requires_approval, max_booking_hours_per_day, max_booking_hours_per_week, max_peak_slots_per_week, cancellation_deadline_hours, status) VALUES
('Phòng Tự học Nhóm',  'Phòng học nhóm yên tĩnh có bảng trắng',         0, 4.00, 10.00, 2, 12, 'active'),
('Phòng Thí nghiệm',   'Phòng lab chuyên biệt, cần phê duyệt',           1, 3.00,  8.00, 2, 24, 'active'),
('Sân Thể thao',       'Sân thể thao trong và ngoài trời',               0, 2.00,  6.00, 2,  6, 'active'),
('Phòng Hội thảo',     'Phòng họp và hội thảo học thuật',                0, 3.00,  8.00, 2, 24, 'active'),
('Phòng CLB & Hội',    'Phòng sinh hoạt câu lạc bộ sinh viên',           0, 4.00, 10.00, 2, 12, 'active'),
('Studio Sáng tạo',    'Studio ghi hình, thu âm – cần phê duyệt',        1, 2.00,  6.00, 2, 48, 'active'),
('Phòng Máy tính',     'Phòng máy tính đa năng',                         0, 3.00,  8.00, 2, 12, 'active');

-- ─── Resources ───────────────────────────────────────────────
INSERT INTO resources (category_id, resource_code, resource_name, location, capacity, description, status) VALUES
-- Phòng tự học nhóm
(1,'GSR-A101','Phòng Tự học A.101','Toà A – Tầng 1', 8,'Phòng học nhóm yên tĩnh, 1 bảng trắng, 1 màn hình TV','available'),
(1,'GSR-A102','Phòng Tự học A.102','Toà A – Tầng 1', 6,'Phòng nhỏ 6 chỗ, cửa sổ nhìn ra sân','available'),
(1,'GSR-B201','Phòng Tự học B.201','Toà B – Tầng 2',10,'Phòng rộng 10 chỗ, có projector','available'),
(1,'GSR-B202','Phòng Tự học B.202','Toà B – Tầng 2', 8,'Trang bị bảng trắng từ tính','available'),
-- Phòng thí nghiệm
(2,'LAB-C201','Phòng Máy tính Lab C.201','Toà C – Tầng 2',30,'30 máy tính i7, màn hình 24", cài đủ phần mềm','available'),
(2,'LAB-C202','Phòng Thí nghiệm Mạng C.202','Toà C – Tầng 2',20,'Thiết bị Cisco, switch, router thực hành','available'),
(2,'LAB-D101','Phòng Thí nghiệm Điện tử D.101','Toà D – Tầng 1',15,'Bộ thực hành điện tử, oscilloscope','maintenance'),
-- Sân thể thao
(3,'SPT-BBALL','Sân Bóng rổ trong nhà','Khu Thể thao – Tầng 1',20,'Sân chuẩn NBA, có 2 bảng rổ điện tử','available'),
(3,'SPT-BMINH','Sân Cầu lông 1','Khu Thể thao – Tầng 1',8, '2 sân cầu lông tiêu chuẩn','available'),
(3,'SPT-BMINH2','Sân Cầu lông 2','Khu Thể thao – Tầng 2',8, '2 sân cầu lông tiêu chuẩn','available'),
(3,'SPT-TENIS','Sân Tennis','Khu ngoài trời',4,'Sân tennis mặt cứng, đèn chiếu sáng','available'),
-- Phòng hội thảo
(4,'MTG-E301','Phòng Hội thảo E.301','Toà E – Tầng 3',50,'Phòng hội thảo lớn, projector 4K, hệ thống âm thanh','available'),
(4,'MTG-E302','Phòng Họp E.302','Toà E – Tầng 3',20,'Phòng họp vừa, bàn tròn, TV 65"','available'),
(4,'MTG-E303','Phòng Họp E.303','Toà E – Tầng 3',12,'Phòng họp nhỏ, không gian ấm cúng','available'),
-- Phòng CLB
(5,'CLB-F101','Phòng CLB Tiếng Anh','Toà F – Tầng 1',25,'Phòng sinh hoạt CLB English Speaking Union','available'),
(5,'CLB-F102','Phòng CLB Khởi nghiệp','Toà F – Tầng 1',20,'Không gian co-working cho dự án khởi nghiệp','available'),
(5,'CLB-F103','Phòng CLB Âm nhạc','Toà F – Tầng 1',15,'Phòng tập nhạc cụ, cách âm tốt','available'),
-- Studio
(6,'STD-G001','Studio Ghi hình G.001','Toà G – Tầng 1',10,'Studio chuyên nghiệp, phông xanh, đèn ring','available'),
(6,'STD-G002','Studio Podcast G.002','Toà G – Tầng 1', 6,'Phòng thu âm podcast, soundproof','available'),
-- Phòng máy tính
(7,'PC-H101','Phòng Máy tính H.101','Toà H – Tầng 1',40,'40 máy tính, cài MS Office, Adobe, AutoCAD','available'),
(7,'PC-H102','Phòng Máy tính H.102','Toà H – Tầng 1',40,'Tương tự H.101, dùng cho thi cử','available');

-- ─── Equipment ───────────────────────────────────────────────
INSERT INTO equipment (equipment_name, description, quantity, status) VALUES
('Projector 4K',        'Máy chiếu Epson 4K WUXGA',    12, 'available'),
('Bảng trắng từ tính',  'Bảng viết bút lông 120x240cm', 20, 'available'),
('Bộ micro không dây',  'Micro Shure wireless set',      8, 'available'),
('Máy quay 4K',         'Sony FX3 body + lens',          4, 'available'),
('Máy tính để bàn',     'Dell Optiplex i7 32GB',        80, 'available'),
('Bộ thiết bị an toàn', 'Kính bảo hộ + găng tay',       30, 'available'),
('Smart TV 75"',        'Samsung QLED 75" 4K',           8, 'available'),
('Đèn studio ring',     'Ring light 18" 3200-5600K',     6, 'available'),
('Bộ switch Cisco',     'Cisco Catalyst 2960 24-port',   10,'available'),
('Đàn piano điện',      'Yamaha P-125B 88 phím',         2, 'available');

-- ─── Resource Equipment ──────────────────────────────────────
INSERT INTO resource_equipment (resource_id, equipment_id, quantity) VALUES
(1,2,1),(1,1,1),(1,7,1),
(2,2,1),
(3,2,1),(3,1,1),(3,7,1),
(4,2,1),
(5,5,30),(5,1,1),
(6,5,20),(6,9,5),
(7,5,15),
(12,1,2),(12,3,2),(12,7,1),
(13,7,1),(13,1,1),
(14,7,1),
(18,4,2),(18,3,2),(18,8,4),
(19,3,2),(19,4,1),
(20,5,40),(20,1,1),
(21,5,40),(21,1,1),
(17,10,2);

-- ─── Booking Policies ────────────────────────────────────────
INSERT INTO booking_policies (category_id, policy_name, max_duration_hours, weekly_quota, max_peak_slots_per_week, cancellation_deadline_hours, requires_approval, auto_approval_enabled, is_active) VALUES
(1,'Chính sách Phòng Tự học',    2.00,5,2,12,0,1,1),
(2,'Chính sách Lab – Phê duyệt', 3.00,3,2,24,1,0,1),
(3,'Chính sách Sân Thể thao',    2.00,4,2, 6,0,1,1),
(4,'Chính sách Phòng Hội thảo',  3.00,4,2,24,0,1,1),
(5,'Chính sách Phòng CLB',       4.00,5,2,12,0,1,1),
(6,'Chính sách Studio – Phê duyệt',2.00,2,2,48,1,0,1),
(7,'Chính sách Phòng Máy tính',  3.00,5,2,12,0,1,1);

-- ─── Time Slots ──────────────────────────────────────────────
-- Mon–Fri for Study Rooms (resource 1–4)
INSERT INTO time_slots (resource_id, day_of_week, start_time, end_time, is_peak, is_active) VALUES
(1,1,'07:30:00','09:30:00',1,1),(1,1,'09:30:00','11:30:00',0,1),(1,1,'13:00:00','15:00:00',1,1),(1,1,'15:00:00','17:00:00',0,1),
(1,2,'07:30:00','09:30:00',1,1),(1,2,'09:30:00','11:30:00',0,1),(1,2,'13:00:00','15:00:00',1,1),(1,2,'15:00:00','17:00:00',0,1),
(1,3,'07:30:00','09:30:00',1,1),(1,3,'09:30:00','11:30:00',0,1),(1,3,'13:00:00','15:00:00',1,1),
(1,4,'07:30:00','09:30:00',1,1),(1,4,'09:30:00','11:30:00',0,1),(1,4,'13:00:00','15:00:00',1,1),
(1,5,'07:30:00','09:30:00',0,1),(1,5,'09:30:00','11:30:00',0,1),
(5,1,'07:30:00','10:30:00',1,1),(5,1,'10:30:00','12:00:00',0,1),(5,1,'13:00:00','16:00:00',1,1),
(5,2,'07:30:00','10:30:00',1,1),(5,2,'13:00:00','16:00:00',1,1),
(8,1,'17:00:00','19:00:00',1,1),(8,1,'19:00:00','21:00:00',1,1),
(8,2,'17:00:00','19:00:00',1,1),(8,2,'19:00:00','21:00:00',1,1),
(9,1,'07:00:00','09:00:00',0,1),(9,1,'17:00:00','19:00:00',1,1),(9,1,'19:00:00','21:00:00',1,1),
(18,1,'09:00:00','12:00:00',1,1),(18,3,'14:00:00','17:00:00',1,1),(18,5,'09:00:00','12:00:00',0,1);
