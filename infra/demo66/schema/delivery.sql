CREATE TABLE `delivery_time` (
    `id` BINARY(16) NOT NULL,
    `min` INT(11) NOT NULL,
    `max` INT(11) NOT NULL,
    `unit` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.delivery_time.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `delivery_time_translation` (
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `delivery_time_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`delivery_time_id`,`language_id`),
    CONSTRAINT `json.delivery_time_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.delivery_time_translation.delivery_time_id` (`delivery_time_id`),
    KEY `fk.delivery_time_translation.language_id` (`language_id`),
    CONSTRAINT `fk.delivery_time_translation.delivery_time_id` FOREIGN KEY (`delivery_time_id`) REFERENCES `delivery_time` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.delivery_time_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;