-- ============================================================
-- Antigo UI/UX Advisory — Database Schema
-- Database: antigo_advisory_db
-- Charset:  utf8mb4 | Collation: utf8mb4_unicode_ci
-- Run this file in phpMyAdmin > SQL tab, or via MySQL CLI:
--   mysql -u root -p < antigo_advisory_db.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `antigo_advisory_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `antigo_advisory_db`;

-- ------------------------------------------------------------
-- 1. USERS
--    Stores both client and admin accounts.
--    role: 'client' | 'admin'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(120)  NOT NULL,
    `email`         VARCHAR(180)  NOT NULL UNIQUE,
    `password_hash` VARCHAR(255)  NOT NULL,
    `role`          ENUM('client','admin') NOT NULL DEFAULT 'client',
    `company`       VARCHAR(120)  DEFAULT NULL,
    `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed demo accounts (passwords = bcrypt of "password")
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `company`) VALUES
('Demo Client',          'demo@client.com',  '$2y$10$PMsLaooowB7pbbWGERvPteeWC.qEh.494hfPklY8eyjoIhlXkjlFy', 'client', 'Visayas Health Care'),
('Kimberly Jayne Antigo','admin@antigo.com', '$2y$10$PMsLaooowB7pbbWGERvPteeWC.qEh.494hfPklY8eyjoIhlXkjlFy', 'admin',  'Antigo UI/UX Advisory');

-- ------------------------------------------------------------
-- 2. INQUIRIES
--    Project leads submitted via inquiry.php (Page 03).
--    status: 'new' | 'reviewed' | 'contacted' | 'converted'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ref_code`     VARCHAR(20)   NOT NULL UNIQUE COMMENT 'e.g. INQ-1001',
    `name`         VARCHAR(120)  NOT NULL,
    `email`        VARCHAR(180)  NOT NULL,
    `company`      VARCHAR(120)  DEFAULT NULL,
    `project_type` VARCHAR(80)   NOT NULL,
    `budget`       VARCHAR(80)   NOT NULL,
    `timeline`     VARCHAR(80)   NOT NULL,
    `description`  TEXT          NOT NULL,
    `file_name`    VARCHAR(255)  DEFAULT NULL,
    `status`       ENUM('new','reviewed','contacted','converted') NOT NULL DEFAULT 'new',
    `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_email`  (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample inquiries
INSERT INTO `inquiries` (`ref_code`, `name`, `email`, `company`, `project_type`, `budget`, `timeline`, `description`, `status`) VALUES
('INQ-1001', 'Maria Santos',   'maria@pesolink.com',  'Pesolink Financial Services', 'UI Design',    '₱150,000 – ₱300,000', '1 Month',   'Redesigning mobile banking dashboard for smoother digital transactions and better conversion.', 'new'),
('INQ-1002', 'Juan dela Cruz', 'juan@visayas.ph',     'Visayas Health Care',          'UX Research',  '₱50,000 – ₱150,000',  '2–3 Months','Patient portal UX audit and user journey mapping for clinic management system.', 'contacted'),
('INQ-1003', 'Ana Reyes',      'ana@cebu-tourism.ph', 'Cebu Tourism Board',           'Design Systems','₱300,000+',           'Flexible',  'Build a scalable design system for our tourism mobile and web properties.', 'reviewed');

-- ------------------------------------------------------------
-- 3. BOOKINGS
--    Consultation slots booked via book-consultation.php (Page 02).
--    status: 'pending' | 'confirmed' | 'completed' | 'cancelled'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_code` VARCHAR(20)   NOT NULL UNIQUE COMMENT 'e.g. BKG-2001',
    `inquiry_id`   INT UNSIGNED  DEFAULT NULL COMMENT 'FK to inquiries (optional, cold bookings allowed)',
    `client_name`  VARCHAR(120)  NOT NULL,
    `client_email` VARCHAR(180)  NOT NULL,
    `service`      VARCHAR(80)   NOT NULL,
    `duration`     VARCHAR(20)   NOT NULL COMMENT '30 min | 60 min',
    `price`        VARCHAR(30)   NOT NULL COMMENT 'e.g. ₱45,000',
    `date`         DATE          NOT NULL,
    `time`         VARCHAR(20)   NOT NULL COMMENT 'e.g. 10:00 AM',
    `format`       VARCHAR(80)   NOT NULL COMMENT 'Google Meet | Phone Call | In-Person Studio',
    `status`       ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bookings_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries`(`id`) ON DELETE SET NULL,
    INDEX `idx_date`   (`date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample bookings
INSERT INTO `bookings` (`booking_code`, `inquiry_id`, `client_name`, `client_email`, `service`, `duration`, `price`, `date`, `time`, `format`, `status`) VALUES
('BKG-2001', 1, 'Maria Santos',   'maria@pesolink.com', 'UI Design',   '60 min', '₱45,000', '2026-09-10', '10:00 AM', 'Video Call (Google Meet)', 'confirmed'),
('BKG-2002', 2, 'Juan dela Cruz', 'juan@visayas.ph',    'UX Research', '60 min', '₱50,000', '2026-09-15', '02:00 PM', 'Video Call (Google Meet)', 'pending');

