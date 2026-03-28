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
	die('<!-- $Id: custom-pages_admin.php 515 2008-07-07 00:28:18Z glossword_team $ -->');
}
/* */
class gw_addon_custom_pages_admin extends gw_addon
{
	var $str;
	var $ar = array();
	var $ar_component = array();
	/* Current component name */
	var $component;
	/* Autoexec */
	function gw_addon_custom_pages_admin()
	{
		$this->init();
#		$this->oL->setHomeDir($this->sys['path_locale']);
#		$this->oL->getCustom('addon_'.$this->component, $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');
		/* Get the list of pages */
		$this->ar = gw_create_tree_custom_pages();
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
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT) 
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
		$oForm->setTag('select', 'class',  'input50');
		/* */
#		$fieldname = $this->component;
		$fieldname = 'page';
		$int_custom_pages = sizeof($vars['page']);

		$str_form .= getFormTitleNav($this->oL->m('1061'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');
		for (; list($elK, $arV) = each($vars['page']);)
		{
			$tmp['strBtnRemove'] = '';
			$tmp['strBtnAdd'] = '<input type="submit" style="text-align:center;width:24px;height:24px" class="submitcancel" name="'.
								'arControl['.$fieldname.']['.GW_A_ADD.']['.$elK.'][0]" title="'.
								$this->oL->m('3_add').'" value="+"/>';
			if ( $int_custom_pages > 1 )
			{
				$tmp['strBtnRemove'] = '<input type="submit" style="text-align:center;width:24px;height:24px" class="submitdel" name="'.
									   'arControl['.$fieldname.']['.GW_A_REMOVE.']['.$elK.'][0]" title="'.
									   $this->oL->m('1157').'" value="&#215;"/>';
			}
			$tmp['strHtmlTB'] = $tmp['strBtnRemove'] . $tmp['strBtnAdd'];
			
			$str_form .= '<fieldset class="admform" style="direction:'.$this->sys['css_dir_numbers'].'">';
			$str_form .= '<legend>&#160;';
			$str_form .= $tmp['strHtmlTB'];
			$str_form .= '</legend>';
			$str_form .= '<div style="direction:'.$this->sys['css_dir_text'].'">';
			
			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<tr><td style="width:'.$v_td1_width.'"></td><td>';
			$str_form .= $oForm->field('hidden', 'arPre[page]['.$elK.'][id_page_phrase]', $arV['id_page_phrase']);
			$str_form .= $oForm->field('hidden', 'arPre[page]['.$elK.'][id_lang]', $arV['id_lang']);
			$str_form .= '</td></tr><tbody>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('dict_name') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('input', 'arPre[page]['.$elK.'][page_title]', gw_fix_db_to_field($arV['page_title'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('announce') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('textarea', 'arPre[page]['.$elK.'][page_descr]', gw_fix_db_to_field($arV['page_descr']), $this->oFunc->getFormHeight($arV['page_descr'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('1058') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('textarea', 'arPre[page]['.$elK.'][page_content]', gw_fix_db_to_field($arV['page_content']), $this->oFunc->getFormHeight($arV['page_content'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('keywords') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('textarea', 'arPre[page]['.$elK.'][page_keywords]', gw_fix_db_to_field($arV['page_keywords']), $this->oFunc->getFormHeight($arV['page_keywords'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('lang') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPre[page]['.$elK.'][id_lang]', $arV['id_lang'], 0, $this->gw_this['vars']['ar_languages'], '', 'input50' ) . '</td>'.
						'</tr>';
			$str_form .= '</tbody></table>';
			$str_form .= '</div>';
			$str_form .= '</fieldset>';
		}

		$str_form .= getFormTitleNav($this->oL->m('1137'), '');
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPre[is_active]', $vars['is_active']) . '</td>'.
					'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPre[is_active]').'">'.$this->oL->m('1320').'</label></td>'.
					'</tr>';

