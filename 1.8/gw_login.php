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
/**
 *  Login process.
 *  $Id: gw_login.php 510 2008-06-26 21:53:45Z glossword_team $
 */
define('IN_GW', 1);
/* Turn on any warnings */
error_reporting(E_ALL);
/* ------------------------------------------------------- */
/* Load configuration */
$sys['is_prepend'] = 1;
include_once('db_config.php');
include_once($sys['path_include_local'] . '/config.inc.php');

if (!defined('GW_DB_HOST'))
{
	print "sys: Can't find config.inc.php GW_DB_HOST is not defined.";
	exit;
}
/* */
include_once( $sys['path_include'] . '/class.forms.php');
include_once( $sys['path_include'] . '/func.admin.inc.php');
/* --------------------------------------------------------
 * Database -> Query storage
 * ----------------------------------------------------- */
$oSqlQ = new $sys['class_queries'];
$oSqlQ->set_suffix('-'.$sys['db_type'].'410');
/* ------------------------------------------------------- */
/* Append system settings */
$sys = array_merge($sys, getSettings());
/* Fill empty settings */
$sys['visualtheme'] = isset($sys['visualtheme']) ? $sys['visualtheme'] : 'gw_admin';
/* Auto time for server  */
$sys['time_now'] = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
$sys['time_now_gmt_unix'] = $sys['time_now'] - @date('Z');
$tmps = array();
/* --------------------------------------------------------
 * mod_rewrite configuration
 * ------------------------------------------------------- */
$sys['is_mod_rewrite'] = 0;
$oHtml = new gw_html;
$oHtml->setVar('is_htmlspecialchars', 0);
$oHtml->setVar('is_mod_rewrite', $sys['is_mod_rewrite']);
$oHtml->is_append_sid = 0;
$oHtml->id_sess_name = GW_SID;
/* --------------------------------------------------------
 * Register global variables
 * ----------------------------------------------------- */
$gw_this['vars'] = $oGlobals->register(array(
	GW_ACTION,GW_ID_DICT,'p','r',GW_TARGET,'arPost','visualtheme',
	'id','cookie','post','k','uri',
	GW_LANG_I, GW_LANG_C, GW_SID,'login','q',
	'gw_visualtheme', 'gw_is_save_visualtheme', 'gw_'.GW_LANG_I, 'gw_is_save_'.GW_LANG_I,
));
$gw_this['cookie'] = array();
if (isset($gw_this['vars']['cookie']))
{
	$gw_this['cookie'] = $gw_this['vars']['_cookie'];
	unset($gw_this['vars']['_cookie']);
}
$oGlobals->do_default($gw_this['vars'][GW_LANG_C], $gw_this['vars'][GW_LANG_I]);
$oGlobals->do_default($gw_this['vars']['lang_enc'], $sys['locale_name']);
$oGlobals->do_default($gw_this['vars']['uri'], '');
$sys['uri'] =& $gw_this['vars']['uri'];
/* */
$gw_this['vars']['visualtheme'] = 'gw_admin';

/* --------------------------------------------------------
 * Set interface language, last modified: 03 Sep 22007
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
$gw_this['vars']['is']['save_'.GW_LANG_I] =
	(isset($gw_this['cookie']['gw_is_save_'.GW_LANG_I]) && $gw_this['cookie']['gw_is_save_'.GW_LANG_I] == 1)
	? 1 : 0;
$gw_this['vars'][GW_LANG_I] = preg_replace("/-([a-z0-9])+$/", '', $gw_this['vars'][GW_LANG_I]);
$gw_this['vars']['lang_enc'] = preg_replace("/^([a-z0-9])+-/", '', $gw_this['vars']['lang_enc']);
$gw_this['vars']['locale_name'] = $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'];

if (isset($gw_this['vars']['arPost']['locale_name']))
{
	$gw_this['vars'][GW_LANG_I] = preg_replace("/-([a-z0-9])+$/", '', $gw_this['vars']['arPost']['locale_name'] );
	$gw_this['vars']['lang_enc'] = preg_replace("/^([a-z0-9])+-/", '', $gw_this['vars']['lang_enc'] );
	$gw_this['vars']['locale_name'] = $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'];
	/* 1.8.8: Save interface language selection */
	setcookie('gw_'.GW_LANG_I.$sys['token'], $gw_this['vars'][GW_LANG_I], $sys['time_now']+$sys['time_sec_y'], $sys['server_dir'] );
	setcookie('gw_is_save_'.GW_LANG_I.$sys['token'], 1,  $sys['time_now']+$sys['time_sec_y'], $sys['server_dir'] );
}


