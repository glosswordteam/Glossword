DROP TABLE IF EXISTS `{PREFIX}captcha`;
CREATE TABLE `{PREFIX}captcha` (
  `id` int(11) NOT NULL auto_increment,
  `date_created` int(11) NOT NULL,
  `captcha` varchar(5) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `captcha` (`captcha`)
) TYPE=MyISAM;
