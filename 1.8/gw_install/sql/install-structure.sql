
DROP TABLE IF EXISTS `{prefix}abbr`;
CREATE TABLE IF NOT EXISTS `{prefix}abbr` (
  `id_abbr` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `id_dict` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `id_group` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_abbr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}abbr_phrase`
--

DROP TABLE IF EXISTS `{prefix}abbr_phrase`;
CREATE TABLE IF NOT EXISTS `{prefix}abbr_phrase` (
  `id_abbr_phrase` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_abbr` int(10) unsigned NOT NULL DEFAULT '0',
  `id_lang` varchar(32) NOT NULL DEFAULT 'en-utf8',
  `abbr_short` tinyblob NOT NULL,
  `abbr_long` tinyblob NOT NULL,
  PRIMARY KEY (`id_abbr_phrase`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}auth_restore`
--

DROP TABLE IF EXISTS `{prefix}auth_restore`;
CREATE TABLE IF NOT EXISTS `{prefix}auth_restore` (
  `id_user` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `auth_key` int(10) unsigned NOT NULL,
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}captcha`
--

DROP TABLE IF EXISTS `{prefix}captcha`;
CREATE TABLE IF NOT EXISTS `{prefix}captcha` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_created` int(10) unsigned NOT NULL,
  `captcha` varchar(5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `captcha` (`captcha`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}component`
--

