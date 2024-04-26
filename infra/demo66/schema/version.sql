CREATE TABLE `version` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `version_commit` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `user_id` BINARY(16) NULL,
    `integration_id` BINARY(16) NULL,
    `auto_increment` INT(11) NULL,
    `is_merge` TINYINT(1) NULL DEFAULT '0',
    `message` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.version_commit.version_id` (`version_id`),
    CONSTRAINT `fk.version_commit.version_id` FOREIGN KEY (`version_id`) REFERENCES `version` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `version_commit_data` (
    `id` BINARY(16) NOT NULL,
    `version_commit_id` BINARY(16) NOT NULL,
    `user_id` BINARY(16) NULL,
    `integration_id` BINARY(16) NULL,
    `auto_increment` INT(11) NULL,
    `entity_name` VARCHAR(255) NOT NULL,
    `entity_id` JSON NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `payload` JSON NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.version_commit_data.entity_id` CHECK (JSON_VALID(`entity_id`)),
    CONSTRAINT `json.version_commit_data.payload` CHECK (JSON_VALID(`payload`)),
    KEY `fk.version_commit_data.version_commit_id` (`version_commit_id`),
    CONSTRAINT `fk.version_commit_data.version_commit_id` FOREIGN KEY (`version_commit_id`) REFERENCES `version_commit` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;