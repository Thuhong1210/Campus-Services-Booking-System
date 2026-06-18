-- Campus Services Booking System
-- INS3064 - IS-VNU

CREATE DATABASE IF NOT EXISTS `campus_services_booking`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `campus_services_booking`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS usage_reports;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS cancellations;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS maintenance_schedules;
DROP TABLE IF EXISTS booking_policies;
DROP TABLE IF EXISTS time_slots;
DROP TABLE IF EXISTS resource_equipment;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS resource_categories;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ===================== ROLES =====================
CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== DEPARTMENTS =====================
CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== USERS =====================
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_id INT UNSIGNED DEFAULT NULL,
  full_name VARCHAR(150) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  student_code VARCHAR(30) DEFAULT NULL UNIQUE,
  staff_code VARCHAR(30) DEFAULT NULL UNIQUE,
  avatar VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- ===================== USER ROLES =====================
CREATE TABLE user_roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  UNIQUE KEY uk_user_role (user_id, role_id)
) ENGINE=InnoDB;

-- ===================== RESOURCE CATEGORIES =====================
CREATE TABLE resource_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  requires_approval TINYINT(1) NOT NULL DEFAULT 0,
  max_booking_hours_per_day DECIMAL(5,2) DEFAULT 4.00,
  max_booking_hours_per_week DECIMAL(5,2) DEFAULT 10.00,
  max_peak_slots_per_week INT UNSIGNED DEFAULT 2,
  cancellation_deadline_hours INT UNSIGNED DEFAULT 24,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== RESOURCES =====================
CREATE TABLE resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  resource_code VARCHAR(50) NOT NULL UNIQUE,
  resource_name VARCHAR(150) NOT NULL,
  location VARCHAR(150) NOT NULL,
  capacity INT UNSIGNED DEFAULT 1,
  description TEXT,
  image VARCHAR(255) DEFAULT NULL,
  status ENUM('available','unavailable','maintenance','restricted') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES resource_categories(id) ON DELETE RESTRICT,
  INDEX idx_resources_category (category_id),
  INDEX idx_resources_status (status)
) ENGINE=InnoDB;

-- ===================== EQUIPMENT =====================
CREATE TABLE equipment (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipment_name VARCHAR(100) NOT NULL,
  description TEXT,
  quantity INT UNSIGNED DEFAULT 1,
  status ENUM('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== RESOURCE EQUIPMENT =====================
CREATE TABLE resource_equipment (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id INT UNSIGNED NOT NULL,
  equipment_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
  UNIQUE KEY uk_resource_equipment (resource_id, equipment_id)
) ENGINE=InnoDB;

-- ===================== TIME SLOTS =====================
CREATE TABLE time_slots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id INT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL COMMENT '0=Sunday, 6=Saturday',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_peak TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  INDEX idx_time_slots_resource (resource_id)
) ENGINE=InnoDB;

-- ===================== BOOKING POLICIES =====================
CREATE TABLE booking_policies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  policy_name VARCHAR(150) NOT NULL,
  max_duration_hours DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  weekly_quota INT UNSIGNED DEFAULT 5,
  max_peak_slots_per_week INT UNSIGNED DEFAULT 2,
  cancellation_deadline_hours INT UNSIGNED DEFAULT 24,
  requires_approval TINYINT(1) NOT NULL DEFAULT 0,
  auto_approval_enabled TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES resource_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ===================== BOOKINGS =====================
CREATE TABLE bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_reference VARCHAR(30) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  resource_id INT UNSIGNED NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  purpose VARCHAR(255) NOT NULL,
  additional_notes TEXT,
  status ENUM('pending','approved','rejected','cancelled','completed','expired') NOT NULL DEFAULT 'pending',
  requires_approval TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE RESTRICT,
  INDEX idx_bookings_user (user_id),
  INDEX idx_bookings_resource (resource_id),
  INDEX idx_bookings_status (status),
  INDEX idx_bookings_start (start_datetime),
  INDEX idx_bookings_end (end_datetime)
) ENGINE=InnoDB;

-- ===================== APPROVALS =====================
CREATE TABLE approvals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  approver_id INT UNSIGNED NOT NULL,
  decision ENUM('approved','rejected') NOT NULL,
  comment TEXT,
  decided_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_approvals_booking (booking_id)
) ENGINE=InnoDB;

-- ===================== CANCELLATIONS =====================
CREATE TABLE cancellations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL UNIQUE,
  cancelled_by INT UNSIGNED NOT NULL,
  reason TEXT NOT NULL,
  cancelled_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ===================== NOTIFICATIONS =====================
CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  booking_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('booking_created','booking_approved','booking_rejected','booking_cancelled','pending_approval','resource_maintenance','schedule_changed','system') NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
  INDEX idx_notifications_user (user_id)
) ENGINE=InnoDB;

-- ===================== USAGE REPORTS =====================
CREATE TABLE usage_reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id INT UNSIGNED NOT NULL,
  report_type ENUM('weekly','monthly','semester') NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  total_bookings INT UNSIGNED DEFAULT 0,
  total_approved INT UNSIGNED DEFAULT 0,
  total_rejected INT UNSIGNED DEFAULT 0,
  total_cancelled INT UNSIGNED DEFAULT 0,
  total_hours DECIMAL(10,2) DEFAULT 0,
  peak_hour_bookings INT UNSIGNED DEFAULT 0,
  utilization_rate DECIMAL(5,2) DEFAULT 0,
  generated_at DATETIME NOT NULL,
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== AUDIT LOGS =====================
CREATE TABLE audit_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  action VARCHAR(50) NOT NULL,
  table_name VARCHAR(80) DEFAULT NULL,
  record_id INT UNSIGNED DEFAULT NULL,
  old_value TEXT,
  new_value TEXT,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_logs_user (user_id),
  INDEX idx_audit_logs_action (action)
) ENGINE=InnoDB;

-- ===================== MAINTENANCE SCHEDULES =====================
CREATE TABLE maintenance_schedules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id INT UNSIGNED NOT NULL,
  maintenance_start DATETIME NOT NULL,
  maintenance_end DATETIME NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ===================== SEED DATA =====================

INSERT INTO roles (role_name, description) VALUES
('Admin', 'System administrator with full access'),
('Student', 'Student user who can book campus resources'),
('Lecturer', 'Lecturer who can approve bookings and create supervised bookings'),
('Staff', 'Campus staff with operational access'),
('Approver', 'Dedicated approver for booking requests');

INSERT INTO departments (department_name, description) VALUES
('Information Technology', 'IT department and computer labs'),
('Business Administration', 'Business and management programs'),
('International Studies', 'International relations and languages'),
('Student Affairs', 'Student services and activities'),
('Campus Facility Office', 'Campus facilities management');

INSERT INTO users (department_id, full_name, username, email, password_hash, phone, student_code, staff_code, status) VALUES
(1, 'System Administrator', 'admin', 'admin@example.com', '$2y$12$xQl8WWuJsOfrXZXIFY6wt.ZNnIyiz01MALkDLiAFlmqVt1j5UZ0Gq', '0901000001', NULL, 'ADM001', 'active'),
(1, 'Nguyen Van Student', 'student', 'student@example.com', '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0901000002', 'SV2024001', NULL, 'active'),
(1, 'Dr. Tran Lecturer', 'lecturer', 'lecturer@example.com', '$2y$12$QktMJ5qJLmCnIXObxaGZEOD.oWwUUVwuWG2XT1aksDKGVhigqO1Oe', '0901000003', NULL, 'LEC001', 'active'),
(5, 'Le Thi Approver', 'approver', 'approver@example.com', '$2y$12$6pFSYdbevPsvksEXAl3dTOdjGuGmmsferhqkvIVFx07eBuNmI.VUq', '0901000004', NULL, 'APP001', 'active'),
(5, 'Campus Staff Member', 'staff', 'staff@example.com', '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0901000005', NULL, 'STF001', 'active'),
(2, 'Pham Minh Anh', 'student2', 'student2@example.com', '$2y$12$EXq/EJCo6xvEogTjXXk32.hCdsmc2xvwgtuEuFdPQEJXGiLvdBaOO', '0901000006', 'SV2024002', NULL, 'active');

INSERT INTO user_roles (user_id, role_id) VALUES
(1, 1), (2, 2), (3, 3), (4, 5), (5, 4), (6, 2);

INSERT INTO resource_categories (category_name, description, requires_approval, max_booking_hours_per_day, max_booking_hours_per_week, max_peak_slots_per_week, cancellation_deadline_hours, status) VALUES
('Group Study Room', 'Shared study rooms for group work', 0, 4.00, 10.00, 2, 12, 'active'),
('Laboratory', 'Specialized lab facilities requiring approval', 1, 3.00, 8.00, 2, 24, 'active'),
('Sports Court', 'Indoor and outdoor sports facilities', 0, 2.00, 6.00, 2, 6, 'active'),
('Meeting Room', 'Meeting rooms for academic and club use', 0, 3.00, 8.00, 2, 24, 'active'),
('Club Room', 'Dedicated club activity rooms', 0, 4.00, 10.00, 2, 12, 'active'),
('Media Studio', 'Recording and media production studios', 1, 2.00, 6.00, 2, 48, 'active');

