CREATE TABLE `script` (
    `id` BINARY(16) NOT NULL,
    `script` LONGTEXT NOT NULL,
    `hook` VARCHAR(255) NOT NULL,
    `name` VARCHAR(1024) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT '0',
    `app_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.script.app_id` (`app_id`),
    CONSTRAINT `fk.script.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;