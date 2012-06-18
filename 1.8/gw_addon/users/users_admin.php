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
	die('<!-- $Id: users_admin.php 531 2008-07-09 19:20:16Z glossword_team $ -->');
}
/* */
class gw_addon_users_admin extends gw_addon
{
	var $str;
	var $ar = array();
	var $ar_component = array();
	/* Current component name */
	var $component;
	/* Autoexec */
	function gw_addon_users_admin()
	{
		$this->init();

		/* Edit own settings by default */
		if ($this->gw_this['vars']['w1'] == '')
		{
			$this->gw_this['vars']['w1'] = $this->oSess->id_user;
		}
		/* Check permissions on editing other user`s profile */
		if (($this->gw_this['vars']['w1'] != $this->oSess->id_user) && !$this->oSess->is('is-users') )
		{
			$this->gw_this['vars']['w1'] = $this->oSess->id_user;
		}
		/* Set special attribute for editing further options */
		$this->sys['is_profile_owner'] = 0;
		if ($this->gw_this['vars']['w1'] == $this->oSess->id_user)
		{
			$this->sys['is_profile_owner'] = 1;
		}
		/* All statements */
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_ADD)
		{
			$this->gw_this['vars']['w1'] = '';
		}
		$this->ar_state = array('is_user_add' => 0, 'is_user_edit' => 0, 'is_profile' => $this->sys['is_profile_owner']);
		if (!$this->sys['is_profile_owner'] 
			&& $this->oSess->is('is-users')
			&& $this->gw_this['vars']['w1']
			)
		{
			$this->ar_state['is_user_edit'] = 1;
		}
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_ADD)
		{
			$this->ar_state['is_user_add'] = 1;
			$this->ar_state['is_profile'] = 0;
			$this->gw_this['vars']['w1'] = '';
		}
	}
	/* */
	function _get_nav()
	{
		return '<div class="actions-secondary">'.
			implode(' ', $this->gw_this['ar_actions_list'][$this->component]).
			'</div>';
	}
	/**
	 * HTML-form for an action or a component
	 */
	function get_form_user($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
	{
		$str_hidden = '';
		$str_form = '';
		$str_js = '';
		$v_class_1 = 'td1';
		$v_class_2 = 'td2';
		$v_td1_width = '25%';

		$oForm = new gwForms();
		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitok', $this->oL->m('3_save'));
		$oForm->Set('submitcancel', $this->oL->m('3_cancel'));
		$oForm->Set('formbgcolor', $this->ar_theme['color_2']);
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']);
		$oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
		$oForm->Set('align_buttons', $this->sys['css_align_right']);
		$oForm->Set('formwidth', '100%');
		$oForm->Set('charset', $this->sys['internal_encoding']);
	
		$oForm->arLtr = array('arPost[visualtheme]', 'arPost[user_settings][locale_name]', 'arPost[login]', 'arPost[gmt_offset]',
						'arPost[pass_new]', 'arPost[pass_confirm]', 'arPost[user_email]');
	
		if ($this->sys['is_upload']) { $oForm->Set('enctype', 'multipart/form-data'); }

		if ($this->ar_state['is_user_add'])
		{
			$oForm->Set('submitok', $this->oL->m('3_add'));
		}

		$oForm->Set('isButtonCancel', 1);
		$oForm->Set('isButtonSubmit', 1);
		$ar_req = array_flip($ar_req);
#		unset($vars['user_settings']);
#prn_r( $vars );
		/* mark fields as "Required" and display error message */
		foreach($vars as $k => $v)
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if (isset($ar_req[$k])) { $ar_req_msg[$k] = '&#160;<span class="red"><strong>*</strong></span>'; }
			if (isset($ar_broken[$k])) { $ar_broken_msg[$k] = '<span class="red"><strong>' . $this->oL->m('reason_9') . '</strong></span><br />'; }
		}

		/* */
		$oForm->setTag('select', 'class',  'input75');
		/* */
		if ($this->ar_state['is_user_edit'] && $this->gw_this['vars']['w1'] != 1)
		{
			$oForm->Set('isButtonDel', 1);
			$oForm->Set('submitdelname', 'remove');
			$oForm->Set('submitdel', $this->oL->m('3_remove'));
		}

		/* */
		$ar_timezones = array('-12'=>'UTC -12','-11'=>'UTC -11','-10'=>'UTC -10','-9.5'=>'UTC -9:30','-9'=>'UTC -9','-8'=>'UTC -8','-7'=>'UTC -7','-6'=>'UTC -6','-5'=>'UTC -5','-4'=>'UTC -4','-3.5'=>'UTC -3:30','-3'=>'UTC -3','-2.5'=>'UTC -2:30','-2'=>'UTC -2','-1'=>'UTC -1','0'=>$this->oL->m('1356'),'0.5'=>'UTC +0:30','1'=>'UTC +1','2'=>'UTC +2','3'=>'UTC +3','3.5'=>'UTC +3:30','4'=>'UTC +4','4.5'=>'UTC +4:30','5'=>'UTC +5','5.5'=>'UTC +5:30','5.75'=>'UTC +5:45','6'=>'UTC +6','6.5'=>'UTC +6:30','7'=>'UTC +7','7.5'=>'UTC +7:30','8'=>'UTC +8','9'=>'UTC +9','9.5'=>'UTC +9:30','10'=>'UTC +10','10.5'=>'UTC +10:30','11'=>'UTC +11','11.5'=>'UTC +11:30','12'=>'UTC +12','12.75'=>'UTC +12:45','13'=>'UTC +13','13.75'=>'UTC +13:45','14'=>'UTC +14');
		/* */
		$str_form .= getFormTitleNav($this->oL->m('user_reginfo'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';

		if ($this->ar_state['is_user_edit'] || $this->oSess->is('is-login'))
		{
			/* allow edit username */
			$oForm->setTag('input', 'maxlength',  '32');
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('login') . ':' . $ar_req_msg['login'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['login'] . $oForm->field('input', 'arPost[login]', textcodetoform($vars['login'])) . '<div class="gray">'.$this->oL->m('tip025').'</div></td>'.
						'</tr>';
			$oForm->setTag('input', 'maxlength',  '');
		}
		else
		{
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('login') . ':</td>'.
						'<td class="disabled">' . $vars['login'] . '</td>'.
						'</tr>';
		}
		if ($this->ar_state['is_user_edit'] || $this->oSess->is('is-password'))
		{
			/* allow edit username */
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('pass_new') . ':' . $ar_req_msg['pass_new'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['pass_new'] . $oForm->field("pass", "arPost[pass_new]", textcodetoform($vars['pass_new']), 16) . 
						($this->ar_state['is_user_add'] ? '' : '<div class="gray">'.$this->oL->m('tip023').'</div>').'</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('pass_confirm') . ':' . $ar_req_msg['pass_confirm'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['pass_confirm'] . $oForm->field('pass', 'arPost[pass_confirm]', textcodetoform($vars['pass_confirm']), 16) . 
						($this->ar_state['is_user_add'] ? '' : '<div class="gray">'.$this->oL->m('tip024').'</div>').'</td>'.
						'</tr>';
		}
		if ($this->ar_state['is_user_edit'] || $this->oSess->is('is-email'))
		{
			/* allow edit e-mail */
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('contact_email') . ': </td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['user_email'] . $oForm->field('input', 'arPost[user_email]', textcodetoform($vars['user_email']), 16) . '</td>'.
						'</tr>';
		}
		else
		{
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('y_email') . ':</td>'.
						'<td class="disabled">' . $vars['user_email'] . '</td>'.
						'</tr>';
		}
		/* Send notice */
		if ($this->ar_state['is_user_add'])
		{
				$oForm->setTag('checkbox', 'onchange', 'is_allow_checkbox(this, \'arPost_user_email_\');');
				$str_form .= '<tr>';
				$str_form .= '<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_send_notice]', $vars['is_send_notice']) . '</td>';
				$str_form .= '<td class="'.$v_class_2.'"><label for="arPost_is_send_notice_">' . $this->oL->m('user_notice') . '</label>';
				$str_form .= '</td>';
				$str_form .= '</tr>';
				$oForm->setTag('checkbox', 'onchange', '');
		}
		/* */
		if ($this->ar_state['is_user_edit'])
		{
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('date_register') . ':</td>'.
						'<td class="disabled">' . date_extract_int($vars['date_reg'] + ($this->oSess->user_get_time_seconds()), "%d %FL %Y %H:%i:%s") . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('date_logged') . ':</td>'.
						'<td class="disabled">' . ((($vars['date_login'] / 1) != 0) ? date_extract_int($vars['date_login'] + ($this->oSess->user_get_time_seconds()), "%d %FL %Y %H:%i:%s") : '&#160;') . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('termsamount') . ':</td>'.
						'<td class="disabled">' . $this->oFunc->number_format($vars['int_items'], 0, $this->oL->languagelist('4')) . '</td>'.
						'</tr>';
			$str_form .= '<tr>';
			$str_form .= '<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_active]', $vars['is_active']) . '</td>';
			$str_form .= '<td class="'.$v_class_2.'"><label for="arPost_is_active_">' . $this->oL->m('allow_user') . '</label>';
			/* Shortcut to remove the user */
			if ($this->gw_this['vars']['w1'] != 1)
			{
			$str_form .= ' <span class="actions-third"><a href="'.
						append_url($this->sys['page_admin']. '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.GW_T_USERS.'&remove=1&w1='.$this->gw_this['vars']['w1']).
						'">'. $this->oL->m('3_remove') .'</a></span>';
			}
			$str_form .= '</td>';
			$str_form .= '</tr>';
			/* 1.8.8 */
			$str_form .= '<tr>';
			$str_form .= '<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_multiple]', $vars['is_multiple']) . '</td>';
			$str_form .= '<td class="'.$v_class_2.'"><label for="arPost_is_multiple_">' . $this->oL->m('1361') . '</label></td>';
			$str_form .= '</tr>';
		}
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';


		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';


			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('1338') . ': </td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['user_fname'] . $oForm->field('input', 'arPost[user_fname]', textcodetoform($vars['user_fname'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('1339') . ': </td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['user_sname'] . $oForm->field('input', 'arPost[user_sname]', textcodetoform($vars['user_sname'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('user_location') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('input', 'arPost[user_settings][location]', textcodetoform($vars['user_settings']['location'])) . '</td>'.
						'</tr>';

		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		/* Options */
		$str_form .= getFormTitleNav($this->oL->m('options'));
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
		
		
		$oForm->setTag('select', 'style', 'width:75%');
#		$str_form .= '<tr>'.
#					'<td style="width:'.$v_td1_width.'" class="'.$v_class_1.'">' . $this->oL->m('visual_theme') . ':</td>'.
#					'<td class="'.$v_class_2.' gray">' . $oForm->field('select', 'arPost[user_settings][visualtheme]', $vars['user_settings']['visualtheme'], 0, $this->gw_this['ar_themes_select']);
#		if ($this->oSess->is('is-sys-settings'))
#		{
#			$str_form .= ' ['.$this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'=visual-themes', $this->oL->m('3_edit')).']';
#		}
#		$str_form .= '</td>'.
#					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('lang') . ':</td>'.
					'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPost[user_settings][locale_name]', $vars['user_settings']['locale_name'], 0, $this->gw_this['vars']['ar_languages']) . '</td>'.
					'</tr>';
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT || $this->gw_this['vars'][GW_ACTION] == 'edit-own')
		{
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('1104') . ':</td>'.
						'<td class="'.$v_class_2.'"><div style="margin-bottom:4px">' . $this->oL->m('1103').': <strong>' .
							date_extract_int( $this->oSess->user_get_time(), "%H:%i %d %FL %Y") .
							'</strong></div>'.
							$oForm->field('select', 'arPost[user_settings][gmt_offset]', $vars['user_settings']['gmt_offset'], 0, $ar_timezones) . '</td>'.
						'</tr>';
			/* Summer Time/DST is in effect */
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[user_settings][is_dst]', $vars['user_settings']['is_dst']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="'.$oForm->text_field2id('arPost[user_settings][is_dst]').'">' . $this->oL->m('1340') . '</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_show_contact]', $vars['is_show_contact']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="'.$oForm->text_field2id('arPost[is_show_contact]').'">' . $this->oL->m('allow_showcontact') . '</label></td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[user_settings][is_htmled]', $vars['user_settings']['is_htmled']) . '</td>'.
						'<td class="'.$v_class_2.'"><label for="'.$oForm->text_field2id('arPost[user_settings][is_htmled]').'">' . $this->oL->m('allow_htmleditor') . '</label></td>'.
						'</tr>';
		}
		
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		

		/* Avatar */
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT || $this->gw_this['vars'][GW_ACTION] == 'edit-own')
		{
			$vars['avatar_img_html'] = '&#160;';
			if ($vars['user_settings']['avatar_img'])
			{
				$vars['avatar_img_html'] = '<div id="user-avatar"><img width="'.$vars['user_settings']['avatar_img_x'].'" height="'.$vars['user_settings']['avatar_img_y'].'" src="'.$this->sys['path_temporary'].'/a/'.$vars['user_settings']['avatar_img'].'" alt="" /></div>';
			}
			$str_form .= getFormTitleNav($this->oL->m('1116'));
			$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';

			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('1111') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $vars['avatar_img_html'];
			if ($vars['user_settings']['avatar_img'] != '')
			{
				$str_form .= '<span class="actions-third">';
				$str_form .= '<script type="text/javascript">';
				$str_form .= 'function USER_remove_avatar() {
gw_getElementById("user-avatar").innerHTML = "";
gw_getElementById("arPost_is_remove_avatar_").value = 1;
gw_getElementById("arPost_user_settings_is_use_avatar_").value = "";
gw_getElementById("arPost_user_settings_is_use_avatar_").checked = false;
document.forms[\'vbform\'][\'submit1\'].click();
}';
				$str_form .= '</script>';
				$this->oHtml->setTag('a', 'onclick', 'return confirm(\''.$this->oL->m('3_remove').': &quot;'.htmlspecialchars($vars['user_settings']['avatar_img']).'&quot;. '.$this->oL->m('9_remove').'\' )');
				$str_form .= $this->oHtml->a( 'javascript:USER_remove_avatar();', $this->oL->m('3_remove') );
				$this->oHtml->setTag('a', 'onclick', '');
				$str_form .= '</span>';
			}
			$str_form .= '</td>';
			$str_form .= '</tr>';
	
			if ($this->sys['is_upload'])
			{
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . $this->oL->m('1108') . ':</td>'.
							'<td class="'.$v_class_2.'">' .
									 $oForm->field('file', 'file_location') .
									'<p>' . sprintf($this->oL->m('1109'), $this->sys['avatar_max_x'], $this->sys['avatar_max_y'], $this->sys['avatar_max_kb']) . '</p>' .
									'<p>' . $this->oL->m('1110') . ': <strong>jpg, png</strong></p>'.
									'<p>' . $this->oL->m('1117') . '</p>'.
									'</td>'.
							'</tr>';
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[user_settings][is_use_avatar]', $vars['user_settings']['is_use_avatar']) . '</td>'.
							'<td class="'.$v_class_2.'"><label for="'.$oForm->text_field2id('arPost[user_settings][is_use_avatar]').'">' . $this->oL->m('is_use_avatar') . '</label></td>'.
							'</tr>';
			}
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';
		}
				
		/* Assigned dictionaries */
		$str_form .= getFormTitleNav($this->oL->m('user_dictionaries'));
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';

		/* Per each dictionary */
		$cnt = 0;
		$ar_dict_ids = array();

		for (reset($this->gw_this['ar_dict_list']); list($k, $arDictParam) = each($this->gw_this['ar_dict_list']);)
		{
			$is_assigned = 0;
			/* $vars['dictionaries'] is flipped */
			if (isset($vars['dictionaries'][$arDictParam['id']]))
			{
				$is_assigned = 1;
			}
			/* show the list of assigned dictionaries to user */
			if ($this->ar_state['is_profile'] && !$is_assigned)
			{
				continue;
			}
			$str_external_link = $arDictParam['is_active']
						? '<a href="'.$this->sys['page_index'].'?a=index&amp;d='. $arDictParam['dict_uri'] .'" onclick="window.open(this.href);return false;">&gt;&gt;&gt;</a> '
									: '<span class="gray">&gt;&gt;&gt; </span>';
			$str_checkbox = '';
			$str_label = $arDictParam['title'];
			if (($this->ar_state['is_user_add'] || $this->ar_state['is_user_edit']))
			{
				$str_checkbox = $oForm->field('checkbox', 'arPost[dictionaries]['. $arDictParam['id'] . ']', $is_assigned);
				$str_label = '<label for="arPost_dictionaries_' . $arDictParam['id'] . '_">'. $arDictParam['title'] .'</label>';
			}
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $str_checkbox.'</td><td class="td2 actions-third">'.$str_external_link .$str_label.'</td>'.
					'</tr>';
			$cnt++;
			$ar_dict_ids[] = $arDictParam['id'];
		}

#prn_r( $this->ar_state );
		if (!$this->ar_state['is_profile'] && $cnt)
		{
			/* Check/Uncheck All */
			$str_form .= '<tr><td></td><td class="td2 gray">
					<a href="#" onclick="return setCheckboxesDict(true, \''.implode(',',$ar_dict_ids).'\')">'.$this->oL->m('select_on').'</a>
					&#8226;
					<a href="#" onclick="return setCheckboxesDict(false, \''.implode(',',$ar_dict_ids).'\')">'.$this->oL->m('select_off').'</a>
				</td>
				</tr>';
		}
		
		/* No assigned dictionaries */
		if ( !$cnt ) {
			
			if ( $this->oSess->is( 'is-sys-settings' ) ) {
				/* Admin user */
				$msg_no_dict = $this->oL->m( '1400' );
			} else {
				/* Other users */
				$msg_no_dict = $this->oL->m( 'reason_4' );
			}
			$str_form .= '<tr><td></td><td class="' . $v_class_2 . '">' . $msg_no_dict . '</td></tr>';
		}
		
#prn_r( $this->oSess->user_get('dictionaries') );

		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		
		/* Permissions  */
		/* See also class.session-1.9.php, menumanager_admin.php */
		$ar_permissions_list = array(
			array(
				'is-email' => '1314',
				'is-login' => '1315',
				'is-password' => '1316',
				'is-profile' => '1355',
				'is-users' => '1262',
			),
			array(
				'is-topics-own' => '1257',
				'is-topics' => '1312',
			),
			array(
				'is-dicts-own' => '1258',
				'is-dicts' => '1260',
			),
			array(
				'is-terms-own' => '1261',
				'is-terms' => '1259',
				'is-terms-import' => '1263',
				'is-terms-export' => '1264',
			),
			array(
				'is-sys-settings' => '1265',
				'is-sys-mnt' => '1313',
			)
		);
		$str_form .= getFormTitleNav($this->oL->m('1037'));
		$ar_permissions_user = unserialize($vars['user_perm']);
		$ar_permissions_ids = array();
		if ($this->ar_state['is_profile'])
		{
			$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
		}
		for (; list($k, $arV) = each($ar_permissions_list);)
		{
			if (!$this->ar_state['is_profile'])
			{
				$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
				$str_form .= '<table class="gw2TableFieldset" width="100%">';
				$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
			}
			for (; list($fieldname, $caption) = each($arV);)
			{
				$str_checked = ((isset($ar_permissions_user[strtoupper($fieldname)]) && $ar_permissions_user[strtoupper($fieldname)]) 
					? 'checked="checked" ' 
					: '');
				if ($this->ar_state['is_profile'])
				{
					/* Show permission info when enabled */
					if ($str_checked)
					{
						$str_form .= '<tr>'.
									'<td class="'.$v_class_1.'"></td>' .
									'<td class="'.$v_class_2.'">'. $this->oL->m($caption) . '</td>'.
									'</tr>';
					}
				}
				else
				{
					/* Allow to check */
					$str_form .= '<tr>'.
								'<td class="'.$v_class_1.'"><input id="'.$oForm->text_field2id('arPost['.$fieldname.']').'" '.$str_checked.' value="'.$fieldname.'" name="arPost[is_permissions][]" type="checkbox" /></td>' .
								'<td class="'.$v_class_2.'"><label for="'.$oForm->text_field2id('arPost['.$fieldname.']').'">' . $this->oL->m($caption) . '</label></td>'.
								'</tr>';
				}
				$ar_permissions_ids[] = $fieldname;
				$cnt++;
			}
			if (!$this->ar_state['is_profile'])
			{
				$str_form .= '</tbody></table></fieldset>';
			}
		}
		if ($this->ar_state['is_profile'])
		{
			$str_form .= '</tbody></table></fieldset>';
		}
		else
		{
			$str_form .= '<script type="text/javascript">/*<![CDATA[*/var arPerm = new Array("'.implode('","', $ar_permissions_ids).'"); /*]]>*/</script>';
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
			$str_form .= '<tr><td></td><td class="td2 gray">
					<a href="#" onclick="return setCheckboxesPerm(1)">'.$this->oL->m('3_profile').' 1</a>
					&#8226;
					<a href="#" onclick="return setCheckboxesPerm(2)">'.$this->oL->m('3_profile').' 2</a>
					&#8226;
					<a href="#" onclick="return setCheckboxesPerm(3)">'.$this->oL->m('3_profile').' 3</a>
					&#8226;
					<a href="#" onclick="return setCheckboxesPerm(4)">'.$this->oL->m('3_profile').' 4</a>
					&#8226;
					<a href="#" onclick="return setCheckboxesPerm(false)">'.$this->oL->m('select_off').'</a>
				</td>
				</tr>';
			$str_form .= '</tbody></table>';
		}
		$str_form .= $oForm->field('hidden', 'arPost[is_remove_avatar]', 0);
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', 'w1', $this->gw_this['vars']['w1']);
		$str_form .= $str_hidden;
		return $oForm->Output($str_form);
	}
	/* */
	function alpha()
	{
		global $strR;
		/* Call an action */
		if ( file_exists($this->sys['path_component_action']) )
		{
			/* check for permissions */
			$ar_perms = $this->oSess->ar_permissions;
			foreach ($ar_perms AS $permission => $is)
			{
				if (!$is)
				{
					unset($ar_perms[$permission]);
				}
			}
			$ar_sql_like2 = 'cmm.req_permission_map LIKE "%:'.implode(':%" OR cmm.req_permission_map LIKE "%:', array_keys($ar_perms) ).':%"';
			$arSql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-component-action-perm', 
				$ar_sql_like2, 
				$this->gw_this['vars'][GW_ACTION],
				$this->gw_this['vars'][GW_TARGET])
			);
			$this->ar_component = isset($arSql[0]) ? $arSql[0] : array();
			/* Component settings found */
			if (!empty($this->ar_component))
			{
				$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).': '. $this->oL->m($this->ar_component['aname']);
				$this->component =& $this->ar_component['id_component_name'];
				include_once( $this->sys['path_component_action'] );
				$strR .= $this->str;
			}
			else
			{
				$this->sys['id_current_status'] = '';
				$strR .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
				$strR .= '<p class="xt">'.$this->gw_this['vars'][GW_TARGET] .': '. $this->gw_this['vars'][GW_ACTION].'</p>';
			}
		}
	}
}
/* */
$oAddonAdm = new gw_addon_users_admin;
$oAddonAdm->alpha();
/* */
/* Do not load old components */
$pathAction = '';
/* end of file */
?>