INSERT INTO resources (category_id, resource_code, resource_name, location, capacity, description, status) VALUES
(1, 'GSR-101', 'Group Study Room A', 'Building A - Floor 1', 8, 'Quiet group study room with whiteboard', 'available'),
(1, 'GSR-102', 'Group Study Room B', 'Building A - Floor 1', 6, 'Small group study room', 'available'),
(2, 'LAB-201', 'Computer Laboratory 1', 'Building B - Floor 2', 30, 'IT lab with 30 workstations', 'available'),
(2, 'LAB-202', 'Chemistry Laboratory', 'Building C - Floor 1', 25, 'Chemistry lab with safety equipment', 'maintenance'),
(3, 'SPT-001', 'Basketball Court', 'Sports Complex', 20, 'Indoor basketball court', 'available'),
(4, 'MTG-301', 'Meeting Room Alpha', 'Building D - Floor 3', 15, 'Conference room with projector', 'available'),
(5, 'CLB-101', 'Debate Club Room', 'Student Center - Floor 1', 20, 'Club activity room', 'available'),
(6, 'MED-001', 'Media Studio 1', 'Creative Arts Building', 10, 'Video and audio recording studio', 'available'),
(6, 'MED-002', 'Media Studio 2', 'Creative Arts Building', 8, 'Podcast and streaming studio', 'restricted');

INSERT INTO equipment (equipment_name, description, quantity, status) VALUES
('Projector', 'HD projector with HDMI', 10, 'available'),
('Whiteboard', 'Magnetic whiteboard', 15, 'available'),
('Microphone Set', 'Wireless microphone set', 5, 'available'),
('Camera', '4K video camera', 3, 'available'),
('Computer Workstation', 'Desktop computer', 30, 'available'),
('Lab Safety Kit', 'Safety goggles and gloves', 25, 'available');

INSERT INTO resource_equipment (resource_id, equipment_id, quantity) VALUES
(1, 2, 1), (1, 1, 1),
(2, 2, 1),
(3, 5, 30), (3, 1, 1),
(4, 6, 25),
(6, 1, 1), (6, 2, 1),
(8, 3, 2), (8, 4, 1),
(9, 3, 1), (9, 4, 1);

INSERT INTO time_slots (resource_id, day_of_week, start_time, end_time, is_peak, is_active) VALUES
(1, 1, '08:00:00', '10:00:00', 1, 1),
(1, 1, '10:00:00', '12:00:00', 0, 1),
(1, 1, '13:00:00', '15:00:00', 1, 1),
(1, 1, '15:00:00', '17:00:00', 0, 1),
(1, 2, '08:00:00', '10:00:00', 1, 1),
(1, 2, '10:00:00', '12:00:00', 0, 1),
(3, 1, '08:00:00', '11:00:00', 1, 1),
(3, 1, '13:00:00', '16:00:00', 1, 1),
(3, 2, '08:00:00', '11:00:00', 0, 1),
(5, 1, '17:00:00', '19:00:00', 1, 1),
(5, 1, '19:00:00', '21:00:00', 1, 1),
(8, 1, '09:00:00', '12:00:00', 1, 1),
(8, 3, '14:00:00', '17:00:00', 1, 1);

INSERT INTO booking_policies (category_id, policy_name, max_duration_hours, weekly_quota, max_peak_slots_per_week, cancellation_deadline_hours, requires_approval, auto_approval_enabled, is_active) VALUES
(1, 'Study Room Standard Policy', 2.00, 5, 2, 12, 0, 1, 1),
(2, 'Laboratory Approval Policy', 3.00, 3, 2, 24, 1, 0, 1),
(3, 'Sports Court Policy', 2.00, 4, 2, 6, 0, 1, 1),
(4, 'Meeting Room Policy', 3.00, 4, 2, 24, 0, 1, 1),
(5, 'Club Room Policy', 4.00, 5, 2, 12, 0, 1, 1),
(6, 'Media Studio Approval Policy', 2.00, 2, 2, 48, 1, 0, 1);

INSERT INTO bookings (booking_reference, user_id, resource_id, start_datetime, end_datetime, purpose, additional_notes, status, requires_approval) VALUES
('BK20250601001', 2, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 'Group project meeting', 'Need whiteboard', 'approved', 0),
('BK20250601002', 2, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 11 HOUR, 'Programming lab session', 'INS3064 project work', 'pending', 1),
('BK20250601003', 6, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 12 HOUR, 'Study session', NULL, 'approved', 0),
('BK20250601004', 2, 8, DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 9 HOUR, DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 11 HOUR, 'Video recording for presentation', 'Need camera setup', 'pending', 1),
('BK20250601005', 6, 5, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 17 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 19 HOUR, 'Basketball practice', NULL, 'approved', 0),
('BK20250601006', 2, 6, DATE_ADD(CURDATE(), INTERVAL -7 DAY) + INTERVAL 14 HOUR, DATE_ADD(CURDATE(), INTERVAL -7 DAY) + INTERVAL 16 HOUR, 'Team meeting', NULL, 'completed', 0),
('BK20250601007', 6, 3, DATE_ADD(CURDATE(), INTERVAL -3 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL -3 DAY) + INTERVAL 10 HOUR, 'Lab work', NULL, 'cancelled', 1);

