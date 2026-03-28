<?php
/**
 *  Glossword - glossary compiler (http://glossword.info/)
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
	die('<!-- $Id: custom_az_admin.php 342 2008-03-11 21:10:31Z yrtimd $ -->');
}
/* */
class gw_addon_custom_az_admin extends gw_addon
{
	var $component = 'custom-az';
	var $ar_groups;
	var $ar_profile;
	var $ar_profiles;
	var $ar_profiles_browse;
	var $left_td_width = '25%';
	/* Autoexec */
	function gw_addon_custom_az_admin()
	{
		$this->init();
		/* The list of subsections */
		$this->ar_profiles = array();
		/* The list of profiles */
		$arSql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-custom_az-profiles-adm'), $this->component);
		while (list($k, $arV) = each($arSql))
		{
			if ($k == 0)
			{
				$id_profile = $arV['id_profile'];
			}
			/* For navigation */
			$this->ar_profiles_browse[$arV['id_profile']] = $this->oHtml->a(
				$this->sys['page_admin'].'?'.GW_ACTION.'=browse&'.GW_TARGET.'='.$this->component.'&tid=' . $arV['id_profile'],
				($arV['id_profile'] == $this->gw_this['vars']['tid'] ? '<strong>'.$arV['profile_name'].'</strong>' : $arV['profile_name'])
			);
			/* For <select> */
			$this->ar_profiles[$arV['id_profile']] = $arV['profile_name'];
		}
	}
	function _get_nav()
	{
		return '<div class="actions-secondary">'.
			implode(' ', $this->gw_this['ar_actions_list'][$this->component]).
			'</div>';
		
	}
	/**
	 * HTML-form for a profile 
	 */
	function get_form_custom_az($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
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
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT
			&& $this->gw_this['vars']['tid'] > 1)
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
		/* Profile */
		if ($this->gw_this['vars']['tid'] != 1)
		{
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_active]', $vars['is_active']) . '</td>'.
						'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_active]').'">'.$this->oL->m('1320').'</label></td>'.
						'</tr>';
		}
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1289') . $ar_req_msg['profile_name'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['profile_name'] . $oForm->field('textarea', 'arPost[profile_name]', $vars['profile_name']) . '</td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field("hidden", GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $str_hidden;
		return $oForm->Output($str_form);
	}
	/* */
	function get_form_import($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
	{
		$oForm = new gwForms();

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
		if ($this->sys['is_upload']) { $oForm->Set('enctype', 'multipart/form-data'); }

		$ar_req = array_flip($ar_req);
		/* mark fields as "Required" and display error message */
		while (is_array($vars) && list($k, $v) = each($vars) )
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if (isset($ar_req[$k])) { $ar_req_msg[$k] = '&#160;<span class="red"><b>*</b></span>'; }
			if (isset($ar_broken[$k])) { $ar_broken_msg[$k] = '<span class="red"><b>' . $this->oL->m('reason_9') . '</b></span><br />'; }
		}
		/* */
		if ( $this->gw_this['vars']['tid'] && isset($this->ar_profiles[$this->gw_this['vars']['tid']]) )
		{
			$str_form .= getFormTitleNav( $this->ar_profiles[$this->gw_this['vars']['tid']], '<span style="float:right">'.$oForm->get_button('submit').'</span>' );
		}
		else
		{
			$str_form .= getFormTitleNav( $this->oL->m('3_profile'), '<span style="float:right">'.$oForm->get_button('submit').'</span>' );
		}
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tbody><tr><td style="width:'.$v_td1_width.'"></td><td>';
		$str_form .= '</td></tr>';

		/* Allows to upload a file */
		if ($this->sys['is_upload'])
		{
			$oForm->setTag('select', 'class',  'input');
			$oForm->setTag('select', 'style',  '');
			$oForm->setTag('file', 'id', 'file_location_xml');
			$oForm->setTag('file', 'dir', 'ltr');
			$oForm->setTag('file', 'size', '25');

			$oForm->setTag('textarea', 'style',  'height:15em;width:100%;font:85% verdana,arial,sans-serif"');
			$str_form .= '<tr>'.
						'<td class="td1">XML</td>'.
						'<td class="td2">' . $oForm->field('textarea', 'arPost[xml]', $vars['xml']) . '</td>'.
						'</tr>';
			
			$str_form .= '<tr>'.
						'<td class="td1">&#160;</td>'.
						'<td class="td2">' . $oForm->field('file', 'file_location', $vars['file_location']) . '</td>'.
						'</tr>';
			$this->ar_profiles[0] = '('.$this->oL->m('3_profile').': '.$this->oL->m('3_add').')';
			$str_form .= '<tr>'.
						'<td class="td1">'.$this->oL->m('3_profile').'</td>'.
						'<td class="td2">'. $oForm->field('select', 'arPost[id_profile]', $this->gw_this['vars']['tid'], 0, $this->ar_profiles) .'</td>'.
						'</tr>';
		}
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $str_hidden;
		return $oForm->Output($str_form);
	}
	/* */
	function get_form_export($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
	{
		$oForm = new gwForms();
		
		$str_hidden = '';
		$str_form = '';
		$v_class_1 = 'td1';
		$v_class_2 = 'td2';
		$v_td1_width = '25%';
		
		$oForm = new gwForms();
		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitok', $this->oL->m('3_export'));
		$oForm->Set('submitcancel', $this->oL->m('3_cancel'));
		$oForm->Set('formbgcolor', $this->ar_theme['color_2']);
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']);
		$oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
		$oForm->Set('align_buttons', $this->sys['css_align_right']);
		$oForm->Set('formwidth', '100%');
		$oForm->Set('charset', $this->sys['internal_encoding']);

		$ar_req = array_flip($ar_req);
		/* mark fields as "Required" and display error message */
		while (is_array($vars) && list($k, $v) = each($vars) )
		{
			$ar_req_msg[$k] = $ar_broken_msg[$k] = '';
			if (isset($ar_req[$k])) { $ar_req_msg[$k] = '&#160;<span class="red"><strong>*</strong></span>'; }
			if (isset($ar_broken[$k])) { $ar_broken_msg[$k] = '<span class="red"><strong>' . $this->oL->m('reason_9') . '</strong></span><br />'; }
		}
		/* */
		$str_form .= getFormTitleNav( $this->ar_profile['profile_name'], '<span style="float:right">'.$oForm->get_button('submit').'</span>' );
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tbody><tr><td style="width:'.$v_td1_width.'"></td><td>';
		$str_form .= '</td></tr>';
		/* */
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1068') . '</td>'.
					'<td class="disabled" style="text-align:left">' . wordwrap($this->sys['path_export'].'/'.$this->filename, 16, "\xe2\x80\x8b", 1). '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_as_file]', $vars['is_as_file']) . '</td>'.
					'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_as_file]').'">'.$this->oL->m('1299').'</label></td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		
		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
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
$oAddonAdm = new gw_addon_custom_az_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['custom_pages_'.GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';
/* end of file */
?>