CREATE TABLE `integration` (
    `id` BINARY(16) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `access_key` VARCHAR(255) NOT NULL,
    `secret_access_key` VARCHAR(1024) NOT NULL,
    `last_usage_at` DATETIME(3) NULL,
    `admin` TINYINT(1) NULL DEFAULT '0',
    `custom_fields` JSON NULL,
    `deleted_at` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.integration.custom_fields` CHECK (JSON_VALID(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `integration_role` (
    `integration_id` BINARY(16) NOT NULL,
    `acl_role_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`integration_id`,`acl_role_id`),
    KEY `fk.integration_role.integration_id` (`integration_id`),
    KEY `fk.integration_role.acl_role_id` (`acl_role_id`),
    CONSTRAINT `fk.integration_role.integration_id` FOREIGN KEY (`integration_id`) REFERENCES `integration` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.integration_role.acl_role_id` FOREIGN KEY (`acl_role_id`) REFERENCES `acl_role` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;