INSERT INTO approvals (booking_id, approver_id, decision, comment, decided_at) VALUES
(2, 3, 'approved', 'Approved for academic project work', NOW() - INTERVAL 1 DAY);

UPDATE bookings SET status = 'approved' WHERE id = 2;

INSERT INTO cancellations (booking_id, cancelled_by, reason, cancelled_at) VALUES
(7, 6, 'Schedule conflict with exam preparation', NOW() - INTERVAL 2 DAY);

INSERT INTO notifications (user_id, booking_id, title, message, type, is_read) VALUES
(2, 1, 'Booking Confirmed', 'Your booking BK20250601001 for Group Study Room A has been approved.', 'booking_approved', 1),
(2, 2, 'Booking Pending Approval', 'Your lab booking BK20250601002 is waiting for lecturer approval.', 'pending_approval', 0),
(3, 2, 'Approval Required', 'New lab booking BK20250601002 requires your approval.', 'pending_approval', 0),
(2, 4, 'Booking Pending Approval', 'Your media studio booking BK20250601004 requires admin approval.', 'pending_approval', 0),
(1, NULL, 'System Welcome', 'Welcome to Campus Services Booking System.', 'system', 1),
(6, 7, 'Booking Cancelled', 'Your booking BK20250601007 has been cancelled.', 'booking_cancelled', 1);

INSERT INTO usage_reports (resource_id, report_type, period_start, period_end, total_bookings, total_approved, total_rejected, total_cancelled, total_hours, peak_hour_bookings, utilization_rate, generated_at) VALUES
(1, 'monthly', DATE_FORMAT(CURDATE(), '%Y-%m-01'), LAST_DAY(CURDATE()), 15, 12, 1, 2, 30.00, 8, 45.50, NOW()),
(3, 'monthly', DATE_FORMAT(CURDATE(), '%Y-%m-01'), LAST_DAY(CURDATE()), 10, 7, 2, 1, 25.00, 6, 38.20, NOW()),
(5, 'monthly', DATE_FORMAT(CURDATE(), '%Y-%m-01'), LAST_DAY(CURDATE()), 8, 7, 0, 1, 16.00, 5, 28.00, NOW()),
(8, 'monthly', DATE_FORMAT(CURDATE(), '%Y-%m-01'), LAST_DAY(CURDATE()), 5, 3, 1, 1, 10.00, 4, 22.50, NOW());

INSERT INTO maintenance_schedules (resource_id, maintenance_start, maintenance_end, reason, status, created_by) VALUES
(4, DATE_ADD(CURDATE(), INTERVAL -2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Annual safety inspection and equipment upgrade', 'in_progress', 1);

INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address, created_at) VALUES
(1, 'login', 'users', 1, NULL, '{"email":"admin@example.com"}', '127.0.0.1', NOW() - INTERVAL 1 HOUR),
(2, 'login', 'users', 2, NULL, '{"email":"student@example.com"}', '127.0.0.1', NOW() - INTERVAL 2 HOUR),
(2, 'create_booking', 'bookings', 1, NULL, '{"reference":"BK20250601001","status":"approved"}', '127.0.0.1', NOW() - INTERVAL 3 DAY),
(2, 'create_booking', 'bookings', 2, NULL, '{"reference":"BK20250601002","status":"pending"}', '127.0.0.1', NOW() - INTERVAL 2 DAY),
(3, 'approve_booking', 'bookings', 2, '{"status":"pending"}', '{"status":"approved"}', '127.0.0.1', NOW() - INTERVAL 1 DAY),
(6, 'cancel_booking', 'bookings', 7, '{"status":"pending"}', '{"status":"cancelled"}', '127.0.0.1', NOW() - INTERVAL 2 DAY),
(1, 'create_resource', 'resources', 1, NULL, '{"code":"GSR-101"}', '127.0.0.1', NOW() - INTERVAL 10 DAY),
(1, 'update_policy', 'booking_policies', 2, '{"requires_approval":0}', '{"requires_approval":1}', '127.0.0.1', NOW() - INTERVAL 5 DAY);
