CREATE TABLE `app` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `path` VARCHAR(4096) NOT NULL,
    `author` VARCHAR(255) NULL,
    `copyright` VARCHAR(255) NULL,
    `license` VARCHAR(255) NULL,
    `active` TINYINT(1) NOT NULL DEFAULT '0',
    `configurable` TINYINT(1) NOT NULL DEFAULT '0',
    `privacy` VARCHAR(255) NULL,
    `version` VARCHAR(255) NOT NULL,
    `icon` LONGBLOB NULL,
    `app_secret` VARCHAR(255) NULL,
    `modules` JSON NULL,
    `main_module` JSON NULL,
    `cookies` JSON NULL,
    `allow_disable` TINYINT(1) NOT NULL DEFAULT '0',
    `base_app_url` VARCHAR(1024) NULL,
    `allowed_hosts` JSON NULL,
    `template_load_priority` INT(11) NULL,
    `integration_id` BINARY(16) NOT NULL,
    `acl_role_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.app.modules` CHECK (JSON_VALID(`modules`)),
    CONSTRAINT `json.app.main_module` CHECK (JSON_VALID(`main_module`)),
    CONSTRAINT `json.app.cookies` CHECK (JSON_VALID(`cookies`)),
    CONSTRAINT `json.app.allowed_hosts` CHECK (JSON_VALID(`allowed_hosts`)),
    CONSTRAINT `json.app.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_translation` (
    `label` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `privacy_policy_extensions` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `app_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`app_id`,`language_id`),
    CONSTRAINT `json.app_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.app_translation.app_id` (`app_id`),
    KEY `fk.app_translation.language_id` (`language_id`),
    CONSTRAINT `fk.app_translation.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.app_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_action_button` (
    `id` BINARY(16) NOT NULL,
    `entity` VARCHAR(255) NOT NULL,
    `view` VARCHAR(255) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `app_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.app_action_button.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.app_action_button.app_id` (`app_id`),
    CONSTRAINT `fk.app_action_button.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_action_button_translation` (
    `label` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `app_action_button_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`app_action_button_id`,`language_id`),
    KEY `fk.app_action_button_translation.app_action_button_id` (`app_action_button_id`),
    KEY `fk.app_action_button_translation.language_id` (`language_id`),
    CONSTRAINT `fk.app_action_button_translation.app_action_button_id` FOREIGN KEY (`app_action_button_id`) REFERENCES `app_action_button` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.app_action_button_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_template` (
    `id` BINARY(16) NOT NULL,
    `template` LONGTEXT NOT NULL,
    `path` VARCHAR(1024) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT '0',
    `app_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.app_template.app_id` (`app_id`),
    CONSTRAINT `fk.app_template.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_payment_method` (
    `id` BINARY(16) NOT NULL,
    `app_name` VARCHAR(255) NOT NULL,
    `identifier` VARCHAR(255) NOT NULL,
    `pay_url` VARCHAR(255) NULL,
    `finalize_url` VARCHAR(255) NULL,
    `validate_url` VARCHAR(255) NULL,
    `capture_url` VARCHAR(255) NULL,
    `refund_url` VARCHAR(255) NULL,
    `recurring_url` VARCHAR(255) NULL,
    `app_id` BINARY(16) NULL,
    `original_media_id` BINARY(16) NULL,
    `payment_method_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.app_payment_method.app_id` (`app_id`),
    KEY `fk.app_payment_method.original_media_id` (`original_media_id`),
    CONSTRAINT `fk.app_payment_method.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.app_payment_method.original_media_id` FOREIGN KEY (`original_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_script_condition` (
    `id` BINARY(16) NOT NULL,
    `identifier` VARCHAR(255) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT '0',
    `group` VARCHAR(255) NULL,
    `script` LONGTEXT NULL,
    `constraints` LONGBLOB NULL,
    `config` JSON NULL,
    `app_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.app_script_condition.config` CHECK (JSON_VALID(`config`)),
    CONSTRAINT `json.app_script_condition.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.app_script_condition.app_id` (`app_id`),
    CONSTRAINT `fk.app_script_condition.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_script_condition_translation` (
    `name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `app_script_condition_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`app_script_condition_id`,`language_id`),
    KEY `fk.app_script_condition_translation.app_script_condition_id` (`app_script_condition_id`),
    KEY `fk.app_script_condition_translation.language_id` (`language_id`),
    CONSTRAINT `fk.app_script_condition_translation.app_script_condition_id` FOREIGN KEY (`app_script_condition_id`) REFERENCES `app_script_condition` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.app_script_condition_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_cms_block` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `block` JSON NOT NULL,
    `template` LONGTEXT NOT NULL,
    `styles` LONGTEXT NOT NULL,
    `app_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.app_cms_block.block` CHECK (JSON_VALID(`block`)),
    CONSTRAINT `json.app_cms_block.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.app_cms_block.app_id` (`app_id`),
    CONSTRAINT `fk.app_cms_block.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_cms_block_translation` (
    `label` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `app_cms_block_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`app_cms_block_id`,`language_id`),
    KEY `fk.app_cms_block_translation.app_cms_block_id` (`app_cms_block_id`),
    KEY `fk.app_cms_block_translation.language_id` (`language_id`),
    CONSTRAINT `fk.app_cms_block_translation.app_cms_block_id` FOREIGN KEY (`app_cms_block_id`) REFERENCES `app_cms_block` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.app_cms_block_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_flow_action` (
    `id` BINARY(16) NOT NULL,
    `app_id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `badge` VARCHAR(255) NULL,
    `parameters` JSON NULL,
    `config` JSON NULL,
    `headers` JSON NULL,
    `requirements` JSON NULL,
    `icon` LONGBLOB NULL,
    `sw_icon` VARCHAR(255) NULL,
    `url` VARCHAR(255) NOT NULL,
    `delayable` TINYINT(1) NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.app_flow_action.parameters` CHECK (JSON_VALID(`parameters`)),
    CONSTRAINT `json.app_flow_action.config` CHECK (JSON_VALID(`config`)),
    CONSTRAINT `json.app_flow_action.headers` CHECK (JSON_VALID(`headers`)),
    CONSTRAINT `json.app_flow_action.requirements` CHECK (JSON_VALID(`requirements`)),
    CONSTRAINT `json.app_flow_action.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.app_flow_action.app_id` (`app_id`),
    CONSTRAINT `fk.app_flow_action.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_flow_action_translation` (
    `label` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `headline` VARCHAR(255) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `app_flow_action_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`app_flow_action_id`,`language_id`),
    CONSTRAINT `json.app_flow_action_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.app_flow_action_translation.app_flow_action_id` (`app_flow_action_id`),
    KEY `fk.app_flow_action_translation.language_id` (`language_id`),
    CONSTRAINT `fk.app_flow_action_translation.app_flow_action_id` FOREIGN KEY (`app_flow_action_id`) REFERENCES `app_flow_action` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.app_flow_action_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_flow_event` (
    `id` BINARY(16) NOT NULL,
    `app_id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `aware` JSON NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.app_flow_event.aware` CHECK (JSON_VALID(`aware`)),
    CONSTRAINT `json.app_flow_event.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.app_flow_event.app_id` (`app_id`),
    CONSTRAINT `fk.app_flow_event.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_shipping_method` (
    `id` BINARY(16) NOT NULL,
    `app_name` VARCHAR(255) NOT NULL,
    `identifier` VARCHAR(255) NOT NULL,
    `app_id` BINARY(16) NULL,
    `shipping_method_id` BINARY(16) NOT NULL,
    `original_media_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.app_shipping_method.app_id` (`app_id`),
    KEY `fk.app_shipping_method.original_media_id` (`original_media_id`),
    CONSTRAINT `fk.app_shipping_method.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.app_shipping_method.original_media_id` FOREIGN KEY (`original_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_administration_snippet` (
    `id` BINARY(16) NOT NULL,
    `value` LONGTEXT NOT NULL,
    `app_id` BINARY(16) NOT NULL,
    `locale_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;