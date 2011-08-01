DROP TABLE IF EXISTS `{PREFIX}toolbar`;

DROP TABLE IF EXISTS `{PREFIX}history_terms`;
CREATE TABLE IF NOT EXISTS `{PREFIX}history_terms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_user` int(10) unsigned NOT NULL DEFAULT '0',
  `id_dict` int(10) unsigned NOT NULL DEFAULT '0',
  `id_term` int(10) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_complete` tinyint(1) NOT NULL DEFAULT '1',
  `date_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `int_bytes` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `crc32u` int(10) NOT NULL DEFAULT '0',
  `term_1` varchar(16) BINARY NOT NULL DEFAULT '',
  `term_2` varchar(16) BINARY NOT NULL DEFAULT '',
  `term_3` varchar(16) BINARY NOT NULL DEFAULT '',
  `term` tinyblob NOT NULL,
  `term_uri` varchar(255) NOT NULL DEFAULT '',
  `defn` mediumblob NOT NULL,
  `keywords` mediumblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `{PREFIX}custom_az`;
CREATE TABLE `{PREFIX}custom_az` (
  `id_letter` int(10) unsigned NOT NULL auto_increment,
  `id_profile` smallint(5) unsigned NOT NULL default '1',
  `int_sort` int(10) unsigned NOT NULL default '10',
  `az_value` varchar(8) BINARY NOT NULL,
  PRIMARY KEY (`id_letter`),
  KEY `az_value` (`id_profile`,`az_value`,`int_sort`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `{PREFIX}custom_az_profiles`;
CREATE TABLE `{PREFIX}custom_az_profiles` (
  `id_profile` smallint(5) unsigned NOT NULL auto_increment,
  `is_active` tinyint(1) NOT NULL default '1',
  `profile_name` tinyblob NOT NULL,
  PRIMARY KEY  (`id_profile`)
) ENGINE=MyISAM;

