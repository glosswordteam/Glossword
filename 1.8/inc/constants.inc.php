<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *   2008 Glossword.biz team
 *   2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: constants.inc.php 84 2007-06-19 13:01:21Z yrtimd $ -->');
}
/**
 *  page numbers, url parameters, navigation map, dictionary fields
 *   
 *  Configuration scheme:
 *  index -> config.inc -> lib.prepend, constants.inc -> custom.inc
 *                                      ^^^^^^^^^^^^^
 */
if (!defined('GW_DB_HOST')){ define('GW_DB_HOST', 'localhost'); }
if (!defined('GW_DB_DATABASE')){ define('GW_DB_DATABASE',  'glossword1'); }
if (!defined('GW_DB_USER')){ define('GW_DB_USER',  'root'); }
if (!defined('GW_DB_PASSWORD')){ define('GW_DB_PASSWORD',  'root'); }
if (!isset($sys['tbl_prefix'])){ $sys['tbl_prefix'] = ''; }
// --------------------------------------------------------
// Database table names
define('TBL_AUTH',          $sys['tbl_prefix'] . 'auth');
define('TBL_DICT',          $sys['tbl_prefix'] . 'dict');
define('TBL_STAT_DICT',     $sys['tbl_prefix'] . 'stat_dict');
define('TBL_SETTINGS',      $sys['tbl_prefix'] . 'settings');
define('TBL_SESS',          $sys['tbl_prefix'] . 'sessions');
define('TBL_USERS',         $sys['tbl_prefix'] . 'users');
define('TBL_MAP_USER_DICT', $sys['tbl_prefix'] . 'map_user_to_dict');
define('TBL_MAP_USER_TERM', $sys['tbl_prefix'] . 'map_user_to_term');
define('TBL_WORDLIST',      $sys['tbl_prefix'] . 'wordlist');
define('TBL_WORDMAP',       $sys['tbl_prefix'] . 'wordmap');
define('TBL_SRCH_RESULTS',  $sys['tbl_prefix'] . 'search_results');
define('TBL_CUSTOMPAGES',   $sys['tbl_prefix'] . 'pages');
define('TBL_CUSTOMPAGES_PH',$sys['tbl_prefix'] . 'pages_phrase');
define('TBL_SUBSCR_DICT',   $sys['tbl_prefix'] . 'subscribe_map_user_to_dict');
define('TBL_COMPONENT',     $sys['tbl_prefix'] . 'component');
define('TBL_COMPONENT_MENU',$sys['tbl_prefix'] . 'component_menu');
// --------------------------------------------------------
// Critical system constants. DO NOT EDIT.
// --------------------------------------------------------
// Action URL parameters
define('GW_SID',          'sid');
define('GW_ID_DICT',      'd');
// '?index.php?a=[...]&t=topics' calls file '[...]_topic.at.php'
define('GW_ACTION',       'a');
define('GW_A_BROWSE',     'browse');
define('GW_A_CONFIG',     'cfg');
define('GW_A_CLEAN',      'clean');
define('GW_A_EXPORT',     'export');
define('GW_A_ADD',        'add');
define('GW_A_EDIT',       'edit');
define('GW_A_LIST',       'list');
define('GW_A_MAINTENANCE','maintenance');
define('GW_A_PRINT',      'print');
define('GW_A_REMOVE',     'remove');
define('GW_A_SEARCH',     'srch');
define('GW_A_IMPORT',     'import');
define('GW_A_PROFILE',    'profile');
define('GW_A_REGISTER',   'register');
define('GW_A_UPDATE',     'update');
define('GW_A_CONTENTS',   'contents');
// --------------------------------------------------------
// Target URL parameters
// '?index.php?a=import&t=[...]' calls file 'import_[...].at.php'
define('GW_TARGET',       't');
define('GW_T_TOPIC',      'topic'); /* below 1.8.7 */
define('GW_T_TOPICS',     'topics');
define('GW_T_DICT',       'dict'); /* below 1.8.7 */
define('GW_T_DICTS',      'dicts');
define('GW_T_TERM',       'term'); /* below 1.8.7 */
define('GW_T_TERMS',      'terms');
define('GW_T_THEME',      'visual-themes');
define('GW_T_SYSTEM',     'settings');
define('GW_T_USERS',      'users');
define('GW_T_CUSTOMPAGE', 'custom-pages');
define('GW_T_CUSTOM_AZ',  'custom-az');
// --------------------------------------------------------
define('GW_LANG_I', 'il');
define('GW_LANG_C', 'cl');
// --------------------------------------------------------
/* -------------------------------------------------------- */
/* Switches */
define('GW_AFTER_DICT_UPDATE', 1);
define('GW_AFTER_TERM_ADD', 2);
define('GW_AFTER_SRCH_BACK', 3);
define('GW_AFTER_TERM_GW_A_IMPORT', 4);

define('GW_TPL_TITLE', 3);
define('GW_TPL_DICT', 4);
define('GW_TPL_TERM_LIST', 5);
define('GW_TPL_SEARCH', 6);
define('GW_TPL_TERM', 7);
define('GW_TPL_TERM_PRINT', 8);
define('GW_TPL_CUSTOM_PAGE', 9);
define('GW_TPL_PROFILE', 10);
define('GW_TPL_LOGIN', 11);
define('GW_TPL_ADMIN', 12);
define('GW_TPL_SEARCH_ADM', 14);
define('GW_TPL_MAIL', 15);
define('GW_TPL_CONTENTS', 16);

// --------------------------------------------------------
// Dictionary fields:
// - 1 Field name (latin characters only)
// - 2 Type [ textarea | input | file ]
// - 3 Search index length [ 0 - off | 1..9 | auto ]
// - 4 Is multiple records (possible to split each item with a new line)
// - 5 Is root element (<line><term>..<audio>..<defn>..</line>)
// - 6 column order for CSV-export/import
//  "-5" is used for `term_uri` in CSV.
// Array keys cannot be changed, i.e. 2 is always transcription.
$arFields = array(
				-1  => array('id',     'input',    'auto', 0,  0, 0),
				-2  => array('t1',     'input',    'auto', 0,  0, 2),
				-3  => array('t2',     'input',    'auto', 0,  0, 3),
				-4  => array('t3',     'input',    'auto', 0,  0, 4),
				-5  => array('uri',    'input',    'auto', 0,  0, 5),
				1  => array('term',    'input',    'auto', 0,  1, 1),
				2  => array('trsp',    'textarea', 'auto', 1,  0, 6),
				3  => array('abbr',    '',         'auto', '', 0, 8),
				4  => array('trns',    '',         'auto', '', 0, 9),
				0  => array('defn',    'textarea', 'auto', 1,  1, 7),
				5  => array('usg',     'textarea', 'auto', 1,  0, 10),
				9  => array('address', 'textarea', 'auto', 0,  0, 15),
				10 => array('phone',   'textarea', 'auto', 0,  0, 16),
				7  => array('syn',     'textarea', 'auto', 1,  0, 12),
				11 => array('antonym', 'textarea', 'auto', 1,  0, 13),
				8  => array('see',     'textarea', 'auto', 1,  0, 11),
				6  => array('src',     'textarea', 1,      0,  0, 14),
		);
$intFields = count($arFields);

/* end of file */
?>