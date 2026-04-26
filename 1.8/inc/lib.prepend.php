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
 *  Configuration scheme:
 *  index -> config.inc -> lib.prepend, constants.inc -> custom
 *                         ^^^^^^^^^^^
 */
/* Prefix for constants */
$sys['prefix_c'] = 'GW_';

/* Auto time for server */
date_default_timezone_set('UTC');

/* ------------------------------------------------------- */
/* Load functions */
include_once($sys['path_gwlib'] . '/class.timer.php');
$oTimer = new gw_timer();
include_once($sys['path_include'] . '/class.gwtk.php');
include_once($sys['path_include'] . '/class.rendercells.php');
include_once($sys['path_include'] . '/class.forms.php');
include_once($sys['path_include'] . '/class.gw_htmlforms.php'); /* extends gw_forms */
include_once($sys['path_include'] . '/func.browse.inc.php');
include_once($sys['path_include'] . '/func.catalog.inc.php');
include_once($sys['path_include'] . '/func.crypt.inc.php');
include_once($sys['path_include'] . '/func.shuffle.php');
include_once($sys['path_include'] . '/func.sql.inc.php');
include_once($sys['path_include'] . '/func.srch.inc.php');
include_once($sys['path_include'] . '/func.stat.inc.php');
include_once($sys['path_include'] . '/func.text.inc.php');
include_once($sys['path_include'] . '/constants.inc.php');
include_once($sys['path_gwlib'] . '/class.xslt.php');
include_once($sys['path_gwlib'] . '/class.render.php'); /* extends gw_htmlforms */
/* New from Glossword 2.0 */
include_once($sys['path_gwlib'] . '/class.db.cache.php');
include_once($sys['path_gwlib'] . '/class.db.mysqli.php');
include_once($sys['path_gwlib'] . '/class.db.q.php');
include_once($sys['path_include'] . '/query_storage.php'); /* extends gw_query */
include_once($sys['path_gwlib'] . '/class.domxml.php');
include_once($sys['path_gwlib'] . '/class.headers.php');
include_once($sys['path_gwlib'] . '/class.globals.php');
include_once($sys['path_gwlib'] . '/class.html.php');
include_once($sys['path_gwlib'] . '/class.logwriter.php');
include_once($sys['path_gwlib'] . '/class.case.php');
include_once($sys['path_gwlib'] . '/class.session-1.9.php');
include_once($sys['path_gwlib'] . '/class.ua.php');
include_once($sys['path_gwlib'] . '/class.tpl.php'); /* requires class.ua.php */
#include_once( $sys['path_include'] . '/class.session.ext.php' ); /* extends gw_sessions */
include_once($sys['path_include'] . '/class.template.ext.php'); /* extends gwv_template */
include_once($sys['path_gwlib'] . '/class.route.php');
if (!isset($sys['server_proto'])) {
    $sys['server_proto'] = 'http://';
}
/* HTTP_HOST and GW_REQUEST_URI constants */
if (!isset($sys['server_host'])) {
    $sys['server_host'] = isset($_SERVER["HTTP_HOST"]) && !empty($_SERVER["HTTP_HOST"])
        ? $_SERVER["HTTP_HOST"]
        : (isset($_SERVER["HTTP_HOST"])
            ? $_SERVER["HTTP_HOST"]
            : (getenv('SERVER_NAME') != '') ? getenv('SERVER_NAME')
                : 'localhost');
}
define('HTTP_HOST', $sys['server_host']);
define('GW_REQUEST_URI', isset($_SERVER['REQUEST_URI'])
    ? $_SERVER['REQUEST_URI']
    : ((getenv('REQUEST_URI') != '')
        ? getenv('REQUEST_URI')
        : ((isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') ? ($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'])
            : $_ENV['PHP_SELF'])));
if (!isset($sys['server_dir'])) {
    $sys['server_dir'] = dirname(GW_REQUEST_URI);
    if (preg_match("/\/$/", GW_REQUEST_URI)) {
        $sys['server_dir'] = dirname(GW_REQUEST_URI . 'index.php');
    }
    /* allow to login when the script is not installed */
    if (preg_match("/\/gw_admin/", $sys['server_dir'])) {
        $sys['server_dir'] = str_replace('/gw_admin', '', $sys['server_dir']);
    }
}

if (!isset($sys['server_url'])) {
    $sys['server_url'] = $sys['server_proto'] . $sys['server_host'] . $sys['server_dir'];
}

$_SERVER['HTTP_ACCEPT_ENCODING'] = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : (isset($_SERVER['HTTP_TE']) ? $_SERVER['HTTP_TE'] : '');

/* Get PHP version */
$tmp['arPhpVer'] = explode('.', PHP_VERSION);

define('PHP_VERSION_INT', intval(sprintf('%d%02d%02d', $tmp['arPhpVer'][0], $tmp['arPhpVer'][1], $tmp['arPhpVer'][2])));

/* ------------------------------------------------------- */
define('REMOTE_IP', gw_get_remote_ip());
$HTTP_REF = getenv('HTTP_REFERER');

/* ------------------------------------------------------- */
/* Specific technical purposes (error level, banners, counters etc.) */
$arLocalIp = ['192.168.', '172.', '127.', '10.'];
$sys['is_show_stat'] = 1;
foreach ($arLocalIp as $k => $v) {
    if (preg_match("/^" . $v . "/", HTTP_HOST)) {
        define('IS_MYHOST', 0); /* 05 oct 2006: turted off by default*/
        $sys['is_show_stat'] = 0;
        break;
    }
}
if (!defined('IS_MYHOST')) {
    define('IS_MYHOST', 0);
}
/* ------------------------------------------------------- */
define('CRLF', "\r\n");
define('LF', "\\n\\");
/* ------------------------------------------------------- */
/* Does upload allowed? */
$sys['is_upload'] = (function_exists('ini_get'))
    ? ((strtolower(ini_get('file_uploads')) == 'on' || ini_get('file_uploads') == 1) && intval(ini_get('upload_max_filesize')))
    : 0;
/* Maximum upload size */
$sys['max_upload_size'] = ini_get('upload_max_filesize');
if (strpos($sys['max_upload_size'], 'M') !== false) {
    /* Megabytes */
    $sys['max_upload_size'] = intval($sys['max_upload_size']);
} elseif (strpos($sys['max_upload_size'], 'G') !== false) {
    /* Gigabytes */
    $sys['max_upload_size'] = intval($sys['max_upload_size']) * 1024;
}
/* ------------------------------------------------------- */
/* Custom configuration. Must be always at the end */
if (@file_exists($sys['path_include_local'] . '/gw_config.php')) {
    include($sys['path_include_local'] . '/gw_config.php');
}

/* ------------------------------------------------------- */
/* Dynamic path names */
$sys['path_img_full'] = $sys['server_proto'] . $sys['server_host'] . $sys['server_dir'] . '/' . $sys['path_img'];
$sys['path_img_ph'] = $sys['path_img'];
$sys['path_img'] = $sys['server_dir'] . '/' . $sys['path_img'];
$sys['page_index'] = $sys['server_dir'] . '/index.php';

/* ------------------------------------------------------- */
/* Database class */
$oDb = new gwtkDb;
if ($sys['is_cache_sql']) {
    $oDb->cache_lifetime = $sys['cache_lifetime'];
    $oDb->setCache($sys['path_cache_sql']);
}
/* ------------------------------------------------------- */
$tmp['time_php_init'] = $oTimer->end();
$oTimer = new gw_timer;
$oUrlBuilder = new gwUrlBuilder($sys);

