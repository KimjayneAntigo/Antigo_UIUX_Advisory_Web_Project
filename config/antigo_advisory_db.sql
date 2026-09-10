-- Antigo UI/UX Advisory — Consolidated Database Schema & Seed Data

CREATE DATABASE IF NOT EXISTS `antigo_advisory_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `antigo_advisory_db`;

-- USERS
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

-- INQUIRIES
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED  DEFAULT NULL,
    `ref_code`     VARCHAR(20)   NOT NULL UNIQUE COMMENT 'e.g. INQ-1001',
    `name`         VARCHAR(120)  NOT NULL,
    `email`        VARCHAR(180)  NOT NULL,
    `company`      VARCHAR(120)  DEFAULT NULL,
    `project_type` VARCHAR(80)   NOT NULL,
    `budget`       VARCHAR(80)   NOT NULL,
    `timeline`     VARCHAR(80)   NOT NULL,
    `description`  TEXT          NOT NULL,
    `file_name`    VARCHAR(255)  DEFAULT NULL,
    `status`       ENUM('new','reviewed','contacted','converted','lost') NOT NULL DEFAULT 'new',
    `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_inquiries_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_status`  (`status`),
    INDEX `idx_email`   (`email`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample inquiries
INSERT INTO `inquiries` (`user_id`, `ref_code`, `name`, `email`, `company`, `project_type`, `budget`, `timeline`, `description`, `status`) VALUES
(NULL, 'INQ-1001', 'Maria Santos',   'maria@pesolink.com',  'Pesolink Financial Services', 'UI Design',    '$150,000 – $300,000', '1 Month',   'Redesigning mobile banking dashboard for smoother digital transactions and better conversion.', 'new'),
(1,    'INQ-1002', 'Juan dela Cruz', 'demo@client.com',     'Visayas Health Care',          'UX Research',  '$50,000 – $150,000',  '2–3 Months','Patient portal UX audit and user journey mapping for clinic management system.', 'contacted'),
(NULL, 'INQ-1003', 'Ana Reyes',      'ana@cebu-tourism.ph', 'Cebu Tourism Board',           'Design Systems','$300,000+',           'Flexible',  'Build a scalable design system for our tourism mobile and web properties.', 'reviewed');

-- BOOKINGS
CREATE TABLE IF NOT EXISTS `bookings` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT UNSIGNED  DEFAULT NULL,
    `booking_code`   VARCHAR(20)   NOT NULL UNIQUE COMMENT 'e.g. BKG-2001',
    `inquiry_id`     INT UNSIGNED  DEFAULT NULL COMMENT 'FK to inquiries (optional)',
    `client_name`    VARCHAR(120)  NOT NULL,
    `client_email`   VARCHAR(180)  NOT NULL,
    `guest_name`     VARCHAR(120)  DEFAULT NULL,
    `guest_email`    VARCHAR(180)  DEFAULT NULL,
    `service`        VARCHAR(80)   NOT NULL,
    `duration`       VARCHAR(20)   NOT NULL COMMENT '30 min | 60 min',
    `duration_min`   INT UNSIGNED  DEFAULT 60,
    `price`          VARCHAR(30)   NOT NULL COMMENT 'e.g. $450',
    `price_php`      INT UNSIGNED  DEFAULT 450,
    `date`           DATE          NOT NULL,
    `booking_date`   DATE          DEFAULT NULL,
    `time`           VARCHAR(20)   NOT NULL COMMENT 'e.g. 10:00 AM',
    `booking_time`   VARCHAR(20)   DEFAULT NULL,
    `format`         VARCHAR(80)   NOT NULL COMMENT 'Google Meet | Phone Call | In-Person Studio',
    `meeting_format` VARCHAR(80)   DEFAULT NULL,
    `status`         ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bookings_user`    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_bookings_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries`(`id`) ON DELETE SET NULL,
    INDEX `idx_date`    (`date`),
    INDEX `idx_status`  (`status`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample bookings
INSERT INTO `bookings` (`user_id`, `booking_code`, `inquiry_id`, `client_name`, `client_email`, `service`, `duration`, `price`, `date`, `time`, `format`, `status`) VALUES
(NULL, 'BKG-2001', 1, 'Maria Santos',   'maria@pesolink.com', 'UI Design',   '60 min', '$450', '2026-09-10', '10:00 AM', 'Video Call (Google Meet)', 'confirmed'),
(1,    'BKG-2002', 2, 'Demo Client',    'demo@client.com',    'UX Research', '60 min', '$500', '2026-09-15', '02:00 PM', 'Video Call (Google Meet)', 'pending');

-- PROJECTS
CREATE TABLE IF NOT EXISTS `projects` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT UNSIGNED  DEFAULT NULL,
    `project_code`   VARCHAR(20)   NOT NULL UNIQUE COMMENT 'e.g. PRJ-3001',
    `title`          VARCHAR(200)  NOT NULL,
    `category`       VARCHAR(100)  NOT NULL,
    `client_name`    VARCHAR(120)  NOT NULL,
    `client_email`   VARCHAR(180)  DEFAULT NULL,
    `company`        VARCHAR(120)  DEFAULT NULL,
    `budget`         VARCHAR(50)   NOT NULL COMMENT 'Display budget, e.g. $250,000',
    `due_date`       DATE          NOT NULL,
    `current_phase`  TINYINT       NOT NULL DEFAULT 1 COMMENT '1–5',
    `phase_name`     VARCHAR(80)   NOT NULL DEFAULT 'Discovery & Research',
    `progress`       TINYINT       NOT NULL DEFAULT 20 COMMENT 'Percentage 0–100',
    `status`         VARCHAR(40)   NOT NULL DEFAULT 'Discovery',
    `status_type`    ENUM('pending','in_design','completed') NOT NULL DEFAULT 'pending',
    `internal_notes` TEXT          DEFAULT NULL COMMENT 'Admin-only',
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_projects_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_status_type`  (`status_type`),
    INDEX `idx_client_email` (`client_email`),
    INDEX `idx_user_id`      (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample projects
INSERT INTO `projects` (`user_id`, `project_code`, `title`, `category`, `client_name`, `client_email`, `company`, `budget`, `due_date`, `current_phase`, `phase_name`, `progress`, `status`, `status_type`) VALUES
(NULL, 'PRJ-3001', 'Pesolink Mobile Banking Redesign',   'Fintech · Mobile App',    'Maria Santos',   'maria@pesolink.com', 'Pesolink Financial Services', '$250,000', '2026-09-30', 3, 'UI/UX Design',         60, 'UI Design',   'in_design'),
(1,    'PRJ-3002', 'Visayas Health Care Patient Portal', 'Healthcare · Web App',    'Demo Client',    'demo@client.com',    'Visayas Health Care',          '$180,000', '2026-10-15', 2, 'Wireframing',          40, 'Wireframing', 'in_design'),
(NULL, 'PRJ-3003', 'Cebu Tourism Responsive Website',    'Tourism · Responsive Web','Ana Reyes',      'ana@cebu-tourism.ph','Cebu Tourism Board',            '$120,000', '2026-11-30', 1, 'Discovery & Research', 20, 'Discovery',   'pending');

-- PROJECT FILES
CREATE TABLE IF NOT EXISTS `project_files` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`  INT UNSIGNED  NOT NULL,
    `uploaded_by` INT UNSIGNED  DEFAULT NULL,
    `name`        VARCHAR(255)  NOT NULL,
    `size`        VARCHAR(20)   NOT NULL COMMENT 'e.g. 2.4 MB',
    `file_path`   VARCHAR(500)  DEFAULT NULL,
    `uploaded_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_files_project`  FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_files_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL,
    INDEX `idx_project_id`  (`project_id`),
    INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sample project files
INSERT INTO `project_files` (`project_id`, `uploaded_by`, `name`, `size`, `file_path`) VALUES
(1, 2, 'Pesolink_UI_Design_v1.fig',      '4.2 MB', NULL),
(1, 2, 'UX_Research_Report_Aug2026.pdf', '1.8 MB', NULL),
(1, 2, 'Design_Tokens_Spec.pdf',         '0.9 MB', NULL),
(2, 2, 'HealthPortal_Wireframes_v2.fig', '3.1 MB', NULL),
(2, 2, 'Patient_Journey_Map.pdf',        '1.2 MB', NULL);

-- PROJECT MESSAGES
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

-- Seed sample messages for PRJ-3001 and PRJ-3002
INSERT INTO `project_messages` (`project_id`, `sender`, `role`, `message`) VALUES
(1, 'Kimberly Jayne Antigo', 'designer', 'Hi Maria! I have completed the initial wireframes for the dashboard. Please review the Figma file and share your feedback.'),
(1, 'Maria Santos',          'client',   'Looks great! Can we adjust the color scheme on the bottom navigation to match our brand blue?'),
(1, 'Kimberly Jayne Antigo', 'designer', 'Absolutely! Updated the nav colors in v1.2 of the design file. You can review the latest version in the files section.'),
(2, 'Kimberly Jayne Antigo', 'designer', 'Hi! The wireframe iterations for the Visayas Health patient onboarding flow are ready for your review.'),
(2, 'Demo Client',           'client',   'Thank you Kimberly! The patient history step looks very intuitive. We will review it with our clinic heads.');
