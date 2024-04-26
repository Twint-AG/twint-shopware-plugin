CREATE TABLE `media` (
    `id` BINARY(16) NOT NULL,
    `user_id` BINARY(16) NULL,
    `media_folder_id` BINARY(16) NULL,
    `mime_type` VARCHAR(255) NULL,
    `file_extension` VARCHAR(255) NULL,
    `uploaded_at` DATETIME(3) NULL,
    `file_name` LONGTEXT NULL,
    `file_size` INT(11) NULL,
    `media_type` LONGBLOB NULL,
    `meta_data` JSON NULL,
    `config` JSON NULL,
    `path` VARCHAR(255) NULL,
    `private` TINYINT(1) NULL DEFAULT '0',
    `thumbnails_ro` LONGBLOB NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.media.meta_data` CHECK (JSON_VALID(`meta_data`)),
    CONSTRAINT `json.media.media_type` CHECK (JSON_VALID(`media_type`)),
    CONSTRAINT `json.media.config` CHECK (JSON_VALID(`config`)),
    CONSTRAINT `json.media.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.media.user_id` (`user_id`),
    KEY `fk.media.media_folder_id` (`media_folder_id`),
    CONSTRAINT `fk.media.media_folder_id` FOREIGN KEY (`media_folder_id`) REFERENCES `media_folder` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_default_folder` (
    `id` BINARY(16) NOT NULL,
    `entity` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.media_default_folder.custom_fields` CHECK (JSON_VALID(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_thumbnail` (
    `id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NOT NULL,
    `width` INT(11) NOT NULL,
    `height` INT(11) NOT NULL,
    `path` VARCHAR(255) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.media_thumbnail.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.media_thumbnail.media_id` (`media_id`),
    CONSTRAINT `fk.media_thumbnail.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_translation` (
    `title` VARCHAR(255) NULL,
    `alt` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `media_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`media_id`,`language_id`),
    CONSTRAINT `json.media_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.media_translation.media_id` (`media_id`),
    KEY `fk.media_translation.language_id` (`language_id`),
    CONSTRAINT `fk.media_translation.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.media_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_folder` (
    `id` BINARY(16) NOT NULL,
    `use_parent_configuration` TINYINT(1) NULL DEFAULT '0',
    `media_folder_configuration_id` BINARY(16) NOT NULL,
    `default_folder_id` BINARY(16) NULL,
    `parent_id` BINARY(16) NULL,
    `child_count` INT(11) NULL,
    `path` LONGTEXT NULL,
    `name` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.media_folder.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.media_folder.parent_id` (`parent_id`),
    KEY `fk.media_folder.media_folder_configuration_id` (`media_folder_configuration_id`),
    CONSTRAINT `fk.media_folder.parent_id` FOREIGN KEY (`parent_id`) REFERENCES `media_folder` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.media_folder.media_folder_configuration_id` FOREIGN KEY (`media_folder_configuration_id`) REFERENCES `media_folder_configuration` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_thumbnail_size` (
    `id` BINARY(16) NOT NULL,
    `width` INT(11) NOT NULL,
    `height` INT(11) NOT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.media_thumbnail_size.custom_fields` CHECK (JSON_VALID(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_folder_configuration` (
    `id` BINARY(16) NOT NULL,
    `create_thumbnails` TINYINT(1) NULL DEFAULT '0',
    `keep_aspect_ratio` TINYINT(1) NULL DEFAULT '0',
    `thumbnail_quality` INT(11) NULL,
    `private` TINYINT(1) NULL DEFAULT '0',
    `no_association` TINYINT(1) NULL DEFAULT '0',
    `media_thumbnail_sizes_ro` LONGBLOB NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.media_folder_configuration.custom_fields` CHECK (JSON_VALID(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_folder_configuration_media_thumbnail_size` (
    `media_folder_configuration_id` BINARY(16) NOT NULL,
    `media_thumbnail_size_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`media_folder_configuration_id`,`media_thumbnail_size_id`),
    KEY `fk.media_folder_configuration_media_thumbnail_size.media_folder_configuration_id` (`media_folder_configuration_id`),
    KEY `fk.media_folder_configuration_media_thumbnail_size.media_thumbnail_size_id` (`media_thumbnail_size_id`),
    CONSTRAINT `fk.media_folder_configuration_media_thumbnail_size.media_folder_configuration_id` FOREIGN KEY (`media_folder_configuration_id`) REFERENCES `media_folder_configuration` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.media_folder_configuration_media_thumbnail_size.media_thumbnail_size_id` FOREIGN KEY (`media_thumbnail_size_id`) REFERENCES `media_thumbnail_size` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_tag` (
    `media_id` BINARY(16) NOT NULL,
    `tag_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`media_id`,`tag_id`),
    KEY `fk.media_tag.media_id` (`media_id`),
    KEY `fk.media_tag.tag_id` (`tag_id`),
    CONSTRAINT `fk.media_tag.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.media_tag.tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;