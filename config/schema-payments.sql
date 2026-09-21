-- Migration: Create payments table
USE `antigo_advisory_db`;

CREATE TABLE IF NOT EXISTS `payments` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `user_id`          INT UNSIGNED NOT NULL,
    `amount`           DECIMAL(10,2) NOT NULL,
    `payment_method`   ENUM('GCash','Bank Transfer','Cash','Card') NOT NULL,
    `status`           ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    `admin_notes`      TEXT DEFAULT NULL,
    `submitted_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `verified_at`      TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_payments_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payments_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;