		global $topic_mode;
		$topic_mode = 'form';
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_ADD) /* adding a topic */
		{
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1156') . ':</td>'.
					'<td class="'.$v_class_2.'">'
						   . '<select name="arPre[id_parent]" class="input50">'
						   . '<option value="0">'. $this->oL->m('root_topic') .'</option>'
						   . gw_get_thread_pages($vars['ar'], 0, 1)
						   . '</select>' .
					'</td>'.
					'</tr>';
		}
		else
		{
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1156') . ':</td>'.
					'<td class="'.$v_class_2.'">'
						   . '<select name="arPre[id_parent]"  class="input50">'
						   . '<option value="0">'. $this->oL->m('root_topic') .'</option>'
						   . gw_get_thread_pages($vars['ar'], 0, 1)
						   . '</select>' .
					'</td>'.
					'</tr>';
		}
		$oForm->setTag('input', 'onkeyup', 'gwJS.strNormalize(this)');
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1060') . ':</td>'.
					'<td class="'.$v_class_2.'">' . $this->sys['server_dir'].'/'.$this->sys['path_temporary'].'/t/'.$this->sys['visualtheme'].'/<br />' . $oForm->field('input', 'arPre[page_icon]', gw_fix_db_to_field($vars['page_icon'])) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1073') . ':</td>'.
					'<td class="'.$v_class_2.'">' . $this->sys['server_dir'].'/'. GW_A_CUSTOMPAGE. '/<br />'.$oForm->field('input', 'arPre[page_uri]', gw_fix_db_to_field($vars['page_uri'])) . '</td>'.
					'</tr>';
		$oForm->setTag('input', 'onkeyup', '');

		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1059') . ' 1:</td>'.
					'<td class="'.$v_class_2.'">&lt;?'.'php<br />' . $oForm->field('textarea', 'arPre[page_php_1]', gw_fix_db_to_field($vars['page_php_1']), $this->oFunc->getFormHeight($vars['page_php_1'])) . '<br />?&gt;</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1059') . ' 2:</td>'.
					'<td class="'.$v_class_2.'">&lt;?'.'php<br />' . $oForm->field('textarea', 'arPre[page_php_2]', gw_fix_db_to_field($vars['page_php_2']), $this->oFunc->getFormHeight($vars['page_php_2'])) . '<br />?&gt;</td>'.
					'</tr>';

		$str_form .= '</tbody></table>';

		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field("hidden", GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
#		$str_form .= $oForm->field('hidden', 'arPost[id_action_old]', $vars['id_action_old']);
		$str_form .= $str_hidden;
		return $oForm->Output($str_form);
	}
	/* Import and Export */
	function get_dates()
	{
		$arSql = $this->oDb->sqlExec( $this->oSqlQ->getQ('get-date-mm', $this->sys['tbl_prefix'].'pages') );
		$ar = array('max' => time(), 'min' => 0);
		for (; list($arK, $arV) = each($arSql);)
		{
			if (empty($arV['max']) && empty($arV['min']))
			{
				/* no date */
				$ar['max'] = $ar['min'] = $this->sys['time_now_gmt_unix'];
			}
			else
			{
				$ar['max'] = $arV['max'];
				$ar['min'] = $arV['min'];
			}
		}
		return $ar;
	}
	/* HTML-form for Export */
	function get_form_export($vars)
	{
		$str_form = '';
		/* */
		$oForm = new gwForms();
		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitdel', $this->oL->m('3_remove'));
		$oForm->Set('submitok', $this->oL->m('3_export'));
		$oForm->Set('submitcancel', $this->oL->m('3_cancel'));
		$oForm->Set('formbgcolor', $this->ar_theme['color_2']);
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']);
		$oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
		$oForm->Set('align_buttons', $this->sys['css_align_right']);
		$oForm->Set('formwidth', '100%');
		$oForm->Set('charset', $this->sys['internal_encoding']);
		/* */
		$str_form .= getFormTitleNav( $this->oL->m('3_export'), '<span style="float:right">'.$oForm->get_button('submit').'</span>' );
  
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tr><td style="width:15%"></td><td></td></tr><tbody>';
		$str_form .= '<tr><td></td><td class="td2">' . $this->oL->m('tip001') . '</td></tr>';
		$str_form .= '<tr>'.
					'<td class="td1">' . $this->oL->m('from_time') . ':</td>'.
					'<td class="td2">' . htmlFormSelectDate('arPost[date_min]', @date("YmdHis", $vars['min'])) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="td1">' . $this->oL->m('till_time') . ':</td>'.
					'<td class="td2">' . htmlFormSelectDate('arPost[date_max]', @date("YmdHis", $vars['max'])) . '</td>'.
					'</tr>';

		$str_form .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[is_as_file]', $vars['is_as_file']) . '</td>'.
					'<td class="td2">' . '<label for="'.$oForm->text_field2id('arPost[is_as_file]').'">'.$this->oL->m('1299').'</label></td>'.
					'</tr>';

		$str_form .= '<tr><td></td>'.
					'<td class="td2" style="text-align:center;background:'.$this->ar_theme['color_1'].'">';
		$str_form .= '<a href="javascript:setToday();">' . $this->oL->m('today') . '</a>';
		$str_form .= ' • <a href="javascript:setD();">' . $this->oL->m('yesterday') . '</a>';
		$str_form .= ' • <a href="javascript:setM();">' . $this->oL->m('month') . '</a>';
		$str_form .= ' • <a href="javascript:setAll();">' . $this->oL->m('3_all_time') . '</a>';
		$str_form .= '</td>';
		$str_form .= '</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		/* */
		$strForm = '';
		include($this->sys['path_include'] . '/'. GW_ACTION . '.' . $this->gw_this['vars'][GW_ACTION] . '.js.php');
		$str_form .= $strForm;

		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', 'arPost[fmt]', 'xml');
		return $oForm->Output($str_form);
	}
	/* HTML-form for Import*/
	function get_form_import($vars)
	{
		$str_form = '';
		/* */
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
		/* */
		$str_form .= getFormTitleNav($this->oL->m('3_import'), '<strong class="xw">'.$this->gw_this['vars']['tid'].'</strong>');
		$str_form .= '<table class="gw2TableFieldset gray" width="100%">';
		$str_form .= '<tbody>';
		$str_form .= '<tr><td style="width:1%"></td><td></td></tr><tbody>';

		$str_form .= '<tr>'.
					 '<td class="td1"></td>'.
					 '<td class="td2">XML</td>'.
					'</tr>';
		$str_form .= '<tr>';
		$str_form .= '<td></td>';
		$str_form .= '<td>';

		$str_form .= '<table id="table_xml" class="gw2TableFieldset" width="100%" style="border:1px '.$this->ar_theme['color_4'].' solid"><tbody>';
		$str_form .= '<tr>'.
					'<td class="td1" style="width:11%"></td>'.
					'<td class="td2"><textarea '.
					' onfocus="if(typeof(document.layers)==\'undefined\'||typeof(ts)==\'undefined\'){ts=1;this.form.elements[\'arPost[\'+\'xml\'+\']\'].select();}"'.
					' style="width:100%;font:85% verdana,arial,sans-serif"'.
					' name="arPost[xml]" id="arPost_xml_" dir="ltr" cols="45" rows="10">' . htmlspecialchars_ltgt($vars['xml']) . '</textarea>'.
					'</td>'.
					'</tr>';
		/* Allows to upload a file */
		if ($this->sys['is_upload'])
		{
			$oForm->setTag('file', 'id', 'file_location_xml');
			$oForm->setTag('file', 'dir', 'ltr');
			$str_form .= '<tr>'.
						'<td class="td1">&#160;</td>'.
						'<td class="td1">' . $oForm->field('file', 'file_location', $vars['file_location']) . '</td>'.
						'</tr>';
		}
		$arBoxId['id'] = 'arPost_is_overwrite_';
		$str_form .= '<tr>';
		$str_form .= '<td class="td1">' . $oForm->field('radio', 'arPost[is_overwrite]', '1', $vars['is_overwrite'], $arBoxId) . '</td>';
		$str_form .= '<td class="td2"><label for="arPost_is_overwrite_">'.$this->oL->m('overwrite').'</label></td>';
		$str_form .= '</tr>';
		$arBoxId['id'] = 'arPost_is_merge_';
		$str_form .= '<tr>';
		$str_form .= '<td class="td1">' . $oForm->field('radio', 'arPost[is_overwrite]', '0', $vars['is_merge'], $arBoxId) . '</td>';
		$str_form .= '<td class="td2"><label for="arPost_is_merge_">'.$this->oL->m('1158').'</label></td>';
		$str_form .= '</tr>';
		$str_form .= '</tbody></table>';
		/* */
		$str_form .= '</td></tr>';
		$str_form .= '</tbody></table>';

		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
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
$oAddonAdm = new gw_addon_custom_pages_admin;
$oAddonAdm->alpha();
/* */
/* Do not load old components */
$pathAction = '';
/* end of file */
?>