CREATE TABLE `notification` (
    `id` BINARY(16) NOT NULL,
    `status` VARCHAR(255) NOT NULL,
    `message` VARCHAR(255) NOT NULL,
    `admin_only` TINYINT(1) NULL DEFAULT '0',
    `required_privileges` JSON NULL,
    `created_by_integration_id` BINARY(16) NULL,
    `created_by_user_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.notification.required_privileges` CHECK (JSON_VALID(`required_privileges`)),
    KEY `fk.notification.created_by_integration_id` (`created_by_integration_id`),
    KEY `fk.notification.created_by_user_id` (`created_by_user_id`),
    CONSTRAINT `fk.notification.created_by_integration_id` FOREIGN KEY (`created_by_integration_id`) REFERENCES `integration` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.notification.created_by_user_id` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;