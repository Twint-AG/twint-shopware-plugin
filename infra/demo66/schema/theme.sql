CREATE TABLE `theme` (
    `id` BINARY(16) NOT NULL,
    `technical_name` VARCHAR(255) NULL,
    `name` VARCHAR(255) NOT NULL,
    `author` VARCHAR(255) NOT NULL,
    `preview_media_id` BINARY(16) NULL,
    `parent_theme_id` BINARY(16) NULL,
    `theme_json` JSON NULL,
    `base_config` JSON NULL,
    `config_values` JSON NULL,
    `active` TINYINT(1) NOT NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.theme.theme_json` CHECK (JSON_VALID(`theme_json`)),
    CONSTRAINT `json.theme.base_config` CHECK (JSON_VALID(`base_config`)),
    CONSTRAINT `json.theme.config_values` CHECK (JSON_VALID(`config_values`)),
    CONSTRAINT `json.theme.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.theme.preview_media_id` (`preview_media_id`),
    CONSTRAINT `fk.theme.preview_media_id` FOREIGN KEY (`preview_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `theme_translation` (
    `description` VARCHAR(255) NULL,
    `labels` JSON NULL,
    `help_texts` JSON NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `theme_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`theme_id`,`language_id`),
    CONSTRAINT `json.theme_translation.labels` CHECK (JSON_VALID(`labels`)),
    CONSTRAINT `json.theme_translation.help_texts` CHECK (JSON_VALID(`help_texts`)),
    CONSTRAINT `json.theme_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.theme_translation.theme_id` (`theme_id`),
    KEY `fk.theme_translation.language_id` (`language_id`),
    CONSTRAINT `fk.theme_translation.theme_id` FOREIGN KEY (`theme_id`) REFERENCES `theme` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.theme_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `theme_sales_channel` (
    `sales_channel_id` BINARY(16) NOT NULL,
    `theme_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`sales_channel_id`),
    KEY `fk.theme_sales_channel.theme_id` (`theme_id`),
    KEY `fk.theme_sales_channel.sales_channel_id` (`sales_channel_id`),
    CONSTRAINT `fk.theme_sales_channel.theme_id` FOREIGN KEY (`theme_id`) REFERENCES `theme` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.theme_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `theme_media` (
    `theme_id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`theme_id`,`media_id`),
    KEY `fk.theme_media.theme_id` (`theme_id`),
    KEY `fk.theme_media.media_id` (`media_id`),
    CONSTRAINT `fk.theme_media.theme_id` FOREIGN KEY (`theme_id`) REFERENCES `theme` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.theme_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `theme_child` (
    `parent_id` BINARY(16) NOT NULL,
    `child_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`parent_id`,`child_id`),
    KEY `fk.theme_child.parent_id` (`parent_id`),
    KEY `fk.theme_child.child_id` (`child_id`),
    CONSTRAINT `fk.theme_child.parent_id` FOREIGN KEY (`parent_id`) REFERENCES `theme` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.theme_child.child_id` FOREIGN KEY (`child_id`) REFERENCES `theme` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;