ALTER TABLE `%s` ADD `crc32u` INT(10) unsigned NOT NULL DEFAULT '0' AFTER `int_bytes`;
ALTER TABLE `%s` ADD `is_complete` ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `is_active`;