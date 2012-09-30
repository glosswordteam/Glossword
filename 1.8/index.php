<?php
/**
 *  $Id: index.php 543 2008-07-24 06:05:47Z glossword_team $
 */
/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2012 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/* ------------------------------------------------------- */
/**
 *  Website
 */
/* ------------------------------------------------------- */
if (!defined('IN_GW'))
{
	define('IN_GW', true);
}
define('THIS_SCRIPT', 'index.php');
define('GW_IS_BROWSE_WEB',   1);
define('GW_IS_BROWSE_ADMIN', 0);
/* ------------------------------------------------------- */
/* Load configuration */
$sys['is_prepend'] = 1;
include_once('db_config.php');
include_once($sys['path_include_local'] . '/config.inc.php');
/* --------------------------------------------------------
 * Database -> Query storage
 * ----------------------------------------------------- */
$oSqlQ = new $sys['class_queries'];
$oSqlQ->set_suffix('-'.$sys['db_type'].'410');
/* ------------------------------------------------------- */
/* Append system settings */
$sys = array_merge($sys, getSettings());
/* Fill empty settings */
$sys['visualtheme'] = isset($sys['visualtheme']) ? $sys['visualtheme'] : 'gw_brand';
/* Auto time for server  */
$sys['time_now'] = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
$sys['time_now_gmt_unix'] = $sys['time_now'] - @date('Z');
$sys['time_now_db'] = ($sys['time_now_gmt_unix'] - date("s", $sys['time_now_gmt_unix']));
$sys['time_now_db_cache'] = $sys['time_now_db'] - (date("i", $sys['time_now_db']) * 60);
/* 1.8.7: Technical support */
if (isset($_GET['server_']) && $sys['is_allow_tech_support'])
{
	header('Content-Type: text/plain');
	print "\n".'server_proto   : '.$sys['server_proto'];
	print "\n".'server_host    : '.$sys['server_host'];
	print "\n".'server_dir     : '.$sys['server_dir'];
	print "\n".'server_url     : '.$sys['server_url'];
	print "\n";
	print "\n".'GW_REQUEST_URI : '.GW_REQUEST_URI;
	print "\n".'PHP_VERSION_INT: '.PHP_VERSION;
	print "\n".'SERVER_SOFTWARE: '.getenv('SERVER_SOFTWARE');
	print "\n".'API            : '.PHP_SAPI;
	print "\n";
	print "\n".'path_img_full  : '.$sys['path_img_full'];
	print "\n".'path_img_ph    : '.$sys['path_img_ph'];
	print "\n".'path_img       : '.$sys['path_img'];
	print "\n".'page_index     : '.$sys['page_index'];
	print "\n";
	print "\n".'time_now          : '.@date("Y-M-d H:i:s", $sys['time_now']);
	print "\n".'time_now_gmt_unix : '.@date("Y-M-d H:i:s", $sys['time_now_gmt_unix']);
	print "\n".'time_now_db       : '.@date("Y-M-d H:i:s", $sys['time_now_db']);
	print "\n".'time_now_db_cache : '.@date("Y-M-d H:i:s", $sys['time_now_db_cache']);
	exit;
}
/* --------------------------------------------------------
 * mod_rewrite configuration
 * ------------------------------------------------------- */
