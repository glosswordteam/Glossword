

DROP TABLE IF EXISTS `#__gw_items_tmp`;
CREATE TABLE IF NOT EXISTS `#__gw_items_tmp` (
  `id_item` int(10) unsigned NOT NULL,
  `id_lang_c` int(10) unsigned NOT NULL,
  `id_lang_1` int(10) unsigned NOT NULL,
  `item_mdate` datetime NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_complete` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `cnt_hits` int(10) unsigned NOT NULL DEFAULT '0',
  `contents_a` binary(8) NOT NULL,
  `contents_b` binary(8) NOT NULL,
  `contents_so` binary(16) NOT NULL,
  `int_sort_1` int(10) unsigned NOT NULL,
  `int_sort_2` int(10) unsigned NOT NULL,
  `int_sort_3` int(10) unsigned NOT NULL,
  `int_sort_4` int(10) unsigned NOT NULL,
  `int_sort_5` int(10) unsigned NOT NULL,
  `int_sort_6` int(10) unsigned NOT NULL,
  `int_sort_7` int(10) unsigned NOT NULL,
  `int_sort_8` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_item`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__gw_az_letters`;
CREATE TABLE IF NOT EXISTS `#__gw_az_letters` (
  `id_letter` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_lang` int(10) unsigned NOT NULL,
  `uc_crc32u` int(10) unsigned NOT NULL,
  `int_sort` int(10) unsigned NOT NULL,
  `uc` varbinary(8) NOT NULL,
  `lc` varbinary(8) NOT NULL,
  PRIMARY KEY (`id_letter`),
  KEY `int_sort` (`int_sort`),
  KEY `lc_crc32u` (`uc_crc32u`,`id_lang`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__gw_pid`;
CREATE TABLE IF NOT EXISTS `#__gw_pid` (
  `id_pid` char(32) NOT NULL DEFAULT '',
  `pid_value` tinyblob NOT NULL,
  `cdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `mdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id_pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `#__gw_tv`;
CREATE TABLE IF NOT EXISTS `#__gw_tv` (
  `id_tv` bigint(18) unsigned NOT NULL AUTO_INCREMENT,
  `id_lang` int(10) unsigned NOT NULL DEFAULT '1',
  `id_pid` char(32) DEFAULT NULL,
  `is_active` tinyint(1) unsigned NOT NULL COMMENT '0 - untranslated, 4 - translated and approved',
  `is_complete` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `id_user_created` int(10) unsigned NOT NULL DEFAULT '2',
  `id_user_modified` int(10) unsigned NOT NULL DEFAULT '2',
  `tv_value` blob NOT NULL,
  `cnt_bytes` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `cnt_words` int(10) unsigned NOT NULL DEFAULT '0',
  `cdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `mdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id_tv`),
  UNIQUE KEY `id_lang` (`id_lang`,`id_pid`),
  KEY `id_pid_mix` (`is_complete`,`id_lang`,`id_pid`),
  KEY `id_pid` (`id_pid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 PACK_KEYS=1 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='Translation Variants';


-- --------------------------------------------------------

DROP TABLE IF EXISTS `#__gw_blocks`;
CREATE TABLE IF NOT EXISTS `#__gw_blocks` (
  `id_block` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `block_type` tinyint(1) unsigned NOT NULL,
  `block_place` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `block_name` varchar(255) NOT NULL,
  `block_contents` blob NOT NULL,
  `block_cdate` datetime NOT NULL,
  `block_mdate` datetime NOT NULL,
  PRIMARY KEY (`id_block`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------


DROP TABLE IF EXISTS `#__gw_cached_units`;
CREATE TABLE IF NOT EXISTS `#__gw_cached_units` (
  `id_unit` bigint(18) unsigned NOT NULL,
  `cdate` datetime NOT NULL,
  `unit_group` bigint(18) unsigned NOT NULL,
  `unit_value` blob NOT NULL,
  PRIMARY KEY (`id_unit`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------


DROP TABLE IF EXISTS `#__gw_contents`;
CREATE TABLE IF NOT EXISTS `#__gw_contents` (
  `id_contents` bigint(18) unsigned NOT NULL,
  `id_item` int(10) unsigned NOT NULL,
  `id_field` int(10) unsigned NOT NULL,
  `id_user_created` int(10) unsigned NOT NULL,
  `id_user_modified` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
  `cnt_bytes` int(10) unsigned NOT NULL DEFAULT '0',
  `cnt_words` int(10) unsigned NOT NULL DEFAULT '0',
  `contents_a` binary(8) NOT NULL,
  `contents_b` binary(8) NOT NULL,
  `contents_so` binary(16) NOT NULL,
  `contents_1` int(10) unsigned NOT NULL,
  `contents_2` int(10) unsigned NOT NULL,
  `contents_3` int(10) unsigned NOT NULL,
  `contents_4` int(10) unsigned NOT NULL,
  `contents_5` int(10) unsigned NOT NULL,
  `contents_6` int(10) unsigned NOT NULL,
  `contents_7` int(10) unsigned NOT NULL,
  `contents_8` int(10) unsigned NOT NULL,
  `contents_value` blob NOT NULL,
  `contents_value_cached` blob NOT NULL,
  PRIMARY KEY (`id_contents`),
  KEY `id_item` (`id_item`),
  KEY `id_lang` (`id_lang`),
  KEY `id_item_field_lang` (`id_item`,`id_field`,`id_lang`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_contents_si`
--

DROP TABLE IF EXISTS `#__gw_contents_si`;
CREATE TABLE IF NOT EXISTS `#__gw_contents_si` (
  `id_contents` bigint(18) unsigned NOT NULL AUTO_INCREMENT,
  `id_item` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
  `contents_si` text NOT NULL,
  PRIMARY KEY (`id_contents`),
  FULLTEXT KEY `item_si` (`contents_si`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_fields`
--

DROP TABLE IF EXISTS `#__gw_fields`;
CREATE TABLE IF NOT EXISTS `#__gw_fields` (
  `id_field` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xml_tag` varchar(255) NOT NULL,
  `field_name` tinyblob NOT NULL,
  PRIMARY KEY (`id_field`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_fieldsets`
--

DROP TABLE IF EXISTS `#__gw_fieldsets`;
CREATE TABLE IF NOT EXISTS `#__gw_fieldsets` (
  `id_fieldset` mediumint(5) unsigned NOT NULL AUTO_INCREMENT,
  `fieldset_name` tinyblob NOT NULL,
  `mdate` datetime NOT NULL,
  PRIMARY KEY (`id_fieldset`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_items`
--

DROP TABLE IF EXISTS `#__gw_items`;
CREATE TABLE IF NOT EXISTS `#__gw_items` (
  `id_item` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id_user_created` int(10) unsigned NOT NULL,
  `item_id_user_modified` int(10) unsigned NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_complete` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `cnt_hits` int(10) unsigned NOT NULL DEFAULT '0',
  `item_cdate` datetime NOT NULL,
  `item_mdate` datetime NOT NULL,
  PRIMARY KEY (`id_item`),
  KEY `is_active` (`is_active`,`item_cdate`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_items_uri`
--

DROP TABLE IF EXISTS `#__gw_items_uri`;
CREATE TABLE IF NOT EXISTS `#__gw_items_uri` (
  `id_item` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_uri` varchar(255) NOT NULL,
  PRIMARY KEY (`id_item`),
  KEY `item_uri` (`item_uri`(128))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_languages`
--

DROP TABLE IF EXISTS `#__gw_languages`;
CREATE TABLE IF NOT EXISTS `#__gw_languages` (
  `id_lang` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `lang_name` varbinary(64) NOT NULL,
  `lang_native` varbinary(64) NOT NULL,
  `region` char(3) NOT NULL,
  `isocode1` char(2) NOT NULL,
  `isocode3` char(3) NOT NULL,
  `direction` enum('ltr','rtl') NOT NULL DEFAULT 'ltr',
  `thousands_separator` char(6) NOT NULL DEFAULT ',',
  `decimal_separator` char(6) NOT NULL DEFAULT '.',
  `month_short` tinyblob NOT NULL,
  `month_long` tinyblob NOT NULL,
  `month_decl` tinyblob NOT NULL,
  `day_of_week` tinyblob NOT NULL,
  `byte_units` tinyblob NOT NULL,
  PRIMARY KEY (`id_lang`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_map_field_to_fieldset`
--

DROP TABLE IF EXISTS `#__gw_map_field_to_fieldset`;
CREATE TABLE IF NOT EXISTS `#__gw_map_field_to_fieldset` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_fieldset` int(10) unsigned NOT NULL,
  `id_field` int(10) unsigned NOT NULL,
  `int_sort` mediumint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_fieldset` (`id_fieldset`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_map_item_to_tag`
--

DROP TABLE IF EXISTS `#__gw_map_item_to_tag`;
CREATE TABLE IF NOT EXISTS `#__gw_map_item_to_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_item` int(10) unsigned NOT NULL,
  `dict_crc32u` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_term__dict_crc32u` (`id_item`,`dict_crc32u`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_sessions_jos`
--

DROP TABLE IF EXISTS `#__gw_sessions_jos`;
CREATE TABLE IF NOT EXISTS `#__gw_sessions_jos` (
  `id_sess` char(32) NOT NULL,
  `id_user` int(10) unsigned NOT NULL DEFAULT '1',
  `is_remember` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `mdate` datetime NOT NULL,
  `sess_settings` blob,
  `ip` int(10) unsigned NOT NULL DEFAULT '0',
  `ua` varchar(255) NOT NULL,
  PRIMARY KEY (`id_sess`),
  KEY `changed` (`mdate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_settings`
--

DROP TABLE IF EXISTS `#__gw_settings`;
CREATE TABLE IF NOT EXISTS `#__gw_settings` (
  `id_varname` varchar(32) NOT NULL,
  `value` mediumblob NOT NULL,
  PRIMARY KEY (`id_varname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_sidebar`
--

DROP TABLE IF EXISTS `#__gw_sidebar`;
CREATE TABLE IF NOT EXISTS `#__gw_sidebar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_block` int(10) unsigned NOT NULL,
  `int_sort` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_translit`
--

DROP TABLE IF EXISTS `#__gw_translit`;
CREATE TABLE IF NOT EXISTS `#__gw_translit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_profile` mediumint(5) unsigned NOT NULL,
  `crc32u` int(10) unsigned NOT NULL,
  `str_from` varchar(100) NOT NULL,
  `str_to` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `crc32u` (`crc32u`,`id_profile`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_unicode_normalization`
--

DROP TABLE IF EXISTS `#__gw_unicode_normalization`;
CREATE TABLE IF NOT EXISTS `#__gw_unicode_normalization` (
  `crc32u` int(10) unsigned NOT NULL,
  `str_from` varchar(9) NOT NULL,
  `str_to` varchar(100) NOT NULL,
  PRIMARY KEY (`crc32u`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_usergroups_jos`
--

DROP TABLE IF EXISTS `#__gw_usergroups_jos`;
CREATE TABLE IF NOT EXISTS `#__gw_usergroups_jos` (
  `id_group` tinyint(3) NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `int_sort` mediumint(5) unsigned NOT NULL DEFAULT '10',
  `group_name` tinyblob NOT NULL,
  `group_descr` tinyblob NOT NULL,
  `group_title` varchar(128) NOT NULL,
  `group_perm` blob NOT NULL,
  `mdate` datetime NOT NULL,
  PRIMARY KEY (`id_group`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `#__gw_users_jos`
--

DROP TABLE IF EXISTS `#__gw_users_jos`;
CREATE TABLE IF NOT EXISTS `#__gw_users_jos` (
  `id_user` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varbinary(128) NOT NULL,
  `password` char(32) NOT NULL,
  `id_group` tinyint(3) unsigned NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Banned/Active/Pending/Removed',
  `is_visible` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_moderated` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `id_user_public` bigint(18) unsigned NOT NULL DEFAULT '0',
  `date_reg` datetime NOT NULL,
  `date_login` datetime NOT NULL,
  `cnt_terms` int(10) unsigned NOT NULL DEFAULT '0',
  `cnt_comments` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `cnt_bytes` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `user_fname` tinyblob NOT NULL,
  `user_sname` tinyblob NOT NULL,
  `user_nickname` tinyblob NOT NULL,
  `user_email` varchar(128) NOT NULL,
  `user_location` tinyblob NOT NULL,
  `user_settings` blob NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
