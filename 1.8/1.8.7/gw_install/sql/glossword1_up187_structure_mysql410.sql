ALTER TABLE `{PREFIX}history_terms` CHANGE `is_complete` `is_complete` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';
UPDATE `{PREFIX}history_terms` SET `is_complete` = '1';

ALTER TABLE `{PREFIX}custom_az_profiles` CHANGE `is_active` `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';
UPDATE `{PREFIX}custom_az_profiles` SET `is_active` = '1';

ALTER TABLE `{PREFIX}abbr` CHANGE `is_active` `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';
UPDATE `{PREFIX}abbr` SET `is_active` = '1';

ALTER TABLE `{PREFIX}dict` CHANGE `is_active` `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';
UPDATE `{PREFIX}dict` SET `is_active` = '1', date_created = (date_created - 86400);

ALTER TABLE `{PREFIX}pages` CHANGE `is_active` `is_active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';
ALTER TABLE `{PREFIX}pages` CHANGE `int_sort` `int_sort` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '10';
UPDATE `{PREFIX}pages` SET `is_active` = '1';

ALTER TABLE `{PREFIX}theme` CHANGE `is_active` `is_active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';
UPDATE `{PREFIX}theme` SET `is_active` = '1';

ALTER TABLE `{PREFIX}topics` CHANGE `is_active` `is_active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';
ALTER TABLE `{PREFIX}topics` CHANGE `int_sort` `int_sort` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '10';
UPDATE `{PREFIX}topics` SET `is_active` = '1';

DROP TABLE IF EXISTS `{PREFIX}auth`;


DROP TABLE IF EXISTS `{PREFIX}custom_az`;
CREATE TABLE IF NOT EXISTS `{PREFIX}custom_az` (
  `id_letter` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_profile` smallint(5) unsigned NOT NULL DEFAULT '1',
  `int_sort` int(10) unsigned NOT NULL DEFAULT '10',
  `az_value` varbinary(8) NOT NULL,
  `az_value_lc` varbinary(8) NOT NULL,
  `az_int` int(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_letter`),
  KEY `az_value` (`id_profile`,`int_sort`,`az_int`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS {PREFIX}sessions;
CREATE TABLE IF NOT EXISTS {PREFIX}sessions (
  id_sess char(32) NOT NULL,
  id_user int(10) unsigned NOT NULL DEFAULT '1',
  is_remember tinyint(1) unsigned NOT NULL DEFAULT '0',
  date_changed int(10) unsigned NOT NULL DEFAULT '0',
  ip int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id_sess)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS {PREFIX}users;
CREATE TABLE IF NOT EXISTS {PREFIX}users (
  id_user int(10) unsigned NOT NULL AUTO_INCREMENT,
  login varbinary(128) NOT NULL,
  `password` char(32) NOT NULL,
  is_active tinyint(1) unsigned NOT NULL DEFAULT '1',
  is_show_contact tinyint(1) unsigned NOT NULL DEFAULT '1',
  date_reg int(10) unsigned NOT NULL DEFAULT '0',
  date_login int(10) unsigned NOT NULL DEFAULT '0',
  int_items int(10) unsigned NOT NULL DEFAULT '0',
  user_fname varbinary(64) NOT NULL,
  user_sname varbinary(64) NOT NULL,
  user_email varchar(255) NOT NULL,
  user_perm blob NOT NULL,
  user_settings blob NOT NULL,
  PRIMARY KEY (id_user)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS {PREFIX}auth_restore;
CREATE TABLE IF NOT EXISTS {PREFIX}auth_restore (
  id_user int(10) unsigned NOT NULL AUTO_INCREMENT,
  auth_key int(10) unsigned NOT NULL,
  date_created int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id_user)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{PREFIX}virtual_keyboard`;
CREATE TABLE IF NOT EXISTS `{PREFIX}virtual_keyboard` (
  `id_profile` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `vkbd_name` tinyblob NOT NULL,
  `vkbd_letters` tinyblob NOT NULL,
  PRIMARY KEY (`id_profile`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{PREFIX}component_menu`;
DROP TABLE IF EXISTS {PREFIX}component;
CREATE TABLE IF NOT EXISTS {PREFIX}component (
  id_component tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
  id_component_name varchar(64) NOT NULL,
  is_active tinyint(1) unsigned NOT NULL DEFAULT '1',
  int_sort mediumint(8) unsigned NOT NULL DEFAULT '10',
  vv1 tinyint(3) unsigned NOT NULL DEFAULT '1',
  vv2 tinyint(3) unsigned NOT NULL DEFAULT '0',
  vv3 tinyint(3) unsigned NOT NULL DEFAULT '0',
  cname varchar(128) NOT NULL,
  PRIMARY KEY (id_component)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {PREFIX}component_actions;
CREATE TABLE IF NOT EXISTS {PREFIX}component_actions (
  id_action tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
  aname varchar(64) NOT NULL,
  aname_sys varchar(64) NOT NULL,
  icon varchar(64) NOT NULL,
  PRIMARY KEY (id_action)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS {PREFIX}component_map;
CREATE TABLE IF NOT EXISTS {PREFIX}component_map (
  id smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  id_component tinyint(2) unsigned NOT NULL DEFAULT '1',
  id_action tinyint(2) unsigned NOT NULL DEFAULT '1',
  is_active_map tinyint(1) unsigned NOT NULL DEFAULT '1',
  is_in_menu tinyint(1) unsigned NOT NULL DEFAULT '1',
  int_sort smallint(5) unsigned NOT NULL DEFAULT '10',
  req_permission_map text NOT NULL,
  PRIMARY KEY (id),
  KEY id_component (id_action,id_component)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

