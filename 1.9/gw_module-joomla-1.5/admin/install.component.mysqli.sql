DROP TABLE IF EXISTS `#__gw_config`;
CREATE TABLE IF NOT EXISTS `#__gw_config` (
  `setting_key` varchar(32) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `#__gw_config` VALUES('path_core_abs', '');
INSERT INTO `#__gw_config` VALUES('db_host', '');
INSERT INTO `#__gw_config` VALUES('db_name', '');
INSERT INTO `#__gw_config` VALUES('db_user', '');
INSERT INTO `#__gw_config` VALUES('db_pass', '');
INSERT INTO `#__gw_config` VALUES('table_prefix', 'jos_gw_');
INSERT INTO `#__gw_config` VALUES('server_proto', 'http://');
INSERT INTO `#__gw_config` VALUES('server_host', '');
INSERT INTO `#__gw_config` VALUES('server_dir', '');
INSERT INTO `#__gw_config` VALUES('path_temp_web', '');
INSERT INTO `#__gw_config` VALUES('server_dir_admin', '');
INSERT INTO `#__gw_config` VALUES('db_driver', 'mysqli');
INSERT INTO `#__gw_config` VALUES('path_temp_abs', '');



