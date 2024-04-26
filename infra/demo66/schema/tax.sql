CREATE TABLE `tax` (
    `id` BINARY(16) NOT NULL,
    `tax_rate` DOUBLE NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `position` INT(11) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.tax.custom_fields` CHECK (JSON_VALID(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tax_rule` (
    `id` BINARY(16) NOT NULL,
    `tax_rule_type_id` BINARY(16) NOT NULL,
    `country_id` BINARY(16) NOT NULL,
    `tax_rate` DOUBLE NOT NULL,
    `data` JSON NULL,
    `tax_id` BINARY(16) NOT NULL,
    `active_from` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.tax_rule.data` CHECK (JSON_VALID(`data`)),
    KEY `fk.tax_rule.tax_rule_type_id` (`tax_rule_type_id`),
    KEY `fk.tax_rule.country_id` (`country_id`),
    KEY `fk.tax_rule.tax_id` (`tax_id`),
    CONSTRAINT `fk.tax_rule.tax_rule_type_id` FOREIGN KEY (`tax_rule_type_id`) REFERENCES `tax_rule_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.tax_rule.country_id` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.tax_rule.tax_id` FOREIGN KEY (`tax_id`) REFERENCES `tax` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tax_rule_type` (
    `id` BINARY(16) NOT NULL,
    `technical_name` VARCHAR(255) NOT NULL,
    `position` INT(11) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.tax_rule_type.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tax_rule_type_translation` (
    `type_name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `tax_rule_type_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`tax_rule_type_id`,`language_id`),
    KEY `fk.tax_rule_type_translation.tax_rule_type_id` (`tax_rule_type_id`),
    KEY `fk.tax_rule_type_translation.language_id` (`language_id`),
    CONSTRAINT `fk.tax_rule_type_translation.tax_rule_type_id` FOREIGN KEY (`tax_rule_type_id`) REFERENCES `tax_rule_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.tax_rule_type_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tax_provider` (
    `id` BINARY(16) NOT NULL,
    `identifier` VARCHAR(255) NOT NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `priority` INT(11) NOT NULL,
    `process_url` VARCHAR(255) NULL,
    `availability_rule_id` BINARY(16) NULL,
    `app_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.tax_provider.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.tax_provider.availability_rule_id` (`availability_rule_id`),
    KEY `fk.tax_provider.app_id` (`app_id`),
    CONSTRAINT `fk.tax_provider.availability_rule_id` FOREIGN KEY (`availability_rule_id`) REFERENCES `rule` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk.tax_provider.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tax_provider_translation` (
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `tax_provider_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`tax_provider_id`,`language_id`),
    CONSTRAINT `json.tax_provider_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.tax_provider_translation.tax_provider_id` (`tax_provider_id`),
    KEY `fk.tax_provider_translation.language_id` (`language_id`),
    CONSTRAINT `fk.tax_provider_translation.tax_provider_id` FOREIGN KEY (`tax_provider_id`) REFERENCES `tax_provider` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.tax_provider_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;