CREATE TABLE `seo_url` (
    `id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NULL,
    `language_id` BINARY(16) NOT NULL,
    `foreign_key` BINARY(16) NOT NULL,
    `route_name` VARCHAR(50) NOT NULL,
    `path_info` VARCHAR(750) NOT NULL,
    `seo_path_info` VARCHAR(750) NOT NULL,
    `is_canonical` TINYINT(1) NULL DEFAULT '0',
    `is_modified` TINYINT(1) NULL DEFAULT '0',
    `is_deleted` TINYINT(1) NULL DEFAULT '0',
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.seo_url.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.seo_url.language_id` (`language_id`),
    KEY `fk.seo_url.sales_channel_id` (`sales_channel_id`),
    CONSTRAINT `fk.seo_url.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.seo_url.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seo_url_template` (
    `id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NULL,
    `entity_name` VARCHAR(64) NOT NULL,
    `route_name` VARCHAR(255) NOT NULL,
    `template` VARCHAR(750) NULL,
    `is_valid` TINYINT(1) NULL DEFAULT '0',
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.seo_url_template.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.seo_url_template.sales_channel_id` (`sales_channel_id`),
    CONSTRAINT `fk.seo_url_template.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;