$oHtml = new gw_html;
$oHtml->setVar('ar_url_append', $sys['ar_url_append']);
$oHtml->setVar('is_htmlspecialchars', 0);
$oHtml->setVar('is_mod_rewrite', $sys['is_mod_rewrite']);
#$oHtml->setVar('is_mod_rewrite', 0);
$oHtml->setVar('mod_rewrite_suffix', $sys['mod_rewrite_suffix']);
$oHtml->setVar('mod_rewrite_index', $sys['mod_rewrite_index']);
$oHtml->setVar('server_dir', $sys['page_index']);
$oHtml->setVar('mod_rewrite_rule',
	array(
		'a='.GW_A_LIST   => '/a/d/p,w1,w2,w3',
		'a='.GW_A_PRINT  => '/a/q,t,d',
		'a=index'        => '/a/d/',
		'a=contents'     => '/a/d/',
		'a='.GW_A_SEARCH => '',
		'a='.GW_T_TERM   => '/a/r/d,t',
		'a='.GW_A_CUSTOMPAGE => '/a/id,uid,t,d',
		'a='.GW_A_PROFILE => '/a/t/id',
		'/a/t/id_topic,p,id,uid')
);
/* can't use dirname(GW_REQUEST_URI) */
$sys['GW_REQUEST_URI'] = str_replace($sys['page_index'], '', GW_REQUEST_URI); /* index.php?a= */
if ($sys['server_dir'] != '')
{
	$sys['GW_REQUEST_URI'] = str_replace($sys['server_dir'].'/', '', $sys['GW_REQUEST_URI']); /* /?a= */
}
/* */
if ($sys['is_mod_rewrite']
	&& !preg_match("/^\?/", $sys['GW_REQUEST_URI'])
	&& !preg_match('/a='.GW_A_SEARCH.'/', $sys['GW_REQUEST_URI'])
	)
{
	parse_str( $oHtml->url_dir2str($sys['GW_REQUEST_URI']), $_GET);
}
$gw_str['javascripts'] = '';
#prn_r(  $_GET );
#exit;
/* --------------------------------------------------------
 * Register global variables
 * ----------------------------------------------------- */
$gw_this['vars'] = $oGlobals->register(array(
	GW_ACTION,GW_ID_DICT,'id_dict','p','r',GW_TARGET,'q','u','w1','w2','w3','w','in','ie','is',
	GW_A_SEARCH,'arPost','strict','visualtheme','uid','uri',
	'id_srch','post','id','id_topic','name','email','url','is_gzip',
	GW_LANG_I, GW_LANG_C, GW_SID,
	'gw_visualtheme', 'gw_is_save_visualtheme', 'gw_'.GW_LANG_I, 'gw_is_save_'.GW_LANG_I,
));
/* Move cookies */
$gw_this['cookie'] = array();
if (isset($gw_this['vars']['_cookie']))
{
	$gw_this['cookie'] = $gw_this['vars']['_cookie'];
	unset($gw_this['vars']['_cookie']);
}

$oGlobals->do_default($gw_this['vars']['is_gzip'], $sys['is_use_gzip']);
$oGlobals->do_default($gw_this['vars'][GW_LANG_C], $gw_this['vars'][GW_LANG_I]);
$oGlobals->do_default($gw_this['vars']['lang_enc'], $sys['locale_name']);
$oGlobals->do_default($gw_this['vars']['p'], 1);
$oGlobals->do_default($gw_this['vars']['d'], 0);
$oGlobals->do_default($gw_this['vars']['uri'], '');
/* used for Session class */
$sys['uri'] =& $gw_this['vars']['uri'];

$gw_this['vars']['id'] = urldecode($gw_this['vars']['id']);

/* Depreciated method */
for (reset($gw_this['vars']); list($k1, $v1) = each($gw_this['vars']);)
{
	$$k1 = $v1;
}
/* Fix for #118 */
if ( isset ( $t ) )
{
	unset( $t );
}
$gw_this['vars']['class_render'] = 'gw_render';
/* switch between `/index.php?a=action' and `/action/' (Apache mod_rewrite) */
if ($sys['is_mod_rewrite'])
{
	if (preg_match("/^\?/", $sys['GW_REQUEST_URI']))
	{
		// redirect to a directory
		if( !preg_match('/a=preferences/', GW_REQUEST_URI)
			&& !preg_match('/a='.GW_A_SEARCH.'/', GW_REQUEST_URI)
			&& !preg_match('/q=/', GW_REQUEST_URI)
			&& !preg_match('/a='.GW_A_CUSTOMPAGE.'/', GW_REQUEST_URI) ) // convert for mod_rewrite
		{
			gwtk_header($sys['server_proto'].$sys['server_host'].$oHtml->url_normalize($sys['GW_REQUEST_URI']), $sys['is_delay_redirect'], __FILE__.__LINE__);
		}
	}
}
unset($arPostVars);
## ---------------------------------------------------------
## printable version
$gw_this['vars']['is_print'] = 0;
if ($gw_this['vars'][GW_ACTION] == GW_A_PRINT)
{
	$gw_this['vars'][GW_ACTION] = GW_T_TERM;
	$gw_this['vars']['is_print'] = 1;
}
##
## ---------------------------------------------------------

