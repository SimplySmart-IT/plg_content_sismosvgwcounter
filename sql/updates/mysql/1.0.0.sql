--
-- Table structure for table `#__sismos_vgwcounter`
--
CREATE TABLE IF NOT EXISTS `#__sismos_vgwcounter` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`content_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'FK to the #__content table.',
	`contact_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'FK to the #__contact table.',
	`private_idc` varchar(255) NOT NULL,
	`public_idc` varchar(255) NOT NULL,
	`created` datetime NOT NULL,
	`in_use_since` datetime,
	PRIMARY KEY (`id`),
	UNIQUE (`public_idc`),
	KEY `idx_content` (`content_id`),
	KEY `idx_contact` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;