/* Translation engine */
$oL = new gwtk;
$oL->setHomeDir($sys['path_locale']);
$oL->setLocale($gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc']);
$gw_this['vars']['ar_languages'] = $oL->getLanguages();
$oL->getCustom('admin', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('err', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
$oL->getCustom('mail', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
/* Redirect URL */
$url_admin = $sys['server_proto'] . $sys['server_host'] .  $sys['page_admin'];
#print $url_admin;
/* --------------------------------------------------------
 * Template engine
 * ------------------------------------------------------- */
$oTpl = new $sys['class_tpl'];
$oTpl->init('gw_admin');
$oTpl->is_tpl_show_names = $sys['is_tpl_show_names'];
$oTpl->addVal( 'v:path_tpl',         $sys['server_dir'] . '/' . $sys['path_tpl'] . '/common');
$oTpl->addVal( 'v:language',         $oL->languagelist("0") );
$oTpl->addVal( 'v:text_direction',   $oL->languagelist("1") );
$oTpl->addVal( 'v:charset',          $oL->languagelist("2") );
$oTpl->addVal( 'v:top_right',        $oL->m('securenote') );
$oTpl->addVal( 'href:home',          $sys['server_dir'].'/index.php' );
$oTpl->addVal( 'url:title_page',     $oHtml->a($sys['page_index'], $oL->m('3_tomain')) );
$oTpl->addVal( 'l:top_of_page',      $oL->m('3_top') );
$oTpl->addVal( 'v:glossword_version',$sys['version']);
$oTpl->addVal( 'v:html_title',       $sys['site_name'] . $sys['txt_sep_htmltitle'] . $oL->m('2_login') );
$oTpl->addVal( 'v:path_css_script',  $sys['path_css_script'] );
$oTpl->addVal( 'v:path_img',         $sys['path_img'] );
$oTpl->addVal( 'v:site_name',        $sys['site_name']);
$oTpl->addVal( 'v:path_css',         $sys['server_dir'].'/'.$sys['path_temporary'].'/t/'.$gw_this['vars']['visualtheme'] );
$oTpl->addVal( 'v:lang_i_name',      GW_LANG_I );
$oTpl->addVal( 'v:lang_i_value',     $gw_this['vars'][GW_LANG_I] );
/* I request you to retain the copyright notice! */
$oTpl->addVal( 'v:copyright',        $sys['str_branding'] );

$sys['css_align_right'] = 'right';
$sys['css_align_left'] = 'left';
if ($oL->languagelist('1') == 'rtl')
{
	$sys['css_align_right'] = 'left';
	$sys['css_align_left'] = 'right';
}
$oTpl->addVal( 'v:css_align_right', $sys['css_align_right']);
$oTpl->addVal( 'v:css_align_left',  $sys['css_align_left']);

/* 24 jan 2006: load theme setting from database */
$ar_theme = gw_get_theme($gw_this['vars']['visualtheme']);
$oTpl->addVal( 'v:visualtheme', str_replace('_', '-', $gw_this['vars']['visualtheme']) );

$tmp['ar_req'] = array('user_name', 'user_pass');
/* */
$gw_this['str_status'] = '';

/* Start session */
$oSess = new gw_session_1_9;
$oSess->oDb =& $oDb;
$oSess->oL =& $oL;
$oSess->sys =& $sys;
$oSess->load_settings();
/* Get Session ID */
$id_sess = isset($gw_this['cookie'][GW_SID])
		? $gw_this['cookie'][GW_SID]
		: (isset($gw_this[GW_SID]) ? $gw_this[GW_SID] : 0);
/* */
$str_login = '';
$vars = array();
/* */
function gw_login_form($ar_vars, $ar_broken = array(), $ar_req = array())
{
	global $oL, $oTpl, $oHtml, $oFunc, $oSess;
	global $sys, $gw_this, $ar_theme;

	$oForm = new gwForms();
	$oForm->action = $sys['page_login'];
	$oForm->submitok = $oL->m('3_login');
	$oForm->isButtonCancel = 0;
	$oForm->Set('charset', $sys['internal_encoding']);

	$strForm = '';
	$v_class_1 = 'td1';
	$v_class_2 = 'td2';

	/* Mark fields as "REQUIRED" and make error messages */
	$ar_req = array_flip($ar_req);
	while (is_array($ar_vars) && list($k, $v) = each($ar_vars) )
	{
		$ar_req_m[$k] = isset($ar_req[$k]) ? '&#160;<span class="red"><strong>*</strong></span>' : '';
		$ar_broken_m[$k] = isset($ar_broken[$k]) ? ' <span class="red"><strong>' . $oL->m('reason_9') .'</strong></span>' : '';
	}
	$oForm->formwidth = '100%';
	$oForm->formbgcolor = $ar_theme['color_2'];
	$oForm->formbordercolor = $ar_theme['color_4'];
	$oForm->setTag('input', 'style', 'width:98%');
	/* HTML-code */
	if ($gw_this['vars'][GW_ACTION] == 'lostpass')
	{
		/* "Lost password" page */
		$str_formtitle = $oL->m('3_password_lost');
		$oForm->submitok = $oL->m('3_password_send');
		$oForm->strNotes = '<span class="xt">'.
					$oHtml->a( $oSess->url_login . '?' . GW_TARGET . '=' . GW_T_USERS . '&' .  GW_ACTION . '=login',
					'&gt; '.$oL->m('2_login')) .
					'</span>';
		$strForm .= getFormTitleNav($str_formtitle);
		$strForm .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$strForm .= '<table class="gw2TableFieldset" width="100%"><tbody>';
		$strForm .= '<tr>'.
					'<td style="width:35%" class="'.$v_class_1.'">' . $oL->m('login') . ':' . $ar_req_m['user_name'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_m['user_name'] . $oForm->field('input', 'arPost[user_name]', textcodetoform($ar_vars['user_name']), 16) . '</td>'.
					'</tr>';
		$strForm .= '</tbody></table>';
		$strForm .= '</fieldset>';
		$strForm .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$strForm .= '<table class="gw2TableFieldset" width="100%"><tbody>';
		$strForm .= '<tr>'.
					'<td style="width:35%" class="'.$v_class_1.'">' . $oL->m('y_email') . ':' . $ar_req_m['user_email'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_m['user_email'] . $oForm->field('input', 'arPost[user_email]', textcodetoform($ar_vars['user_email']), 64) . '</td>'.
				'</tr>';
		$strForm .= '</tbody></table>';
		$strForm .= '</fieldset>';
		$strForm .= $oForm->field('hidden', GW_ACTION, 'lostpass');
		$gw_this['str_status'] = $oL->m('1349');
	}
	else
	{
		/* "log in" page */
		$oForm->strNotes = '<span class="xt">'.
					$oHtml->a( $oSess->url_login . '?' . GW_TARGET . '=' . GW_T_USERS . '&' .  GW_ACTION . '=lostpass',
					'&gt; '.$oL->m('3_password_lost')) .
					'</span>';
		$str_formtitle = $oL->m('2_login');

		$oForm->setTag('input', 'maxlength',  '32');
		$strForm .= getFormTitleNav($str_formtitle);
		$strForm .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$strForm .= '<table class="gw2TableFieldset" width="100%">';
		$strForm .= '<tbody><tr>'.
					'<td style="width:35%" class="'.$v_class_1.'"><label for="arPost_user_name_">' . $oL->m('login') . ':' . $ar_req_m['user_name'] . '</label></td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_m['user_name'] . $oForm->field('input', 'arPost[user_name]', textcodetoform($ar_vars['user_name']), 16) . '</td>'.
					'</tr>';
		$strForm .= '<tr>'.
					'<td class="'.$v_class_1.'"><label for="arPost_user_pass_">' . $oL->m('password') . ':' . $ar_req_m['user_pass'] . '</label></td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_m['user_pass'] . $oForm->field('pass', 'arPost[user_pass]', textcodetoform($ar_vars['user_pass']), 16) . '</td>'.
					'</tr>';
		$oForm->setTag('select', 'class', 'input');
		$oForm->setTag('select', 'style', 'width:98%');
		$strForm .= '<tr>'.
					'<td class="'.$v_class_1.'"><label for="arPost_locale_name_">' . $oL->m('lang') . ':</label></td>'.
					'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPost[locale_name]', $ar_vars['locale_name'], 0, $gw_this['vars']['ar_languages']) . '</td>'.
					'</tr>';
		$strForm .= '</tbody></table>';
		$strForm .= '</fieldset>';
		$strForm .= $oForm->field( 'hidden', GW_ACTION, 'login' );
	}
	$strForm .= $oForm->field( 'hidden', 'uri', $gw_this['vars']['uri'] );
	$strForm .= $oForm->field( 'hidden', GW_SID, $oSess->id_sess );
	return $oForm->Output($strForm);
}

/* Activation key */
if ($gw_this['vars']['k'])
{
	/* Select key */
	$sql = 'SELECT u.id_user, u.login, u.user_email, CONCAT(u.user_fname," ",u.user_sname) AS user_name ';
	$sql .= 'FROM `'.$oSess->db_table_users.'` AS u, `'.$sys['tbl_prefix'].'auth_restore` AS a  ';
	$sql .= 'WHERE a.auth_key = "'.gw_text_sql($gw_this['vars']['k']).'" ';
	$sql .= 'AND a.id_user = u.id_user ';
	$sql .= 'LIMIT 1 ';
	$arSql = $oDb->sqlExec($sql);
	$arSql = isset($arSql[0]) ? $arSql[0] : array();

	if (empty($arSql))
	{
		/* No key found */
		$gw_this['str_status'] = $oL->m('reason_23');
	}
	else
	{
		/* Remove key */
		$sql = 'DELETE FROM `'.$sys['tbl_prefix'].'auth_restore` ';
		$sql .= 'WHERE auth_key = "'.gw_text_sql($gw_this['vars']['k']).'" ';
		$sql .= 'LIMIT 1 ';
		$oDb->sqlExec($sql);

		/* Create a new password */
		$str_password = kMakeUid('', 8);
		$sql = gw_sql_update(array('password' => md5($str_password)), $oSess->db_table_users, 'id_user = "'.$arSql['id_user'].'"');
		$oDb->sqlExec($sql);

		$sys['is_debug_mail'] = 0;
		/* Turn debug mode on for localhosts */
		if (strpos($sys['server_host'], '127.0.0') !== false)
		{
			$sys['is_debug_mail'] = 1;
		}
		/* Start new messenger */
		$oMail = new tkit_mail('mail_password');
		/* Prepage subject */
		$str_subject = $oL->m('2_page_22');
		/* Prepage mail body */
		$url_to_login = $sys['server_url'].'/'.$sys['file_login'];
		$str_body = str_replace(
			array('{USERNAME}','{SITENAME}','{LOGIN_URL}','{CONTACT_EMAIL}','{LOGIN}','{PASSWORD}','{U_LOGIN}','{U_PASSWORD}'),
			array($arSql['user_name'], strip_tags($sys['site_name']),
				$oHtml->a($url_to_login, $url_to_login),
				$sys['y_email'],
				$oL->m('login'),
				$oL->m('password'),
				$arSql['login'],
				$str_password
			),
			$oL->m('mail_newuser')
		);
		/* Send mail */
		if (!$oMail->send(
			$sys['site_name'],
			$sys['site_email_from'],
			$arSql['user_name'],
			$arSql['user_email'],
			$sys['mail_subject_prefix'].' '.$str_subject,
			$oMail->create_message($str_subject, $str_body),
			$sys['is_debug_mail'])
			)
		{
			$gw_this['str_status'] .= $oL->m('1079').': '.$arSql['user_name'] . ' &lt;'.$arSql['user_email'].'&gt;';
			#$gw_this['str_status'] .= '<textarea cols="40" rows="10" class="input">'.$str_body.'</textarea>';
		}
		else
		{
			$gw_this['str_status'] .= $oL->m('reason_22');
		}
	}
}
/* */
if ($gw_this['vars']['post'] == '')
{
	/* Start new session */
#	$oSess->sess_init($id_sess);

	$vars['user_name'] = '';
	$vars['user_pass'] = '';
	$vars['user_email'] = '';
	$vars['locale_name'] = $gw_this['vars']['locale_name'];
	$str_login .= gw_login_form( $vars );
}
else
{
	if ($gw_this['vars'][GW_ACTION] == 'lostpass')
	{
		/* Reset password */
		$arSql = array();
		if ($gw_this['vars']['arPost']['user_name'] || $gw_this['vars']['arPost']['user_email'])
		{
			$sql = 'SELECT id_user, user_email, CONCAT(user_fname," ",user_sname) as user_name, is_active ';
			$sql .= 'FROM `'.$oSess->db_table_users.'` ';
			if ($gw_this['vars']['arPost']['user_name'])
			{
				$sql .= 'WHERE login = "'.gw_text_sql($gw_this['vars']['arPost']['user_name']).'" ';
			}
			else if ($gw_this['vars']['arPost']['user_email'])
			{
				$sql .= 'WHERE user_email = "'.gw_text_sql($gw_this['vars']['arPost']['user_email']).'" ';
			}
			$sql .= 'LIMIT 1 ';
			$arSql = $oDb->sqlExec($sql);
			$arSql = isset($arSql[0]) ? $arSql[0] : array();
		}
		if (empty($arSql))
		{
			/* No such user */
			$str_login .= gw_login_form( $gw_this['vars']['arPost'] );
			$gw_this['str_status'] = $oL->m('reason_20');
		}
		else
		{
			/* User found */
			/* Check for status */
			if ($arSql['is_active'] == '1')
			{
				/* Remove old activation keys (if exists) */
				$sql = 'DELETE ';
				$sql .= 'FROM `'. $sys['tbl_prefix'].'auth_restore` ';
				$sql .= 'WHERE `id_user` = "'.$arSql['id_user'].'" ';
				$oDb->sqlExec($sql);
				/* Create new activation key */
				$int_act_key = kMakeUid('', 9, 3);
				$sql = gw_sql_insert(array('id_user' => $arSql['id_user'], 'auth_key' => $int_act_key, 'date_created' => $sys['time_now_gmt_unix']), $sys['tbl_prefix'].'auth_restore');
				$oDb->sqlExec($sql);

				$sys['is_debug_mail'] = 0;
				/* Turn debug mode on for localhosts */
				if (strpos($sys['server_host'], '127.0.0') !== false)
				{
					$sys['is_debug_mail'] = 1;
				}
				/* Start new messenger */
				$oMail = new tkit_mail('mail_password');
				/* Prepage subject */
				$str_subject = $oL->m('2_page_22');
				/* Prepage mail body */
				$url_to_key = $sys['server_url'].'/'.$sys['file_login'].'?k='.$int_act_key;
				$str_body = str_replace(
					array('{USERNAME}','{SITENAME}','{U_ACTIVATE}'),
					array($arSql['user_name'], strip_tags($sys['site_name']),
					$oHtml->a($url_to_key, $url_to_key)
					),
					$oL->m('mail_newpass')
				);
				/* 1.8.8 */
				$str_body .= '<br /><div style="font-size:70%">'.REMOTE_IP.'<br />'.REMOTE_UA.'</div>';

				/* Send mail */
				if (!$oMail->send(
					$sys['site_name'],
					$sys['site_email_from'],
					$arSql['user_name'],
					$arSql['user_email'],
					$sys['mail_subject_prefix'].' '.$str_subject,
					$oMail->create_message($str_subject, $str_body),
					$sys['is_debug_mail'])
					)
				{
					$gw_this['str_status'] .= $oL->m('1079').': '.$arSql['user_name'] . ' &lt;'.$arSql['user_email'].'&gt;';
#					$gw_this['str_status'] .= '<textarea cols="40" rows="10" class="input">'.$str_body.'</textarea>';
				}
				else
				{
					$gw_this['str_status'] .= $oL->m('reason_22');
				}
			}
			else
			{
				$gw_this['str_status'] = $oL->m('reason_16');
			}
		}
	}
	else
	{
		/* Try to log in */
		$sql = 'SELECT u.* ';
		$sql .= 'FROM `'.$oSess->db_table_users.'` AS u ';
		$sql .= 'WHERE u.login = "'.gw_text_sql($gw_this['vars']['arPost']['user_name']).'" ';
		$sql .= 'AND u.password = "'.md5(gw_text_sql($gw_this['vars']['arPost']['user_pass'])).'" ';
		$sql .= 'LIMIT 1 ';
		$arSql = $oDb->sqlExec($sql);
		$arSql = isset($arSql[0]) ? $arSql[0] : array();
		if (!empty($arSql))
		{
			/* User found */

			/* Check user status */
			if ($arSql['is_active'] == '1')
			{
				/* Account is active */
				/* Load user settings */
				$oSess->is_remember = 1;
				$oSess->user_start($arSql['id_user'], 'merge');
				/* Remove old sessions for the user (if exists) */
				if (!$arSql['is_multiple'])
				{
					$sql = 'DELETE ';
					$sql .= 'FROM `'.$oSess->db_table_sessions.'` ';
					$sql .= 'WHERE `id_user` = "'.$arSql['id_user'].'" ';
					$oDb->sqlExec($sql);
				}
				/* Add session into databse */
				$oSess->sess_insert($arSql['id_user']);
				/* Update last user activity */
				$oSess->user_set_val('date_login', $oSess->time_now_gmt_unix);
				/* Add cookies */
				setcookie( $oSess->sid.$sys['token'], $oSess->id_sess, $sys['time_now'] + ($oSess->int_timeout * 2), $sys['server_dir'] );
				setcookie( $oSess->sid.'r'.$sys['token'], 1, $sys['time_now'] + ($oSess->int_timeout * 2), $sys['server_dir'] );
				/* Close Session class */
				$oSess->sess_close();
				/* 1.8.8 restore URL */
				$uri = '';
				if ($gw_this['vars']['uri'])
				{
					$uri .= '?'.base64_decode($gw_this['vars']['uri']);
				}
				/* redirect to admin page */
				gwtk_header( $sys['server_url'].'/'.$sys['file_admin'].$uri );
				exit;
			}
			else
			{
				/* Accound disabled */
				$gw_this['vars']['arPost']['locale_name'] = $gw_this['vars']['locale_name'];
				$str_login .= gw_login_form( $gw_this['vars']['arPost'] );
				$gw_this['str_status'] = $oL->m('reason_16');
			}
		}
		else
		{
			/* No such user */
			$gw_this['vars']['arPost']['locale_name'] = $gw_this['vars']['locale_name'];
			$str_login .= gw_login_form( $gw_this['vars']['arPost'] );
			$gw_this['str_status'] = $oL->m('reason_20');
		}
	}
}
$oTpl->addVal( 'non::objLogin', $str_login );

/* Current status */
$oTpl->addVal('non::objStatus', $gw_this['str_status']);
/* Render page */
$oTpl->set_tpl(GW_TPL_LOGIN);

/* Content-type for XHTML 1.1 */
#$sys['content_type'] = preg_match("/application\/xhtml\+xml/", $_SERVER['HTTP_ACCEPT']) ? 'application/xhtml+xml' : $sys['content_type'];
$oTpl->addVal( 'v:content_type', $sys['content_type'] );
$oTpl->parse();

$oHdr->add('Expires: ' . date("D, d M Y H:i:s", $sys['time_now_gmt_unix']) . ' GMT');
$oHdr->add('Last-Modified: ' . date("D, d M Y H:i:s", $sys['time_now_gmt_unix']) . ' GMT');
$oHdr->add('Cache-Control: no-cache, must-revalidate');
$oHdr->add('Pragma: no-cache');
$oHdr->add('Content-Type: '.$sys['content_type'].'; charset='.$oL->languagelist('2'));
$oHdr->output();

print $oTpl->output();
/* Close session */
#$oSess->user_close();
/* end of file */
?>