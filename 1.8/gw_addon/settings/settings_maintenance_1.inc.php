<?php
/**
 * Glossword - glossary compiler (http://glossword.info/)
 * © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: settings_maintenance_1.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

$ar_req_fields = 'message';

$this->ar_msg_topics = array(
	1 => $this->oL->m('1029'),
	2 => $this->oL->m('1030'),
	3 => $this->oL->m('1031'),
	4 => $this->oL->m('1032'),
	5 => $this->oL->m('1033'),
	6 => $this->oL->m('1034'),
);

/* */
if ($this->gw_this['vars']['post'] == '')
{
	/* Default settings */
	$vars['id_topic'] = 1;
	$vars['is_attach'] = 1;
	$vars['message'] = '';
	$vars['is_preview'] = 1;
	/* Not submitted */
	$this->str .= $this->get_form_support($vars, 0, 0, $ar_req_fields);
}
else
{
	/* */
	$arPost =& $this->gw_this['vars']['arPost'];
	/* Fix on/off options */
	$arIsV = array('is_attach');
	for (; list($k, $v) = each($arIsV);)
	{
		$arPost[$v] = isset($arPost[$v]) ? $arPost[$v] : 0;
	}
	/* */
	if ($arPost['is_preview'])
	{
		/* Preview */
		$arPost['is_preview'] = 0;
		$arPost['name'] =  $this->gw_this['vars']['name'];
		$arPost['email'] =  $this->gw_this['vars']['email'];
		$arPost['id_topic'] = $this->gw_this['vars']['arPost']['id_topic'];
		$this->str .= $this->get_form_support_preview($arPost);
	}
	else
	{
		/* Send */
		$v_mailto = 'team@glossword.biz';
		$str_mail_body = '<'.'?xml version="1.0"?'.'>';
		for (; list($k, $v) = each($arPost);)
		{
			$str_mail_body .= '<'.$k .'><![CDATA[ '. $v . ']]></'.$k .'>';
		}
		$str_mail_body .= '</xml>';
		$str_mail_body = htmlspecialchars_ltgt($str_mail_body);
		if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
		{
			$str_mail_body = gw_stripslashes($str_mail_body); 
		}

		$this->oL->getCustom('mail', $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');

		/* Start new messenger */
		$oMail = new tkit_mail('mail_feedback');
		$this->sys['is_debug_mail'] = 0;
		/* Prepage subject */
		$str_subject = '[GW] Glossword report';
		/* Send mail */
		if ($oMail->send(
				$arPost['name'],
				$arPost['email'],
				'Dmitry-Sh',
				$v_mailto,
				$str_subject,
				$oMail->create_message($str_subject, $str_mail_body),
				$this->sys['is_debug_mail']
			))
		{
			$this->str = '<br />'.$this->oL->m('fb_complete');
		}
		else
		{
			$this->str .= sprintf('<br /><span class="red xu">'.$this->oL->m('reason_17').'</span>', $v_mailto);
			$this->str .= '<br /><br /><div class="xt">';
			$this->str .= $str_mail_body;
			$this->str .= '</div>';
		}
	}
}

/* */
function gw_get_cfg()
{
	global $sys, $oDb;
	/* */
	$arSql = $oDb->sqlExec('SELECT version() as v');
	$arInfoA = array(
	'{API}'                  => PHP_SAPI,
	'{DOCUMENT_ROOT}'        => getenv('DOCUMENT_ROOT'),
	'{SCRIPT_FILENAME}'      => getenv('SCRIPT_FILENAME'),
	'{SERVER_SOFTWARE}'      => getenv('SERVER_SOFTWARE'),
	'{GW_REQUEST_URI}'          => preg_replace('/[0-9a-f]{32}/', '', GW_REQUEST_URI),
	'{sys_server}'           => $sys['server_proto'].$sys['server_host'].$sys['server_dir'],
	);
	$arInfoT = array(
	'{expose_php}'           => (int) ini_get("expose_php"),
	'{magic_quotes_gpc}'     => (int) ini_get("magic_quotes_gpc"),
	'{magic_quotes_runtime}' => (int) ini_get("magic_quotes_runtime"),
	'{magic_quotes_sybase}'  => (int) ini_get("magic_quotes_sybase"),
	'{max_execution_time}'   => (int) ini_get("max_execution_time"),
	'{post_max_size}'        => (int) ini_get("post_max_size"),
	'{register_globals}'     => (int) ini_get("register_globals"),
	'{safe_mode}'            => (int) ini_get("safe_mode"),
	'{short_open_tag}'       => (int) ini_get("short_open_tag"),
	'{mbstring.internal_encoding}' => ini_get('mbstring.internal_encoding')
	);
	$str = '';
	$str .= '<line>'.CRLF;
	$str .= '<term>'.$sys['server_host'].'</term>'.CRLF;
	$str .= '<defn>'.CRLF;
	while (list($k, $v) = each($arInfoA))
	{
		$str .= '<abbr lang="'.$k.'">'.$v.'</abbr>'.CRLF;
	}
	while (list($k, $v) = each($arInfoT))
	{
		$str .= '<trns lang="'.$k.'">'.$v.'</trns>'.CRLF;
	}
	if (function_exists('get_loaded_extensions'))
	{
		$ar = get_loaded_extensions();
		sort($ar);
		while(list($k, $v) = each($ar))
		{
			$str .= '<see>'.$v.'</see>'.CRLF;
		}
	}
	$str .= '</defn>'.CRLF;
	$str .= '</line>'.CRLF;
	return $str;
}


?>