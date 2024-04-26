CREATE TABLE `newsletter_recipient` (
    `id` BINARY(16) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NULL,
    `first_name` VARCHAR(255) NULL,
    `last_name` VARCHAR(255) NULL,
    `zip_code` VARCHAR(255) NULL,
    `city` VARCHAR(255) NULL,
    `street` VARCHAR(255) NULL,
    `status` VARCHAR(255) NOT NULL,
    `hash` VARCHAR(255) NOT NULL,
    `custom_fields` JSON NULL,
    `confirmed_at` DATETIME(3) NULL,
    `salutation_id` BINARY(16) NULL,
    `language_id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.newsletter_recipient.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.newsletter_recipient.salutation_id` (`salutation_id`),
    KEY `fk.newsletter_recipient.language_id` (`language_id`),
    KEY `fk.newsletter_recipient.sales_channel_id` (`sales_channel_id`),
    CONSTRAINT `fk.newsletter_recipient.salutation_id` FOREIGN KEY (`salutation_id`) REFERENCES `salutation` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.newsletter_recipient.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.newsletter_recipient.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `newsletter_recipient_tag` (
    `newsletter_recipient_id` BINARY(16) NOT NULL,
    `tag_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`newsletter_recipient_id`,`tag_id`),
    KEY `fk.newsletter_recipient_tag.newsletter_recipient_id` (`newsletter_recipient_id`),
    KEY `fk.newsletter_recipient_tag.tag_id` (`tag_id`),
    CONSTRAINT `fk.newsletter_recipient_tag.newsletter_recipient_id` FOREIGN KEY (`newsletter_recipient_id`) REFERENCES `newsletter_recipient` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.newsletter_recipient_tag.tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;