CREATE TABLE `language` (
    `id` BINARY(16) NOT NULL,
    `parent_id` BINARY(16) NULL,
    `locale_id` BINARY(16) NOT NULL,
    `translation_code_id` BINARY(16) NULL,
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.language.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.language.parent_id` (`parent_id`),
    KEY `fk.language.locale_id` (`locale_id`),
    KEY `fk.language.translation_code_id` (`translation_code_id`),
    CONSTRAINT `fk.language.parent_id` FOREIGN KEY (`parent_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.language.locale_id` FOREIGN KEY (`locale_id`) REFERENCES `locale` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.language.translation_code_id` FOREIGN KEY (`translation_code_id`) REFERENCES `locale` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;