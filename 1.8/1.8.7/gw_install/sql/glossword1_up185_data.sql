ALTER TABLE `{PREFIX}abbr_phrase` CHANGE `abbr_short` `abbr_short` TINYBLOB NOT NULL;
ALTER TABLE `{PREFIX}abbr_phrase` CHANGE `abbr_long` `abbr_long` TINYBLOB NOT NULL;
ALTER TABLE `{PREFIX}dict` ADD `dict_uri` VARCHAR(255) NOT NULL AFTER `title`;
REPLACE INTO `{PREFIX}settings` VALUES ('version', '1.8.5');
REPLACE INTO `{PREFIX}settings` VALUES ('visualtheme', 'gw_brand');
UPDATE `{PREFIX}dict` SET `dict_uri` = `title`;