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
	die('<!-- $Id: menumanager_admin.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* */
class gw_addon_menumanager_admin extends gw_addon
{
	var $str;
	var $ar_component = array();
	/* Current component name */
	var $component;
	/* Autoexec */
	function gw_addon_menumanager_admin()
	{
		$this->init();
#		$this->oL->setHomeDir($this->sys['path_locale']);
#		$this->oL->getCustom('addon_'.$this->addon_name, $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');
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
	function get_form($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
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
		if ($this->gw_this['vars'][GW_ACTION] == 'edit') 
		{
			$oForm->Set('isButtonDel', 1);
		}
		
		$oForm->Set('isButtonCancel', 1);
		$oForm->Set('isButtonSubmit', 1);
		$ar_req = array_flip($ar_req);
		/* mark fields as "Required" and display error message */
		while (is_array($vars) && list($k, $v) = each($vars) )
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if (isset($ar_req[$k])) { $ar_req_msg[$k] = '&#160;<span class="red"><strong>*</strong></span>'; }
			if (isset($ar_broken[$k])) { $ar_broken_msg[$k] = '<span class="red"><strong>' . $this->oL->m('reason_9') . '</strong></span><br />'; }
		}
		/* */
		$str_form .= getFormTitleNav( $this->oL->m('1137'), '<span style="float:right">'.$oForm->get_button('submit').'</span>' );
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tbody>';

		switch ($this->gw_this['vars']['w1'])
		{
			case 'primary':
				#$ar_perms_var = explode(':', $vars['req_permission']);
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_active]', $vars['is_active']) . '</td>'.
							'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_active]').'">'.$this->oL->m('1320').'</label></td>'.
							'</tr>';
				$oForm->setTag('input', 'maxlength', 32);
				$oForm->setTag('input', 'onkeyup', 'gwJS.preview0z(\'component-name-1\', this);gwJS.preview0z(\'component-name-2\', this)');

				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<label for="'.$oForm->text_field2id('arPost[id_component_name]').'">'.$this->oL->m('1321').'</label>'.$ar_req_msg['cname'].'</td>'.
							'<td class="'.$v_class_2.'">' . $ar_broken_msg['id_component_name'] . $oForm->field('input', 'arPost[id_component_name]', $vars['id_component_name']);
				if ($vars['id_component_name'])
				{
					$str_form .= '<div class="gray">/'.$this->sys['path_addon'].'/<span id="component-name-1">'.$vars['id_component_name'].'</span>/<span id="component-name-2">'.$vars['id_component_name'].'</span>_admin.php</div>';
				}
				else
				{
					$str_form .= '<div class="gray">/'.$this->sys['path_addon'].'/<span id="component-name-1">'.$this->oL->m('1331').'</span>/<span id="component-name-2">'.$this->oL->m('1331').'</span>_admin.php</div>';
				}
				$str_form .= '</td></tr>';

				$oForm->setTag('input', 'onkeyup', '');
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<label for="'.$oForm->text_field2id('arPost[cname]').'">'.$this->oL->m('1322').'</label>'.$ar_req_msg['cname'].'</td>'.
							'<td class="'.$v_class_2.'">' . $ar_broken_msg['cname'] . $oForm->field('input', 'arPost[cname]', $vars['cname']) .
							'<div class="gray">'.$this->oL->m($vars['cname']).'</div></td>'.
							'</tr>';
				$oForm->setTag('input', 'onkeyup', '');
			break;
			case 'secondary':
				$ar_perms_var = explode(':', $vars['req_permission_map']);
				$oForm->setTag('select', 'class', 'input');
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_active_map]', $vars['is_active_map']) . '</td>'.
							'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_active_map]').'">'.$this->oL->m('1320').'</label></td>'.
							'</tr>';
				/* Construct the list of components */
				$sql = 'SELECT * FROM `'.$this->sys['tbl_prefix'].'component` ORDER BY int_sort';
				$arSql = $this->oDb->sqlRun($sql);
				$ar_select_components = array();
				$ar_select_cname_sys = array();
				for (; list($k, $arV) = each($arSql);)
				{
					$ar_select_components[$arV['id_component']] = $this->oL->m($arV['cname']);
					$ar_select_cname_sys[$arV['id_component']] = $arV['id_component_name'];
				}
				$oForm->setTag('select', 'onchange', 'gw_menumanager_get_option(\'component-name-1\', this);gw_menumanager_get_option(\'component-name-2\', this)');
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<label for="'.$oForm->text_field2id('arPost[id_component]').'">'.$this->oL->m('1331').'</label>'.$ar_req_msg['id_component'].'</td>'.
							'<td class="'.$v_class_2.'">' . $ar_broken_msg['id_component'] . $oForm->field('select', 'arPost[id_component]', $vars['id_component'], $ar_select_cname_sys, $ar_select_components);
				$str_form .= '</td></tr>';
				$oForm->setTag('select', 'onchange', '');
				/* Visible area */
				$ar_select_visible = array(
					1 =>  $this->oL->m('1324'),
					2 => $this->oL->m('1325'),
					0 => $this->oL->m('1326')
				);
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<label for="'.$oForm->text_field2id('arPost[is_in_menu]').'">'.$this->oL->m('1323').'</label>'.$ar_req_msg['is_in_menu'].'</td>'.
							'<td class="'.$v_class_2.'">' . $ar_broken_msg['id_action'] . $oForm->field('select', 'arPost[is_in_menu]', $vars['is_in_menu'], '', $ar_select_visible).'</td>'.
							'</tr>';


				$str_form .= '<tr><td style="width:'.$v_td1_width.'"></td><td></td></tr>';
				$str_form .= '</tbody></table>';
				$str_form .= '</fieldset>';

				$oForm->setTag('input', 'maxlength', 32);

				$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
				$str_form .= '<table class="gw2TableFieldset" width="100%">';
				$str_form .= '<tbody>';
		
				/* Construct the list of actions */
				$sql = 'SELECT * FROM `'.$this->sys['tbl_prefix'].'component_actions` ORDER BY aname_sys';
				$arSql = $this->oDb->sqlRun($sql);
				$ar_select_actions = array();
				$ar_select_aname_sys = array();
				for (; list($k, $arV) = each($arSql);)
				{
					$ar_select_actions[$arV['id_action']] = $this->oL->m($arV['aname']);
					$ar_select_aname_sys[$arV['id_action']] = $arV['aname_sys'];
				}

				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<input onclick="gw_menumanager_menuitem_check()" onchange="gw_menumanager_menuitem_check()" '.(isset($vars['is_use_id_action'][1])?'checked="checked" ':'').'type="radio" name="arPost[is_use_id_action][]" id="arPost-use-id-action-1" value="1" />' . '</td>'.
							'<td class="'.$v_class_2.'">' . '<label onclick="gw_menumanager_menuitem_check()" for="arPost-use-id-action-1">'.$this->oL->m('dictdump_list').'</label></td>'.
							'</tr>';
				/* The list of actions */
				$oForm->setTag('select', 'onchange', 'gw_menumanager_get_option(\'menu-item-name\', this)');
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'"></td>'.
							'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPost[id_action]', $vars['id_action'], $ar_select_aname_sys, $ar_select_actions);
				$str_form .= '</td></tr>';
				$oForm->setTag('select', 'onchange', '');
				/* Create new or Edit. "add_menu_item" - have to hardcode */
				if ($this->gw_this['vars'][GW_ACTION] == 'add_menu_item')
				{
					$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<input onclick="gw_menumanager_menuitem_check()" onchange="gw_menumanager_menuitem_check()" '.(isset($vars['is_use_id_action'][0])?'checked="checked" ':'').'type="radio" name="arPost[is_use_id_action][]" id="arPost-use-id-action-0" value="0" />' . '</td>'.
							'<td class="'.$v_class_2.'">' . '<label onclick="gw_menumanager_menuitem_check()" for="arPost-use-id-action-0">'.$this->oL->m('1327').'</label></td>'.
							'</tr>';
				}
				elseif ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT)
				{
					$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<input onclick="gw_menumanager_menuitem_check()" onchange="gw_menumanager_menuitem_check()" '.(isset($vars['is_use_id_action'][0])?'checked="checked" ':'').'type="radio" name="arPost[is_use_id_action][]" id="arPost-use-id-action-0" value="0" />' . '</td>'.
							'<td class="'.$v_class_2.'">' . '<label onclick="gw_menumanager_menuitem_check()" for="arPost-use-id-action-0">'.$this->oL->m('3_edit').'</label></td>'.
							'</tr>';
				}
				$oForm->setTag('input', 'onkeyup', 'gwJS.preview0z(\'menu-item-name\', this)');
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<label for="'.$oForm->text_field2id('arPost[aname_sys]').'">'.$this->oL->m('1321').'</label>'.$ar_req_msg['aname_sys'].'</td>'.
							'<td class="'.$v_class_2.'">' . $ar_broken_msg['aname_sys'] . $oForm->field('input', 'arPost[aname_sys]', $vars['aname_sys']);
				if ($vars['id_component_name'])
				{
					$str_form .= '<div class="gray">/'.$this->sys['path_addon'].'/<span id="component-name-1">'.$vars['id_component_name'].'</span>/<span id="component-name-2">'.$vars['id_component_name'].'</span>_<span id="menu-item-name">'.$vars['aname_sys'].'</span>.inc.php</div>';
				}
				else
				{
					$str_form .= '<div class="gray">/'.$this->sys['path_addon'].'/<span id="component-name-1">'.$this->oL->m('1331').'</span>/<span id="component-name-2">'.$this->oL->m('1331').'</span>_<span id="menu-item-name">*</span>.inc.php</div>';
				}
				$str_form .= '</td></tr>';
				$oForm->setTag('input', 'onkeyup', '');
				
				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<label for="'.$oForm->text_field2id('arPost[aname]').'">'.$this->oL->m('1322').'</label>'.$ar_req_msg['aname'].'</td>'.
							'<td class="'.$v_class_2.'">' . $ar_broken_msg['aname'] . $oForm->field('input', 'arPost[aname]', $vars['aname']) .
							'<div class="gray">'.$this->oL->m($vars['aname']).'</div></td>'.
							'</tr>';

				$str_form .= '<tr>'.
							'<td class="'.$v_class_1.'">' . '<label for="'.$oForm->text_field2id('arPost[icon]').'">'.$this->oL->m('1328').'</label>'.$ar_req_msg['icon'].'</td>'.
							'<td class="'.$v_class_2.'">' . $ar_broken_msg['icon'] . $oForm->field('input', 'arPost[icon]', $vars['icon']).'</td>'.
							'</tr>';
				$str_js .= '
