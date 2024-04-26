CREATE TABLE `sales_channel` (
    `id` BINARY(16) NOT NULL,
    `type_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `customer_group_id` BINARY(16) NOT NULL,
    `currency_id` BINARY(16) NOT NULL,
    `payment_method_id` BINARY(16) NOT NULL,
    `shipping_method_id` BINARY(16) NOT NULL,
    `country_id` BINARY(16) NOT NULL,
    `analytics_id` BINARY(16) NULL,
    `navigation_category_id` BINARY(16) NOT NULL,
    `navigation_category_version_id` BINARY(16) NOT NULL,
    `navigation_category_depth` INT(11) NULL,
    `footer_category_id` BINARY(16) NULL,
    `footer_category_version_id` BINARY(16) NOT NULL,
    `service_category_id` BINARY(16) NULL,
    `service_category_version_id` BINARY(16) NOT NULL,
    `mail_header_footer_id` BINARY(16) NULL,
    `hreflang_default_domain_id` BINARY(16) NULL,
    `short_name` VARCHAR(255) NULL,
    `tax_calculation_type` VARCHAR(255) NULL,
    `access_key` VARCHAR(255) NOT NULL,
    `configuration` JSON NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `hreflang_active` TINYINT(1) NULL DEFAULT '0',
    `maintenance` TINYINT(1) NULL DEFAULT '0',
    `maintenance_ip_whitelist` JSON NULL,
    `payment_method_ids` JSON NULL,
    `home_cms_page_id` BINARY(16) NULL,
    `home_cms_page_version_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.sales_channel.configuration` CHECK (JSON_VALID(`configuration`)),
    CONSTRAINT `json.sales_channel.maintenance_ip_whitelist` CHECK (JSON_VALID(`maintenance_ip_whitelist`)),
    CONSTRAINT `json.sales_channel.payment_method_ids` CHECK (JSON_VALID(`payment_method_ids`)),
    CONSTRAINT `json.sales_channel.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.sales_channel.type_id` (`type_id`),
    KEY `fk.sales_channel.language_id` (`language_id`),
    KEY `fk.sales_channel.customer_group_id` (`customer_group_id`),
    KEY `fk.sales_channel.currency_id` (`currency_id`),
    KEY `fk.sales_channel.payment_method_id` (`payment_method_id`),
    KEY `fk.sales_channel.shipping_method_id` (`shipping_method_id`),
    KEY `fk.sales_channel.country_id` (`country_id`),
    KEY `fk.sales_channel.home_cms_page_id` (`home_cms_page_id`,`home_cms_page_version_id`),
    KEY `fk.sales_channel.navigation_category_id` (`navigation_category_id`,`navigation_category_version_id`),
    KEY `fk.sales_channel.footer_category_id` (`footer_category_id`,`navigation_category_version_id`),
    KEY `fk.sales_channel.service_category_id` (`service_category_id`,`navigation_category_version_id`),
    KEY `fk.sales_channel.mail_header_footer_id` (`mail_header_footer_id`),
    CONSTRAINT `fk.sales_channel.type_id` FOREIGN KEY (`type_id`) REFERENCES `sales_channel_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.customer_group_id` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_group` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.shipping_method_id` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.country_id` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.home_cms_page_id` FOREIGN KEY (`home_cms_page_id`,`home_cms_page_version_id`) REFERENCES `cms_page` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.navigation_category_id` FOREIGN KEY (`navigation_category_id`,`navigation_category_version_id`) REFERENCES `category` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.footer_category_id` FOREIGN KEY (`footer_category_id`,`navigation_category_version_id`) REFERENCES `category` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.service_category_id` FOREIGN KEY (`service_category_id`,`navigation_category_version_id`) REFERENCES `category` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel.mail_header_footer_id` FOREIGN KEY (`mail_header_footer_id`) REFERENCES `mail_header_footer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_translation` (
    `name` VARCHAR(255) NOT NULL,
    `home_slot_config` JSON NULL,
    `home_enabled` TINYINT(1) NOT NULL DEFAULT '0',
    `home_name` VARCHAR(255) NULL,
    `home_meta_title` VARCHAR(255) NULL,
    `home_meta_description` VARCHAR(255) NULL,
    `home_keywords` VARCHAR(255) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`sales_channel_id`,`language_id`),
    CONSTRAINT `json.sales_channel_translation.home_slot_config` CHECK (JSON_VALID(`home_slot_config`)),
    CONSTRAINT `json.sales_channel_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.sales_channel_translation.sales_channel_id` (`sales_channel_id`),
    KEY `fk.sales_channel_translation.language_id` (`language_id`),
    CONSTRAINT `fk.sales_channel_translation.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_country` (
    `sales_channel_id` BINARY(16) NOT NULL,
    `country_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`sales_channel_id`,`country_id`),
    KEY `fk.sales_channel_country.sales_channel_id` (`sales_channel_id`),
    KEY `fk.sales_channel_country.country_id` (`country_id`),
    CONSTRAINT `fk.sales_channel_country.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_country.country_id` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_currency` (
    `sales_channel_id` BINARY(16) NOT NULL,
    `currency_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`sales_channel_id`,`currency_id`),
    KEY `fk.sales_channel_currency.sales_channel_id` (`sales_channel_id`),
    KEY `fk.sales_channel_currency.currency_id` (`currency_id`),
    CONSTRAINT `fk.sales_channel_currency.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_currency.currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_domain` (
    `id` BINARY(16) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `currency_id` BINARY(16) NOT NULL,
    `snippet_set_id` BINARY(16) NOT NULL,
    `hreflang_use_only_locale` TINYINT(1) NULL DEFAULT '0',
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.sales_channel_domain.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.sales_channel_domain.sales_channel_id` (`sales_channel_id`),
    KEY `fk.sales_channel_domain.language_id` (`language_id`),
    KEY `fk.sales_channel_domain.currency_id` (`currency_id`),
    KEY `fk.sales_channel_domain.snippet_set_id` (`snippet_set_id`),
    CONSTRAINT `fk.sales_channel_domain.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_domain.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_domain.currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_domain.snippet_set_id` FOREIGN KEY (`snippet_set_id`) REFERENCES `snippet_set` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_language` (
    `sales_channel_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`sales_channel_id`,`language_id`),
    KEY `fk.sales_channel_language.sales_channel_id` (`sales_channel_id`),
    KEY `fk.sales_channel_language.language_id` (`language_id`),
    CONSTRAINT `fk.sales_channel_language.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_language.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_payment_method` (
    `sales_channel_id` BINARY(16) NOT NULL,
    `payment_method_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`sales_channel_id`,`payment_method_id`),
    KEY `fk.sales_channel_payment_method.sales_channel_id` (`sales_channel_id`),
    KEY `fk.sales_channel_payment_method.payment_method_id` (`payment_method_id`),
    CONSTRAINT `fk.sales_channel_payment_method.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_payment_method.payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_shipping_method` (
    `sales_channel_id` BINARY(16) NOT NULL,
    `shipping_method_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`sales_channel_id`,`shipping_method_id`),
    KEY `fk.sales_channel_shipping_method.sales_channel_id` (`sales_channel_id`),
    KEY `fk.sales_channel_shipping_method.shipping_method_id` (`shipping_method_id`),
    CONSTRAINT `fk.sales_channel_shipping_method.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_shipping_method.shipping_method_id` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_type` (
    `id` BINARY(16) NOT NULL,
    `cover_url` VARCHAR(255) NULL,
    `icon_name` VARCHAR(255) NULL,
    `screenshot_urls` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.sales_channel_type.screenshot_urls` CHECK (JSON_VALID(`screenshot_urls`)),
    CONSTRAINT `json.sales_channel_type.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_type_translation` (
    `name` VARCHAR(255) NOT NULL,
    `manufacturer` VARCHAR(255) NULL,
    `description` VARCHAR(255) NULL,
    `description_long` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `sales_channel_type_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`sales_channel_type_id`,`language_id`),
    CONSTRAINT `json.sales_channel_type_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.sales_channel_type_translation.sales_channel_type_id` (`sales_channel_type_id`),
    KEY `fk.sales_channel_type_translation.language_id` (`language_id`),
    CONSTRAINT `fk.sales_channel_type_translation.sales_channel_type_id` FOREIGN KEY (`sales_channel_type_id`) REFERENCES `sales_channel_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.sales_channel_type_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_channel_analytics` (
    `id` BINARY(16) NOT NULL,
    `tracking_id` VARCHAR(255) NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `track_orders` TINYINT(1) NULL DEFAULT '0',
    `anonymize_ip` TINYINT(1) NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;