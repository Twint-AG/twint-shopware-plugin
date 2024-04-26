CREATE TABLE `log_entry` (
    `id` BINARY(16) NOT NULL,
    `message` LONGTEXT NULL,
    `level` INT(11) NULL,
    `channel` VARCHAR(255) NULL,
    `context` JSON NULL,
    `extra` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.log_entry.context` CHECK (JSON_VALID(`context`)),
    CONSTRAINT `json.log_entry.extra` CHECK (JSON_VALID(`extra`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;