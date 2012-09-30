<?php
/*
 * Query storage
 *  � 2008-2012 Glossword.biz team <team at glossword dot biz>
 *  � 2002-2008 Dmitry N. Shilnikov
 * $Id: query_storage_global-mysql410.php 491 2008-06-13 10:05:06Z glossword_team $
 */

$tmp['ar_queries'] = array(
	'get-vkbd-default' => 'SELECT *
						FROM `'.$sys['tbl_prefix'].'virtual_keyboard`
						WHERE `is_index_page` = "1"
						LIMIT 1
					',
	'get-vkbd-profile' => 'SELECT *
						FROM `'.$sys['tbl_prefix'].'virtual_keyboard`
						WHERE `id_profile` = "%d"
						LIMIT 1
					',
	'get-vkbd-profiles' => 'SELECT *
						FROM `'.$sys['tbl_prefix'].'virtual_keyboard`
						ORDER BY `vkbd_name`
					',
	'get-history-to-rollback' => 'SELECT ht.*
						FROM `'.$sys['tbl_prefix'].'history_terms` AS ht
						WHERE ht.id = "%d"
					',
	'get-history-by-date' => 'SELECT ht.id_term, ht.id
						FROM `'.$sys['tbl_prefix'].'history_terms` AS ht
						WHERE ht.id_term = "%d"
						AND ht.date_modified = "%s" LIMIT 1
					',
	'get-history-to-remove' => 'SELECT ht.id_term, ht.id_dict
						FROM `'.$sys['tbl_prefix'].'history_terms` AS ht
						WHERE ht.is_active = "3"
					',
	'get-history-by-term_id' => 'SELECT ht.*, CONCAT(u.user_fname, " ", u.user_sname) AS user_name, u.id_user
						FROM `'.$sys['tbl_prefix'].'history_terms` AS ht
						LEFT JOIN `'.$sys['tbl_prefix'].'users` AS u
						ON (u.id_user = ht.id_user)
						WHERE ht.id_term = "%d"
						ORDER BY ht.date_modified DESC
						%s
					',
	'get-custom_az-int' => 'SELECT `az_int` AS value
						FROM `'.$sys['tbl_prefix'].'custom_az`
						WHERE `id_profile` = "%d"
						ORDER BY `int_sort`
				',
	'get-custom_az-letters' => 'SELECT `int_sort`, `az_value`, `az_value_lc`
						FROM `'.$sys['tbl_prefix'].'custom_az`
						WHERE id_profile = "%d"
						ORDER BY `int_sort`
				',
	'get-custom_az-by-char' => 'SELECT `int_sort`, `az_value` as L1
						FROM `'.$sys['tbl_prefix'].'custom_az` AS tb
						WHERE `id_profile` = "%d"
						AND `az_value` IN ("%s")
						ORDER BY `int_sort`
					',
	'get-custom_az-profiles' => 'SELECT *
						FROM `'.$sys['tbl_prefix'].'custom_az_profiles`
						WHERE `is_active` = "1"
						ORDER BY `profile_name`
				',
	'get-keywords-by-term_id' => 'SELECT wl.*, wm.term_match
						FROM `'.$sys['tbl_prefix'].'wordlist` as wl, `'.$sys['tbl_prefix'].'wordmap` as wm
						WHERE wl.word_id = wm.word_id
						AND wm.term_id = "%d"
						AND wm.dict_id = "%d"
						ORDER BY wm.term_match DESC, wl.word_text
						',
	'top-search-last' => 'SELECT `id_dict`, `q`, `date_created`
						FROM `'.$sys['tbl_prefix'].'stat_search`
						WHERE `found` > 0
						GROUP BY `q`
						ORDER BY `date_created` DESC
						LIMIT 0, %d
					',
	'get-themes-adm' => 'SELECT th.*
						FROM `'.$sys['tbl_prefix'].'theme` AS th
						ORDER BY th.theme_name ASC
						%s
					',
	'cnt-themes-adm' => 'SELECT count(*) AS n
						FROM '.$sys['tbl_prefix'].'theme AS th
						ORDER BY th.theme_name ASC
					',
	'get-themes' => 'SELECT th.*
						FROM '.$sys['tbl_prefix'].'theme AS th
						WHERE th.is_active = "1"
						AND th.id_theme != "gw\\_admin"
						ORDER BY th.theme_name ASC
					',
	'top-user-terms' => 'SELECT u.id_user, CONCAT(u.user_fname, " ", u.user_sname) AS user_name, u.int_items
						FROM '.$sys['tbl_prefix'].'users AS u
						WHERE u.is_active = "1"
						AND u.int_items > 0
						ORDER BY u.int_items DESC, user_name ASC
						',
	'get-theme-code-key' => 'SELECT ths.settings_key, ths.settings_value, ths.date_modified, ths.date_compiled, ths.code, ths.code_i
						FROM '.$sys['tbl_prefix'].'theme AS th, '.$sys['tbl_prefix'].'theme_settings AS ths
						WHERE ths.id_theme = "%s"
						AND th.id_theme = ths.id_theme
						AND th.is_active = "1"
						AND ths.settings_key = "%s"
					',
	'get-theme-code-gp' => 'SELECT g.id_group, ths.settings_key, ths.settings_value, ths.date_modified, ths.date_compiled, ths.code, ths.code_i
						FROM '.$sys['tbl_prefix'].'theme AS th, '.$sys['tbl_prefix'].'theme_settings AS ths, '.$sys['tbl_prefix'].'theme_group AS g
						WHERE ths.id_theme = "%s"
						AND th.id_theme = ths.id_theme
						AND th.is_active = "1"
						AND ths.settings_key = g.settings_key
						AND g.id_group IN (%s)
						ORDER BY g.int_sort
					',
	'get-theme' => 'SELECT ths.settings_key, ths.settings_value
						FROM '.$sys['tbl_prefix'].'theme AS th, '.$sys['tbl_prefix'].'theme_settings AS ths, '.$sys['tbl_prefix'].'theme_group AS g
						WHERE ths.id_theme = "%s"
						AND th.id_theme = ths.id_theme
						AND th.is_active = "1"
						AND ths.settings_key = g.settings_key
						AND g.id_group IN (%s)
						GROUP BY g.settings_key
						ORDER BY g.id_group, g.int_sort
					',
	'get-abbr-code' => 'SELECT a.id_group, a.id_abbr, b.abbr_short, b.abbr_long
						FROM `'.$sys['tbl_prefix'].'abbr` AS a, `'.$sys['tbl_prefix'].'abbr_phrase` AS b
						WHERE a.id_abbr = b.id_abbr
						AND a.is_active = "1"
						AND b.id_lang = "%s"
						%s
					',
	'get-abbr-list' => 'SELECT a.id_abbr, b.abbr_short, b.abbr_long
						FROM `'.$sys['tbl_prefix'].'abbr` AS a, `'.$sys['tbl_prefix'].'abbr_phrase` AS b
						WHERE a.id_abbr = b.id_abbr
						AND a.is_active = "1"
						AND b.id_lang = "%s"
						%s
						ORDER BY a.id_group DESC, b.id_lang ASC, b.abbr_short
					',
	'get-components-actions' => 'SELECT cm.*, cma.aname, cma.aname_sys, cma.icon, cmm.id, cmm.id_action, cmm.is_active_map, cmm.is_in_menu, cmm.req_permission_map
						FROM `'.$sys['tbl_prefix'].'component` AS cm
						LEFT JOIN `'.$sys['tbl_prefix'].'component_map` AS cmm 
							ON (cmm.id_component = cm.id_component)
						LEFT JOIN `'.$sys['tbl_prefix'].'component_actions` AS cma 
							ON (cmm.id_action = cma.id_action)
						WHERE (%s) AND (%s) %s
						ORDER BY cm.int_sort ASC, cmm.int_sort ASC
					',
	'get-component-action-perm' => 'SELECT cm.cname, cm.id_component_name, cma.aname, cma.aname_sys, cma.icon, cmm.id
						FROM `'.$sys['tbl_prefix'].'component` AS cm,
						`'.$sys['tbl_prefix'].'component_actions` AS cma,
						`'.$sys['tbl_prefix'].'component_map` AS cmm
						WHERE (%s) 
						AND cma.aname_sys = "%s"
						AND cm.id_component_name = "%s"
						AND cmm.id_action = cma.id_action
						AND cmm.id_component = cm.id_component
						LIMIT 1
					',
	'get-component-menu' => 'SELECT cm.php_code, cmm.*
						FROM '.$sys['tbl_prefix'].'component_menu AS cmm, '.$sys['tbl_prefix'].'component AS cm
						WHERE cm.id_component = cmm.id_component
						AND cm.is_active = "1"
						ORDER BY cm.int_sort ASC, cm.id_component, 
						cmm.icon ASC, cmm.id_action
					',
	'get-custompages-lang' => 'SELECT gp.id_page, gpph.id_lang
						FROM '.$sys['tbl_prefix'].'pages AS gp, '.$sys['tbl_prefix'].'pages_phrase AS gpph
						WHERE %s
						AND gp.id_page = gpph.id_page
						AND gp.is_active = "1"
						GROUP BY gpph.id_lang
					',
	'get-pages-list' => 'SELECT gpph.page_title, gpph.id_lang, gp.id_page, gp.page_uri, gp.page_icon, gp.page_php_2, gp.int_sort, gp.id_user
						FROM '.$sys['tbl_prefix'].'pages AS gp, '.$sys['tbl_prefix'].'pages_phrase AS gpph
						WHERE gp.id_page = gpph.id_page
						AND gp.is_active = "1"
						AND gp.id_parent = "0"
						ORDER BY gp.int_sort ASC
					',
	'get-custompages-list' => 'SELECT gpph.page_title AS title, gpph.page_descr, gpph.id_lang, gp.id_page, gp.is_active,
						gp.page_uri, gp.id_parent as p, gp.page_icon, gp.page_php_2, gp.int_sort, gp.id_user
						FROM `'.$sys['tbl_prefix'].'pages` AS gp, `'.$sys['tbl_prefix'].'pages_phrase` AS gpph
						WHERE gp.id_page = gpph.id_page
						%s
						ORDER BY gp.int_sort ASC
					',
	'get-custompages_id-by-title' => 'SELECT gp.id_page, gpph.page_title
						FROM '.$sys['tbl_prefix'].'pages AS gp, '.$sys['tbl_prefix'].'pages_phrase AS gpph
						WHERE gp.id_page = gpph.id_page
						GROUP BY gp.id_page
						ORDER BY gpph.id_lang, gpph.page_title
					',
	'get-custompages_id-by-p' => 'SELECT gp.id_page, gpph.page_title, gpph.page_descr
						FROM '.$sys['tbl_prefix'].'pages AS gp, '.$sys['tbl_prefix'].'pages_phrase AS gpph
						WHERE gp.id_page = gpph.id_page
						AND gp.id_parent = "%d"
						GROUP BY gp.id_page
						ORDER BY gpph.id_lang, gpph.page_title
					',
	'get-custompages' => 'SELECT gp.id_page, gp.date_created, gp.date_modified, gp.page_php_1, gp.page_php_2, gp.page_icon, gp.page_uri,
						gpph.page_title, gpph.page_descr, gpph.page_content, gpph.id_lang
						FROM '.$sys['tbl_prefix'].'pages AS gp, '.$sys['tbl_prefix'].'pages_phrase AS gpph
						WHERE %s
						AND gpph.id_lang = "%s"
						AND gp.id_page = gpph.id_page
						AND gp.is_active = "1"
						LIMIT 1
					',
	'get-topics_id-by-p' => 'SELECT tp.id_topic, tpph.topic_title, tp.id_user, tpph.topic_descr
						FROM '.$sys['tbl_prefix'].'topics AS tp, '.$sys['tbl_prefix'].'topics_phrase AS tpph
						WHERE tp.id_topic = tpph.id_topic
						AND tp.id_parent = "%d"
						GROUP BY tp.id_topic
						ORDER BY tpph.id_lang, tpph.topic_title
					',
	'get-subtopics-list' => 'SELECT tpph.topic_title AS title, tp.id_user, tpph.topic_descr, tpph.id_lang, tp.id_topic, 
						tp.id_parent AS p, tp.topic_icon, tp.int_sort, tp.is_active, tp.int_items
						FROM `'.$sys['tbl_prefix'].'topics` AS tp, `'.$sys['tbl_prefix'].'topics_phrase` AS tpph
						WHERE tp.id_topic = tpph.id_topic
						%s
						ORDER BY tp.int_sort ASC
					',
	'cnt-users' => 'SELECT count(*) AS n FROM `'.$sys['tbl_prefix'].'users` AS u
						WHERE u.id_user != "%d"
						AND u.id_user != "%d"
					',
	'get-users' => 'SELECT u.* FROM `'.$sys['tbl_prefix'].'users` AS u
						WHERE u.id_user != "%d"
						AND u.id_user != "%d"
						ORDER BY u.user_fname ASC, u.user_sname ASC, u.user_email ASC
						%
					',
	'get-users-by-dict_id' => 'SELECT u.id_user, CONCAT(u.user_fname, " ", u.user_sname) AS user_name
						FROM '.$sys['tbl_prefix'].'users AS u, '.$sys['tbl_prefix'].'map_user_to_term AS mut
						WHERE mut.user_id = u.id_user
						AND mut.dict_id = "%d"
						GROUP BY mut.user_id
						ORDER BY u.int_items DESC
					',
	'get-users-by-term_id' => 'SELECT u.id_user, CONCAT(u.user_fname, " ", u.user_sname) AS user_name
						FROM '.$sys['tbl_prefix'].'users AS u, '.$sys['tbl_prefix'].'map_user_to_term AS mut
						WHERE mut.user_id = u.id_user
						AND mut.term_id = "%d"
						GROUP BY mut.user_id
						ORDER BY u.int_items DESC 
					',
	'get-settings' => 'SELECT `settings_key`, `settings_val`
						FROM `'.$sys['tbl_prefix'].'settings`
					',
	'get-dicts-admin' => 'SELECT d.*
						FROM `'.$sys['tbl_prefix'].'dict` AS d
						ORDER BY d.title, d.is_active, d.lang
					',
	'get-dicts-web' => 'SELECT d.*
						FROM `'.$sys['tbl_prefix'].'dict` AS d
						WHERE d.is_active = "1"
						AND date_created <= %s
						ORDER BY d.lang, d.title
					',
	'get-dict' => 'SELECT CONCAT(u.user_fname, " ", u.user_sname) AS user_name, u.user_email, u.location, u.is_showcontact, d.*
						FROM '.$sys['tbl_prefix'].'dict AS d, '.$sys['tbl_prefix'].'users AS u
						WHERE d.id = "%d"
						AND d.id_user = u.id_user
						AND d.is_active = "1"
					',
	'get-terms-total' => 'SELECT count(*) AS num, SUM(int_terms) AS sum
						FROM '.$sys['tbl_prefix'].'dict`
						WHERE is_active = "1"
					',
	'top-dict-updated' => 'SELECT d.id, d.date_modified, s.hits, d.title, d.dict_uri
						FROM '.$sys['tbl_prefix'].'dict AS d, '.$sys['tbl_prefix'].'stat_dict AS s
						WHERE d.is_active = "1"
						AND d.id = s.id
						AND d.date_created < %s
						ORDER BY d.date_modified DESC LIMIT 0, %d
					',
	'top-dict-updated-adm' => 'SELECT d.id, d.date_modified, s.hits, d.title, d.dict_uri
						FROM '.$sys['tbl_prefix'].'dict AS d, '.$sys['tbl_prefix'].'stat_dict AS s
						WHERE d.is_active = "1"
						AND d.id = s.id
						ORDER BY d.date_modified DESC LIMIT 0, %d
					',
	'top-dict-hits-avg' => 'SELECT d.id, s.hits, d.title, d.dict_uri, (s.hits / ((%s - d.date_created) / 86400) ) AS hits_avg
						FROM '.$sys['tbl_prefix'].'dict AS d, '.$sys['tbl_prefix'].'stat_dict AS s
						WHERE d.is_active = "1"
						AND d.id = s.id
						AND d.date_created < %s
						ORDER BY hits_avg DESC, s.hits DESC, d.int_terms ASC, d.title ASC LIMIT 0, %d
					',
	'top-dict-new' => 'SELECT d.id, d.date_created, s.hits, d.title, d.dict_uri
						FROM '.$sys['tbl_prefix'].'dict AS d, '.$sys['tbl_prefix'].'stat_dict AS s
						WHERE d.is_active = "1"
						AND d.id = s.id
						AND d.date_created < %s
						ORDER BY d.date_created DESC LIMIT 0, %d
					',
	'top-term-new' => 'SELECT id, id_user, date_modified, term, term_uri
						FROM `%s`
						WHERE is_active = "1"
						AND date_created <= %s
						ORDER BY %s LIMIT 0, %d
					',
	'top-term-new-adm' => 'SELECT id, date_modified, term, term_uri
						FROM `%s`
						ORDER BY date_modified DESC, term LIMIT 0, %d
					',
	'up-dict-hits' => 'UPDATE '.$sys['tbl_prefix'].'stat_dict
						SET hits = hits + 1
						WHERE id = "%d"
					',
	'get-az' => 'SELECT t.term_1 AS L1, t.term_2 AS L2, term_3 AS L3
						FROM `%s` AS t
						WHERE t.is_active = "1"
						AND t.date_created <= %s
						GROUP BY L1, L2, L3
						ORDER BY %s t.term_1, t.term_2, t.term_3, t.term
					',
	'get-az-terms' => 'SELECT t.term_1 AS L1, t.term, t.id AS id_term, t.term_uri
						FROM `%s` AS t
						WHERE t.is_active = "1"
						AND t.term_1 = "%s"
						AND t.date_created <= %s
						ORDER BY %s t.term_1, t.term_order
						LIMIT 0, %d
					',
	'get-tb-by-id_lang' => 'SELECT tb.int_sort, tb.character as L1
						FROM `%s` AS tb
						WHERE tb.id_lang = "%s"
						ORDER BY tb.int_sort
					',
	'get-term-by-id' => 'SELECT id AS tid, id_user, is_active, is_complete, term_1, term_2, term_3,
							term, term_uri, defn, date_created, date_modified
						FROM `%s`
						WHERE is_active = "1"
						AND id = "%d"
						AND date_created <= %s
						LIMIT 1
						',
	'get-term-by-term' => 'SELECT id AS tid, id_user, is_active, is_complete, term_1, term_2, term_3, term, term_uri, defn, date_created, date_modified
						FROM `%s`
						WHERE is_active = "1"
						AND term = "%s" OR term_uri = "%s"
						AND date_created <= %s
						LIMIT 1
						',
	'get-term-by-id-adm' => 'SELECT id AS tid, id_user, is_active, is_complete, term_1, term_2, term_3, 
							 term_order, term, term_uri, defn, date_created, date_modified
						FROM `%s`
						WHERE id = "%d"
						LIMIT 1
					',
	'get-term-by-name' => 'SELECT t.id as tid, id_user, t.is_active, t.is_complete, t.term_1, t.term_2, term_3, t.term, t.term_uri, t.defn, t.date_created, t.date_modified
						FROM `%s` AS k, `%s` AS m, `%s` AS t
						WHERE is_active = "1"
						AND m.dict_id = "%d"
						AND m.term_match = "1"
						AND k.word_id = m.word_id
						AND t.id = m.term_id
						AND k.word_text IN (%s)
						GROUP BY m.term_id
					',
	'get-term-by-name-adm' => 'SELECT t.id as tid, id_user, t.is_active, t.is_complete, t.term_1, t.term_2, term_3, t.term, t.term_uri, t.defn, t.date_created, t.date_modified
						FROM `%s` AS k, `%s` AS m, `%s` AS t
						WHERE m.dict_id = "%d"
						AND m.term_match = "1"
						AND k.word_id = m.word_id
						AND t.id = m.term_id
						AND k.word_text IN (%s)
						GROUP BY m.term_id
					',
	'cnt-term' => 'SELECT count(*) AS n
						FROM `%s`
						WHERE is_active = "1"
						AND date_created <= %s
					',
	'cnt-term-by-t1' => 'SELECT count(*) AS n
						FROM `%s`
						WHERE is_active = "1"
						AND term_1 = "%s"
						AND date_created <= %s
					',
	'cnt-term-by-t1t2' => 'SELECT count(*) AS n
						FROM `%s`
						WHERE is_active = "1"
						AND term_1 = "%s"
						AND term_2 = "%s"
						AND date_created <= %s
					',
	'cnt-term-by-t1t2t3' => 'SELECT count(*) AS n
						FROM `%s`
						WHERE is_active = "1"
						AND term_1 = "%s"
						AND term_2 = "%s"
						AND term_3 = "%s"
						AND date_created <= %s
					',
	'get-term-by-t1' => 'SELECT t.id AS id_term, t.id_user, t.date_created, t.date_modified, t.term, t.term_uri, t.is_active, t.is_complete, t.int_bytes, %s
						FROM `%s` AS t
						WHERE t.is_active = "1"
						AND t.term_1 = "%s"
						AND t.date_created <= %s
						ORDER BY %s t.term_order
					',
	'get-term-by-t1t2' => 'SELECT t.id AS id_term, t.id_user, t.date_created, t.date_modified, t.term, t.term_uri, t.is_active, t.is_complete, t.int_bytes, %s
						FROM `%s` AS t
						WHERE t.is_active = "1"
						AND t.term_1 = "%s"
						AND t.term_2 = "%s"
						AND t.date_created <= %s
						ORDER BY %s t.term_order
					',
	'get-term-by-t1t2t3' => 'SELECT t.id AS id_term, t.id_user, t.date_created, t.date_modified, t.term, t.term_uri, t.is_active, t.is_complete, t.int_bytes, %s
						FROM `%s` AS t
						WHERE t.is_active = "1"
						AND t.term_1 = "%s"
						AND t.term_2 = "%s"
						AND t.term_3 = "%s"
						AND t.date_created <= %s
						ORDER BY %s t.term_order
					',
	'get-term-by-id_term' => 'SELECT id AS id_term, term, id_user, term_uri
						FROM `%s`
						WHERE id = "%s" 
						AND date_created <= %s
						%s
					',
	'get-term-by-page' => 'SELECT t.id AS id_term, t.id_user, t.date_created, t.date_modified, t.term, t.term_uri, t.is_active, t.is_complete, t.int_bytes, %s
						FROM `%s` AS t
						WHERE t.is_active = "1"
						AND t.date_created <= "%s"
						ORDER BY %s t.term_order
						%s
					',
	'srch-word-cnt' => 'SELECT m.term_id, m.dict_id
						FROM `'.$sys['tbl_prefix'].'dict` AS d, `' . TBL_WORDLIST . '` AS k, `' . TBL_WORDMAP . '` AS m%s
						WHERE m.dict_id = "%d" %s
						AND k.word_id = m.word_id
						AND m.term_id = t.id
						AND m.dict_id = d.id
						AND d.is_active = "1"
						AND %s
						AND m.date_created <= %s
						AND d.date_created <= %s
						GROUP BY m.term_id, m.dict_id
						ORDER BY m.dict_id
					',
	'srch-word-cnt-phrase' => 'SELECT m.term_id, m.dict_id, t.term, t.term_uri, t.defn
						FROM `' . TBL_WORDLIST . '` AS k, `' . TBL_WORDMAP . '` AS m%s
						WHERE m.dict_id IN (%s) %s
						AND k.word_id = m.word_id
						AND m.term_id = t.id
						AND %s
						AND m.date_created <= %s
						GROUP BY m.term_id, m.dict_id
						ORDER BY m.dict_id
					',
	'srch-keyword-by-term' => 'SELECT k.word_text
						FROM `' . TBL_WORDLIST . '` AS k, `' . TBL_WORDMAP . '` AS m
						WHERE m.dict_id IN (%s) %s
						AND k.word_id = m.word_id
						AND m.term_id IN (%s)
						GROUP BY m.term_id
					',
	'srch-result-cnt' => 'SELECT hits, found FROM `'.$sys['tbl_prefix'].'search_results`
						WHERE id_srch = "%s"
					',
	'srch-result-id' => 'SELECT * FROM '.$sys['tbl_prefix'].'search_results
						WHERE id_srch = "%s"
					',
	'get-terms-in' => 'SELECT ((LENGTH(term) - LENGTH(REPLACE(term, "%s", ""))) / LENGTH("%s")) AS str_count1,
						((LENGTH(defn) - LENGTH(REPLACE(defn, "%s", ""))) / LENGTH("%s")) AS str_count2,
						t.id AS id_term, t.id_user, t.term, t.term_uri, t.is_active, t.is_complete, t.defn, t.int_bytes
						FROM `%s` AS t
						WHERE t.id IN (%s)
						AND t.is_active = "1"
						ORDER BY str_count1 DESC, str_count2 DESC,
						%s t.term_1, t.term_order
						%s
					',
	'get-terms-in-adm' => 'SELECT t.id AS id_term, t.id_user, t.date_created, t.date_modified, t.term, t.term_uri, t.defn, t.is_active, t.is_complete, t.int_bytes
						FROM `%s` AS t
						WHERE id IN (%s)
						ORDER BY %s t.term_1, t.term_order
						%s
					',
	'upd-srch-results++' => 'UPDATE '.$sys['tbl_prefix'].'search_results
						SET hits = "%d"
						WHERE id_srch = "%s"
					',
	'upd-srch-q' => 'UPDATE '.$sys['tbl_prefix'].'search_results
						SET hits = "%d", q = "%s"
						WHERE id_srch = "%s"
					',
	'get-term-exists' => 'SELECT t.id, t.term, t.term_uri, t.id_user
						FROM `%s` AS k, '.TBL_WORDMAP.' AS m, `%s` AS t
						WHERE m.dict_id = "%d"
						AND m.term_match = "%d"
						AND k.word_id = m.word_id
						AND t.id = m.term_id
						AND t.is_active != "3"
						AND %s
						GROUP BY m.term_id
					',
	'get-term-exists-spec' => 'SELECT t.id, t.term, t.term_uri
						FROM `%s` AS t
						WHERE (t.term LIKE "%s" OR t.id = "%d")
						AND t.is_active != "3"
						GROUP BY t.id
					',
	'get-term-rand' => 'SELECT t.term, t.defn, t.term_uri
						FROM `%s` as t
						WHERE LENGTH(t.defn) < 4096
						ORDER BY RAND()
						LIMIT 0, 1
					',
	'get-term-export' => 'SELECT term_1, term_2, term_3, id, term, term_uri, defn
						FROM `%s` as t
						WHERE %s
						ORDER BY t.term
						%s
					',
	'get-word' => 'SELECT word_text, word_id
						FROM `%s` 
						WHERE word_text IN (%s)
					',
	'del-wordmap-by-term-dict' => 'DELETE FROM '.TBL_WORDMAP.'
						WHERE term_id = "%d"
						AND dict_id = "%d"
					',
	'get-terms-all' => 'SELECT t.id as term_id
						FROM `%s` AS t
						WHERE t.is_active = "1"
						GROUP BY t.id
					',
	'get-terms-all-adm' => 'SELECT t.id as term_id
						FROM `%s` AS t
						%s
						GROUP BY t.id
						LIMIT 0, %s
					',
	'get-date-mm' => 'SELECT MIN(date_modified) AS min, MAX(date_modified) AS max
						FROM `%s`
					',
	'cnt-dict-date' => 'SELECT count(*) AS n
						FROM `%s`
						WHERE date_modified >= %s
						AND date_modified <= %s
					',
	'get-records-date' => 'SELECT *
						FROM `%s`
						WHERE date_modified >= %s
						AND date_modified <= %s
					',
	'get-users-edit' => 'SELECT *
						FROM '.$sys['tbl_prefix'].'users
						WHERE perm_level < 16
						AND perm_level >= 2
						ORDER BY date_reg DESC
					',
	'get-dict-valid' => 'SELECT d.id, d.tablename
						FROM '.$sys['tbl_prefix'].'dict AS d, '.$sys['tbl_prefix'].'stat_dict AS s
						WHERE d.is_active = "1"
						AND d.id = s.id
						ORDER BY d.int_items, d.title
					',
	'rename-tbl' => 'ALTER TABLE `%s` RENAME `%s`',
	'del-by-id' => 'DELETE FROM `%s` WHERE id = %d',
	'del-by-dict_id' => 'DELETE FROM `%s` WHERE dict_id = "%d"',
	'del-term_id' => 'DELETE FROM `%s` WHERE id = "%s"',
	'del-term_id-dict_d' => 'DELETE FROM `%s` WHERE term_id = "%s" AND dict_id = "%d"',
	'del-term_id-id_d' => 'DELETE FROM `%s` WHERE term_id = "%s" AND id_d = "%d"',
	'del-srch-by-dict' => 'DELETE FROM `'.$sys['tbl_prefix'].'search_results` WHERE id_d = "%d"',
	'del-table' => 'DELETE FROM `%s`',
	'del-wordmap-by-dict' => 'DELETE FROM `'.TBL_WORDMAP.'` WHERE dict_id = "%d"',
	'drop-table' => 'DROP TABLE IF EXISTS `%s`',
	'create-dict' => "CREATE TABLE `%s` (
						`id` int(10) unsigned NOT NULL auto_increment,
						`id_user` int(10) unsigned NOT NULL default '2',
						`is_active` tinyint(1) unsigned NOT NULL default '1',
						`is_complete` tinyint(1) unsigned NOT NULL default '1',
						`date_modified` int(10) unsigned NOT NULL default '0',
						`date_created` int(10) unsigned NOT NULL default '0',
						`int_bytes` mediumint(8) unsigned NOT NULL default '0',
						`crc32u` int(10) NOT NULL default '0',
						`term_a` int(9) unsigned NOT NULL default '0',
						`term_b` int(9) unsigned NOT NULL default '0',
						`term_c` int(9) unsigned NOT NULL default '0',
						`term_d` int(9) unsigned NOT NULL default '0',
						`term_e` int(9) unsigned NOT NULL default '0',
						`term_f` int(9) unsigned NOT NULL default '0',
						`term_1` varbinary(16) NOT NULL default '',
						`term_2` varbinary(16) NOT NULL default '',
						`term_3` varbinary(16) NOT NULL default '',
						`term` varbinary(255) NOT NULL default '',
						`term_uri` varchar(255) NOT NULL default '',
						`term_order` tinyblob NOT NULL,
						`defn` mediumblob NOT NULL,
						PRIMARY KEY (`id`),
						KEY `count` (`is_active`,`date_created`),
						KEY `recent` (`is_active`,`date_modified`,`date_created`),
						KEY `term123` (`is_active`,`term_1`,`term_2`,`term_3`,`date_created`)
					 ) ENGINE=MyISAM DEFAULT CHARSET=utf8
					"
);
/* end of file */
?>