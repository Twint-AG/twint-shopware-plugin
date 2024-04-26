CREATE TABLE `document` (
    `id` BINARY(16) NOT NULL,
    `document_type_id` BINARY(16) NOT NULL,
    `file_type` VARCHAR(255) NOT NULL,
    `referenced_document_id` BINARY(16) NULL,
    `order_id` BINARY(16) NOT NULL,
    `document_media_file_id` BINARY(16) NULL,
    `order_version_id` BINARY(16) NOT NULL,
    `config` JSON NOT NULL,
    `sent` TINYINT(1) NULL DEFAULT '0',
    `static` TINYINT(1) NULL DEFAULT '0',
    `deep_link_code` VARCHAR(255) NOT NULL,
    `document_number` VARCHAR(64) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.document.config` CHECK (JSON_VALID(`config`)),
    CONSTRAINT `json.document.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.document.document_type_id` (`document_type_id`),
    KEY `fk.document.order_id` (`order_id`,`order_version_id`),
    KEY `fk.document.referenced_document_id` (`referenced_document_id`),
    KEY `fk.document.document_media_file_id` (`document_media_file_id`),
    CONSTRAINT `fk.document.document_type_id` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.document.order_id` FOREIGN KEY (`order_id`,`order_version_id`) REFERENCES `order` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.document.document_media_file_id` FOREIGN KEY (`document_media_file_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_type` (
    `id` BINARY(16) NOT NULL,
    `technical_name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.document_type.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_type_translation` (
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `document_type_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`document_type_id`,`language_id`),
    CONSTRAINT `json.document_type_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.document_type_translation.document_type_id` (`document_type_id`),
    KEY `fk.document_type_translation.language_id` (`language_id`),
    CONSTRAINT `fk.document_type_translation.document_type_id` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.document_type_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_base_config` (
    `id` BINARY(16) NOT NULL,
    `document_type_id` BINARY(16) NOT NULL,
    `logo_id` BINARY(16) NULL,
    `name` VARCHAR(255) NOT NULL,
    `filename_prefix` VARCHAR(255) NULL,
    `filename_suffix` VARCHAR(255) NULL,
    `global` TINYINT(1) NOT NULL DEFAULT '0',
    `document_number` VARCHAR(64) NULL,
    `config` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `custom_fields` JSON NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.document_base_config.config` CHECK (JSON_VALID(`config`)),
    CONSTRAINT `json.document_base_config.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.document_base_config.document_type_id` (`document_type_id`),
    KEY `fk.document_base_config.logo_id` (`logo_id`),
    CONSTRAINT `fk.document_base_config.document_type_id` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.document_base_config.logo_id` FOREIGN KEY (`logo_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_base_config_sales_channel` (
    `id` BINARY(16) NOT NULL,
    `document_base_config_id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NULL,
    `document_type_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.document_base_config_sales_channel.document_type_id` (`document_type_id`),
    KEY `fk.document_base_config_sales_channel.document_base_config_id` (`document_base_config_id`),
    KEY `fk.document_base_config_sales_channel.sales_channel_id` (`sales_channel_id`),
    CONSTRAINT `fk.document_base_config_sales_channel.document_type_id` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.document_base_config_sales_channel.document_base_config_id` FOREIGN KEY (`document_base_config_id`) REFERENCES `document_base_config` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.document_base_config_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;