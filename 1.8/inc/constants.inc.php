<?php

/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2026 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW')) {
    die('<!-- Not in App -->');
}

/**
 * Page numbers, URL parameters, navigation map and dictionary fields.
 *
 * Configuration scheme:
 * index -> config.inc -> lib.prepend, constants.inc -> custom.inc
 *                                      ^^^^^^^^^^^^^
 */

/*
 * Keep define() here for backward compatibility.
 * These values may already be defined in local config.
 */
if (!defined('GW_DB_HOST')) {
    define('GW_DB_HOST', 'localhost');
}

if (!defined('GW_DB_DATABASE')) {
    define('GW_DB_DATABASE', 'glossword1');
}

if (!defined('GW_DB_USER')) {
    define('GW_DB_USER', 'root');
}

if (!defined('GW_DB_PASSWORD')) {
    define('GW_DB_PASSWORD', 'root');
}

if (!isset($sys) || !is_array($sys)) {
    $sys = [];
}

if (!isset($sys['tbl_prefix'])) {
    $sys['tbl_prefix'] = '';
}

// --------------------------------------------------------
// Database table names
/*
 * Keep define() for table names because tbl_prefix is runtime data.
 */
define('TBL_MAP_USER_TERM', $sys['tbl_prefix'] . 'map_user_to_term');
define('TBL_WORDLIST', $sys['tbl_prefix'] . 'wordlist');
define('TBL_WORDMAP', $sys['tbl_prefix'] . 'wordmap');

// --------------------------------------------------------
// Critical system constants. DO NOT EDIT.
// --------------------------------------------------------

// Action URL parameters
const GW_SID     = 'sid';
const GW_ID_DICT = 'd';

/* '?index.php?a=[...]&t=topics' calls file '[...]_topic.at.php' */
const GW_ACTION        = 'a';
const GW_A_BROWSE      = 'browse';
const GW_A_CONFIG      = 'cfg';
const GW_A_CLEAN       = 'clean';
const GW_A_EXPORT      = 'export';
const GW_A_ADD         = 'add';
const GW_A_EDIT        = 'edit';
const GW_A_LIST        = 'list';
const GW_A_MAINTENANCE = 'maintenance';
const GW_A_PRINT       = 'print';
const GW_A_REMOVE      = 'remove';
const GW_A_SEARCH      = 'srch';
const GW_A_IMPORT      = 'import';
const GW_A_PROFILE     = 'profile';
const GW_A_REGISTER    = 'register';
const GW_A_UPDATE      = 'update';
const GW_A_CONTENTS    = 'contents';

// --------------------------------------------------------
// Target URL parameters
// '?index.php?a=import&t=[...]' calls file 'import_[...].at.php'
const GW_TARGET             = 't';
const GW_TARGET_ID          = 'tid';
const GW_T_TOPIC            = 'topic';
const GW_T_TOPICS           = 'topics';
const GW_T_DICT             = 'dict';
const GW_T_DICTS            = 'dicts';
const GW_T_TERM             = 'term';
const GW_T_TERMS            = 'terms';
const GW_T_THEME            = 'visual-themes';
const GW_T_SYSTEM           = 'settings';
const GW_T_USERS            = 'users';
const GW_T_CUSTOMPAGE       = 'custom-pages';
const GW_T_CUSTOM_AZ        = 'custom-az';
const GW_T_VIRTUAL_KEYBOARD = 'virtual-keyboards';
const GW_T_ABBR             = 'abbr';
const GW_T_MENUMANAGER      = 'menumanager';

// --------------------------------------------------------
const GW_LANG_I = 'il';
const GW_LANG_C = 'cl';

// --------------------------------------------------------
// Switches
const GW_AFTER_DICT_UPDATE      = 1;
const GW_AFTER_TERM_ADD         = 2;
const GW_AFTER_SRCH_BACK        = 3;
const GW_AFTER_TERM_GW_A_IMPORT = 4;

const GW_TPL_TITLE       = 3;
const GW_TPL_DICT        = 4;
const GW_TPL_TERM_LIST   = 5;
const GW_TPL_SEARCH      = 6;
const GW_TPL_TERM        = 7;
const GW_TPL_TERM_PRINT  = 8;
const GW_TPL_CUSTOM_PAGE = 9;
const GW_TPL_PROFILE     = 10;
const GW_TPL_LOGIN       = 11;
const GW_TPL_ADMIN       = 12;
const GW_TPL_SEARCH_ADM  = 14;
const GW_TPL_MAIL        = 15;
const GW_TPL_CONTENTS    = 16;

// --------------------------------------------------------
// Dictionary fields:
// - 1 Field name (latin characters only)
// - 2 Type [ textarea | input | file ]
// - 3 Search index length [ 0 - off | 1..9 | auto ]
// - 4 Is multiple records (possible to split each item with a new line)
// - 5 Is root element (<line><term>..<audio>..<defn>..</line>)
// - 6 Column order for CSV export/import
// "-5" is used for `term_uri` in CSV.
// Array keys cannot be changed, i.e. 2 is always transcription.
//
// Keep legacy variable names for backward compatibility.
$arFields = [
    -1 => ['id', 'input', 'auto', 0, 0, 0],
    -2 => ['t1', 'input', 'auto', 0, 0, 2],
    -3 => ['t2', 'input', 'auto', 0, 0, 3],
    -4 => ['t3', 'input', 'auto', 0, 0, 4],
    -5 => ['uri', 'input', 'auto', 0, 0, 5],
    1  => ['term', 'input', 'auto', 0, 1, 1],
    2  => ['trsp', 'textarea', 'auto', 1, 0, 6],
    3  => ['abbr', '', 'auto', '', 0, 8],
    4  => ['trns', '', 'auto', '', 0, 9],
    0  => ['defn', 'textarea', 'auto', 1, 1, 7],
    5  => ['usg', 'textarea', 'auto', 1, 0, 10],
    9  => ['address', 'textarea', 'auto', 0, 0, 15],
    10 => ['phone', 'textarea', 'auto', 0, 0, 16],
    7  => ['syn', 'textarea', 'auto', 1, 0, 12],
    11 => ['antonym', 'textarea', 'auto', 1, 0, 13],
    8  => ['see', 'textarea', 'auto', 1, 0, 11],
    6  => ['src', 'textarea', 1, 0, 0, 14],
];

$intFields = count($arFields);