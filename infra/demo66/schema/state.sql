CREATE TABLE `state_machine` (
    `id` BINARY(16) NOT NULL,
    `technical_name` VARCHAR(255) NOT NULL,
    `initial_state_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.state_machine.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `state_machine_translation` (
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `state_machine_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`state_machine_id`,`language_id`),
    CONSTRAINT `json.state_machine_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.state_machine_translation.state_machine_id` (`state_machine_id`),
    KEY `fk.state_machine_translation.language_id` (`language_id`),
    CONSTRAINT `fk.state_machine_translation.state_machine_id` FOREIGN KEY (`state_machine_id`) REFERENCES `state_machine` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.state_machine_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `state_machine_state` (
    `id` BINARY(16) NOT NULL,
    `technical_name` VARCHAR(255) NOT NULL,
    `state_machine_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.state_machine_state.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.state_machine_state.state_machine_id` (`state_machine_id`),
    CONSTRAINT `fk.state_machine_state.state_machine_id` FOREIGN KEY (`state_machine_id`) REFERENCES `state_machine` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `state_machine_state_translation` (
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `state_machine_state_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`state_machine_state_id`,`language_id`),
    CONSTRAINT `json.state_machine_state_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.state_machine_state_translation.state_machine_state_id` (`state_machine_state_id`),
    KEY `fk.state_machine_state_translation.language_id` (`language_id`),
    CONSTRAINT `fk.state_machine_state_translation.state_machine_state_id` FOREIGN KEY (`state_machine_state_id`) REFERENCES `state_machine_state` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.state_machine_state_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `state_machine_transition` (
    `id` BINARY(16) NOT NULL,
    `action_name` VARCHAR(255) NOT NULL,
    `state_machine_id` BINARY(16) NOT NULL,
    `from_state_id` BINARY(16) NOT NULL,
    `to_state_id` BINARY(16) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.state_machine_transition.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.state_machine_transition.state_machine_id` (`state_machine_id`),
    KEY `fk.state_machine_transition.from_state_id` (`from_state_id`),
    KEY `fk.state_machine_transition.to_state_id` (`to_state_id`),
    CONSTRAINT `fk.state_machine_transition.state_machine_id` FOREIGN KEY (`state_machine_id`) REFERENCES `state_machine` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.state_machine_transition.from_state_id` FOREIGN KEY (`from_state_id`) REFERENCES `state_machine_state` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.state_machine_transition.to_state_id` FOREIGN KEY (`to_state_id`) REFERENCES `state_machine_state` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `state_machine_history` (
    `id` BINARY(16) NOT NULL,
    `referenced_id` BINARY(16) NOT NULL,
    `referenced_version_id` BINARY(16) NOT NULL,
    `state_machine_id` BINARY(16) NOT NULL,
    `entity_name` VARCHAR(255) NOT NULL,
    `from_state_id` BINARY(16) NOT NULL,
    `to_state_id` BINARY(16) NOT NULL,
    `action_name` VARCHAR(255) NULL,
    `user_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.state_machine_history.state_machine_id` (`state_machine_id`),
    KEY `fk.state_machine_history.from_state_id` (`from_state_id`),
    KEY `fk.state_machine_history.to_state_id` (`to_state_id`),
    KEY `fk.state_machine_history.user_id` (`user_id`),
    CONSTRAINT `fk.state_machine_history.state_machine_id` FOREIGN KEY (`state_machine_id`) REFERENCES `state_machine` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.state_machine_history.from_state_id` FOREIGN KEY (`from_state_id`) REFERENCES `state_machine_state` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.state_machine_history.to_state_id` FOREIGN KEY (`to_state_id`) REFERENCES `state_machine_state` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.state_machine_history.user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;