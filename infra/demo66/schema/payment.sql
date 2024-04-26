CREATE TABLE `payment_method` (
    `id` BINARY(16) NOT NULL,
    `plugin_id` BINARY(16) NULL,
    `handler_identifier` VARCHAR(255) NULL,
    `position` INT(11) NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `after_order_enabled` TINYINT(1) NULL DEFAULT '0',
    `availability_rule_id` BINARY(16) NULL,
    `media_id` BINARY(16) NULL,
    `technical_name` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.payment_method.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.payment_method.media_id` (`media_id`),
    KEY `fk.payment_method.availability_rule_id` (`availability_rule_id`),
    KEY `fk.payment_method.plugin_id` (`plugin_id`),
    CONSTRAINT `fk.payment_method.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.payment_method.availability_rule_id` FOREIGN KEY (`availability_rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.payment_method.plugin_id` FOREIGN KEY (`plugin_id`) REFERENCES `plugin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_method_translation` (
    `name` VARCHAR(255) NOT NULL,
    `distinguishable_name` VARCHAR(255) NULL,
    `description` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `payment_method_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`payment_method_id`,`language_id`),
    CONSTRAINT `json.payment_method_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.payment_method_translation.payment_method_id` (`payment_method_id`),
    KEY `fk.payment_method_translation.language_id` (`language_id`),
    CONSTRAINT `fk.payment_method_translation.payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.payment_method_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;