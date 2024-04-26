CREATE TABLE `snippet_set` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `base_file` VARCHAR(255) NOT NULL,
    `iso` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.snippet_set.custom_fields` CHECK (JSON_VALID(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `snippet` (
    `id` BINARY(16) NOT NULL,
    `snippet_set_id` BINARY(16) NOT NULL,
    `translation_key` VARCHAR(255) NOT NULL,
    `value` LONGTEXT NOT NULL,
    `author` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.snippet.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.snippet.snippet_set_id` (`snippet_set_id`),
    CONSTRAINT `fk.snippet.snippet_set_id` FOREIGN KEY (`snippet_set_id`) REFERENCES `snippet_set` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;