CREATE TABLE `flow` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `event_name` VARCHAR(255) NOT NULL,
    `priority` INT(11) NULL,
    `payload` LONGBLOB NULL,
    `invalid` TINYINT(1) NULL DEFAULT '0',
    `active` TINYINT(1) NULL DEFAULT '0',
    `description` VARCHAR(500) NULL,
    `custom_fields` JSON NULL,
    `app_flow_event_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.flow.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.flow.app_flow_event_id` (`app_flow_event_id`),
    CONSTRAINT `fk.flow.app_flow_event_id` FOREIGN KEY (`app_flow_event_id`) REFERENCES `app_flow_event` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `flow_sequence` (
    `id` BINARY(16) NOT NULL,
    `flow_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NULL,
    `action_name` VARCHAR(255) NULL,
    `config` JSON NULL,
    `position` INT(11) NULL,
    `display_group` INT(11) NULL,
    `true_case` TINYINT(1) NULL DEFAULT '0',
    `parent_id` BINARY(16) NULL,
    `custom_fields` JSON NULL,
    `app_flow_action_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.flow_sequence.config` CHECK (JSON_VALID(`config`)),
    CONSTRAINT `json.flow_sequence.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.flow_sequence.flow_id` (`flow_id`),
    KEY `fk.flow_sequence.rule_id` (`rule_id`),
    KEY `fk.flow_sequence.parent_id` (`parent_id`),
    KEY `fk.flow_sequence.app_flow_action_id` (`app_flow_action_id`),
    CONSTRAINT `fk.flow_sequence.flow_id` FOREIGN KEY (`flow_id`) REFERENCES `flow` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.flow_sequence.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.flow_sequence.parent_id` FOREIGN KEY (`parent_id`) REFERENCES `flow_sequence` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.flow_sequence.app_flow_action_id` FOREIGN KEY (`app_flow_action_id`) REFERENCES `app_flow_action` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `flow_template` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `config` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.flow_template.config` CHECK (JSON_VALID(`config`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;