/* Start session 
$oSess = new $sys['class_session'];
$oSess->time_now = $sys['time_now'];
$oSess->start();
$oHtml->is_append_sid = 1;
$oHtml->id_sess_name = GW_SID;
*/

/* Get the list of dictionaries */
$gw_this['ar_dict_list'] = getDictArray();
$arDictParam = array('is_leech' => 0, 'id' => 0);

/* Dictionary selected */
if ($gw_this['vars'][GW_ID_DICT])
{
	/* Get dictionary settings */
	$arDictParam = getDictParam($gw_this['vars'][GW_ID_DICT]);
	/* Wrong dictionary ID number found, redirect */
	if (empty($arDictParam) && !$gw_this['vars']['q'])
	{
		gwtk_header($sys['server_proto'].$sys['server_host'].$sys['page_index'], $sys['is_delay_redirect'], __FILE__.__LINE__);
	}
}
/* Encrypted Term ID selected */
if ($gw_this['vars'][GW_TARGET] 
	&& !$gw_this['vars'][GW_ID_DICT] 
	&& ($gw_this['vars'][GW_ACTION] == 'term'))
{
	$u = $gw_this['vars'][GW_TARGET];
	$udecoded = url_decrypt($sys['is_hideurl'], $u);
	if (!preg_match("/&d=/", $udecoded)){ $udecoded = url_decrypt(2, $u); }
	if (!preg_match("/&d=/", $udecoded)){ $udecoded = url_decrypt(1, $u); }
	if (!preg_match("/&d=/", $udecoded)){ $udecoded = url_decrypt(0, $u); }
	$u = $udecoded;
	if ($u != '')
	{
		parse_str($udecoded, $ar_u);
		$gw_this['vars'][GW_ID_DICT] = $ar_u['d'];
		$gw_this['vars']['t'] = $ar_u['t'];
		$arDictParam = getDictParam($gw_this['vars'][GW_ID_DICT]);
	}
	else
	{
		$gw_this['vars']['layout'] = 'catalog';
	}
}

/* Not installed */
if (!isset($sys['version']))
{
	if (file_exists('gw_install/index.php'))
	{
		gwtk_header($sys['server_proto'].$sys['server_host'].$sys['server_dir'].'/gw_install/index.php', 1);
	}
	$sys['y_email'] = $sys['y_name'] = $sys['site_name'] = $sys['site_desc'] = '';
	$sys['path_img'] = 'img';
	$sys['is_hideurl'] = $sys['time_new'] = $sys['time_upd'] = 1;
	$sys['version'] = 'not installed';
	$sys['locale_name'] = 'en-utf8';
}
$gw_this['vars']['layout'] = ( !empty($gw_this['vars'][GW_ACTION]) ) ? $gw_this['vars'][GW_ACTION] : 'title';
// --------------------------------------------------------
// Load extensions: 27 aug 2003
// --------------------------------------------------------
$gw_this['vars']['funcnames'][GW_T_TERM] = isset($gw_this['vars']['funcnames'][GW_T_TERM]) ? $gw_this['vars']['funcnames'][GW_T_TERM] : array();
$gw_this['vars']['funcnames'][GW_T_DICT] = isset($gw_this['vars']['funcnames'][GW_T_DICT]) ? $gw_this['vars']['funcnames'][GW_T_DICT] : array();

