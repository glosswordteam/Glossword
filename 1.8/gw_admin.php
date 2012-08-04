<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/* Protects script from wrong virtual host configuration */
if (defined('IN_GW'))
{
	die("<!-- $Id: gw_admin.php 531 2008-07-09 19:20:16Z glossword_team $ -->");
}
/**
 *  Main administrative events.
 */
/* Time counter */
$mtime = explode(" ", microtime());
$starttime = $mtime[1] + $mtime[0];
define('IN_GW', TRUE);
define('THIS_SCRIPT', 'gw_admin.php');
define('GW_IS_BROWSE_WEB',   0);
define('GW_IS_BROWSE_ADMIN', 1);
/* Maximum error control when in admin mode */
error_reporting(E_ALL | E_STRICT);
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
$sys['visualtheme'] = isset($sys['visualtheme']) ? $sys['visualtheme'] : 'gw_silver';
/* Auto time for server  */
$sys['time_now'] = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
$sys['time_now_gmt_unix'] = $sys['time_now'] - @date('Z');
$sys['time_now_db'] = ($sys['time_now_gmt_unix'] - @date("s", $sys['time_now_gmt_unix']));
$sys['time_now_db_cache'] = $sys['time_now_db'] - (@date("i", $sys['time_now_db']) * 60);
/* Add filter for output */
$sys['filters_output'] = array('gw_text_smooth_light');
/* --------------------------------------------------------
 * mod_rewrite configuration
 * ------------------------------------------------------- */
$oHtml = new gw_html;
$oHtml->setVar('is_htmlspecialchars', 0);
$oHtml->setVar('is_mod_rewrite', 0);
$oHtml->is_append_sid = 0;
$oHtml->id_sess_name = GW_SID;

/* --------------------------------------------------------
 * Register global variables
 * ----------------------------------------------------- */
$gw_this['vars'] = $oGlobals->register(array(
	'arControl','arPost','arPre','d','in','file_location','note_afterpost',
	GW_ACTION,GW_SID,GW_SID.'r',GW_TARGET,GW_LANG_I,GW_LANG_C,'visualtheme','uri',
	'id','uid','isConfirm',GW_A_SEARCH,'id_srch','is','name','email','id_topic',
	'isUpdate','mode','p','post','remove','q','strict','submit','tid','w1','w2','w3',
	'gw_is_save_visualtheme', 'gw_'.GW_LANG_I, 'gw_is_save_'.GW_LANG_I
));
/* Move cookies */
$gw_this['cookie'] = array();
if (isset($gw_this['vars']['_cookie']))
{
	$gw_this['cookie'] = $gw_this['vars']['_cookie'];
	unset($gw_this['vars']['_cookie']);
}

$oGlobals->do_default($gw_this['vars']['p'], 1);
$oGlobals->do_default($gw_this['vars'][GW_LANG_C], $gw_this['vars'][GW_LANG_I]);
$oGlobals->do_default($gw_this['vars']['lang_enc'], $sys['locale_name']);
$oGlobals->do_default($gw_this['vars']['visualtheme'], $sys['visualtheme']);
$oGlobals->do_default($gw_this['vars']['uri'], '');
/* used for Session class */
$sys['uri'] =& $gw_this['vars']['uri'];

/* Depreciated method */
for (reset($gw_this['vars']); list($k1, $v1) = each($gw_this['vars']);)
{
	$$k1 = $v1;
}
#$arPostVars = array('file_location','arAudio','arImg','arVideo');
#for (reset($arPostVars); list($k, $v) = each($arPostVars);)
#{
#	if (isset($_FILES[$v]) && ($_FILES[$v] != '')) // get values from FILES
#	{
#		$$v = $_FILES[$v];
#		$gw_this['_files'][$v] = $_FILES[$v];
#	}
#}
#prn_r( $_FILES, __LINE__ );
unset($arPostVars);


/* --------------------------------------------------------
 * Set interface language, last modified: 09 july 2008
 * ----------------------------------------------------- */
