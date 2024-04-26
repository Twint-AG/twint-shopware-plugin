CREATE TABLE `scheduled_task` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `scheduled_task_class` VARCHAR(512) NOT NULL,
    `run_interval` INT(11) NOT NULL,
    `default_run_interval` INT(11) NOT NULL,
    `status` VARCHAR(255) NOT NULL,
    `last_execution_time` DATETIME(3) NULL,
    `next_execution_time` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;