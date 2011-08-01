UPDATE `%s` SET `date_created` = (date_created - 3600), `date_modified` = (date_modified - 3600);
ALTER TABLE `%s` CHANGE `int_bytes` `int_bytes` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `%s` CHANGE `term_1` `term_1` VARBINARY(16) NOT NULL, CHANGE `term_2` `term_2` VARBINARY(16) NOT NULL, CHANGE `term_3` `term_3` VARBINARY(16) NOT NULL;
ALTER TABLE `%s` DROP INDEX `term123`, ADD INDEX `term123` (`is_active`, `term_1`, `term_2`, `term_3`, `term`, `date_created`);

