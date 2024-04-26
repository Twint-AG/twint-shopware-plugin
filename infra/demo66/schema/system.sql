CREATE TABLE `system_config` (
    `id` BINARY(16) NOT NULL,
    `configuration_key` VARCHAR(255) NOT NULL,
    `configuration_value` JSON NOT NULL,
    `sales_channel_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.system_config.configuration_value` CHECK (JSON_VALID(`configuration_value`)),
    KEY `fk.system_config.sales_channel_id` (`sales_channel_id`),
    CONSTRAINT `fk.system_config.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;