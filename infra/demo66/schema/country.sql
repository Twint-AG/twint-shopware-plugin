CREATE TABLE `country` (
    `id` BINARY(16) NOT NULL,
    `iso` VARCHAR(255) NULL,
    `position` INT(11) NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `shipping_available` TINYINT(1) NULL DEFAULT '0',
    `iso3` VARCHAR(255) NULL,
    `display_state_in_registration` TINYINT(1) NULL DEFAULT '0',
    `force_state_in_registration` TINYINT(1) NULL DEFAULT '0',
    `check_vat_id_pattern` TINYINT(1) NULL DEFAULT '0',
    `vat_id_required` TINYINT(1) NULL DEFAULT '0',
    `vat_id_pattern` VARCHAR(255) NULL,
    `customer_tax` JSON NULL,
    `company_tax` JSON NULL,
    `postal_code_required` TINYINT(1) NULL DEFAULT '0',
    `check_postal_code_pattern` TINYINT(1) NULL DEFAULT '0',
    `check_advanced_postal_code_pattern` TINYINT(1) NULL DEFAULT '0',
    `advanced_postal_code_pattern` VARCHAR(255) NULL,
    `default_postal_code_pattern` VARCHAR(1024) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.country.customer_tax` CHECK (JSON_VALID(`customer_tax`)),
    CONSTRAINT `json.country.company_tax` CHECK (JSON_VALID(`company_tax`)),
    CONSTRAINT `json.country.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `country_state` (
    `id` BINARY(16) NOT NULL,
    `country_id` BINARY(16) NOT NULL,
    `short_code` VARCHAR(255) NOT NULL,
    `position` INT(11) NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.country_state.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.country_state.country_id` (`country_id`),
    CONSTRAINT `fk.country_state.country_id` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `country_state_translation` (
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `country_state_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`country_state_id`,`language_id`),
    CONSTRAINT `json.country_state_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.country_state_translation.country_state_id` (`country_state_id`),
    KEY `fk.country_state_translation.language_id` (`language_id`),
    CONSTRAINT `fk.country_state_translation.country_state_id` FOREIGN KEY (`country_state_id`) REFERENCES `country_state` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.country_state_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `country_translation` (
    `name` VARCHAR(255) NOT NULL,
    `address_format` JSON NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `country_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`country_id`,`language_id`),
    CONSTRAINT `json.country_translation.address_format` CHECK (JSON_VALID(`address_format`)),
    CONSTRAINT `json.country_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.country_translation.country_id` (`country_id`),
    KEY `fk.country_translation.language_id` (`language_id`),
    CONSTRAINT `fk.country_translation.country_id` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.country_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;