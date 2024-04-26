CREATE TABLE `salutation` (
    `id` BINARY(16) NOT NULL,
    `salutation_key` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.salutation.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `salutation_translation` (
    `display_name` VARCHAR(255) NOT NULL,
    `letter_name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `salutation_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`salutation_id`,`language_id`),
    CONSTRAINT `json.salutation_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.salutation_translation.salutation_id` (`salutation_id`),
    KEY `fk.salutation_translation.language_id` (`language_id`),
    CONSTRAINT `fk.salutation_translation.salutation_id` FOREIGN KEY (`salutation_id`) REFERENCES `salutation` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.salutation_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;