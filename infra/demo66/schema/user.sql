CREATE TABLE `user` (
    `id` BINARY(16) NOT NULL,
    `locale_id` BINARY(16) NOT NULL,
    `username` VARCHAR(255) NOT NULL,
    `password` VARCHAR(1024) NOT NULL,
    `first_name` VARCHAR(255) NOT NULL,
    `last_name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NULL,
    `email` VARCHAR(255) NOT NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `admin` TINYINT(1) NULL DEFAULT '0',
    `last_updated_password_at` DATETIME(3) NULL,
    `time_zone` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `avatar_id` BINARY(16) NULL,
    `store_token` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.user.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.user.locale_id` (`locale_id`),
    KEY `fk.user.avatar_id` (`avatar_id`),
    CONSTRAINT `fk.user.locale_id` FOREIGN KEY (`locale_id`) REFERENCES `locale` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_config` (
    `id` BINARY(16) NOT NULL,
    `user_id` BINARY(16) NOT NULL,
    `key` VARCHAR(255) NOT NULL,
    `value` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.user_config.value` CHECK (JSON_VALID(`value`)),
    KEY `fk.user_config.user_id` (`user_id`),
    CONSTRAINT `fk.user_config.user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_access_key` (
    `id` BINARY(16) NOT NULL,
    `user_id` BINARY(16) NOT NULL,
    `access_key` VARCHAR(255) NOT NULL,
    `secret_access_key` VARCHAR(1024) NOT NULL,
    `last_usage_at` DATETIME(3) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.user_access_key.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.user_access_key.user_id` (`user_id`),
    CONSTRAINT `fk.user_access_key.user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_recovery` (
    `id` BINARY(16) NOT NULL,
    `hash` VARCHAR(255) NOT NULL,
    `user_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;