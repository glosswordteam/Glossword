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
if (!defined('IN_GW'))
{
	die('<!-- $Id: users_edit.inc.php 531 2008-07-09 19:20:16Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* Load user settings */
if ($this->sys['is_profile_owner'])
{
	$arSql = $this->oSess->user_get();
}
else
{
	$arSql = $this->oSess->user_load_values($this->gw_this['vars']['w1']);
	/* No such user */
	if (empty($arSql))
	{
		$this->str .= '<p>'.$this->oL->m('1297').'</p>';
		return false;
	}
}
$ar_req_fields = array('login');

/* Users without permissions can't edit "login" field */
if (!$this->oSess->is('is-users'))
{
	$ar_req_fields = array();
}

/* correct unknown settings */
$ar_user_settings = array('is_dst' => @date('I'), 'locale_name' => $this->gw_this['vars']['locale_name'], 'visualtheme' => 'gw_brand', 'location' => '', 'avatar_img' => '', 'is_use_avatar' => 0, 'is_htmled' => '1', 'gmt_offset' => 0, 'date_format' => 'F j, Y, g:i a');
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

	$arSql['dictionaries'] = $arSql['user_settings']['dictionaries'];

	/* Removing */
	if ($this->gw_this['vars']['remove'])
	{
		/* Keep guest user */
		if ($this->gw_this['vars']['w1'] == 1)
		{
			$this->str .= $this->_get_nav();
			$this->str .= '<div class="xt">'.$this->oL->m('1293').'</div>';
			return;
		}
		/* Change heading */
		$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
			': '. $this->oL->m('3_remove');

		$msg = '&quot;'.$arSql['login'].' '.$arSql['user_email'].'&quot;';

		$oConfirm = new gwConfirmWindow;
		$oConfirm->action = $this->sys['page_admin'];
		$oConfirm->submitok = $this->oL->m('3_remove');
		$oConfirm->submitcancel = $this->oL->m('3_cancel');
		$oConfirm->formbgcolor = $this->ar_theme['color_2'];
		$oConfirm->formbordercolor = $this->ar_theme['color_4'];
		$oConfirm->formbordercolorL = $this->ar_theme['color_1'];
		$oConfirm->setQuestion('<p class="xr"><span class="red"><strong>' . $this->oL->m('9_remove') .
									'</strong></span></p><p class="xt"><span class="gray">'. $this->oL->m('3_remove').
									': </span>'.$msg.'</p>');
		$oConfirm->tAlign = 'center';
		$oConfirm->formwidth = '400';
		$oConfirm->setField( 'hidden', 'w1', $this->gw_this['vars']['w1'] );
		$oConfirm->setField( 'hidden', GW_ACTION, GW_A_REMOVE );
		$oConfirm->setField( 'hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET] );
		$oConfirm->setField( 'hidden', $this->oSess->sid, $this->oSess->id_sess );
		$this->str .= $oConfirm->Form();

		return;
	}

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

	/* If someone edits other profile */
	$id_user = ($this->gw_this['vars']['w1'] != '') ? $this->gw_this['vars']['w1'] : $this->oSess->id_user;
	$ar_user = $this->oSess->user_load_values($id_user, 'return');

#prn_r( $ar_user, 'user_load_values' );

	/* compare changed username with existent */
	if (isset($arPost['login']))
	{
		$arExistent = $this->oSess->auth_info('', $arPost['login']);
		while (list($k, $arV) = each($arExistent))
		{
			if (isset($arV['id_user']) && ($arV['id_user'] != $id_user))
			{
				$arBroken['login'] = true;
				$this->str .= '<p class="xr"><span class="red">'. $this->oL->m('reason_15') . '</span></p>';
			}
		}
		if (!$arPost['login'])
		{
			$arBroken['login'] = true;
		}
	}
	/* check e-mail */
	if (isset($arPost['user_email']) && $arPost['user_email'])
	{
		$arExistent = $this->oSess->auth_info('', '', $arPost['user_email']);
		while (list($k, $arV) = each($arExistent))
		{
			if (isset($arV['id_user']) && ($arV['id_user'] != $id_user))
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
	/* New login correct, current password is ok, both passwords is correct */
	if (sizeof($arBroken) == 0)
	{
		$isPostError = 0;
	}
	else
	{
		$isPostError = 1;
		/* Admin changes profile of another user */
		if ($this->sys['is_profile_owner'])
		{
			$arPost['dictionaries'] = $this->oSess->user_get('dictionaries');
		}
		$arSql['pass_new'] = '';
		$arSql['pass_confirm'] = '';

#		$arPost = array_merge_clobber($arPost, $arSql);
#		$arSql = $this->oSess->user_load_values($this->gw_this['vars']['w1']);

		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form_user($arSql, 0, $arBroken, $ar_req_fields);
	}
	$file_location = '';
	
	if (isset($this->gw_this['vars']['_files']['file_location']))
	{
		$file_location = $this->gw_this['vars']['_files']['file_location'];
	}
	/* */
	if (!empty($file_location))
	{
		$avatar_file = isset($file_location['tmp_name']) ? $file_location['tmp_name'] : '';
		if ( $avatar_file ) {
			$ar_img_size = getimagesize($avatar_file);
			$file_target = urlencode($this->sys['time_now'].'_'.$file_location['name']);
			/* Create directory */
			$this->oFunc->file_put_contents($this->sys['path_temporary'].'/a/'.$file_target, '');
			if (is_uploaded_file($avatar_file)
				&& move_uploaded_file($avatar_file, $this->sys['path_temporary'].'/a/'.$file_target)
				)
			{
				/* remove old avatar, image could not exist */
				if (isset($ar_user['user_settings']['avatar_img']) && $ar_user['user_settings']['avatar_img'] != '')
				{
					@unlink($sys['path_temporary'].'/a/'.$ar_user['user_settings']['avatar_img']);
				}
				/* resize image: source path, target path, max x, lib, debug */
				if (($ar_img_size[0] > $this->sys['avatar_max_x'])
				  || ($ar_img_size[1] > $this->sys['avatar_max_y']))
				{
					include_once( $this->sys['path_include'] . '/func.img.inc.php' );
					gw_image_resize($this->sys['path_temporary'].'/a/'.$file_target, $this->sys['path_temporary'].'/a/'.$file_target, $this->sys['avatar_max_x'], 'gd2', 0);
					$ar_img_size = getimagesize($this->sys['path_temporary'].'/a/'.$file_target);
				}
				$arPost['user_settings']['avatar_img_x'] = $ar_img_size[0];
				$arPost['user_settings']['avatar_img_y'] = $ar_img_size[1];
				$arPost['user_settings']['avatar_img'] = $file_target;
			}
		}
	}
	/* final update */
	if (!$isPostError)
	{
#$this->sys['isDebugQ'] = 1;

		$q1 = $ar_q = array();
		if (isset($arPost['pass_confirm']) && ($arPost['pass_confirm'] != ''))
		{
			$q1['password'] = md5($arPost['pass_confirm']);
		}
		/* Must be 1 */
		$q1['is_active'] = isset($arPost['is_active']) ? $arPost['is_active'] : 1;
		
		$q1['is_multiple'] = isset($arPost['is_multiple']) ? $arPost['is_multiple'] : 0;

		/* Fix on/off options for user  */
		$ar_user_settings_on_off = array('is_dst', 'is_use_avatar', 'is_htmled');
		for (; list($k, $v) = each($ar_user_settings_on_off);)
		{
			if (!isset($arPost['user_settings'][$v])) { $arPost['user_settings'][$v] = 0; }
		}

		$q1['is_show_contact'] = $arPost['is_show_contact'];

		/* Login */
		if ($this->oSess->is('is-users') || $this->oSess->is('is-login'))
		{
			$q1['login'] = $arPost['login'];
		}
		/* E-mail */
		if ($this->oSess->is('is-users') || $this->oSess->is('is-email'))
		{
			$q1['user_email'] = $arPost['user_email'];
		}

		$q1['user_fname'] = $arPost['user_fname'];
		$q1['user_sname'] = $arPost['user_sname'];

		/* Change interface language */
		if ($this->sys['is_profile_owner'])
		{
			$this->gw_this['vars'][GW_LANG_I] = preg_replace("/-([a-z0-9])+$/", '', $arPost['user_settings']['locale_name'] );
			$this->gw_this['vars']['lang_enc'] = preg_replace("/^([a-z0-9])+-/", '', $this->gw_this['vars']['lang_enc'] );
			$this->gw_this['vars']['locale_name'] = $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'];
			$this->oL->setLocale($this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc']);
		}

		/* 1.8.10: clear previously assigned settings */
#		$ar_user['user_settings'] = array();
		/* Import previously assigned settings */
		$arPost = array_merge_clobber($arSql, $arPost);

		/* Assigned dictionaries */
		$arPost['user_settings']['dictionaries'] = isset($arPost['dictionaries']) ? $arPost['dictionaries'] : array();

#prn_r( $arPost['user_settings']  );

		/* Update avatar settings */
		if ($arPost['is_remove_avatar'])
		{
			$this->oFunc->file_remove_f($this->sys['path_temporary'].'/a/'.$arPost['user_settings']['avatar_img']);
			$arPost['user_settings']['avatar_img_x'] = 0;
			$arPost['user_settings']['avatar_img_y'] = 0;
			$arPost['user_settings']['avatar_img'] = '';
		}

		/* Selected permissions */
		$arPost['is_permissions'] = isset($arPost['is_permissions']) ? $arPost['is_permissions'] : array();
		foreach($arPost['is_permissions'] as $k => $v)
		{
			$arPost['is_permissions'][strtoupper($v)] = 1;
			unset($arPost['is_permissions'][$k]);
		}
		if ($this->gw_this['vars']['w1'] != $this->oSess->id_user)
		{
			$q1['user_perm'] = serialize($arPost['is_permissions']);
		}
		#$q1['user_settings'] = array_merge_clobber($ar_user['user_settings'], $arPost['user_settings']);
		$q1['user_settings'] =& $arPost['user_settings'];

#prn_r( $q1, __LINE__ );

		/* Update interface language */
		if ($this->sys['is_profile_owner'])
		{
			$gw_this['vars'][GW_LANG_I] = preg_replace("/-([a-z0-9])+$/", '',$arPost['user_settings']['locale_name']);
			$gw_this['vars']['lang_enc'] = preg_replace("/^([a-z0-9])+-/", '', $arPost['user_settings']['locale_name']);

			/* Save interface language, set cookie */
			setcookie('gw_'.GW_LANG_I.$this->sys['token'], $gw_this['vars'][GW_LANG_I], $this->sys['time_now']+$this->sys['time_sec_y'], $this->sys['server_dir'], '' );
			setcookie('gw_is_save_'.GW_LANG_I.$this->sys['token'], 1, $this->sys['time_now']+$this->sys['time_sec_y'], $this->sys['server_dir'], '' );

		}

		$q1['user_settings'] = serialize($q1['user_settings']);

		$ar_q[] = gw_sql_update($q1, $this->oSess->db_table_users, 'id_user = "'.$id_user.'"');

		/* Redirect */
		$url = GW_ACTION.'='.GW_A_EDIT .'&'. GW_TARGET.'='.GW_T_USERS;
		if ($this->gw_this['vars']['w1'] != $this->oSess->id_user)
		{
			$url .= '&w1=' . $this->gw_this['vars']['w1'];
		}
		else
		{
				$url = GW_ACTION.'=edit-own&'. GW_TARGET.'='.GW_T_USERS;
		}
		$this->str .= postQuery($ar_q, $url.'&note_afterpost='.$this->oL->m('1332'), $this->sys['isDebugQ'], 0);
	}
	unset($arPost['is_send_notice']);
}

?>