function gw_menumanager_get_option(id, el)
{
	target_el = gw_getElementById(id);
	target_el.removeChild(target_el.lastChild);
	target_el.appendChild(document.createTextNode( el.options[el.options.selectedIndex].title ));
}
function gw_menumanager_menuitem_check()
{

	gw_menumanager_get_option(\'menu-item-name\', gw_getElementById(\'arPost_id_action_\'));

	el = gw_getElementById("arPost-use-id-action-1");
	if (el.checked)
	{
		gw_getElementById("arPost_aname_").style.color = 
		gw_getElementById("arPost_icon_").style.color = 
		gw_getElementById("arPost_aname_sys_").style.color = "#AAA";
		gw_getElementById("arPost_id_action_").style.color = "#000";
		gw_getElementById("arPost_aname_").disabled = true;
		gw_getElementById("arPost_icon_").disabled = true;
		gw_getElementById("arPost_aname_sys_").disabled = true;
		gw_getElementById("arPost_id_action_").disabled = false;
	}
	else
	{
		target_el = gw_getElementById(\'menu-item-name\');
		target_el.removeChild(target_el.lastChild);
		target_el.appendChild(document.createTextNode( gw_getElementById("arPost_aname_sys_").value ));

		gw_getElementById("arPost_aname_").style.color = 
		gw_getElementById("arPost_icon_").style.color = 
		gw_getElementById("arPost_aname_sys_").style.color = "#000";
		gw_getElementById("arPost_id_action_").style.color = "#AAA";
		gw_getElementById("arPost_aname_").disabled = false;
		gw_getElementById("arPost_icon_").disabled = false;
		gw_getElementById("arPost_aname_sys_").disabled = false;
		gw_getElementById("arPost_id_action_").disabled = true;
	}
	
}
gw_menumanager_menuitem_check();

gw_menumanager_get_option(\'component-name-1\', gw_getElementById(\'arPost_id_component_\'));
gw_menumanager_get_option(\'component-name-2\', gw_getElementById(\'arPost_id_component_\'));
gw_menumanager_get_option(\'menu-item-name\', gw_getElementById(\'arPost_id_action_\'));
							';
			break;
		}
		/* Menu item only */
		if (isset($ar_perms_var))
		{
			$str_form .= '<tr><td style="width:'.$v_td1_width.'"></td><td></td></tr>';
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';
			$str_form .= '<script type="text/javascript">';
			$str_form .= $str_js;
			$str_form .= '</script>';
			foreach ($ar_perms_var as $k => $permission)
			{
				unset($ar_perms_var[$k]);
				if ($permission)
				{
					$ar_perms_var[$permission] = 1;
				}
			}
			/* User permissions */
			$str_form .= getFormTitleNav($this->oL->m('1037'));
			$ar_user_permissions = array(
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
					'is-cpages-own' => '1317',
					'is-cpages' => '1318',
				),
				array(
					'is-sys-settings' => '1265',
					'is-sys-mnt' => '1313',
				)
			);
			for (; list($k, $arV) = each($ar_user_permissions);)
			{
				$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
				$str_form .= '<table class="gw2TableFieldset" width="100%"><tbody>';
				for (; list($fieldname, $caption) = each($arV);)
				{
					$str_checked = (isset($ar_perms_var[$fieldname]) ? 'checked="checked" ' : '');
					$str_form .= '<tr>'.
								'<td style="width:'.$v_td1_width.'" class="'.$v_class_1.'"><input id="'.$oForm->text_field2id('arPost['.$fieldname.']').'" '.$str_checked.' value="'.$fieldname.'" name="arPost[is_permissions][]" type="checkbox" /></td>' .
								'<td class="'.$v_class_2.'"><label for="'.$oForm->text_field2id('arPost['.$fieldname.']').'">' . $this->oL->m($caption) . '</label></td>'.
								'</tr>';
				}
				$str_form .= '</tbody></table>';
				$str_form .= '</fieldset>';
			}
		}
		else
		{
			/* close table */ 
			$str_form .= '</tbody></table>';
			$str_form .= '</fieldset>';
		}
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field("hidden", GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
		$str_form .= $oForm->field('hidden', 'w1', $this->gw_this['vars']['w1']);
		$str_form .= $oForm->field('hidden', 'w2', $this->gw_this['vars']['w2']);
		$str_form .= $oForm->field('hidden', 'arPost[id_action_old]', $vars['id_action_old']);
		$str_form .= $str_hidden;
		return $oForm->Output($str_form);
	}
	/* */
	function alpha()
	{
		global $strR;

		/* On remove */
		if (is_array($this->gw_this['vars']['arControl']))
		{
			$arControl = array_keys($this->gw_this['vars']['arControl']);
			$this->gw_this['vars'][GW_ACTION] = $arControl[0];
		}
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
				gw_text_sql($this->gw_this['vars'][GW_ACTION]),
				gw_text_sql($this->gw_this['vars'][GW_TARGET]))
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
				$strR .= $this->oL->m('reason_13');
			}
		}
	}
}
/* */
$oAddonAdm = new gw_addon_menumanager_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['menumanager_'.GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';
/* end of file */
?>