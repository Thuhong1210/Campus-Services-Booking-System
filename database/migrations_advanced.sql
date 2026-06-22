-- ============================================================
-- Campus Services Booking System – Advanced Features Migration
-- Run AFTER the base schema (campus_services_booking.sql)
-- ============================================================

USE `campus_services_booking`;

SET FOREIGN_KEY_CHECKS = 0;

-- ===================== WAITLIST =====================
CREATE TABLE IF NOT EXISTS waitlist (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  resource_id INT UNSIGNED NOT NULL,
  desired_start DATETIME NOT NULL,
  desired_end DATETIME NOT NULL,
  status ENUM('waiting','notified','confirmed','expired','cancelled') NOT NULL DEFAULT 'waiting',
  notified_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  INDEX idx_waitlist_resource (resource_id),
  INDEX idx_waitlist_user (user_id),
  INDEX idx_waitlist_status (status)
) ENGINE=InnoDB;

-- ===================== RECURRING BOOKINGS =====================
CREATE TABLE IF NOT EXISTS recurring_bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id VARCHAR(36) NOT NULL COMMENT 'UUID shared by all bookings in the series',
  booking_id INT UNSIGNED NOT NULL,
  recurrence_type ENUM('weekly','monthly') NOT NULL,
  occurrence_number INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  INDEX idx_recurring_group (group_id)
) ENGINE=InnoDB;

-- ===================== BOOKING EQUIPMENT =====================
CREATE TABLE IF NOT EXISTS booking_equipment (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  equipment_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
  UNIQUE KEY uk_booking_equipment (booking_id, equipment_id)
) ENGINE=InnoDB;

-- ===================== FEEDBACK / RATINGS =====================
CREATE TABLE IF NOT EXISTS booking_feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL COMMENT '1-5 stars overall',
  cleanliness_rating TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5',
  equipment_rating TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5',
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_feedback_booking (booking_id),
  INDEX idx_feedback_user (user_id)
) ENGINE=InnoDB;

-- ===================== LOGIN ATTEMPTS (Security) =====================
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(150) NOT NULL COMMENT 'username or email',
  ip_address VARCHAR(45),
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts_identifier (identifier),
  INDEX idx_login_attempts_ip (ip_address),
  INDEX idx_login_attempts_time (attempted_at)
) ENGINE=InnoDB;

-- ===================== PASSWORD RESETS =====================
CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token VARCHAR(100) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_password_resets_token (token)
) ENGINE=InnoDB;

-- ===================== ALTER USERS TABLE (Security) =====================
ALTER TABLE users
  ADD COLUMN failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN locked_until DATETIME DEFAULT NULL,
  ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN last_login_at DATETIME DEFAULT NULL,
  ADD COLUMN remember_token VARCHAR(100) DEFAULT NULL;

-- ===================== ALTER BOOKINGS TABLE (Recurring) =====================
ALTER TABLE bookings
  ADD COLUMN recurring_group_id VARCHAR(36) DEFAULT NULL,
  ADD COLUMN is_recurring TINYINT(1) NOT NULL DEFAULT 0;

-- ===================== NOTIFICATION TYPE EXTENSION =====================
-- Add new notification types for waitlist and feedback
ALTER TABLE notifications
  MODIFY COLUMN type ENUM(
    'booking_created','booking_approved','booking_rejected',
    'booking_cancelled','pending_approval','resource_maintenance',
    'schedule_changed','system','waitlist_available','feedback_reminder'
  ) NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ===================== SEED: Additional Settings =====================
INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
('waitlist_confirm_hours', '24', 'Hours user has to confirm waitlist slot before it expires'),
('max_login_attempts', '5', 'Max failed login attempts before account lockout'),
('lockout_duration_minutes', '30', 'Account lockout duration in minutes'),
('session_timeout_minutes', '60', 'Session timeout in minutes of inactivity'),
('feedback_enabled', '1', 'Enable post-booking feedback (1=yes, 0=no)'),
('import_max_rows', '500', 'Maximum rows per CSV import'),
('recurring_max_occurrences', '12', 'Maximum recurring booking occurrences');