// --------------------------------------------------------
// Load visual theme, last modified: 14 feb 2006
// --------------------------------------------------------
// Conditions:
// 1) set theme name to $sys['visualtheme'] if no custom theme name defined.
// 2) set theme name to system theme for non-dictionary pages (title, top10, feedback, search).
// 3) set theme name to system theme when searching in all dictionaries.
// 4) set theme name from dictionary settings if a dictionary ID was specified, search page also included.
// 5) set theme name from cookie if cookie exists.

/* -------------------------------------------------------- */
/* Replace main page by dictionary page */
if (!$gw_this['vars'][GW_ID_DICT] && ($gw_this['vars'][GW_ACTION] == ''))
{
	for (reset($gw_this['ar_dict_list']); list($kDict, $vDict) = each($gw_this['ar_dict_list']);)
	{
		$arDictParam = getDictParam($vDict['dict_uri']);
		if (isset($arDictParam['is_dict_as_index']) && $arDictParam['is_dict_as_index'])
		{
			$gw_this['vars'][GW_ID_DICT] = $arDictParam['uri'];
			$gw_this['vars']['layout'] = $gw_this['vars'][GW_ACTION] = GW_A_LIST;
			if (!$gw_this['vars']['visualtheme'])
			{
				/* Change theme name */
				$gw_this['vars']['visualtheme'] = $arDictParam['visualtheme'];
			}
			if (!$gw_this['vars']['visualtheme'])
			{
				/* Roll back to system settings */
				$gw_this['vars']['visualtheme'] = $sys['visualtheme'];
			}
			break;
		}
		else
		{
			unset($arDictParam);
		}
	}
}
/* */
if ( $gw_this['vars'][GW_ID_DICT] ) {
	
	/* 1.8.7: Sorting order */
	$arDictParam['az_sql'] = $arDictParam['az_order'] = '';
	/* 1.8.7: Select custom alphabetic order */
	if ($arDictParam['id_custom_az'] > 1)
	{
		$arSql = $oDb->sqlRun($oSqlQ->getQ('get-custom_az-int', $arDictParam['id_custom_az']) );
		/* A part of SQL-request for listing terms */
		$sql_az = '';
		$ar_az = array();
		for (; list($k, $v) = each($arSql);)
		{
			$ar_az[] = $v['value'];
		}
		if (!empty($ar_az))
		{
			$arDictParam['az_order'] = implode(', ', $ar_az);
			$arDictParam['az_sql'] = ' FIELD(t.term_a, '.$arDictParam['az_order'].
				'), FIELD(t.term_b, '.$arDictParam['az_order'].
					'), FIELD(t.term_c, '.$arDictParam['az_order'].
					'), FIELD(t.term_d, '.$arDictParam['az_order'].
					'), FIELD(t.term_e, '.$arDictParam['az_order'].
					'), FIELD(t.term_f, '.$arDictParam['az_order'].'), ';
		}
	}
}

/* No custom theme defined */
if ($gw_this['vars']['visualtheme'] == '')
{
	$gw_this['vars']['visualtheme'] = $sys['visualtheme'];
	$gw_this['vars']['is']['save_visualtheme'] = 0;
	/* Get theme from a dictionary settings */
	if ($gw_this['vars'][GW_ID_DICT])
	{
		$gw_this['vars']['visualtheme'] = $arDictParam['visualtheme'] ? $arDictParam['visualtheme'] : $sys['visualtheme'];
	}
	/* Cookie overwrites dictionary default theme */
	if (isset($gw_this['cookie']['gw_visualtheme']) && $gw_this['cookie']['gw_visualtheme'] == '')
	{
		$gw_this['cookie']['gw_visualtheme'] = $gw_this['vars']['visualtheme'];
	}
	else if (isset($gw_this['cookie']['gw_visualtheme']))
	{
		$gw_this['vars']['visualtheme'] = $gw_this['cookie']['gw_visualtheme'];
	}
}
else
{
	if (isset($gw_this['vars']['is']['save_visualtheme']) 
		&& $gw_this['vars']['is']['save_visualtheme'])
	{
		/* Save custom theme, set cookie */
		gw_set_cookie('gw_visualtheme', $gw_this['vars']['visualtheme'], 0);
		gw_set_cookie('gw_is_save_visualtheme', 1, 0);
		$gw_this['cookie']['gw_visualtheme'] = $gw_this['vars']['visualtheme'];
		$gw_this['cookie']['gw_is_save_visualtheme'] = 1;
	}
	else if ($gw_this['vars']['srch'] == '')
	{
		gw_set_cookie('gw_visualtheme', '', 0);
		gw_set_cookie('gw_is_save_visualtheme', '', 0);
		$gw_this['cookie']['gw_visualtheme'] = '';
		$gw_this['cookie']['gw_is_save_visualtheme'] = '';
	}
}