DROP TABLE IF EXISTS `{prefix}component`;
CREATE TABLE IF NOT EXISTS `{prefix}component` (
  `id_component` tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
  `id_component_name` varchar(64) NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `int_sort` mediumint(8) unsigned NOT NULL DEFAULT '10',
  `vv1` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `vv2` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `vv3` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `cname` varchar(128) NOT NULL,
  PRIMARY KEY (`id_component`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}component_actions`
--

DROP TABLE IF EXISTS `{prefix}component_actions`;
CREATE TABLE IF NOT EXISTS `{prefix}component_actions` (
  `id_action` tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
  `aname` varchar(64) NOT NULL,
  `aname_sys` varchar(64) NOT NULL,
  `icon` varchar(64) NOT NULL,
  PRIMARY KEY (`id_action`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}component_map`
--

DROP TABLE IF EXISTS `{prefix}component_map`;
CREATE TABLE IF NOT EXISTS `{prefix}component_map` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_component` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `id_action` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `is_active_map` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_in_menu` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `int_sort` smallint(5) unsigned NOT NULL DEFAULT '10',
  `req_permission_map` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_component` (`id_action`,`id_component`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}custom_az`
--

DROP TABLE IF EXISTS `{prefix}custom_az`;
CREATE TABLE IF NOT EXISTS `{prefix}custom_az` (
  `id_letter` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_profile` smallint(5) unsigned NOT NULL DEFAULT '1',
  `int_sort` int(10) unsigned NOT NULL DEFAULT '10',
  `az_value` varbinary(8) NOT NULL,
  `az_value_lc` varbinary(8) NOT NULL,
  `az_int` int(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_letter`),
  KEY `az_value` (`id_profile`,`int_sort`,`az_int`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}custom_az_profiles`
--

DROP TABLE IF EXISTS `{prefix}custom_az_profiles`;
CREATE TABLE IF NOT EXISTS `{prefix}custom_az_profiles` (
  `id_profile` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `profile_name` tinyblob NOT NULL,
  PRIMARY KEY (`id_profile`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}dict`
--

DROP TABLE IF EXISTS `{prefix}dict`;
CREATE TABLE IF NOT EXISTS `{prefix}dict` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `id_topic` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `id_user` smallint(5) unsigned NOT NULL DEFAULT '1',
  `id_custom_az` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `id_vkbd` smallint(5) unsigned NOT NULL DEFAULT '0',
  `lang` varchar(12) NOT NULL DEFAULT 'en-utf8',
  `date_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `int_terms` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `int_terms_total` int(10) unsigned NOT NULL DEFAULT '0',
  `int_bytes` int(10) unsigned NOT NULL DEFAULT '0',
  `tablename` varchar(32) NOT NULL DEFAULT '',
  `visualtheme` varchar(64) NOT NULL DEFAULT '{prefix}silver',
  `title` varbinary(255) NOT NULL DEFAULT '',
  `dict_uri` varbinary(255) NOT NULL DEFAULT '',
  `announce` varbinary(255) NOT NULL DEFAULT '',
  `description` blob NOT NULL,
  `keywords` varbinary(255) NOT NULL DEFAULT '',
  `dict_settings` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_created` (`date_created`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}history_terms`
--

DROP TABLE IF EXISTS `{prefix}history_terms`;
CREATE TABLE IF NOT EXISTS `{prefix}history_terms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_user` int(10) unsigned NOT NULL DEFAULT '0',
  `id_dict` int(10) unsigned NOT NULL DEFAULT '0',
  `id_term` int(10) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_complete` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `date_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `int_bytes` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `crc32u` int(10) NOT NULL DEFAULT '0',
  `term_a` int(9) unsigned NOT NULL DEFAULT '0',
  `term_b` int(9) unsigned NOT NULL DEFAULT '0',
  `term_c` int(9) unsigned NOT NULL DEFAULT '0',
  `term_d` int(9) unsigned NOT NULL DEFAULT '0',
  `term_e` int(9) unsigned NOT NULL DEFAULT '0',
  `term_f` int(9) unsigned NOT NULL DEFAULT '0',
  `term_1` varbinary(16) NOT NULL,
  `term_2` varbinary(16) NOT NULL,
  `term_3` varbinary(16) NOT NULL,
  `term` tinyblob NOT NULL,
  `term_uri` varchar(255) NOT NULL DEFAULT '',
  `term_order` varchar(255) NOT NULL,
  `defn` mediumblob NOT NULL,
  `keywords` mediumblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}import_sessions`
--

DROP TABLE IF EXISTS `{prefix}import_sessions`;
CREATE TABLE IF NOT EXISTS `{prefix}import_sessions` (
  `id_session` varchar(32) NOT NULL,
  `id_user` int(10) unsigned NOT NULL DEFAULT '0',
  `id_dict` smallint(5) unsigned NOT NULL DEFAULT '0',
  `date_start` int(10) unsigned NOT NULL DEFAULT '0',
  `date_end` int(10) unsigned NOT NULL DEFAULT '0',
  `int_items_total` int(10) unsigned NOT NULL DEFAULT '0',
  `int_items_passed` int(10) unsigned NOT NULL DEFAULT '0',
  `int_bytes` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` varbinary(255) NOT NULL,
  `settings` blob NOT NULL,
  PRIMARY KEY (`id_session`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}map_user_to_dict`
--

DROP TABLE IF EXISTS `{prefix}map_user_to_dict`;
CREATE TABLE IF NOT EXISTS `{prefix}map_user_to_dict` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` smallint(5) unsigned NOT NULL DEFAULT '1',
  `dict_id` smallint(5) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}map_user_to_term`
--

DROP TABLE IF EXISTS `{prefix}map_user_to_term`;
CREATE TABLE IF NOT EXISTS `{prefix}map_user_to_term` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `term_id` int(10) unsigned NOT NULL DEFAULT '0',
  `dict_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `utd` (`user_id`,`term_id`,`dict_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}pages`
--

DROP TABLE IF EXISTS `{prefix}pages`;
CREATE TABLE IF NOT EXISTS `{prefix}pages` (
  `id_page` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `id_user` smallint(5) unsigned NOT NULL DEFAULT '2',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `int_sort` smallint(5) unsigned NOT NULL DEFAULT '10',
  `id_parent` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `date_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `page_icon` varchar(64) NOT NULL DEFAULT '',
  `page_uri` varchar(255) NOT NULL DEFAULT '',
  `page_php_1` mediumblob NOT NULL,
  `page_php_2` tinyblob NOT NULL,
  PRIMARY KEY (`id_page`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}pages_phrase`
--

DROP TABLE IF EXISTS `{prefix}pages_phrase`;
CREATE TABLE IF NOT EXISTS `{prefix}pages_phrase` (
  `id_page_phrase` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `id_page` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `id_lang` varchar(10) NOT NULL DEFAULT 'en-utf8',
  `page_title` varbinary(255) NOT NULL DEFAULT '',
  `page_keywords` blob NOT NULL,
  `page_descr` mediumblob NOT NULL,
  `page_content` mediumblob NOT NULL,
  PRIMARY KEY (`id_page_phrase`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}search_results`
--

DROP TABLE IF EXISTS `{prefix}search_results`;
CREATE TABLE IF NOT EXISTS `{prefix}search_results` (
  `id_srch` varchar(32) NOT NULL DEFAULT '',
  `id_d` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `srch_date` int(10) unsigned NOT NULL DEFAULT '0',
  `found` int(10) unsigned NOT NULL DEFAULT '1',
  `hits` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `q` tinyblob NOT NULL,
  `srch_settings` mediumblob NOT NULL,
  PRIMARY KEY (`id_srch`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}sessions`
--

DROP TABLE IF EXISTS `{prefix}sessions`;
CREATE TABLE IF NOT EXISTS `{prefix}sessions` (
  `id_sess` char(32) NOT NULL,
  `id_user` int(10) unsigned NOT NULL DEFAULT '1',
  `is_remember` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `date_changed` int(10) unsigned NOT NULL DEFAULT '0',
  `ip` int(10) unsigned NOT NULL DEFAULT '0',
  `ua` varchar(255) NOT NULL,
  PRIMARY KEY (`id_sess`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}settings`
--

DROP TABLE IF EXISTS `{prefix}settings`;
CREATE TABLE IF NOT EXISTS `{prefix}settings` (
  `settings_key` varchar(127) NOT NULL DEFAULT '',
  `settings_val` mediumblob NOT NULL,
  PRIMARY KEY (`settings_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}stat_dict`
--

DROP TABLE IF EXISTS `{prefix}stat_dict`;
CREATE TABLE IF NOT EXISTS `{prefix}stat_dict` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}stat_search`
--

DROP TABLE IF EXISTS `{prefix}stat_search`;
CREATE TABLE IF NOT EXISTS `{prefix}stat_search` (
  `id_stat_search` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_dict` int(10) unsigned NOT NULL DEFAULT '0',
  `id_field` smallint(3) NOT NULL DEFAULT '-1',
  `found` smallint(3) unsigned NOT NULL DEFAULT '0',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `ip_long` int(10) unsigned NOT NULL DEFAULT '0',
  `q` blob,
  PRIMARY KEY (`id_stat_search`),
  KEY `date_created` (`date_created`),
  KEY `id_dict` (`id_dict`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 PACK_KEYS=1 DELAY_KEY_WRITE=1;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}theme`
--

DROP TABLE IF EXISTS `{prefix}theme`;
CREATE TABLE IF NOT EXISTS `{prefix}theme` (
  `id_theme` varchar(128) NOT NULL DEFAULT '',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `v1` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `v2` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `v3` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `theme_name` varbinary(128) NOT NULL DEFAULT '',
  `theme_author` varbinary(128) NOT NULL DEFAULT '',
  `theme_email` varchar(128) NOT NULL DEFAULT '',
  `theme_url` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_theme`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}theme_group`
--

DROP TABLE IF EXISTS `{prefix}theme_group`;
CREATE TABLE IF NOT EXISTS `{prefix}theme_group` (
  `settings_key` varchar(128) NOT NULL DEFAULT '',
  `id_group` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `int_sort` mediumint(8) unsigned NOT NULL DEFAULT '0',
  KEY `settings_key` (`settings_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}theme_settings`
--

DROP TABLE IF EXISTS `{prefix}theme_settings`;
CREATE TABLE IF NOT EXISTS `{prefix}theme_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_theme` varchar(128) NOT NULL DEFAULT '',
  `date_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `date_compiled` int(10) unsigned NOT NULL DEFAULT '0',
  `settings_key` varchar(128) NOT NULL DEFAULT '',
  `settings_value` blob NOT NULL,
  `code` blob NOT NULL,
  `code_i` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_theme` (`id_theme`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}topics`
--

DROP TABLE IF EXISTS `{prefix}topics`;
CREATE TABLE IF NOT EXISTS `{prefix}topics` (
  `id_topic` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `id_user` smallint(5) unsigned NOT NULL DEFAULT '2',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `int_sort` smallint(5) unsigned NOT NULL DEFAULT '10',
  `int_items` int(10) unsigned NOT NULL DEFAULT '0',
  `id_parent` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `date_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `topic_icon` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_topic`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}topics_phrase`
--

DROP TABLE IF EXISTS `{prefix}topics_phrase`;
CREATE TABLE IF NOT EXISTS `{prefix}topics_phrase` (
  `id_topic_phrase` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `id_topic` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `id_lang` varchar(10) NOT NULL DEFAULT 'en-utf8',
  `topic_title` varbinary(255) NOT NULL DEFAULT '',
  `topic_descr` mediumblob NOT NULL,
  PRIMARY KEY (`id_topic_phrase`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}users`
--

DROP TABLE IF EXISTS `{prefix}users`;
CREATE TABLE IF NOT EXISTS `{prefix}users` (
  `id_user` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varbinary(128) NOT NULL,
  `password` char(32) NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_multiple` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_show_contact` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `date_reg` int(10) unsigned NOT NULL DEFAULT '0',
  `date_login` int(10) unsigned NOT NULL DEFAULT '0',
  `int_items` int(10) unsigned NOT NULL DEFAULT '0',
  `user_fname` varbinary(64) NOT NULL,
  `user_sname` varbinary(64) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_perm` blob NOT NULL,
  `user_settings` blob NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}virtual_keyboard`
--

DROP TABLE IF EXISTS `{prefix}virtual_keyboard`;
CREATE TABLE IF NOT EXISTS `{prefix}virtual_keyboard` (
  `id_profile` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_index_page` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `vkbd_name` tinyblob NOT NULL,
  `vkbd_letters` tinyblob NOT NULL,
  PRIMARY KEY (`id_profile`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}wordlist`
--

DROP TABLE IF EXISTS `{prefix}wordlist`;
CREATE TABLE IF NOT EXISTS `{prefix}wordlist` (
  `word_text` varbinary(100) NOT NULL DEFAULT '',
  `word_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`word_text`),
  UNIQUE KEY `word_id` (`word_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 PACK_KEYS=1 DELAY_KEY_WRITE=1;

-- --------------------------------------------------------

--
-- Структура таблицы `{prefix}wordmap`
--

DROP TABLE IF EXISTS `{prefix}wordmap`;
CREATE TABLE IF NOT EXISTS `{prefix}wordmap` (
  `word_id` int(10) unsigned NOT NULL DEFAULT '0',
  `term_id` int(10) unsigned NOT NULL DEFAULT '0',
  `dict_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `term_match` tinyint(2) NOT NULL DEFAULT '0',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `word_id` (`word_id`),
  KEY `term_id` (`term_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1 DELAY_KEY_WRITE=1;
