CREATE TABLE `shipping_method` (
    `id` BINARY(16) NOT NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `position` INT(11) NULL,
    `availability_rule_id` BINARY(16) NULL,
    `media_id` BINARY(16) NULL,
    `delivery_time_id` BINARY(16) NOT NULL,
    `tax_type` VARCHAR(50) NOT NULL,
    `tax_id` BINARY(16) NULL,
    `technical_name` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.shipping_method.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.shipping_method.delivery_time_id` (`delivery_time_id`),
    KEY `fk.shipping_method.availability_rule_id` (`availability_rule_id`),
    KEY `fk.shipping_method.media_id` (`media_id`),
    KEY `fk.shipping_method.tax_id` (`tax_id`),
    CONSTRAINT `fk.shipping_method.delivery_time_id` FOREIGN KEY (`delivery_time_id`) REFERENCES `delivery_time` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.shipping_method.availability_rule_id` FOREIGN KEY (`availability_rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.shipping_method.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.shipping_method.tax_id` FOREIGN KEY (`tax_id`) REFERENCES `tax` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shipping_method_tag` (
    `shipping_method_id` BINARY(16) NOT NULL,
    `tag_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`shipping_method_id`,`tag_id`),
    KEY `fk.shipping_method_tag.shipping_method_id` (`shipping_method_id`),
    KEY `fk.shipping_method_tag.tag_id` (`tag_id`),
    CONSTRAINT `fk.shipping_method_tag.shipping_method_id` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.shipping_method_tag.tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shipping_method_price` (
    `id` BINARY(16) NOT NULL,
    `shipping_method_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NULL,
    `calculation` INT(11) NULL,
    `calculation_rule_id` BINARY(16) NULL,
    `quantity_start` DOUBLE NULL,
    `quantity_end` DOUBLE NULL,
    `currency_price` JSON NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.shipping_method_price.currency_price` CHECK (JSON_VALID(`currency_price`)),
    CONSTRAINT `json.shipping_method_price.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.shipping_method_price.shipping_method_id` (`shipping_method_id`),
    KEY `fk.shipping_method_price.rule_id` (`rule_id`),
    KEY `fk.shipping_method_price.calculation_rule_id` (`calculation_rule_id`),
    CONSTRAINT `fk.shipping_method_price.shipping_method_id` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.shipping_method_price.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.shipping_method_price.calculation_rule_id` FOREIGN KEY (`calculation_rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shipping_method_translation` (
    `name` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `tracking_url` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `shipping_method_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`shipping_method_id`,`language_id`),
    CONSTRAINT `json.shipping_method_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.shipping_method_translation.shipping_method_id` (`shipping_method_id`),
    KEY `fk.shipping_method_translation.language_id` (`language_id`),
    CONSTRAINT `fk.shipping_method_translation.shipping_method_id` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.shipping_method_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;