$gw_this['vars']['is']['save_visualtheme'] = 
	(isset($gw_this['cookie']['gw_is_save_visualtheme']) && $gw_this['cookie']['gw_is_save_visualtheme'] == 1)
	? 1 : 0;
/* --------------------------------------------------------
 * Template engine
 * ------------------------------------------------------- */
$oTpl = new $sys['class_tpl'];
$oTpl->init($gw_this['vars']['visualtheme']);
$oTpl->is_tpl_show_names = $sys['is_tpl_show_names'];
$arTplVars['dict'] = $arTplVars['srch'] = array();
$oTpl->addVal( 'v:server_url', $sys['server_url'] );
$oTpl->addVal( 'v:server_dir', $sys['server_dir'] );
$oTpl->addVal( 'v:path_img',   $sys['path_img'] );
$oTpl->addVal( 'v:path_css_script', $sys['path_css_script'] );
$oTpl->addVal( 'v:keyboard',   '');
/* 24 jan 2006: load theme setting from database */
$ar_theme = gw_get_theme($gw_this['vars']['visualtheme']);

/* --------------------------------------------------------
 * Set interface language, last modified: 10 mar 2004
 * ----------------------------------------------------- */
/* No custom language defined */
if ($gw_this['vars'][GW_LANG_I] == '')
{
	$gw_this['vars'][GW_LANG_I] = $sys['locale_name'];
	/* Get dictionary language */
	if (($gw_this['vars'][GW_ID_DICT]) && isset($arDictParam['lang']))
	{
		$gw_this['vars'][GW_LANG_I] = ((${GW_ACTION} == GW_A_SEARCH) && !$strict)
									? $gw_this['vars'][GW_LANG_I]
									: ($arDictParam['lang'] ? $arDictParam['lang'] : $sys['locale_name']);
		$gw_this['vars']['lang_enc'] = $arDictParam['lang'] ? $arDictParam['lang'] : $sys['locale_name'];
	}
	/* Cookie overwrites dictionary default language */
	if (isset($gw_this['cookie']['gw_'.GW_LANG_I]) && $gw_this['cookie']['gw_'.GW_LANG_I] == '')
	{
		$gw_this['cookie']['gw_'.GW_LANG_I] = $gw_this['vars'][GW_LANG_I];
	}
	else if (isset($gw_this['cookie']['gw_'.GW_LANG_I]))
	{
		$gw_this['vars'][GW_LANG_I] = $gw_this['cookie']['gw_'.GW_LANG_I];
	}
}
else
{
	if (isset($gw_this['vars']['is']['save_'.GW_LANG_I]) 
		&& $gw_this['vars']['is']['save_'.GW_LANG_I])
	{
		/* Save interface language, set cookie */
		setcookie('gw_'.GW_LANG_I.$sys['token'], $gw_this['vars'][GW_LANG_I], $sys['time_now']+$sys['time_sec_y'], $sys['server_dir'] );
		setcookie('gw_is_save_'.GW_LANG_I.$sys['token'], 1,  $sys['time_now']+$sys['time_sec_y'], $sys['server_dir'] );
		$gw_this['cookie']['gw_'.GW_LANG_I] = $gw_this['vars'][GW_LANG_I];
		$gw_this['cookie']['gw_is_save_'.GW_LANG_I] = 1;
	}
	else
	{
		setcookie('gw_'.GW_LANG_I.$sys['token'], $gw_this['vars'][GW_LANG_I], $sys['time_now']-2, $sys['server_dir'] );
		setcookie('gw_is_save_'.GW_LANG_I.$sys['token'], 1,  $sys['time_now']-2, $sys['server_dir'] );
		$gw_this['cookie']['gw_'.GW_LANG_I] = '';
		$gw_this['cookie']['gw_is_save_'.GW_LANG_I] = '';
	}
}
$gw_this['vars']['is']['save_'.GW_LANG_I] = 
	(isset($gw_this['cookie']['gw_is_save_'.GW_LANG_I]) && $gw_this['cookie']['gw_is_save_'.GW_LANG_I] == 1)
	? 1 : 0;
