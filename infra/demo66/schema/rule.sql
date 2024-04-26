CREATE TABLE `rule` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `priority` INT(11) NOT NULL,
    `description` LONGTEXT NULL,
    `payload` LONGBLOB NULL,
    `invalid` TINYINT(1) NULL DEFAULT '0',
    `areas` JSON NULL,
    `custom_fields` JSON NULL,
    `module_types` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.rule.areas` CHECK (JSON_VALID(`areas`)),
    CONSTRAINT `json.rule.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    CONSTRAINT `json.rule.module_types` CHECK (JSON_VALID(`module_types`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rule_condition` (
    `id` BINARY(16) NOT NULL,
    `type` VARCHAR(255) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    `script_id` BINARY(16) NULL,
    `parent_id` BINARY(16) NULL,
    `value` JSON NULL,
    `position` INT(11) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.rule_condition.value` CHECK (JSON_VALID(`value`)),
    CONSTRAINT `json.rule_condition.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.rule_condition.rule_id` (`rule_id`),
    KEY `fk.rule_condition.script_id` (`script_id`),
    KEY `fk.rule_condition.parent_id` (`parent_id`),
    CONSTRAINT `fk.rule_condition.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.rule_condition.script_id` FOREIGN KEY (`script_id`) REFERENCES `app_script_condition` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.rule_condition.parent_id` FOREIGN KEY (`parent_id`) REFERENCES `rule_condition` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rule_tag` (
    `rule_id` BINARY(16) NOT NULL,
    `tag_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`rule_id`,`tag_id`),
    KEY `fk.rule_tag.rule_id` (`rule_id`),
    KEY `fk.rule_tag.tag_id` (`tag_id`),
    CONSTRAINT `fk.rule_tag.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.rule_tag.tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;