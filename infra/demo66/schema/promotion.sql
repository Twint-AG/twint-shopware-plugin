CREATE TABLE `promotion` (
    `id` BINARY(16) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT '0',
    `valid_from` DATETIME(3) NULL,
    `valid_until` DATETIME(3) NULL,
    `max_redemptions_global` INT(11) NULL,
    `max_redemptions_per_customer` INT(11) NULL,
    `priority` INT(11) NOT NULL,
    `exclusive` TINYINT(1) NOT NULL DEFAULT '0',
    `code` VARCHAR(255) NULL,
    `use_codes` TINYINT(1) NOT NULL DEFAULT '0',
    `use_individual_codes` TINYINT(1) NOT NULL DEFAULT '0',
    `individual_code_pattern` VARCHAR(255) NULL,
    `use_setgroups` TINYINT(1) NOT NULL DEFAULT '0',
    `customer_restriction` TINYINT(1) NULL DEFAULT '0',
    `prevent_combination` TINYINT(1) NOT NULL DEFAULT '0',
    `order_count` INT(11) NULL,
    `orders_per_customer_count` JSON NULL,
    `exclusion_ids` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.promotion.orders_per_customer_count` CHECK (JSON_VALID(`orders_per_customer_count`)),
    CONSTRAINT `json.promotion.exclusion_ids` CHECK (JSON_VALID(`exclusion_ids`)),
    CONSTRAINT `json.promotion.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_sales_channel` (
    `id` BINARY(16) NOT NULL,
    `promotion_id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `priority` INT(11) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.promotion_sales_channel.promotion_id` (`promotion_id`),
    KEY `fk.promotion_sales_channel.sales_channel_id` (`sales_channel_id`),
    CONSTRAINT `fk.promotion_sales_channel.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_individual_code` (
    `id` BINARY(16) NOT NULL,
    `promotion_id` BINARY(16) NOT NULL,
    `code` VARCHAR(255) NOT NULL,
    `payload` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.promotion_individual_code.payload` CHECK (JSON_VALID(`payload`)),
    KEY `fk.promotion_individual_code.promotion_id` (`promotion_id`),
    CONSTRAINT `fk.promotion_individual_code.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_discount` (
    `id` BINARY(16) NOT NULL,
    `promotion_id` BINARY(16) NOT NULL,
    `scope` VARCHAR(255) NOT NULL,
    `type` VARCHAR(32) NOT NULL,
    `value` DOUBLE NOT NULL,
    `consider_advanced_rules` TINYINT(1) NOT NULL DEFAULT '0',
    `max_value` DOUBLE NULL,
    `sorter_key` VARCHAR(32) NULL,
    `applier_key` VARCHAR(32) NULL,
    `usage_key` VARCHAR(32) NULL,
    `picker_key` VARCHAR(32) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.promotion_discount.promotion_id` (`promotion_id`),
    CONSTRAINT `fk.promotion_discount.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_discount_rule` (
    `discount_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`discount_id`,`rule_id`),
    KEY `fk.promotion_discount_rule.discount_id` (`discount_id`),
    KEY `fk.promotion_discount_rule.rule_id` (`rule_id`),
    CONSTRAINT `fk.promotion_discount_rule.discount_id` FOREIGN KEY (`discount_id`) REFERENCES `promotion_discount` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_discount_rule.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_setgroup` (
    `id` BINARY(16) NOT NULL,
    `promotion_id` BINARY(16) NOT NULL,
    `packager_key` VARCHAR(255) NOT NULL,
    `sorter_key` VARCHAR(32) NOT NULL,
    `value` DOUBLE NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.promotion_setgroup.promotion_id` (`promotion_id`),
    CONSTRAINT `fk.promotion_setgroup.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_setgroup_rule` (
    `setgroup_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`setgroup_id`,`rule_id`),
    KEY `fk.promotion_setgroup_rule.setgroup_id` (`setgroup_id`),
    KEY `fk.promotion_setgroup_rule.rule_id` (`rule_id`),
    CONSTRAINT `fk.promotion_setgroup_rule.setgroup_id` FOREIGN KEY (`setgroup_id`) REFERENCES `promotion_setgroup` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_setgroup_rule.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_order_rule` (
    `promotion_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`promotion_id`,`rule_id`),
    KEY `fk.promotion_order_rule.promotion_id` (`promotion_id`),
    KEY `fk.promotion_order_rule.rule_id` (`rule_id`),
    CONSTRAINT `fk.promotion_order_rule.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_order_rule.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_persona_customer` (
    `promotion_id` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`promotion_id`,`customer_id`),
    KEY `fk.promotion_persona_customer.promotion_id` (`promotion_id`),
    KEY `fk.promotion_persona_customer.customer_id` (`customer_id`),
    CONSTRAINT `fk.promotion_persona_customer.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_persona_customer.customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_persona_rule` (
    `promotion_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`promotion_id`,`rule_id`),
    KEY `fk.promotion_persona_rule.promotion_id` (`promotion_id`),
    KEY `fk.promotion_persona_rule.rule_id` (`rule_id`),
    CONSTRAINT `fk.promotion_persona_rule.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_persona_rule.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_cart_rule` (
    `promotion_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`promotion_id`,`rule_id`),
    KEY `fk.promotion_cart_rule.promotion_id` (`promotion_id`),
    KEY `fk.promotion_cart_rule.rule_id` (`rule_id`),
    CONSTRAINT `fk.promotion_cart_rule.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_cart_rule.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_translation` (
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `promotion_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`promotion_id`,`language_id`),
    CONSTRAINT `json.promotion_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.promotion_translation.promotion_id` (`promotion_id`),
    KEY `fk.promotion_translation.language_id` (`language_id`),
    CONSTRAINT `fk.promotion_translation.promotion_id` FOREIGN KEY (`promotion_id`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotion_discount_prices` (
    `id` BINARY(16) NOT NULL,
    `discount_id` BINARY(16) NOT NULL,
    `currency_id` BINARY(16) NOT NULL,
    `price` DOUBLE NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.promotion_discount_prices.discount_id` (`discount_id`),
    KEY `fk.promotion_discount_prices.currency_id` (`currency_id`),
    CONSTRAINT `fk.promotion_discount_prices.discount_id` FOREIGN KEY (`discount_id`) REFERENCES `promotion_discount` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.promotion_discount_prices.currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;