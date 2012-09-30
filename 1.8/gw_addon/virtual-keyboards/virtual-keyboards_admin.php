<?php
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
if (!defined('IN_GW'))
{
	die('<!-- $Id: virtual-keyboards_admin.php 486 2008-06-12 00:45:27Z glossword_team $ -->');
}
/* */
class gw_addon_vkbd_admin extends gw_addon
{
	/* Current component name */
	var $component;
	var $ar_groups;
	var $ar_profile;
	var $ar_profiles;
	/* Autoexec */
	function gw_addon_vkbd_admin()
	{
		$this->init();
	}
	/* */
	function _get_nav()
	{
#		$this->oL->setHomeDir($this->sys['path_locale']);
#		$this->oL->getCustom('addon_'.$this->component, $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');

		/* The list of profiles */
		$arSql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-vkbd-profiles-adm'), $this->component);
		$ar_profiles = array();
		$this->ar_profiles = array();
		while (is_array($arSql) && list($k, $arV) = each($arSql))
		{
			/* For <select> */
			$this->ar_profiles[$arV['id_profile']] = $arV;
		}
		return '<div class="actions-secondary">'.
			implode(' ', $this->gw_this['ar_actions_list'][$this->component]).
			'</div>';
	}
	/**
	 * HTML-form for a profile
	 */
	function get_form_vkbd($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
	{
		$str_hidden = '';
		$str_form = '';
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

		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT)
		{
			$oForm->isButtonDel = 1;
			$oForm->Set('submitdelname', 'remove');
			$oForm->Set('submitdel', $this->oL->m('3_remove'));
		}

		$ar_req = array_flip($ar_req);
		/* mark fields as "Required" and display error message */
		while (is_array($vars) && list($k, $v) = each($vars) )
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if (isset($ar_req[$k])) { $ar_req_msg[$k] = '&#160;<span class="red"><b>*</b></span>'; }
			if (isset($ar_broken[$k])) { $ar_broken_msg[$k] = '<span class="red"><b>' . $this->oL->m('reason_9') . '</b></span><br />'; }
		}
		/* */
		$str_form .= getFormTitleNav( $this->oL->m('1137'), '<span style="float:right">'.$oForm->get_button('submit').'</span>' );

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tbody><tr><td style="width:'.$v_td1_width.'"></td><td>';
		$str_form .= '</td></tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_active]', $vars['is_active']) . '</td>'.
					'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_active]').'">'.$this->oL->m('1320').'</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_index_page]', $vars['is_index_page']) . '</td>'.
					'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_index_page]').'">'.$this->oL->m('1401').'</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1289') . $ar_req_msg['vkbd_name'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['vkbd_name'] . $oForm->field('textarea', 'arPost[vkbd_name]', $vars['vkbd_name']) . '</td>'.
					'</tr>';
		$oForm->setTag('textarea', 'style', 'font-size:200%');
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1306') . $ar_req_msg['vkbd_letters'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['vkbd_letters'] . $oForm->field('textarea', 'arPost[vkbd_letters]', $vars['vkbd_letters']) . '<div class="tooltip">'. $this->oL->m('1307') . '</div></td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT)
		{
			$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
		}
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
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
$oAddonAdm = new gw_addon_vkbd_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['virtual-keyboards_'.GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';
/* end of file */
?>