-- schema migration — run after base schema
ALTER TABLE `inquiries` ADD COLUMN IF NOT EXISTS `user_id` INT UNSIGNED DEFAULT NULL AFTER `id`;
ALTER TABLE `projects`  ADD COLUMN IF NOT EXISTS `user_id` INT UNSIGNED DEFAULT NULL AFTER `id`;
ALTER TABLE `project_files` ADD COLUMN IF NOT EXISTS `uploaded_by` INT UNSIGNED DEFAULT NULL AFTER `project_id`;
ALTER TABLE `bookings`  ADD COLUMN IF NOT EXISTS `user_id` INT UNSIGNED DEFAULT NULL AFTER `id`;
ALTER TABLE `inquiries` MODIFY `status` ENUM('new','reviewed','contacted','converted','lost') NOT NULL DEFAULT 'new';
UPDATE `projects` SET `user_id` = 1 WHERE `client_email` = 'juan@visayas.ph';