-- ------------------------------------------------------------
-- 4. PROJECTS
--    Active client projects managed from admin panel.
--    status_type: 'pending' | 'in_design' | 'completed'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_code`   VARCHAR(20)   NOT NULL UNIQUE COMMENT 'e.g. PRJ-3001',
    `title`          VARCHAR(200)  NOT NULL,
    `category`       VARCHAR(100)  NOT NULL,
    `client_name`    VARCHAR(120)  NOT NULL,
    `client_email`   VARCHAR(180)  DEFAULT NULL,
    `company`        VARCHAR(120)  DEFAULT NULL,
    `budget`         VARCHAR(50)   NOT NULL COMMENT 'Display budget, e.g. ₱250,000',
    `due_date`       DATE          NOT NULL,
    `current_phase`  TINYINT       NOT NULL DEFAULT 1 COMMENT '1–5',
    `phase_name`     VARCHAR(80)   NOT NULL DEFAULT 'Discovery & Research',
    `progress`       TINYINT       NOT NULL DEFAULT 20 COMMENT 'Percentage 0–100',
    `status`         VARCHAR(40)   NOT NULL DEFAULT 'Discovery',
    `status_type`    ENUM('pending','in_design','completed') NOT NULL DEFAULT 'pending',
    `internal_notes` TEXT          DEFAULT NULL COMMENT 'Admin-only, not shown to client',
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status_type`  (`status_type`),
    INDEX `idx_client_email` (`client_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample projects
INSERT INTO `projects` (`project_code`, `title`, `category`, `client_name`, `client_email`, `company`, `budget`, `due_date`, `current_phase`, `phase_name`, `progress`, `status`, `status_type`) VALUES
('PRJ-3001', 'Pesolink Mobile Banking Redesign',    'Fintech · Mobile App',    'Maria Santos',   'maria@pesolink.com', 'Pesolink Financial Services', '₱250,000', '2026-09-30', 3, 'UI/UX Design',         60, 'UI Design',  'in_design'),
('PRJ-3002', 'Visayas Health Care Patient Portal',  'Healthcare · Web App',    'Juan dela Cruz', 'juan@visayas.ph',    'Visayas Health Care',          '₱180,000', '2026-10-15', 2, 'Wireframing',          40, 'Wireframing','in_design'),
('PRJ-3003', 'Cebu Tourism Responsive Website',     'Tourism · Responsive Web','Ana Reyes',      'ana@cebu-tourism.ph','Cebu Tourism Board',            '₱120,000', '2026-11-30', 1, 'Discovery & Research', 20, 'Discovery',  'pending');

-- ------------------------------------------------------------
-- 5. PROJECT FILES
--    Deliverables and reference files per project.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_files` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`  INT UNSIGNED  NOT NULL,
    `name`        VARCHAR(255)  NOT NULL,
    `size`        VARCHAR(20)   NOT NULL COMMENT 'e.g. 2.4 MB',
    `file_path`   VARCHAR(500)  DEFAULT NULL COMMENT 'Server path or URL',
    `uploaded_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_files_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    INDEX `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample project files
INSERT INTO `project_files` (`project_id`, `name`, `size`) VALUES
(1, 'Pesolink_UI_Design_v1.fig',       '4.2 MB'),
(1, 'UX_Research_Report_Aug2026.pdf',  '1.8 MB'),
(1, 'Design_Tokens_Spec.pdf',          '0.9 MB'),
(2, 'HealthPortal_Wireframes_v2.fig',  '3.1 MB'),
(2, 'Patient_Journey_Map.pdf',         '1.2 MB');

-- ------------------------------------------------------------
-- 6. PROJECT MESSAGES
--    Live collaboration thread between client and designer.
--    role: 'client' | 'designer'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_messages` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED  NOT NULL,
    `sender`     VARCHAR(120)  NOT NULL,
    `role`       ENUM('client','designer') NOT NULL,
    `message`    TEXT          NOT NULL,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_messages_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    INDEX `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample messages for PRJ-3001
INSERT INTO `project_messages` (`project_id`, `sender`, `role`, `message`) VALUES
(1, 'Kimberly Jayne Antigo', 'designer', 'Hi Maria! I have completed the initial wireframes for the dashboard. Please review the Figma file and share your feedback.'),
(1, 'Maria Santos',          'client',   'Looks great! Can we adjust the color scheme on the bottom navigation to match our brand blue?'),
(1, 'Kimberly Jayne Antigo', 'designer', 'Absolutely! Updated the nav colors in v1.2 of the design file. You can review the latest version in the files section.');

-- ============================================================
-- End of schema
-- ============================================================
