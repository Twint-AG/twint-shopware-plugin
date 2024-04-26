CREATE TABLE `product` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `parent_id` BINARY(16) NULL,
    `parent_version_id` BINARY(16) NOT NULL,
    `product_manufacturer_id` BINARY(16) NULL,
    `product_manufacturer_version_id` BINARY(16) NOT NULL,
    `unit_id` BINARY(16) NULL,
    `tax_id` BINARY(16) NOT NULL,
    `product_media_id` BINARY(16) NULL,
    `product_media_version_id` BINARY(16) NOT NULL,
    `delivery_time_id` BINARY(16) NULL,
    `product_feature_set_id` BINARY(16) NULL,
    `canonical_product_id` BINARY(16) NULL,
    `cms_page_id` BINARY(16) NULL,
    `cms_page_version_id` BINARY(16) NOT NULL,
    `price` JSON NOT NULL,
    `product_number` VARCHAR(64) NOT NULL,
    `restock_time` INT(11) NULL,
    `auto_increment` INT(11) NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `available` TINYINT(1) NULL DEFAULT '0',
    `is_closeout` TINYINT(1) NULL DEFAULT '0',
    `available_stock` INT(11) NULL,
    `stock` INT(11) NOT NULL,
    `display_group` VARCHAR(255) NULL,
    `variant_listing_config` JSON NULL,
    `variant_restrictions` JSON NULL,
    `manufacturer_number` VARCHAR(255) NULL,
    `ean` VARCHAR(255) NULL,
    `purchase_steps` INT(11) NULL,
    `max_purchase` INT(11) NULL,
    `min_purchase` INT(11) NULL,
    `purchase_unit` DOUBLE NULL,
    `reference_unit` DOUBLE NULL,
    `shipping_free` TINYINT(1) NULL DEFAULT '0',
    `purchase_prices` JSON NULL,
    `mark_as_topseller` TINYINT(1) NULL DEFAULT '0',
    `weight` DOUBLE NULL,
    `width` DOUBLE NULL,
    `height` DOUBLE NULL,
    `length` DOUBLE NULL,
    `release_date` DATETIME(3) NULL,
    `rating_average` DOUBLE NULL,
    `category_tree` JSON NULL,
    `property_ids` JSON NULL,
    `option_ids` JSON NULL,
    `stream_ids` JSON NULL,
    `tag_ids` JSON NULL,
    `category_ids` JSON NULL,
    `child_count` INT(11) NULL,
    `custom_field_set_selection_active` TINYINT(1) NULL DEFAULT '0',
    `sales` INT(11) NULL,
    `states` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    CONSTRAINT `json.product.price` CHECK (JSON_VALID(`price`)),
    CONSTRAINT `json.product.variation` CHECK (JSON_VALID(`variation`)),
    CONSTRAINT `json.product.variant_listing_config` CHECK (JSON_VALID(`variant_listing_config`)),
    CONSTRAINT `json.product.variant_restrictions` CHECK (JSON_VALID(`variant_restrictions`)),
    CONSTRAINT `json.product.purchase_prices` CHECK (JSON_VALID(`purchase_prices`)),
    CONSTRAINT `json.product.category_tree` CHECK (JSON_VALID(`category_tree`)),
    CONSTRAINT `json.product.property_ids` CHECK (JSON_VALID(`property_ids`)),
    CONSTRAINT `json.product.option_ids` CHECK (JSON_VALID(`option_ids`)),
    CONSTRAINT `json.product.stream_ids` CHECK (JSON_VALID(`stream_ids`)),
    CONSTRAINT `json.product.tag_ids` CHECK (JSON_VALID(`tag_ids`)),
    CONSTRAINT `json.product.category_ids` CHECK (JSON_VALID(`category_ids`)),
    CONSTRAINT `json.product.states` CHECK (JSON_VALID(`states`)),
    CONSTRAINT `json.product.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.product.parent_id` (`parent_id`,`version_id`),
    KEY `fk.product.delivery_time_id` (`delivery_time_id`),
    KEY `fk.product.tax_id` (`tax_id`),
    KEY `fk.product.product_manufacturer_id` (`product_manufacturer_id`,`product_manufacturer_version_id`),
    KEY `fk.product.unit_id` (`unit_id`),
    KEY `fk.product.product_media_id` (`product_media_id`,`product_media_version_id`),
    KEY `fk.product.product_feature_set_id` (`product_feature_set_id`),
    KEY `fk.product.cms_page_id` (`cms_page_id`,`cms_page_version_id`),
    KEY `fk.product.canonical_product_id` (`canonical_product_id`,`parent_version_id`),
    CONSTRAINT `fk.product.parent_id` FOREIGN KEY (`parent_id`,`version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product.delivery_time_id` FOREIGN KEY (`delivery_time_id`) REFERENCES `delivery_time` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product.tax_id` FOREIGN KEY (`tax_id`) REFERENCES `tax` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product.product_manufacturer_id` FOREIGN KEY (`product_manufacturer_id`,`product_manufacturer_version_id`) REFERENCES `product_manufacturer` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product.unit_id` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product.product_feature_set_id` FOREIGN KEY (`product_feature_set_id`) REFERENCES `product_feature_set` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product.cms_page_id` FOREIGN KEY (`cms_page_id`,`cms_page_version_id`) REFERENCES `cms_page` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product.canonical_product_id` FOREIGN KEY (`canonical_product_id`,`parent_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_stream_mapping` (
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `product_stream_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_id`,`product_stream_id`),
    KEY `fk.product_stream_mapping.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_stream_mapping.product_stream_id` (`product_stream_id`),
    CONSTRAINT `fk.product_stream_mapping.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_stream_mapping.product_stream_id` FOREIGN KEY (`product_stream_id`) REFERENCES `product_stream` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_category` (
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `category_id` BINARY(16) NOT NULL,
    `category_version_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_id`,`product_version_id`,`category_id`,`category_version_id`),
    KEY `fk.product_category.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_category.category_id` (`category_id`,`category_version_id`),
    CONSTRAINT `fk.product_category.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_category.category_id` FOREIGN KEY (`category_id`,`category_version_id`) REFERENCES `category` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_custom_field_set` (
    `product_id` BINARY(16) NOT NULL,
    `custom_field_set_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_id`,`custom_field_set_id`,`product_version_id`),
    KEY `fk.product_custom_field_set.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_custom_field_set.custom_field_set_id` (`custom_field_set_id`),
    CONSTRAINT `fk.product_custom_field_set.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_custom_field_set.custom_field_set_id` FOREIGN KEY (`custom_field_set_id`) REFERENCES `custom_field_set` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_tag` (
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `tag_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_id`,`product_version_id`,`tag_id`),
    KEY `fk.product_tag.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_tag.tag_id` (`tag_id`),
    CONSTRAINT `fk.product_tag.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_tag.tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_configurator_setting` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NULL,
    `property_group_option_id` BINARY(16) NOT NULL,
    `price` JSON NULL,
    `position` INT(11) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    CONSTRAINT `json.product_configurator_setting.price` CHECK (JSON_VALID(`price`)),
    CONSTRAINT `json.product_configurator_setting.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_configurator_setting.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_configurator_setting.media_id` (`media_id`),
    KEY `fk.product_configurator_setting.property_group_option_id` (`property_group_option_id`),
    CONSTRAINT `fk.product_configurator_setting.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_configurator_setting.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_configurator_setting.property_group_option_id` FOREIGN KEY (`property_group_option_id`) REFERENCES `property_group_option` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_price` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    `price` JSON NOT NULL,
    `quantity_start` INT(11) NOT NULL,
    `quantity_end` INT(11) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    CONSTRAINT `json.product_price.price` CHECK (JSON_VALID(`price`)),
    CONSTRAINT `json.product_price.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_price.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_price.rule_id` (`rule_id`),
    CONSTRAINT `fk.product_price.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_price.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_property` (
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `property_group_option_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_id`,`property_group_option_id`),
    KEY `fk.product_property.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_property.property_group_option_id` (`property_group_option_id`),
    CONSTRAINT `fk.product_property.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_property.property_group_option_id` FOREIGN KEY (`property_group_option_id`) REFERENCES `property_group_option` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_search_keyword` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `keyword` VARCHAR(255) NOT NULL,
    `ranking` DOUBLE NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    KEY `fk.product_search_keyword.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_search_keyword.language_id` (`language_id`),
    CONSTRAINT `fk.product_search_keyword.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_search_keyword.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_keyword_dictionary` (
    `id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `keyword` VARCHAR(255) NOT NULL,
    `reversed` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.product_keyword_dictionary.language_id` (`language_id`),
    CONSTRAINT `fk.product_keyword_dictionary.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_review` (
    `id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `external_user` VARCHAR(255) NULL,
    `external_email` VARCHAR(255) NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `points` DOUBLE NULL,
    `status` TINYINT(1) NULL DEFAULT '0',
    `comment` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.product_review.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_review.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_review.customer_id` (`customer_id`),
    KEY `fk.product_review.sales_channel_id` (`sales_channel_id`),
    KEY `fk.product_review.language_id` (`language_id`),
    CONSTRAINT `fk.product_review.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_review.customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_review.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_review.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_manufacturer` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NULL,
    `link` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    CONSTRAINT `json.product_manufacturer.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.product_manufacturer.media_id` (`media_id`),
    CONSTRAINT `fk.product_manufacturer.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_manufacturer_translation` (
    `name` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `product_manufacturer_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `product_manufacturer_version_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_manufacturer_id`,`language_id`,`product_manufacturer_version_id`),
    CONSTRAINT `json.product_manufacturer_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_manufacturer_translation.product_manufacturer_id` (`product_manufacturer_id`,`product_manufacturer_version_id`),
    KEY `fk.product_manufacturer_translation.language_id` (`language_id`),
    CONSTRAINT `fk.product_manufacturer_translation.product_manufacturer_id` FOREIGN KEY (`product_manufacturer_id`,`product_manufacturer_version_id`) REFERENCES `product_manufacturer` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_manufacturer_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_media` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NOT NULL,
    `position` INT(11) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    CONSTRAINT `json.product_media.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_media.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_media.media_id` (`media_id`),
    CONSTRAINT `fk.product_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_download` (
    `id` BINARY(16) NOT NULL,
    `version_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NOT NULL,
    `position` INT(11) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`version_id`),
    CONSTRAINT `json.product_download.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_download.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_download.media_id` (`media_id`),
    CONSTRAINT `fk.product_download.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_download.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_translation` (
    `meta_description` VARCHAR(255) NULL,
    `name` VARCHAR(255) NOT NULL,
    `keywords` LONGTEXT NULL,
    `description` LONGTEXT NULL,
    `meta_title` VARCHAR(255) NULL,
    `pack_unit` VARCHAR(255) NULL,
    `pack_unit_plural` VARCHAR(255) NULL,
    `custom_search_keywords` JSON NULL,
    `slot_config` JSON NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `product_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_id`,`language_id`,`product_version_id`),
    CONSTRAINT `json.product_translation.custom_search_keywords` CHECK (JSON_VALID(`custom_search_keywords`)),
    CONSTRAINT `json.product_translation.slot_config` CHECK (JSON_VALID(`slot_config`)),
    CONSTRAINT `json.product_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_translation.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_translation.language_id` (`language_id`),
    CONSTRAINT `fk.product_translation.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_option` (
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `property_group_option_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_id`,`property_group_option_id`),
    KEY `fk.product_option.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_option.property_group_option_id` (`property_group_option_id`),
    CONSTRAINT `fk.product_option.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_option.property_group_option_id` FOREIGN KEY (`property_group_option_id`) REFERENCES `property_group_option` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_category_tree` (
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `category_id` BINARY(16) NOT NULL,
    `category_version_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_id`,`product_version_id`,`category_id`,`category_version_id`),
    KEY `fk.product_category_tree.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_category_tree.category_id` (`category_id`,`category_version_id`),
    CONSTRAINT `fk.product_category_tree.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_category_tree.category_id` FOREIGN KEY (`category_id`,`category_version_id`) REFERENCES `category` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_cross_selling` (
    `id` BINARY(16) NOT NULL,
    `position` INT(11) NOT NULL,
    `sort_by` VARCHAR(255) NULL,
    `sort_direction` VARCHAR(255) NULL,
    `type` VARCHAR(255) NOT NULL,
    `active` TINYINT(1) NULL DEFAULT '0',
    `limit` INT(11) NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `product_stream_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.product_cross_selling.translated` CHECK (JSON_VALID(`translated`)),
    KEY `fk.product_cross_selling.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_cross_selling.product_stream_id` (`product_stream_id`),
    CONSTRAINT `fk.product_cross_selling.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_cross_selling.product_stream_id` FOREIGN KEY (`product_stream_id`) REFERENCES `product_stream` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_cross_selling_translation` (
    `name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `product_cross_selling_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_cross_selling_id`,`language_id`),
    KEY `fk.product_cross_selling_translation.product_cross_selling_id` (`product_cross_selling_id`),
    KEY `fk.product_cross_selling_translation.language_id` (`language_id`),
    CONSTRAINT `fk.product_cross_selling_translation.product_cross_selling_id` FOREIGN KEY (`product_cross_selling_id`) REFERENCES `product_cross_selling` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_cross_selling_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_cross_selling_assigned_products` (
    `id` BINARY(16) NOT NULL,
    `cross_selling_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `position` INT(11) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`,`product_version_id`),
    KEY `fk.product_cross_selling_assigned_products.product_id` (`product_id`,`product_version_id`),
    KEY `fk.product_cross_selling_assigned_products.cross_selling_id` (`cross_selling_id`),
    CONSTRAINT `fk.product_cross_selling_assigned_products.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_cross_selling_assigned_products.cross_selling_id` FOREIGN KEY (`cross_selling_id`) REFERENCES `product_cross_selling` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_feature_set` (
    `id` BINARY(16) NOT NULL,
    `features` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.product_feature_set.features` CHECK (JSON_VALID(`features`)),
    CONSTRAINT `json.product_feature_set.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_feature_set_translation` (
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `product_feature_set_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_feature_set_id`,`language_id`),
    KEY `fk.product_feature_set_translation.product_feature_set_id` (`product_feature_set_id`),
    KEY `fk.product_feature_set_translation.language_id` (`language_id`),
    CONSTRAINT `fk.product_feature_set_translation.product_feature_set_id` FOREIGN KEY (`product_feature_set_id`) REFERENCES `product_feature_set` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_feature_set_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_sorting` (
    `id` BINARY(16) NOT NULL,
    `locked` TINYINT(1) NULL DEFAULT '0',
    `url_key` VARCHAR(255) NOT NULL,
    `priority` INT(11) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT '0',
    `fields` JSON NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.product_sorting.fields` CHECK (JSON_VALID(`fields`)),
    CONSTRAINT `json.product_sorting.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_sorting_translation` (
    `label` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `product_sorting_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_sorting_id`,`language_id`),
    KEY `fk.product_sorting_translation.product_sorting_id` (`product_sorting_id`),
    KEY `fk.product_sorting_translation.language_id` (`language_id`),
    CONSTRAINT `fk.product_sorting_translation.product_sorting_id` FOREIGN KEY (`product_sorting_id`) REFERENCES `product_sorting` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_sorting_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_search_config` (
    `id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `and_logic` TINYINT(1) NOT NULL DEFAULT '0',
    `min_search_length` INT(11) NOT NULL,
    `excluded_terms` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.product_search_config.excluded_terms` CHECK (JSON_VALID(`excluded_terms`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_search_config_field` (
    `id` BINARY(16) NOT NULL,
    `product_search_config_id` BINARY(16) NOT NULL,
    `custom_field_id` BINARY(16) NULL,
    `field` VARCHAR(255) NOT NULL,
    `tokenize` TINYINT(1) NOT NULL DEFAULT '0',
    `searchable` TINYINT(1) NOT NULL DEFAULT '0',
    `ranking` INT(11) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.product_search_config_field.product_search_config_id` (`product_search_config_id`),
    KEY `fk.product_search_config_field.custom_field_id` (`custom_field_id`),
    CONSTRAINT `fk.product_search_config_field.product_search_config_id` FOREIGN KEY (`product_search_config_id`) REFERENCES `product_search_config` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_search_config_field.custom_field_id` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_field` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_visibility` (
    `id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `visibility` INT(11) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.product_visibility.sales_channel_id` (`sales_channel_id`),
    KEY `fk.product_visibility.product_id` (`product_id`,`product_version_id`),
    CONSTRAINT `fk.product_visibility.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_visibility.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_stream` (
    `id` BINARY(16) NOT NULL,
    `api_filter` JSON NULL,
    `invalid` TINYINT(1) NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.product_stream.api_filter` CHECK (JSON_VALID(`api_filter`)),
    CONSTRAINT `json.product_stream.translated` CHECK (JSON_VALID(`translated`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_stream_translation` (
    `name` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `product_stream_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`product_stream_id`,`language_id`),
    CONSTRAINT `json.product_stream_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_stream_translation.product_stream_id` (`product_stream_id`),
    KEY `fk.product_stream_translation.language_id` (`language_id`),
    CONSTRAINT `fk.product_stream_translation.product_stream_id` FOREIGN KEY (`product_stream_id`) REFERENCES `product_stream` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_stream_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_stream_filter` (
    `id` BINARY(16) NOT NULL,
    `product_stream_id` BINARY(16) NOT NULL,
    `parent_id` BINARY(16) NULL,
    `type` VARCHAR(255) NOT NULL,
    `field` VARCHAR(255) NULL,
    `operator` VARCHAR(255) NULL,
    `value` LONGTEXT NULL,
    `parameters` JSON NULL,
    `position` INT(11) NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.product_stream_filter.parameters` CHECK (JSON_VALID(`parameters`)),
    CONSTRAINT `json.product_stream_filter.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
    KEY `fk.product_stream_filter.product_stream_id` (`product_stream_id`),
    KEY `fk.product_stream_filter.parent_id` (`parent_id`),
    CONSTRAINT `fk.product_stream_filter.product_stream_id` FOREIGN KEY (`product_stream_id`) REFERENCES `product_stream` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_stream_filter.parent_id` FOREIGN KEY (`parent_id`) REFERENCES `product_stream_filter` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_export` (
    `id` BINARY(16) NOT NULL,
    `product_stream_id` BINARY(16) NOT NULL,
    `storefront_sales_channel_id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `sales_channel_domain_id` BINARY(16) NOT NULL,
    `currency_id` BINARY(16) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `access_key` VARCHAR(255) NOT NULL,
    `encoding` VARCHAR(255) NOT NULL,
    `file_format` VARCHAR(255) NOT NULL,
    `include_variants` TINYINT(1) NULL DEFAULT '0',
    `generate_by_cronjob` TINYINT(1) NOT NULL DEFAULT '0',
    `generated_at` DATETIME(3) NULL,
    `interval` INT(11) NOT NULL,
    `header_template` LONGTEXT NULL,
    `body_template` LONGTEXT NULL,
    `footer_template` LONGTEXT NULL,
    `paused_schedule` TINYINT(1) NULL DEFAULT '0',
    `is_running` TINYINT(1) NULL DEFAULT '0',
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.product_export.product_stream_id` (`product_stream_id`),
    KEY `fk.product_export.storefront_sales_channel_id` (`storefront_sales_channel_id`),
    KEY `fk.product_export.sales_channel_id` (`sales_channel_id`),
    KEY `fk.product_export.sales_channel_domain_id` (`sales_channel_domain_id`),
    KEY `fk.product_export.currency_id` (`currency_id`),
    CONSTRAINT `fk.product_export.product_stream_id` FOREIGN KEY (`product_stream_id`) REFERENCES `product_stream` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_export.storefront_sales_channel_id` FOREIGN KEY (`storefront_sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_export.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_export.sales_channel_domain_id` FOREIGN KEY (`sales_channel_domain_id`) REFERENCES `sales_channel_domain` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.product_export.currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;