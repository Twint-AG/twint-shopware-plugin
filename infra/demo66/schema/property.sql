CREATE TABLE `property_group` (
    `id` BINARY(16) NOT NULL,
    `display_type` VARCHAR(255) NOT NULL,
    `sorting_type` VARCHAR(255) NOT NULL,
    `filterable` TINYINT(1) NULL DEFAULT '0',
    `visible_on_product_detail_page` TINYINT(1) NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.property_group.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `property_group_option` (
    `id` BINARY(16) NOT NULL,
    `property_group_id` BINARY(16) NOT NULL,
    `color_hex_code` VARCHAR(255) NULL,
    `media_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.property_group_option.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.property_group_option.media_id` (`media_id`),
    KEY `fk.property_group_option.property_group_id` (`property_group_id`),
    CONSTRAINT `fk.property_group_option.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.property_group_option.property_group_id` FOREIGN KEY (`property_group_id`) REFERENCES `property_group` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `property_group_option_translation` (
    `name` VARCHAR(255) NOT NULL,
    `position` INT(11) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `property_group_option_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`property_group_option_id`,`language_id`),
    CONSTRAINT `json.property_group_option_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.property_group_option_translation.property_group_option_id` (`property_group_option_id`),
    KEY `fk.property_group_option_translation.language_id` (`language_id`),
    CONSTRAINT `fk.property_group_option_translation.property_group_option_id` FOREIGN KEY (`property_group_option_id`) REFERENCES `property_group_option` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.property_group_option_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `property_group_translation` (
    `name` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `position` INT(11) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `property_group_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`property_group_id`,`language_id`),
    CONSTRAINT `json.property_group_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.property_group_translation.property_group_id` (`property_group_id`),
    KEY `fk.property_group_translation.language_id` (`language_id`),
    CONSTRAINT `fk.property_group_translation.property_group_id` FOREIGN KEY (`property_group_id`) REFERENCES `property_group` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.property_group_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;