$gw_this['vars'][GW_LANG_I] = preg_replace("/-([a-z0-9])+$/", '', $gw_this['vars'][GW_LANG_I]);
$gw_this['vars']['lang_enc'] = preg_replace("/^([a-z0-9])+-/", '', $gw_this['vars']['lang_enc']);
$gw_this['vars']['locale_name'] = $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'];

/* Get the list of topic */
$gw_this['ar_topics_list'] = gw_create_tree_topics();

/* 25 sep 2002, Translation Kit */
$oL = new gwtk;
$oL->setHomeDir($sys['path_locale']);
$oL->setLocale($gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc']);
$gw_this['vars']['ar_languages'] = $oL->getLanguages();
/* Language files */
$oL->getCustom('actions', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('err',     $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('options', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('status',  $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('tdb',     $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('tht',     $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('tol',     $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('custom',  $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
/* Uppercase / lowercase */
$oCase = new gwv_casemap(array(1,2,3,4,5,6,7,8), array(1,2,3));
$oCase->set_replace_sp(array('--'=>' ', '-' => ' '));
$oCase->encoding = $sys['internal_encoding'];
// ---------------------------------------------------------
// Search engine, last modified 1 aug 2003
// ---------------------------------------------------------
// Set common variables for templates
$arTplVars['srch'] = array(
	'l:search_term' => $oL->m('srch_1'),
	'l:search_defn' => $oL->m('srch_0'),
	'l:search_both' => $oL->m('srch_-1'),
	'L_SRCH_PHRASE' => $oL->m('srch_phrase'),
	'l:search_submit' => $oL->m('3_srch_submit'),
);
// Feature list of dictionary IDs
$gw_this['arDictListSrch'] = array();

// Search query, default values
if (!is_array($gw_this['vars'][GW_A_SEARCH])) { $gw_this['vars'][GW_A_SEARCH] = array(); }
if (!isset($gw_this['vars']['srch']['by'])) { $gw_this['vars']['srch']['by'] = 'd'; } // search by dictionary
if (!isset($gw_this['vars']['srch']['in'])) { $gw_this['vars']['srch']['in'] = -1; }  // search in all elements
if (!isset($gw_this['vars']['srch']['adv'])) { $gw_this['vars']['srch']['adv'] = 'all'; } // search all words

// Set switcher for HTML
$arTplVars['srch'][] = array('v:chk_srch_strict' => '' ); // search 'this dictionary only'
$arTplVars['srch'][] = array('v:chk_srch_all' => '' );
$arTplVars['srch'][] = array('v:chk_srch_exact' => '' );
$arTplVars['srch'][] = array('v:chk_srch_any' => '' );
$arTplVars['srch'][] = array('v:chk_srch_in_both' => '' );
$arTplVars['srch'][] = array('v:chk_srch_by_topic' => '' );
$arTplVars['srch'][] = array('v:chk_srch_by_dict' => '' );

// Web parameters conversion, old version
$gw_this['vars']['in'] = ($gw_this['vars']['in'] == 'both') ? -1 : intval($gw_this['vars']['in']);
$gw_this['vars']['in'] = ($gw_this['vars']['in'] == 'defn') ? 0 : intval($gw_this['vars']['in']);
$gw_this['vars']['in'] = ($gw_this['vars']['in'] == 'term') ? 1 : intval($gw_this['vars']['in']);
$gw_this['vars']['in'] = ($gw_this['vars']['in'] == 'syn') ? 7 : intval($gw_this['vars']['in']);
$gw_this['vars']['in'] = ($gw_this['vars']['in'] == 'address') ? 9 : intval($gw_this['vars']['in']);
$gw_this['vars']['in'] = ($gw_this['vars']['in'] == 'phone') ? 10 : intval($gw_this['vars']['in']);

// Go for each search option
if (isset($gw_this['vars']['srch']['adv']) && $gw_this['vars']['srch']['adv'] == 'all')
{
	$arTplVars['srch'][] = array('v:chk_srch_all' => ' checked="checked"' );
}
elseif (isset($gw_this['vars']['srch']['adv']) && $gw_this['vars']['srch']['adv'] == 'exact')
{
	$arTplVars['srch'][] = array('v:chk_srch_exact' => ' checked="checked"' );
}
elseif (isset($gw_this['vars']['srch']['adv']) && $gw_this['vars']['srch']['adv'] == 'any')
{
	$arTplVars['srch'][] = array('v:chk_srch_any' => ' checked="checked"' );
}
else // default
{
	$arTplVars['srch'][] = array('v:chk_srch_all' => ' checked="checked"' );
}
// Set switcher for HTML
// Where to search
if ($gw_this['vars']['srch']['in'] == '1')
{
	// search for term only
	$arTplVars['srch'][] = array('v:chk_srch_in_term' => ' checked="checked"' );
}
elseif (intval($gw_this['vars']['srch']['in']) == 0)
{
	// search in definitions
	$arTplVars['srch'][] = array('v:chk_srch_in_defn' => ' checked="checked"' );
}
elseif (intval($gw_this['vars']['srch']['in']) == -1)
{
	// search in terms and definitions, default
	$arTplVars['srch'][] = array('v:chk_srch_in_both' => ' checked="checked"' );
}
else
{
	// search for all fields
	$gw_this['vars']['srch']['in'] = array(0);
	$arTplVars['srch'][] = array('v:chk_srch_in_term' => ' checked="checked"' );
}
//
if (isset($gw_this['vars']['srch']['by']) && $gw_this['vars']['srch']['by'] == 'd')
{
	// Search by dictionary
	//
	// Set switcher for HTML
	$arTplVars['srch'][] = array('v:chk_srch_by_dict' => ' checked="checked"' );
	for (reset($gw_this['ar_dict_list']); list($kDict, $vDict) = each($gw_this['ar_dict_list']);)
	{
		if ($gw_this['vars'][GW_ID_DICT] == '0')
		{
			/* Collect all dictionaries */
			$gw_this['arDictListSrch'][] = $vDict['id'];
		}
		else if (isset($arDictParam['id']))
		{
			/* Select the current dictionary end exit */
			/* TODO: search for multiple dictionaries */
			$gw_this['arDictListSrch'][] = $arDictParam['id'];
			break;
		}
	}
}
else if (isset($gw_this['vars']['srch']['by']) && $gw_this['vars']['srch']['by'] == 'tp')
{
	// Search by topics
	$arTplVars['srch'][] = array('v:chk_srch_by_topic' => ' checked="checked"' );
	// Get topic's tree ID
	$gw_this['arTreeId'] = ctlgGetTree($gw_this['ar_topics_list'], $gw_this['vars']['id_topic']);
	for (reset($gw_this['ar_dict_list']); list($kDict, $vDict) = each($gw_this['ar_dict_list']);)
	{
		// check if dictionary presents in the selected topic
		if (isset($gw_this['arTreeId'][$vDict['id_topic']]))
		{
			$gw_this['arDictListSrch'][] = $vDict['id'];
		}
	}
}
else /* default */
{
	$arTplVars['srch'][] = array('v:chk_srch_by_dict' => ' checked="checked"' );
}
/* */
if ($strict) // check box for 'this dictionary only'
{
	$arTplVars['srch'][] = array('v:chk_srch_strict' => ' checked="checked"' );
}
/* --------------------------------------------------------
 * Load custom variables.
 * Allows to alter default settings and keep sources clear
 * ----------------------------------------------------- */
if (@file_exists('custom_vars.php'))
{
	include_once('custom_vars.php');
}

/* ------------------------------------------------------- */
/* Load custom page constructor */
if (@file_exists($sys['path_include_local']. 'custom_construct.php'))
{
	include_once($sys['path_include_local']. 'custom_construct.php');
}
else
{
	include_once($sys['path_include']. '/constructor.inc.php');
}
/* ------------------------------------------------------- */
/* Referer log */
if ($sys['is_log_ref'])
{
	if (!isset($oLog))
	{
		$oLog = new gw_logwriter($sys['path_logs']);
	}
	if ( ($HTTP_REF != '') && (!preg_match("/" . HTTP_HOST . '/', $HTTP_REF) ) )
	{
		$oLog->remote_ua = REMOTE_UA;
		$oLog->remote_ip = $oFunc->ip2int(REMOTE_IP);
		$oFunc->file_put_contents($oLog->get_filename('ref'), $oLog->get_str($HTTP_REF), 'a');
	}
}
/* ------------------------------------------------------- */
/* Custom constructor may change locale settings */
$oL->setLocale($gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc']);
if ($sys['is_use_xhtml'] && !$sys['is_debug_output'])
{
	/* Content-type for XHTML 1.1 */
	$sys['content_type'] = preg_match("/application\/xhtml\+xml/", $_SERVER['HTTP_ACCEPT']) ? 'application/xhtml+xml' : $sys['content_type'];
}
$oTpl->addVal( 'v:content_type', $sys['content_type'] );
/* Last header */
$oHdr->add('Content-Type: '.$sys['content_type'].'; charset='.$oL->languagelist('2'));
#$oHdr->add('Content-Type: '.$sys['content_type'].'; charset='.$sys['internal_encoding']);
/* ------------------------------------------------------- */
/* Debug information */
if (GW_DEBUG)
{
	include($sys['path_include'] . '/page.footer.php');
}
/* ------------------------------------------------------- */
/* Compile HTML-template */
$oTpl->parse();
$str_output = $oTpl->output();
/* Process text filters */
while (!$sys['is_debug_output']
		&& is_array($sys['filters_output'])
		&& list($k, $v) = each($sys['filters_output']) )
{
	$str_output = $v($str_output);
}
/* --------------------------------------------------------
 * GZip compression
 * ----------------------------------------------------- */
if ($sys['is_use_gzip'])
{
	/* Start time */
	if ($sys['is_log_gzip'])
	{
		$arLogGzip = array();
		if (!isset($oLog))
		{
			$oLog = new gw_logwriter($sys['path_logs']);
		}
		$arLogGzip[1] = strlen($str_output);
		$arLogGzip[3] = REMOTE_UA;
		$oTimer = new gw_timer('gzip');
	}
	/* requires $oHdr */
	$str_output = $oFunc->text_gzip($str_output, $sys['gzip_level']);
	/* End time */
	if ($sys['is_log_gzip'])
	{
		$arLogGzip[0] = sprintf("%1.5f", $oTimer->end());
		$arLogGzip[2] = strlen($str_output);
		if ($arLogGzip[1] != $arLogGzip[2])
		{
			$oFunc->file_put_contents($oLog->get_filename('gzip'), $oLog->make_str($arLogGzip), 'a');
		}
	}
}
/* --------------------------------------------------------
 * Final output
 * ----------------------------------------------------- */
$oHdr->output();
print $str_output;
/* end of file */
?>