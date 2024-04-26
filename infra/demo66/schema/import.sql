CREATE TABLE `import_export_profile` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NULL,
    `type` VARCHAR(255) NULL,
    `system_default` TINYINT(1) NULL DEFAULT '0',
    `source_entity` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(255) NOT NULL,
    `delimiter` VARCHAR(255) NOT NULL,
    `enclosure` VARCHAR(255) NOT NULL,
    `mapping` JSON NULL,
    `update_by` JSON NULL,
    `config` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.import_export_profile.mapping` CHECK (JSON_VALID(`mapping`)),
    CONSTRAINT `json.import_export_profile.update_by` CHECK (JSON_VALID(`update_by`)),
    CONSTRAINT `json.import_export_profile.config` CHECK (JSON_VALID(`config`)),
    CONSTRAINT `json.import_export_profile.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `import_export_log` (
    `id` BINARY(16) NOT NULL,
    `activity` VARCHAR(255) NOT NULL,
    `state` VARCHAR(255) NOT NULL,
    `records` INT(11) NOT NULL,
    `user_id` BINARY(16) NULL,
    `profile_id` BINARY(16) NULL,
    `file_id` BINARY(16) NULL,
    `invalid_records_log_id` BINARY(16) NULL,
    `username` VARCHAR(255) NULL,
    `profile_name` VARCHAR(255) NULL,
    `config` JSON NOT NULL,
    `result` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.import_export_log.config` CHECK (JSON_VALID(`config`)),
    CONSTRAINT `json.import_export_log.result` CHECK (JSON_VALID(`result`)),
    KEY `fk.import_export_log.user_id` (`user_id`),
    KEY `fk.import_export_log.profile_id` (`profile_id`),
    CONSTRAINT `fk.import_export_log.user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.import_export_log.profile_id` FOREIGN KEY (`profile_id`) REFERENCES `import_export_profile` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `import_export_file` (
    `id` BINARY(16) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `expire_date` DATETIME(3) NOT NULL,
    `size` INT(11) NULL,
    `access_token` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `import_export_profile_translation` (
    `label` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `import_export_profile_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`import_export_profile_id`,`language_id`),
    KEY `fk.import_export_profile_translation.import_export_profile_id` (`import_export_profile_id`),
    KEY `fk.import_export_profile_translation.language_id` (`language_id`),
    CONSTRAINT `fk.import_export_profile_translation.import_export_profile_id` FOREIGN KEY (`import_export_profile_id`) REFERENCES `import_export_profile` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.import_export_profile_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;