/* No custom language defined */
if ($gw_this['vars'][GW_LANG_I] == '')
{
	$gw_this['vars'][GW_LANG_I] = $sys['locale_name'];
	/* Cookie overwrites default language */
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
		setcookie('gw_'.GW_LANG_I.$sys['token'], $gw_this['vars'][GW_LANG_I], $sys['time_now']+$sys['time_sec_y'], $sys['server_dir'], '' );
		setcookie('gw_is_save_'.GW_LANG_I.$sys['token'], 1,  $sys['time_now']+$sys['time_sec_y'], $sys['server_dir'], '' );
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

/* Translation engine */
$oL = new gwtk;
$oL->setHomeDir($sys['path_locale']);
$oL->setLocale($gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc']);
$gw_this['vars']['ar_languages'] = $oL->getLanguages();
//
// Language files
$oL->getCustom('actions', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('admin',   $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('err',     $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('options', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('status',  $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('tdb',     $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('tht',     $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('tol',     $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');

/* Start session */
$oSess = new gw_session_1_9;
$oSess->oL =& $oL;
$oSess->oDb =& $oDb;
$oSess->sys =& $sys;
$oSess->load_settings();
/* Get Session ID */
$id_sess = isset($gw_this['cookie'][GW_SID])
		? $gw_this['cookie'][GW_SID] 
		: (isset($gw_this['vars'][GW_SID]) ? $gw_this['vars'][GW_SID] : 0);
/* Start new session */
$oSess->sess_init($id_sess);

/* Everything below requires authorization */
$oSess->is_auth();

/** Contents below are available for logged users only **/
if ($gw_this['vars'][GW_ACTION] == 'logout')
{
	$oSess->logout();
	exit;
}
##------------------------------------------------
## Admin-only included files
##
## isDebugQ         Show insert/update/delete queries
## path_img_admin   Path to images for administrative interface
## path_export      Path to directory where import/export files will be readed/saved (chmod 0777 required)
##------------------------------------------------
$sys['isDebugQ'] = 0;
$sys['path_img_admin'] = $sys['path_admin'] . '/images';
include_once( $sys['path_include'] . '/class.forms.php');
include_once( $sys['path_include'] . '/class.gw_htmlforms.php');
include_once( $sys['path_include'] . '/func.admin.inc.php');
include_once( $sys['path_include'] . '/class.confirm.php');
/* Uppercase / lowercase */
$oCase = new gwv_casemap(array(1,2,3,4,5,6,7,8), array(1,2,3,4,5));
$oCase->set_replace_sp(array('--' => ' ', '-' => ' '));
$oCase->encoding = $sys['internal_encoding'];
$oCase->is_use_mbstring = 1;

//
// Threads for topic editor
$arImgTread = $arTxtTread = array();
$arImgTread['c'] = '<img src="' . $sys['path_img'] . '/p_c.gif" width="9" height="21" alt="" />';
$arImgTread['i'] = '<img src="' . $sys['path_img'] . '/p_i.gif" width="12" height="21" alt="" />';
$arImgTread['l'] = '<img src="' . $sys['path_img'] . '/p_l.gif" width="12" height="21" alt="" />';
$arImgTread['m'] = '<img src="' . $sys['path_img'] . '/p_m.gif" width="9" height="21" alt="" />';
$arImgTread['n'] = '<img src="' . $sys['path_img'] . '/p_n.gif" width="9" height="21" alt="" />';
$arImgTread['p'] = '<img src="' . $sys['path_img'] . '/p_p.gif" width="9" height="21" alt="" />';
$arImgTread['t'] = '<img src="' . $sys['path_img'] . '/p_t.gif" width="12" height="21" alt="" />';
$arImgTread['trans'] = '<img src="' . $sys['path_img'] . '/0x0.gif" width="12" height="21" alt="" />';
$arImgTread['space'] = '<img src="' . $sys['path_img'] . '/0x0.gif" width="5" height="21" alt="" />';
$arTxtTread['c'] = '─';
$arTxtTread['i'] = '│';
$arTxtTread['l'] = '└';
$arTxtTread['m'] = '●';
$arTxtTread['n'] = '○';
$arTxtTread['p'] = '●';
$arTxtTread['t'] = '├';
$arTxtTread['trans'] = '&#160;';
$arTxtTread['space'] = '&#160;';

/* Get the list of dictionaries */
$gw_this['ar_dict_list'] = getDictArray();
/* Get the list of topics */
$gw_this['ar_topics_list'] = gw_create_tree_topics();
## ---------------------------------------------------
/* --------------------------------------------------------
 * Load visual theme
 * ----------------------------------------------------- */
if ($oSess->user_get('visualtheme'))
{
	#$gw_this['vars']['visualtheme'] = $oSess->user_get('visualtheme');
}
$gw_this['vars']['visualtheme'] = 'gw_admin';
/* Read visual theme names */
$gw_this['ar_themes'] = gw_get_themes_list();
$gw_this['ar_themes_select'] = gw_get_themes_select();


/* 24 jan 2006: load theme setting from database */
$ar_theme = gw_get_theme($gw_this['vars']['visualtheme']);
$ar_theme['tblwidth'] = '100%';
$ar_theme['prepend_abbr_preview'] = '';
$ar_theme['split_abbr_preview'] = ' ';
$ar_theme['append_abbr_preview'] = '';
$ar_theme['split_defn'] = ' &#9674;&#32;';

// Init. strings for HTML-templates
$strR = $strL = $strM = '';
/* --------------------------------------------------------
 * Template engine
 * ------------------------------------------------------- */
$oTpl = new $sys['class_tpl'];
$oTpl->init('gw_admin');
$oTpl->is_tpl_show_names = $sys['is_tpl_show_names'];
$gw_this['id_tpl_page'] = GW_TPL_ADMIN;
$oTpl->tmp['d'] = array();
/* Top bar: Control Panel Home, Online: User | 00:00:00 | Logout */
$ar_menu_info = array();
$ar_menu_info[] = '<span class="white xt"><strong>' . $oL->m('online') . '</strong></span>: <strong>'.
				$oHtml->a( $sys['page_admin'].'?'.GW_ACTION.'=edit-own'. '&t=users', $oSess->user_get('user_fname').' '.$oSess->user_get('user_sname'), $oL->m('3_profile') ) .
				'</strong>';
$ar_menu_info[] = '<span class="white xt">'.$oFunc->date_SecToTime( $sys['time_now_gmt_unix'] - $oSess->user_get('date_login') ).'</span>';
$ar_menu_info[] = $oHtml->a( $sys['page_admin'].'?a=logout&amp;uri='.base64_encode($_SERVER['QUERY_STRING']), $oL->m('3_logout') );

// --------------------------------------------------------
// Load Add-ons
// --------------------------------------------------------
include_once($sys['path_addon'].'/multilingual_vars.php');
include_once($sys['path_addon'].'/fields_extension.php');

// Apply variables for HTML-templates
$oTpl->addVal( 'v:language',       $oL->languagelist("0") );
$oTpl->addVal( 'v:text_direction', $oL->languagelist("1") );
$oTpl->addVal( 'v:charset',        $oL->languagelist("2") );
/* 11 december 2002, r-t-l with l-t-r */
$sys['css_align_right'] = 'right';
$sys['css_align_left'] = 'left';
$sys['css_dir_numbers'] = 'rtl';
$sys['css_dir_text'] = 'ltr';
if ($oL->languagelist('1') == 'rtl')
{
	$sys['css_dir_text'] = 'rtl';
	$sys['css_dir_numbers'] = 'ltr';
	$sys['css_align_right'] = 'left';
	$sys['css_align_left'] = 'right';
}
$oTpl->addVal( 'v:path_tpl',         $sys['server_dir'] . '/' . $sys['path_tpl'] . '/common');
$oTpl->addVal( 'v:path_img_admin',   $sys['path_img_admin'] );
$oTpl->addVal( 'v:css_align_right',  $sys['css_align_right'] );
$oTpl->addVal( 'v:css_align_left',   $sys['css_align_left'] );
$oTpl->addVal( 'v:path_img',         $sys['path_img'] );
$oTpl->addVal( 'v:server_url',       $sys['server_url'] );
$oTpl->addVal( 'v:server_dir',       $sys['server_dir'] );
$oTpl->addVal( 'v:visualtheme',      str_replace('_', '-', $gw_this['vars']['visualtheme']));
$oTpl->addVal( 'v:path_img',         $sys['path_img'] );
$oTpl->addVal( 'v:html_title',       $oL->m('2_page__') );
$oTpl->addVal( 'v:path_css',         $sys['server_dir'].'/'.$sys['path_temporary'].'/t/'.$gw_this['vars']['visualtheme'] );
$oTpl->addVal( 'v:path_css_script',  $sys['path_css_script'] );
$oTpl->addVal( 'v:path_admin',       $sys['path_admin'] );
$oTpl->addVal( 'v:table_width',      $ar_theme['tblwidth'] );
$oTpl->addVal( 'v:form_action',      $sys['page_admin'] );
$oTpl->addVal( 'v:q',                '*' );
$oHtml->setTag('a', 'onclick', 'nw(this);return false');
$oHtml->setTag('a', 'class', 'ext');
$oTpl->addVal( 'url:title_page',     $oHtml->a($sys['server_proto'].$sys['server_host'].$sys['page_index'], $oL->m('3_tomain')) );
$oHtml->unsetTag('a');
$oTpl->addVal( 'url:admin_page',     $oHtml->a($sys['page_admin'], $oL->m('2_page__')) );
$oTpl->addVal( 'l:top_of_page',      $oL->m('3_top') );
$oTpl->addVal( 'v:top_right',        implode(' &#8226; ', $ar_menu_info)  );
$oTpl->addVal( 'v:site_name',        $oL->m('2_page__') );
$oTpl->addVal( 'href:home',          $oHtml->url_normalize($sys['server_proto'].$sys['server_host'].$sys['page_admin']) );
$oTpl->addVal( 'v:glossword_version',$sys['version'] );
$oTpl->addVal( 'termsamount',        $oL->m('termsamount'));
$oTpl->addVal( 'v:sitename',         $oL->m('sitename'));
$oTpl->addVal( 'url:mailto_contact_name', $oFunc->text_mailto('<a href="mailto:'. $sys['y_email'].'">'. $sys['y_name'] . '</a>'));
$oTpl->addVal( 'sid_name',           GW_SID);
$oTpl->addVal( 'sid',                $oSess->id_sess);
$oTpl->addVal( 'A_SEARCH',           GW_A_SEARCH);
$oTpl->addVal( 'v:lang_i_name',      GW_LANG_I );
$oTpl->addVal( 'v:lang_i_value',     $gw_this['vars'][GW_LANG_I] );
$oTpl->addVal( 'v:id_srch',          $gw_this['vars']['id_srch'] );
/* Link to add a term */
if ($oSess->is('is-terms'))
{
	$oTpl->addVal( 'url:add_term',       $oHtml->a($sys['page_admin'] . '?' .GW_ACTION.'='.GW_A_ADD .'&'. GW_TARGET.'='.GW_T_TERMS, $oL->m('3_add_term'), $oL->m('terms').': '.$oL->m('3_add')  ) );
}
/* Link to browse dictionaries */
if ($oSess->is('is-dicts'))
{
	$oTpl->addVal( 'url:browse_dicts',   $oHtml->a($sys['page_admin'] . '?' .GW_ACTION.'='.GW_A_BROWSE .'&'. GW_TARGET.'='.GW_T_DICTS, $oL->m(1335), $oL->m(1335).': '.$oL->m('3_browse')  ) );
}
/* Link to browse users */
if ($oSess->is('is-users'))
{
	$oTpl->addVal( 'url:browse_users',   $oHtml->a($sys['page_admin'] . '?' .GW_ACTION.'='.GW_A_BROWSE .'&'. GW_TARGET.'='.GW_T_USERS, $oL->m(1337), $oL->m(1337).': '.$oL->m('3_browse')  ) );
}
$oTpl->addVal( 'l:term',             $oL->m('term') );
/* Show notice */
if ($gw_this['vars']['note_afterpost'])
{
	$oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($gw_this['vars']['note_afterpost'], true) );
}

/* I request you to retain the copyright notice! */
$oTpl->addVal( 'v:copyright',        $sys['str_branding'] );


// Possible variables for admin mode:
// $p  is page number   [ $p > 1 ]
// $id is dictionary ID [ $id > 0 ]

// ---------------------------------------------------------
// Search engine, last modified 1 aug 2003
// ---------------------------------------------------------
// Add variables used in search form into common templates
$arTplVars['srch'] = array(
	'l:search_term' => $oL->m('srch_1'),
	'l:search_defn' => $oL->m('srch_0'),
	'l:search_both' => $oL->m('srch_-1'),
	'l:search_to_edit' => $oL->m('srch_phrase_edit'),
	'l:search_submit' => strip_tags($oL->m('3_srch_submit')),
	'l:search_approved_term' => $oL->m('1320'),
	'l:search_unapproved_term' => $oL->m('srch_7'),
	'l:search_incomplete_term' => $oL->m('1268'),
	'l:search_removed_term' => $oL->m('1298'),
);

//
// Feature list of dictionary ID
$gw_this['arDictListSrch'] = array();
//
// Search query, default values
#if (!is_array($srch)) { $srch = array(); }
#if (!isset($srch['by'])) { $srch['by'] = 'd'; } // search by dictionary
#if (!isset($gw_this['vars']['srch']['in'])) { $gw_this['vars']['srch']['in'] = 1; }  // search in terms only for admin mode
#if (!isset($srch['adv'])) { $srch['adv'] = 'all'; } // seach all words

if (!is_array($gw_this['vars'][GW_A_SEARCH])) { $gw_this['vars'][GW_A_SEARCH] = array(); }
if (!isset($gw_this['vars']['srch']['by'])) { $gw_this['vars']['srch']['by'] = 'd'; } // search by dictionary
if (!isset($gw_this['vars']['srch']['in'])) { $gw_this['vars']['srch']['in'] = 1; }  // search in all elements
if (!isset($gw_this['vars']['srch']['adv'])) { $gw_this['vars']['srch']['adv'] = 'all'; } // search all words

//
// Set switcher for HTML
$arTplVars['srch']['v:chk_srch_all'] = '';
$arTplVars['srch']['v:chk_srch_exact'] = '';
$arTplVars['srch']['v:chk_srch_any'] = '';
$arTplVars['srch']['v:chk_srch_in_both'] = '';
$arTplVars['srch']['v:chk_srch_in_term_unapproved'] = '';
$arTplVars['srch']['v:chk_srch_in_term_incomplete'] = '';
$arTplVars['srch']['v:chk_srch_in_term_removed'] = '';

if (isset($gw_this['vars']['srch']['by']) && $gw_this['vars']['srch']['by'] == 'd')
{
	/* Search by dictionary */

	// Set switcher for HTML
	$arTplVars['srch']['v:chk_srch_by_dict'] = ' checked="checked"';
	for (reset($gw_this['ar_dict_list']); list($kDict, $vDict) = each($gw_this['ar_dict_list']);)
	{
		if ($d == 0)
		{
			$gw_this['arDictListSrch'][] = $vDict['id'];
		}
		else
		{
			// TODO: search for multiple dictionaries
			$gw_this['arDictListSrch'][] = $d;
			break;
		}
	}
}

// ---------------------------------------------------------
// Fix dictionary ID
/* Old variables */
#$d = ( $id != '' ) ? $id : $d;
#$id = ( $d != '' ) ? $d : $id;
/* 1.7.0 */
$gw_this['vars']['d'] = ($gw_this['vars']['id'] == '') ? $gw_this['vars']['d'] : $gw_this['vars']['id'];
$gw_this['vars']['id'] = ($gw_this['vars']['d'] == '') ? $gw_this['vars']['id'] : $gw_this['vars']['d'];

/* Process selected terms from search results */
if (isset($gw_this['vars']['arPost']) && is_array($gw_this['vars']['arPost']))
{
	if (isset($gw_this['vars']['arPost']['selected_term_on'])
		|| isset($gw_this['vars']['arPost']['selected_term_off'])
		|| isset($gw_this['vars']['arPost']['selected_term_move'])
		)
	{
		$gw_this['vars'][GW_ACTION] = GW_A_EDIT;
		$gw_this['vars'][GW_TARGET] = GW_T_TERMS;
	}
	elseif (isset($gw_this['vars']['arPost']['selected_term_remove']))
	{
		$gw_this['vars'][GW_ACTION] = GW_A_EDIT;
		$gw_this['vars'][GW_TARGET] = GW_T_TERMS;
		$gw_this['vars']['remove'] = 1;
	}
}

/* Set required field names for HTML-forms */
$arReq['dict'] = array('title', 'id_topic', 'uid', 'tablename', 'lang', 'cfg', 'visualtheme');
$arReq['term'] = array('arPre[term][0][value]');
$strDictDetails = '';
// --------------------------------------------------------
// Load extensions: 27 aug 2003
// --------------------------------------------------------
$gw_this['vars']['funcnames'][GW_A_UPDATE . GW_T_TERM] = isset($gw_this['vars']['funcnames'][GW_A_UPDATE . GW_T_TERM]) ? $gw_this['vars']['funcnames'][GW_A_UPDATE . GW_T_TERM] : array();
$gw_this['vars']['funcnames'][GW_A_UPDATE . GW_T_DICT] = isset($gw_this['vars']['funcnames'][GW_A_UPDATE . GW_T_DICT]) ? $gw_this['vars']['funcnames'][GW_A_UPDATE . GW_T_DICT] : array();
## --------------------------------------------------------
## Get dictionary description
## Very important

if ($gw_this['vars']['d'])
{
	// Get dictionary settings
	$arDictParam = getDictParam($gw_this['vars']['d']);
	// No any settings, return to the dictionary listing
	if (empty($arDictParam))
	{
		gwtk_header(append_url($sys['server_proto'].$sys['server_host'].$sys['page_admin'].'?'.GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.GW_T_DICTS));
	}
	/* 1.8.7: Sorting order */
	$arDictParam['az_sql'] = $arDictParam['az_order'] = '';
	/* 1.8.7: Select custom alphabetic order */
	if ($arDictParam['id_custom_az'] > 1)
	{
		$arSql = $oDb->sqlExec($oSqlQ->getQ('get-custom_az-int', $arDictParam['id_custom_az']) );
		/* A part of SQL-request for listing terms */
		$sql_az = '';
		$ar_az = array();
		for (; list($k, $v) = each($arSql);)
		{
			$ar_az[] = $v['value'];
		}
		if (!empty($ar_az))
		{
			$arDictParam['az_order'] = implode(',', $ar_az);
			$arDictParam['az_sql'] = ' FIELD(t.term_a,'.$arDictParam['az_order'].
				'), FIELD(t.term_b,'.$arDictParam['az_order'].
					'), FIELD(t.term_c, '.$arDictParam['az_order'].
					'), FIELD(t.term_d, '.$arDictParam['az_order'].
					'), FIELD(t.term_e, '.$arDictParam['az_order'].
					'), FIELD(t.term_f, '.$arDictParam['az_order'].'), ';
		}
	}
	// add dictionary ID into HTML-template
	$oTpl->addVal( 'd', $gw_this['vars']['d'] );
	$oTpl->addVal( 'v:d', $gw_this['vars']['d'] );
	$oTpl->addVal( 'v:id_dict', $gw_this['vars']['d'] );
	//
	$languagelist = $oL->languagelist();

	/* 1.8.7: Turn on/off */
	$href_onoff = $sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.GW_T_DICTS.'&tid='.$gw_this['vars']['id'].'&id='.$gw_this['vars']['id'];
	$str_is = ($arDictParam['is_active'] 
					? $oHtml->a($href_onoff.'&mode=off', '<span class="green">'.$oL->m('is_1').'</span>')
					: $oHtml->a($href_onoff.'&mode=on', '<span class="red">'.$oL->m('is_0').'</span>', $oL->m('1057') ) );
	//
#	$oHtml->setTag('a', 'style', 'font-size:110%;padding:3px');
	$strDictDetails .= '<table class="dictdetails" cellspacing="0" cellpadding="3" border="0" width="100%">';
	$strDictDetails .= '<tbody><tr>';
	/* Link to dictionary */
	$strDictDetails .= '<td><div class="gray xt">' . $oL->m('dict') . ':</div><span class="xu">'. 
						'<a class="ext" href="'.( $sys['page_index'].'?'.GW_ACTION.'=list&amp;d='.$arDictParam['uri'] ).
						'" onclick="window.open(this.href);return false;">' . $arDictParam['title'] . '</a></span></td>';
	/* */
	$strDictDetails .= '<td style="width:23%" class="actions-third"><div class="gray xt">' . $oL->m('termsamount') . ':</div>';
	if ($arDictParam['int_terms'])
	{
		$strDictDetails .= $oHtml->a($sys['page_admin'].'?'.GW_ACTION.'='.GW_A_SEARCH.'&id='.$arDictParam['id'].'&q=*&srch[in]=103&t=dicts', '<span class="green">'.$oFunc->number_format($arDictParam['int_terms'], 0, $oL->languagelist('4')).'</span>', $oL->m('1320'));
	}
	else
	{
		$strDictDetails .= '<del title="'.$oL->m('1320').'">0</del>';
	}
	$strDictDetails .= ' / ';
	if ($arDictParam['int_terms_total']-$arDictParam['int_terms'])
	{
		$strDictDetails .= $oHtml->a($sys['page_admin'].'?'.GW_ACTION.'='.GW_A_SEARCH.'&id='.$arDictParam['id'].'&q=*&srch[in]=100&t=dicts', '<span class="red">'.$oFunc->number_format($arDictParam['int_terms_total']-$arDictParam['int_terms'], 0, $oL->languagelist('4')).'</span>', $oL->m('srch_7'));
	}
	else
	{
		$strDictDetails .= '<del title="'.$oL->m('srch_7').'">0</del>';
	}
	$strDictDetails .= ' / ';
	if ($arDictParam['int_terms_total'])
	{
		$strDictDetails .= $oHtml->a($sys['page_admin'].'?'.GW_ACTION.'='.GW_A_SEARCH.'&id='.$arDictParam['id'].'&q=*&srch[in]=1&t=dicts', $oFunc->number_format($arDictParam['int_terms_total'], 0, $oL->languagelist('4')), $oL->m('total'));
	}
	else
	{
		$strDictDetails .= '<del title="'.$oL->m('total').'">0</del>';
	}
	$strDictDetails .= '</td>';
	$strDictDetails .= '<td class="actions-third" style="width:7%"><div class="gray xt">' . $oL->m('status') . ':</div>'. $str_is . '</td>';
	$strDictDetails .= '<td style="width:19%"><div class="gray xt">' . $oL->m('lang') . ':</div><div class="xu">'.  ($arDictParam['lang'] ? $languagelist[$arDictParam['lang']] : $languagelist[$sys['locale_name']] ) . '</div></td>';
	$strDictDetails .= '<td style="width:10%"><div class="gray xt">' . $oL->m('size') . ', '.$oL->m('kb').':</div><div class="xu">'. $oFunc->number_format($arDictParam["int_bytes"]/1024, 1, $oL->languagelist('4')) . '</div></td>';
	$strDictDetails .= '</tr>';
	$strDictDetails .= '</tbody></table>';
#	$oHtml->setTag('a', 'style', '');

	$oTpl->addVal( 'v:dict_details', $strDictDetails );
	/* Get term parameters */
	if ($gw_this['vars']['tid'])
	{
		$arTermParam = getTermParam($tid);
	}
}
/* Display navigation toolbar */
$strL .= gw_admin_menu($gw_this['vars'][GW_ACTION], $gw_this['vars'][GW_TARGET]);
/* */
if (empty($arDictParam)){ $arDictParam = array(); }
/* */
$gw_this['vars']['q'] = trim($oFunc->mb_substr($gw_this['vars']['q'], 0, 254));
/* Search for terms */
if ($gw_this['vars'][GW_ACTION] == GW_A_SEARCH)
{
	$gw_this['id_tpl_page'] = GW_TPL_SEARCH_ADM;
	if ($id_srch == '')
	{
		/* 1st search query */
		$gw_this['arSrchResults'] = gw_search($gw_this['vars']['q'], $gw_this['arDictListSrch'], $gw_this['vars'][GW_A_SEARCH]);
	}
	else
	{
		/* list seach results */
		$gw_this['arSrchResults'] = gw_search_results($id_srch, $gw_this['vars']['p']);
	}
	if ($gw_this['arSrchResults']['found'] > 0)
	{
		$oTpl->tmp['d']['if:found'] = '';
	}
	$oTpl->addVal( 'l:search_time', $oL->m('srch_6') );
	$oTpl->addVal( 'v:search_time', $gw_this['arSrchResults']['time'] );
	$oTpl->addVal( 'l:search_matches', $oL->m('srch_matches') );
	$oTpl->addVal( 'l:search_phrase', $oL->m('srch_phrase') );
	$oTpl->addVal( 'l:search_total', $oL->m('srch_5') );
	$oTpl->addVal( 'l:found', $oL->m('srch_3') );
	$oTpl->addVal( 'l:search_found_dict', $oL->m('srch_2') );
	$oTpl->addVal( 'v:q', htmlspecialchars($gw_this['arSrchResults']['q']));
	$oTpl->addVal( 'v:found', $gw_this['arSrchResults']['found']);
	$oTpl->addVal( 'v:found_total', $gw_this['arSrchResults']['found_total']);
	$oTpl->addVal( 'v:requests', $gw_this['arSrchResults']['hits']);
	$intSumPages = 1;
	if (isset($arDictParam['page_limit_search']))
	{
		$intSumPages = ceil($gw_this['arSrchResults']['found'] / $arDictParam['page_limit_search']);
	}
	/* fix page number */
	if ( ( $gw_this['vars']['p'] < 1 ) || ( $gw_this['vars']['p'] > $intSumPages) ){ $gw_this['vars']['p'] = 1; }
	if ($intSumPages > 1)
	{
		$oTpl->addVal('l:pages', $oL->m('L_pages'));
	}
	$sys['total'] = $gw_this['arSrchResults']['found'];
	$oTpl->addVal( 'q', htmlspecialchars($gw_this['arSrchResults']['q']) );
	/* enable page navigation */
	if ($intSumPages > 1)
	{
		$ar_theme['split_pagenumbers'] = ' &#8226; ';
		$oTpl->addVal( 'v:nav_pages',
				getNavToolbar($intSumPages, $gw_this['vars']['p'], $sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_SEARCH.'&id_srch='.$id_srch.'&d='.$d.'&p=')
				);
	}
	if ($gw_this['arSrchResults']['found'] == 0) // nothing was found
	{
		$gw_this['arSrchResults']['html'] =
		'<div style="font:75% verdana, helvetica">'.
		'<p>'. $oL->m('reason_5') . '</p>'.
		'<p>' .$oHtml->a(append_url($sys['page_admin'] . '?'. GW_ACTION . '='.GW_A_SEARCH.'&amp;d='.$d."&amp;q=".urlencode($gw_this['arSrchResults']['q']).'&amp;in=both'),
							$oL->m('srch_-1')).
		'</p>'.
		'</div>';
	}
	/* Where to search */
	$gw_this['arSrchResults']['in'] = isset( $gw_this['arSrchResults']['in'] ) ? intval( $gw_this['arSrchResults']['in'] ) : 1;
	$chk_srch_in = ' selected="selected"';
	if ($gw_this['arSrchResults']['in'] == 1)
	{
		/* search for term only */
		$arTplVars['srch']['v:chk_srch_in_term'] = $chk_srch_in;
	}
	elseif ($gw_this['arSrchResults']['in'] == 0)
	{
		/* search in terms and definitions, default */
		$arTplVars['srch']['v:chk_srch_in_defn'] = $chk_srch_in;
	}
	elseif ($gw_this['arSrchResults']['in'] == -1)
	{
		/* search in terms and definitions, default */
		$arTplVars['srch']['v:chk_srch_in_both'] = $chk_srch_in;
	}
	elseif ($gw_this['arSrchResults']['in'] == 100)
	{
		/* search in unapproved terms */
		$arTplVars['srch']['v:chk_srch_in_term_unapproved'] = $chk_srch_in;
	}
	elseif ($gw_this['arSrchResults']['in'] == 101)
	{
		/* search in unapproved terms */
		$arTplVars['srch']['v:chk_srch_in_term_incomplete'] = $chk_srch_in;
	}
	elseif ($gw_this['arSrchResults']['in'] == 102)
	{
		/* search in unapproved terms */
		$arTplVars['srch']['v:chk_srch_in_term_removed'] = $chk_srch_in;
	}
	elseif ($gw_this['arSrchResults']['in'] == 103)
	{
		/* search in approved terms only */
		$arTplVars['srch']['v:chk_srch_in_term_approved'] = $chk_srch_in;
	}
	else
	{
		/* search for all fields */
		$gw_this['vars']['srch']['in'] = array(0);
		$arTplVars['srch']['v:chk_srch_in_term'] = $chk_srch_in;
	}

}
/* Display search form */
if (($id != '')
	&& (($gw_this['vars'][GW_TARGET] == GW_T_DICT)
	|| ($gw_this['vars'][GW_TARGET] == GW_T_TERM))
	|| !empty($q) || !empty($id_srch))
{
	$oTpl->addVal( 'srch_a_name', GW_A_SEARCH);
}
##
## --------------------------------------------------------

$strForm = $strToMain = '';
$isPostError = 1;

## --------------------------------------------------------
## Preview items (translation, transcription, pages etc.)
if (is_array($arPre))
{
	/* Change current action to "Edit" */
	if ($gw_this['vars'][GW_ACTION] != GW_A_ADD){ $gw_this['vars'][GW_ACTION] = GW_A_EDIT; }
	if (isset($arPre[$t]['remove'])){ $gw_this['vars'][GW_ACTION] = GW_A_REMOVE; }
#	${GW_TARGET} = GW_T_TERM;
}
##
## --------------------------------------------------------
/* Constructs 'at.{GW_ACTION}_{GW_TARGET}.php' files */
$pathAction = $sys['path_include'] . '/' . GW_TARGET . '.' . $gw_this['vars'][GW_TARGET] . '.inc.php';

$gw_this['vars']['cur_funcname'] = $gw_this['vars'][GW_TARGET] . '_' . $gw_this['vars'][GW_ACTION];
$sys['path_component'] = $sys['path_addon'] . '/' . $gw_this['vars'][GW_TARGET] . '/'.$gw_this['vars'][GW_TARGET].'_admin.php';
$sys['path_component_action'] = $sys['path_addon'].'/'.$gw_this['vars'][GW_TARGET].'/'.$gw_this['vars']['cur_funcname'].'.inc.php';

#prn_r( $sys['path_component']  );
#prn_r( $sys['path_component_action']  );

$sys['id_current_status'] = '2_page_'.$gw_this['vars'][GW_TARGET] . '_' . $gw_this['vars'][GW_ACTION];

/* include components */
if ($gw_this['vars'][GW_TARGET] != '')
{
	/* */
	include_once($sys['path_addon'].'/class.gw_addon.php');
	/* General component class */
	file_exists($sys['path_component'] )
		? include_once($sys['path_component'] )
		: '';
#	/* Actions for component */
#	file_exists($sys['path_component_action'] )
#		? include_once($sys['path_component_action'] )
#		: '';
	/* Old */
	print $pathAction;
	file_exists($pathAction)
		? include_once($pathAction)
		: (isset($gw_this['class_'.$gw_this['vars'][GW_TARGET]])
			? $$gw_this['vars'][GW_TARGET] = new $gw_this['class_'.$gw_this['vars'][GW_TARGET]]
			: '' );
}

#file_exists($sys['path_include'].'/t.'.$gw_this['vars']['cur_funcname'].'.inc.php')
#	? include_once($sys['path_include'].'/t.'.$gw_this['vars']['cur_funcname'].'.inc.php')
#	: '';

/* Set current action name (page numbers) */
$oTpl->addVal( 'v:current_action', $oL->m($sys['id_current_status'] ));

/* Create dictionaries list */
$oTpl->AddVal( 'block:SearchSelect', getDictSrch('', 1, 99,'', 1, $gw_this['vars']['d']));

/* Turn on the block of HTML-code when editing or adding terms is allowed */
if ($oSess->is('is-terms') || $oSess->is('is-terms-own'))
{
	$oTpl->tmp['d']['if:term_add'] = '';
}

/* Control panel home */
if ($sys['id_current_status'] == '2_page__')
{
	/* Print the list of recently updated dictionaries and terms */
	if (sizeof($gw_this['ar_dict_list']) > 0)
	{
		/* Last updated dictionaries */
		$oTpl->addVal( 'block:dict_updated',
			gw_html_block_small(
				$oL->m('r_dict_updated'),
				getTop10('DICT_UPDATED', $sys['max_dict_top'], 1),
				0, 0)
		);
		/* Last updated terms */
		$oTpl->addVal( 'block:term_updated',
			gw_html_block_small(
				$oL->m('r_term_updated'),
				getTop10('TERM_UPDATED', intval($sys['max_dict_top']/2), 0),
				0, 0)
		);
	}
	$oTpl->addVal( 'A_ADD', GW_A_ADD );
	$oTpl->addVal( 'L_ADD', $oL->m('3_add') );
	$arHelpMap = array(
					'tip016' => 'tip019',
					'3_add_term' => 'tip020',
				 );
	$strHelp = '';
	$strHelp .= '<dl>';
	for(; list($k, $v) = each($arHelpMap);)
	{
		$strHelp .= '<dt><b>' . $oL->m($k) . '</b></dt>';
		$strHelp .= '<dd>' . $oL->m($v) . '</dd>';
	}
	$strHelp .= '</dl>';
	$strR .= '<br/>'.kTbHelp($oL->m('2_tip'), $strHelp);
}
else
{
   $oTpl->addVal( 'v:html_title', $oL->m('2_page__') . ' - ' . strip_tags($oL->m($sys['id_current_status'])) );
}
/* Add previously defined template variables */
for (reset($arTplVars['srch']); list($k, $v) = each($arTplVars['srch']);)
{
	$oTpl->AddVal($k, $v);
}

/* Render page */
$oTpl->set_tpl($gw_this['id_tpl_page']);


/* Append URL for integration */
$tmp['input_url_append'] = '';
for (reset($sys['ar_url_append']); list($k, $v) = each($sys['ar_url_append']);)
{
	$tmp['input_url_append'] .= '<input type="hidden" name="'.$k.'" value="'.$v.'" />';
}
$oTpl->addVal( 'v:input_url_append', $tmp['input_url_append'] );


/* Parse dynamic blocks */
for (reset($oTpl->tmp['d']); list($id_dynamic, $arV) = each($oTpl->tmp['d']);)
{
	if (is_array($arV))
	{
		for (reset($arV); list($k2, $v2) = each($arV);)
		{
			for (reset($v2); list($k, $v) = each($v2);)
			{
				$oTpl->assign(array($k => $v));
			}
			$oTpl->parseDynamic($id_dynamic);
		}
	}
	else
	{		
		$oTpl->parseDynamic($id_dynamic);
	}
	unset($oTpl->tmp['d'][$id_dynamic]);
}

/* Content-type for XHTML 1.1 */
#$sys['content_type'] = preg_match("/application\/xhtml\+xml/", $_SERVER['HTTP_ACCEPT']) ? 'application/xhtml+xml' : $sys['content_type'];
$oTpl->addVal( 'v:content_type', $sys['content_type'] );

/* Close sessions */
$oSess->sess_close();

// --------------------------------------------------------
// Debug information
if (GW_DEBUG)
{
	include($sys['path_include'] . '/page.footer.php');
}
/* The last header */
$oHdr->add('Content-Type: '.$sys['content_type'].'; charset='.$oL->languagelist('2'));
$oHdr->output();

$oTpl->addVal( 'TOMAIN', $strToMain );
$oTpl->addVal( 'ADMIN_RIGHT_SIDE', $strR );
$oTpl->addVal( 'ADMIN_LEFT_SIDE', $strL );
#prn_r( $oTpl->pairsV );
$oTpl->parse();
$str_output = $oTpl->output();
/* Process text filters */
while (!$sys['is_debug_output']
		&& is_array($sys['filters_output'])
		&& list($k, $v) = each($sys['filters_output']) )
{
	$str_output = $v($str_output);
}
print $str_output;

#print_r($_POST);

/* end of file */
?>