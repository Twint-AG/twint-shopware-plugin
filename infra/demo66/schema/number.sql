CREATE TABLE `number_range` (
    `id` BINARY(16) NOT NULL,
    `type_id` BINARY(16) NOT NULL,
    `global` TINYINT(1) NOT NULL DEFAULT '0',
    `pattern` VARCHAR(255) NOT NULL,
    `start` INT(11) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.number_range.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.number_range.type_id` (`type_id`),
    CONSTRAINT `fk.number_range.type_id` FOREIGN KEY (`type_id`) REFERENCES `number_range_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `number_range_sales_channel` (
    `id` BINARY(16) NOT NULL,
    `number_range_id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `number_range_type_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.number_range_sales_channel.number_range_id` (`number_range_id`),
    KEY `fk.number_range_sales_channel.sales_channel_id` (`sales_channel_id`),
    KEY `fk.number_range_sales_channel.number_range_type_id` (`number_range_type_id`),
    CONSTRAINT `fk.number_range_sales_channel.number_range_id` FOREIGN KEY (`number_range_id`) REFERENCES `number_range` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.number_range_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.number_range_sales_channel.number_range_type_id` FOREIGN KEY (`number_range_type_id`) REFERENCES `number_range_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `number_range_state` (
    `id` BINARY(16) NOT NULL,
    `number_range_id` BINARY(16) NOT NULL,
    `last_value` INT(11) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `number_range_type` (
    `id` BINARY(16) NOT NULL,
    `technical_name` VARCHAR(255) NULL,
    `global` TINYINT(1) NOT NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.number_range_type.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `number_range_type_translation` (
    `type_name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `number_range_type_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`number_range_type_id`,`language_id`),
    CONSTRAINT `json.number_range_type_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.number_range_type_translation.number_range_type_id` (`number_range_type_id`),
    KEY `fk.number_range_type_translation.language_id` (`language_id`),
    CONSTRAINT `fk.number_range_type_translation.number_range_type_id` FOREIGN KEY (`number_range_type_id`) REFERENCES `number_range_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.number_range_type_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `number_range_translation` (
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `number_range_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`number_range_id`,`language_id`),
    CONSTRAINT `json.number_range_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.number_range_translation.number_range_id` (`number_range_id`),
    KEY `fk.number_range_translation.language_id` (`language_id`),
    CONSTRAINT `fk.number_range_translation.number_range_id` FOREIGN KEY (`number_range_id`) REFERENCES `number_range` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.number_range_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;