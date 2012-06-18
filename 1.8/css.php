<?php
/**
 *  $Id$
 */
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  © 2002-2007 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
define('IN_GW', 1);
/* Load config */
include_once('db_config.php');
$sys['id_prepend'] = 0;
include_once($sys['path_include_local'].'/config.inc.php');

define('GW_REQUEST_URI', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI']
					: ((getenv('REQUEST_URI') != '') ? getenv('REQUEST_URI')
					: ((isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') ? ($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'])
					: $_ENV['PHP_SELF'])));

/* When Glossword is not installed */
if (!isset($sys['server_dir']))
{
	$sys['server_dir'] = dirname(GW_REQUEST_URI);
	if (preg_match("/\/$/", GW_REQUEST_URI))
	{
		$sys['server_dir'] = dirname(GW_REQUEST_URI.'index.php');
	}
	/* allow to login when the script is not installed */
	if (preg_match("/\/gw_admin/", $sys['server_dir']))
	{	
		$sys['server_dir'] = str_replace('/gw_admin', '', $sys['server_dir']);
	}
}
if (!isset($sys['server_proto']))
{
	$sys['server_proto'] = 'http://';
}
/* HTTP_HOST and GW_REQUEST_URI constants */
if (!isset($sys['server_host']))
{
	$sys['server_host'] = isset($_SERVER["HTTP_HOST"])&&!empty($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"]
					: (isset($HTTP_SERVER_VARS["HTTP_HOST"]) ? $HTTP_SERVER_VARS["HTTP_HOST"]
					: (getenv('SERVER_NAME') != '') ? getenv('SERVER_NAME')
					: 'localhost');
}
/* Constants */
define('CRLF', "\r\n");
define('LF', "\\n\\");
define('REMOTE_UA', trim(substr(getenv('HTTP_USER_AGENT'), 0, 255)));

define('TBL_WORDLIST',      $sys['tbl_prefix'] . 'wordlist');
define('TBL_WORDMAP',       $sys['tbl_prefix'] . 'wordmap');

include_once( $sys['path_gwlib']. '/class.timer.php' );
include_once( $sys['path_gwlib']. '/class.db.cache.php' );
include_once( $sys['path_gwlib']. '/class.db.mysql.php');
include_once( $sys['path_gwlib']. '/class.db.q.php' );
include_once( $sys['path_include'] . '/query_storage.php' ); /* extends gw_query */
include_once( $sys['path_gwlib']. '/class.globals.php');
include_once( $sys['path_gwlib']. '/class.func.php');
include_once( $sys['path_gwlib']. '/class.headers.php');

$oDb = new gwtkDb;
$oSqlQ = new $sys['class_queries'];
$oSqlQ->set_suffix('-'.$sys['db_type'].'410');

$oTimer = new gw_timer;

/* Append system settings */
$sys = array_merge($sys, getSettings());
/* Auto time for server  */
$sys['time_now'] = time();
$is_dst = intval(date('I', $sys['time_now']) - date('I'));
$sys['server_gmt_offset'] = $is_dst ? date('Z') : ( date('Z') / 3600) - 1;
$sys['time_now_gmt_unix'] = $sys['time_now'] - ($sys['server_gmt_offset'] * 3600);

$_SERVER['HTTP_ACCEPT_ENCODING'] = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : (isset($_SERVER['HTTP_TE']) ? $_SERVER['HTTP_TE'] : '');

$tmp['arPhpVer'] = explode('.', PHP_VERSION);
define('PHP_VERSION_INT', intval(sprintf('%d%02d%02d', $tmp['arPhpVer'][0], $tmp['arPhpVer'][1], $tmp['arPhpVer'][2])));
/* --------------------------------------------------------
 * Additional functions
 * ----------------------------------------------------- */
function gw_text_tpl_compile($t = '', $ar = array())
{
	$arCmd = array();
	/* Search for template tags */
	$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
	if (preg_match_all($preg, $t, $tmp['tpl_matches']))
	{
		while (list($k, $cmd_src) = each($tmp['tpl_matches'][2]))
		{
			$arCmd[] = $tmp['tpl_matches'][1][$k].$cmd_src.$tmp['tpl_matches'][3][$k];
			$tmp['cmd'] = trim($cmd_src);
			$tmp['cmd'] = isset($ar[$tmp['cmd']]) ? $ar[$tmp['cmd']] : '';
			$arRpl[] = $tmp['cmd'];
		}
		/* replaces variables only */
		$t = str_replace($arCmd, $arRpl, $t);
	}
	return $t;
}
function gw_get_theme($theme_name)
{
	global $sys, $oDb, $oSqlQ;
	$ar_theme = array();
	$arSql = $oDb->sqlRun($oSqlQ->getQ('get-theme', gw_text_sql($theme_name), '1'), 'theme');
	if (empty($arSql))
	{
		/* custom theme is not found, load default theme */
		$theme_name = $sys['path_theme'] = $sys['visualtheme'];
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-theme', gw_text_sql($theme_name), '1'), 'theme');
		if (empty($arSql))
		{
			die('Unable to load visual theme `' . $theme_name.'` from table `'.$sys['tbl_prefix'].'themes`. Check database settings or re-install the software.');
		}
	}
	else
	{
		$sys['path_theme'] = $theme_name;
	}
	for (; list($kV, $arV) = each($arSql);)
	{
		$ar_theme[$arV['settings_key']] = $arV['settings_value'];
		unset($arSql[$kV]);
	}
	return $ar_theme;
}
function getSettings()
{
	global $oSqlQ, $oDb, $oFunc, $sys;
	$strA = array();
	$arSql = $oDb->sqlRun($oSqlQ->getQ('get-settings'), 'st');
	for (; list($k, $v) = each($arSql);)
	{
		$strA[$v['settings_key']] = $v['settings_val'];
	}
	/* */
#	$strA['int_time_server'] = $oFunc->date_get_localtime($strA['gmt_offset'], intval(date('I')) );
	/* No system settings found, run install */
	return $strA;
}
/* --------------------------------------------------------
 * Register global variables
 * ----------------------------------------------------- */
$gv['vars'] = $oGlobals->register(array('t','is_host','is_gzip', 'dir'));
$gv['vars']['t'] = preg_replace("/[^a-zA-Z0-9_\.-]/", '', $gv['vars']['t']);
$oGlobals->do_default($gv['vars']['is_host'], 1);
$oGlobals->do_default($gv['vars']['is_gzip'], $sys['is_use_gzip']);
/* Character `-' is not valid for CSS-style. */
$gv['vars']['t'] = str_replace('-', '_', $gv['vars']['t']);
/* --------------------------------------------------------
 * Variables used in CSS style
 * ----------------------------------------------------- */
$arPairsV = array(
		'v:path_css' => $sys['server_dir'].'/'.$sys['path_temporary'].'/t/'.$gv['vars']['t'],
		'v:path_img' => $sys['server_dir'].'/'.$sys['path_img'],
		'v:visualtheme' => str_replace('_', '-', $gv['vars']['t'])
);
$sys['v:css_align_right'] = 'right';
$sys['v:css_align_left'] = 'left';
if ($gv['vars']['dir'] == 'rtl')
{
	$sys['v:css_align_right'] = 'left';
	$sys['v:css_align_left'] = 'right';
}
$sys['v:path_img_full'] = $sys['server_proto'] . $sys['server_host'] .'/'. $sys['server_dir'] .'/'. $sys['path_img'];
$sys['v:path_img_ph'] = $sys['path_img'];
$sys['v:path_img'] = $sys['server_dir'] .'/'. $sys['path_img'];

$arPairsV = array_merge($arPairsV, gw_get_theme($gv['vars']['t']) );
$arPairsV = array_merge($arPairsV, $sys );

/* --------------------------------------------------------
 * Parse CSS-file contents
 * ----------------------------------------------------- */
$gv['vars']['t'] = $sys['path_temporary'].'/t/'.$gv['vars']['t'].'/style.css';
$gv['vars']['css_contents'] = '';
$sys['date_modified_u'] = time();

if (file_exists($gv['vars']['t']))
{
	$sys['date_modified_u'] = filectime($gv['vars']['t']);
	$gv['vars']['css_contents'] = $oFunc->file_get_contents($sys['path_tpl'].'/common/gw_tags.css');
	$gv['vars']['css_contents'] .= $oFunc->file_get_contents($gv['vars']['t']);
	$gv['vars']['css_contents'] = gw_text_tpl_compile($gv['vars']['css_contents'], $arPairsV);
	$gv['vars']['css_contents'] = $oFunc->text_smooth_css($gv['vars']['css_contents'], 0);
}
/* --------------------------------------------------------
 * Send Expires
 * ----------------------------------------------------- */
if (!$gv['vars']['is_host'])
{
	/* enable caching for non-localhost */
	$oHdr->add("Expires: " . gmdate("D, d M Y H:i:s", ($sys['date_modified_u'] + 43200)) . " GMT");
	$oHdr->add("Last-Modified: " . gmdate("D, d M Y H:i:s", $sys['date_modified_u']) . " GMT");
}
$oHdr->add('Content-Type: text/css; charset=UTF-8');
/* --------------------------------------------------------
 * GZip compression
 * ----------------------------------------------------- */
if ($gv['vars']['is_gzip'])
{
	/* requires $oHdr */
	$gv['vars']['css_contents'] = $oFunc->text_gzip($gv['vars']['css_contents'], $sys['gzip_level']);
}
$oHdr->output();
#prn_r( $oTimer->end(), __LINE__ );
print $gv['vars']['css_contents'];
/* end of file */
?>