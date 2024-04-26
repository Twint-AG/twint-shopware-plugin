CREATE TABLE `landing_page` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `cms_page_id` BINARY(16) NULL,
    `cms_page_version_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    CONSTRAINT `json.landing_page.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.landing_page.cms_page_id` (`cms_page_id`,`cms_page_version_id`),
    CONSTRAINT `fk.landing_page.cms_page_id` FOREIGN KEY (`cms_page_id`,`cms_page_version_id`) REFERENCES `cms_page` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `landing_page_translation` (
    `name` VARCHAR(255) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `slot_config` JSON NULL,
    `meta_title` LONGTEXT NULL,
    `meta_description` LONGTEXT NULL,
    `keywords` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `landing_page_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `landing_page_version_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`landing_page_id`,`language_id`,`landing_page_version_id`),
    CONSTRAINT `json.landing_page_translation.slot_config` CHECK (JSON_VALID(`slot_config`)),
    CONSTRAINT `json.landing_page_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.landing_page_translation.landing_page_id` (`landing_page_id`,`landing_page_version_id`),
    KEY `fk.landing_page_translation.language_id` (`language_id`),
    CONSTRAINT `fk.landing_page_translation.landing_page_id` FOREIGN KEY (`landing_page_id`,`landing_page_version_id`) REFERENCES `landing_page` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.landing_page_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `landing_page_tag` (
    `landing_page_id` BINARY(16) NOT NULL,
    `landing_page_version_id` BINARY(16) NOT NULL,
    `tag_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`landing_page_id`,`landing_page_version_id`,`tag_id`),
    KEY `fk.landing_page_tag.landing_page_id` (`landing_page_id`,`landing_page_version_id`),
    KEY `fk.landing_page_tag.tag_id` (`tag_id`),
    CONSTRAINT `fk.landing_page_tag.landing_page_id` FOREIGN KEY (`landing_page_id`,`landing_page_version_id`) REFERENCES `landing_page` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.landing_page_tag.tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `landing_page_sales_channel` (
    `landing_page_id` BINARY(16) NOT NULL,
    `landing_page_version_id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`landing_page_id`,`landing_page_version_id`,`sales_channel_id`),
    KEY `fk.landing_page_sales_channel.landing_page_id` (`landing_page_id`,`landing_page_version_id`),
    KEY `fk.landing_page_sales_channel.sales_channel_id` (`sales_channel_id`),
    CONSTRAINT `fk.landing_page_sales_channel.landing_page_id` FOREIGN KEY (`landing_page_id`,`landing_page_version_id`) REFERENCES `landing_page` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.landing_page_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;