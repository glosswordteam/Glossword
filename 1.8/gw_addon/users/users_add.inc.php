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
	die('<!-- $Id: users_add.inc.php 482 2008-06-11 02:34:56Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();


$ar_req_fields = array('login','pass_new','pass_confirm');

/* correct unknown settings */
$ar_user_settings = array('is_show_contact' => 1, 'locale_name' => $this->gw_this['vars']['locale_name'], 'visualtheme' => 'gw_brand', 'location' => '', 'avatar_img' => '', 'is_use_avatar' => 0, 'is_htmled' => '1', 'gmt_offset' => 0, 'date_format' => 'F j, Y, g:i a');
for (; list($k, $v) = each($ar_user_settings);)
{
	if (!isset($arSql['user_settings'][$k])) { $arSql['user_settings'][$k] = $v; }
}
/* Not submitted */
if ($this->gw_this['vars']['post'] == '')
{
	$is_first = 1;
	$arSql['pass_new'] = '';
	$arSql['pass_confirm'] = '';
	$arSql['login'] = kMakeUid('user-', 13, 1);
	$arSql['user_email'] = '';
	$arSql['user_fname'] = '';
	$arSql['user_sname'] = '';
	$arSql['user_perm'] = 'a:0:{}';
	$arSql['is_send_notice'] = 0;
	$arSql['dictionaries'] = array();

	$ar_user_perm = $this->oSess->get_access_names();
	foreach($ar_user_perm as $k => $v)
	{
		$ar_permissions[strtoupper($k)] = $v;
	}
	$arSql['user_perm'] = serialize($ar_permissions);

	$this->str .= $this->get_form_user($arSql, $is_first, 0, $ar_req_fields);
}
else
{
	/* */
	$arPost =& $this->gw_this['vars']['arPost'];

	/* Fix on/off options */
	$arIsV = array('is_show_contact','is_send_notice');
	for (; list($k, $v) = each($arIsV);)
	{
		$arPost[$v]  = isset($arPost[$v]) ? $arPost[$v] : 0;
	}
	$arBroken = array();


	/* protect other's username from changing */
	if (isset($arPost['login']) && !$this->oSess->is('is-users') && $this->gw_this['vars']['w1'] != '')
	{
		unset($arPost['login']);
	}
	/* If someone edits other profile */
	$id_user = ($this->gw_this['vars']['w1'] != '') ? $this->gw_this['vars']['w1'] : $this->oSess->id_user;
#	$ar_user = array('');

	/* compare username with existent */
	if ($arPost['login'])
	{
		$arExistent = $this->oSess->auth_info('', $arPost['login']);
		while (list($k, $arV) = each($arExistent))
		{
			if (isset($arV['id_user']) && ($arV['id_user'] != $id_user))
			{
				$arBroken['login'] = true;
				$this->str .= '<p class="xr"><span class="red">'. $this->oL->m('reason_15') . '</span></p>';
				$isPostError = 1;
			}
		}
	}
	else
	{
		/* Login is required */
		$arBroken['login'] = true;
	}
	/* check e-mail */
	if ($arPost['user_email'])
	{
		$arExistent = $this->oSess->auth_info('', '', $arPost['user_email']);

		while (list($k, $arV) = each($arExistent))
		{
			if (isset($arV['id_user']))
			{
				$arBroken['user_email'] = true;
				$isPostError = 1;
				$this->str .= '<p class="xr"><span class="red">' . $this->oL->m('reason_18') . '</span></p>';
			}
		}
	}
	/* parse password change */
	$is_pass_changed = 0;
	if (!empty($arPost['pass_new']) || !empty($arPost['pass_confirm']))
	{
		if ( $arPost['pass_new'] != $arPost['pass_confirm'] )
		{
			$arBroken['pass_new'] = $arBroken['pass_confirm'] = true;
			$this->str .= '<p class="xr"><span class="red">'. $this->oL->m('reason_14') . '</span></p>';
			$isPostError = 1;
		}
		else if ( strlen($arPost['pass_new']) > 32 )
		{
			$arBroken['pass_new'] = $arBroken['pass_confirm'] = true;
			$this->str .= '<p class="xr"><span class="red">'. $this->oL->m('reason_19') . '</span></p>';
			$isPostError = 1;
		}
		else if ($this->gw_this['vars']['w1'] == $this->oSess->id_user)
		{
			/* password changed by current user */
			$is_pass_changed = 1;
		}
	}
	/* Password cannot be null */
	if (!$this->ar_state['is_profile'] && !$arPost['pass_new'])
	{
		$isPostError = 1;
		$arBroken['pass_new'] = $arBroken['pass_confirm'] = true;
	}

	/* New login correct, current password is ok, both passwords is correct */
	if (sizeof($arBroken) == 0)
	{
		$isPostError = 0;
	}
	else
	{
		$isPostError = 1;

		$arPost['pass_new'] = '';
		$arPost['pass_confirm'] = '';
		/* */
		if (empty($arPost['is_permissions']))
		{
			$arPost['user_perm'] = serialize(array());
		}
		else
		{
			foreach($arPost['is_permissions'] as $k => $v)
			{
				$ar_permissions[strtoupper($v)] = 1;
			}
			$arPost['user_perm'] = serialize($ar_permissions);
		}
#prn_r( $arPost );
		$this->str .= $this->get_form_user($arPost, 0, 0, $ar_req_fields);
	}

	/* final update */
	if (!$isPostError)
	{
#$this->sys['isDebugQ'] = 1;

		$q1 = $ar_q = array();
		if (isset($arPost['username']))
		{
			$q1['login'] = $arPost['username'];
		}
		if (isset($arPost['pass_confirm']) && ($arPost['pass_confirm'] != ''))
		{
			$q1['password'] = md5($arPost['pass_confirm']);
		}

		/* */
		if ($arPost['is_send_notice'])
		{
			$this->oL->getCustom('mail', $this->sys['locale_name'], 'join');

			/* Start new messenger */
			$oMail = new tkit_mail('mail_feedback');
			$this->sys['is_debug_mail'] = 0;
			/* Turn debug mode on for localhosts */
			if (strpos($this->sys['server_host'], '127.0.0') !== false)
			{
				$this->sys['is_debug_mail'] = 1;
			}
			/* Prepage subject */
			$str_subject = $this->oL->m('2_page_user_register');
			/* Prepage mail body */
			$url_to_login = $this->sys['server_url'].'/'.$this->sys['file_login'];
			$str_body = str_replace(
				array('{USERNAME}','{SITENAME}','{LOGIN_URL}','{CONTACT_EMAIL}','{LOGIN}','{PASSWORD}','{U_LOGIN}','{U_PASSWORD}'),
				array($arPost['user_fname'].' '.$arPost['user_sname'], strip_tags($this->sys['site_name']),
					$this->oHtml->a($url_to_login, $url_to_login),
					$this->sys['y_email'],
					$this->oL->m('login'),
					$this->oL->m('password'),
					$arPost['login'],
					$arPost['pass_confirm']
				),
				$this->oL->m('mail_newuser')
			);
			/* Send mail */
			$oMail->send(
				$this->sys['site_name'],
				$this->sys['site_email_from'],
				$arPost['user_fname'].' '.$arPost['user_sname'],
				$arPost['user_email'],
				$this->sys['mail_subject_prefix'].' '.$str_subject,
				$oMail->create_message($str_subject, $str_body),
				$this->sys['is_debug_mail']
			);
		}

		/* Assigned dictionaries */
		$arPost['user_settings']['dictionaries'] = isset($arPost['dictionaries']) ? $arPost['dictionaries'] : array();

		/* Selected permissions */
		$arPost['is_permissions'] = isset($arPost['is_permissions']) ? $arPost['is_permissions'] : array();
		foreach($arPost['is_permissions'] as $k => $v)
		{
			$arPost['is_permissions'][strtoupper($v)] = 1;
			unset($arPost['is_permissions'][$k]);
		}

		$q1['id_user'] = $this->oDb->MaxId($this->oSess->db_table_users, 'id_user');
		$q1['is_active'] = isset($arPost['is_active']) ? $arPost['is_active'] : 1;

		$q1['login'] = $arPost['login'];
		$q1['is_show_contact'] = 1;
		$q1['date_reg'] = $this->sys['time_now_gmt_unix'];
		$q1['user_email'] = $arPost['user_email'];
		$q1['user_perm'] = serialize($arPost['is_permissions']);
		$q1['user_fname'] = $arPost['user_fname'];
		$q1['user_sname'] = $arPost['user_sname'];
#		$q1['user_settings'] = array_merge_clobber($ar_user['user_settings'], $arPost['user_settings']);
		$q1['user_settings'] = serialize($arPost['user_settings']);

		$ar_q[] = gw_sql_insert($q1, $this->oSess->db_table_users, 'id_user = "'.$id_user.'"');
#prn_r( $q1 );
#prn_r( $arPost );

		/* Assign dictionaries map */
		for (; list($k, $v) = each($arPost['user_settings']['dictionaries']);)
		{
			$q2 = array();
			$q2['user_id'] = $q1['id_user'];
			$q2['dict_id'] = $k;
			$ar_q[] = gw_sql_replace($q2, TBL_MAP_USER_DICT);
		}

		/* Redirect */
		$url = GW_ACTION.'='.GW_A_BROWSE .'&'. GW_TARGET.'='.GW_T_USERS;
		if ($this->gw_this['vars']['w1'] != $this->oSess->id_user)
		{
			$url .= '&w1=' . $this->gw_this['vars']['w1'];
		}
		$this->str .= postQuery($ar_q, $url, $this->sys['isDebugQ'], 0);
	}

	unset($arPost['is_send_notice']);
}


?>