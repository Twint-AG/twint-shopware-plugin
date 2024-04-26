CREATE TABLE `mail_template` (
    `id` BINARY(16) NOT NULL,
    `mail_template_type_id` BINARY(16) NOT NULL,
    `system_default` TINYINT(1) NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.mail_template.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.mail_template.mail_template_type_id` (`mail_template_type_id`),
    CONSTRAINT `fk.mail_template.mail_template_type_id` FOREIGN KEY (`mail_template_type_id`) REFERENCES `mail_template_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mail_template_translation` (
    `sender_name` VARCHAR(255) NULL,
    `description` LONGTEXT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `content_html` LONGTEXT NOT NULL,
    `content_plain` LONGTEXT NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `mail_template_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`mail_template_id`,`language_id`),
    CONSTRAINT `json.mail_template_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.mail_template_translation.mail_template_id` (`mail_template_id`),
    KEY `fk.mail_template_translation.language_id` (`language_id`),
    CONSTRAINT `fk.mail_template_translation.mail_template_id` FOREIGN KEY (`mail_template_id`) REFERENCES `mail_template` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.mail_template_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mail_template_type` (
    `id` BINARY(16) NOT NULL,
    `technical_name` VARCHAR(255) NOT NULL,
    `available_entities` JSON NULL,
    `template_data` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.mail_template_type.available_entities` CHECK (JSON_VALID(`available_entities`)),
    CONSTRAINT `json.mail_template_type.template_data` CHECK (JSON_VALID(`template_data`)),
    CONSTRAINT `json.mail_template_type.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mail_template_type_translation` (
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `mail_template_type_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`mail_template_type_id`,`language_id`),
    CONSTRAINT `json.mail_template_type_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.mail_template_type_translation.mail_template_type_id` (`mail_template_type_id`),
    KEY `fk.mail_template_type_translation.language_id` (`language_id`),
    CONSTRAINT `fk.mail_template_type_translation.mail_template_type_id` FOREIGN KEY (`mail_template_type_id`) REFERENCES `mail_template_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.mail_template_type_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mail_template_media` (
    `id` BINARY(16) NOT NULL,
    `mail_template_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NOT NULL,
    `position` INT(11) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.mail_template_media.mail_template_id` (`mail_template_id`),
    KEY `fk.mail_template_media.media_id` (`media_id`),
    CONSTRAINT `fk.mail_template_media.mail_template_id` FOREIGN KEY (`mail_template_id`) REFERENCES `mail_template` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.mail_template_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mail_header_footer` (
    `id` BINARY(16) NOT NULL,
    `system_default` TINYINT(1) NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.mail_header_footer.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mail_header_footer_translation` (
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `header_html` LONGTEXT NULL,
    `header_plain` LONGTEXT NULL,
    `footer_html` LONGTEXT NULL,
    `footer_plain` LONGTEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `mail_header_footer_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`mail_header_footer_id`,`language_id`),
    KEY `fk.mail_header_footer_translation.mail_header_footer_id` (`mail_header_footer_id`),
    KEY `fk.mail_header_footer_translation.language_id` (`language_id`),
    CONSTRAINT `fk.mail_header_footer_translation.mail_header_footer_id` FOREIGN KEY (`mail_header_footer_id`) REFERENCES `mail_header_footer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.mail_header_footer_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;