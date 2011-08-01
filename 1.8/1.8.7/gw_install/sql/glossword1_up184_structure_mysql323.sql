DROP TABLE IF EXISTS `{PREFIX}component`;
CREATE TABLE `{PREFIX}component` (
  `id_component` varchar(32) NOT NULL default '',
  `is_active` enum('0','1') NOT NULL default '1',
  `int_sort` mediumint(5) NOT NULL default '10',
  `php_code` blob NOT NULL,
  `vv1` int(3) unsigned NOT NULL default '1',
  `vv2` int(3) unsigned NOT NULL default '0',
  `vv3` int(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_component`)
) ENGINE=MyISAM;
ALTER TABLE `{PREFIX}sessions` CHANGE `ua` `ua` VARCHAR(255) NOT NULL;
