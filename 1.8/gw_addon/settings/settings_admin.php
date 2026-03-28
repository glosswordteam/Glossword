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
	die('<!-- $Id: settings_admin.php 515 2008-07-07 00:28:18Z glossword_team $ -->');
}
/* */
class gw_addon_settings_admin extends gw_addon
{
	var $str;
	var $ar_component = array();
	/* Current component name */
	var $component;
	/* Autoexec */
	function gw_addon_settings_admin()
	{
		$this->init();
	}
	/* */
	function _get_nav()
	{
		return '<div class="actions-secondary">'.
			implode(' ', $this->gw_this['ar_actions_list'][$this->component]).
			'</div>';
	}
	/**
	 * @param    array   $vars       posted variables
	 * @param    int     $runtime    is this form posted first time [ 0 - no | 1 - yes ] // todo: bolean
	 * @param    array   $arBroken   the names of broken fields (after post)
	 * @param    array   $arReq      the names of required fields (after post)
	 * @return   string  complete HTML-code
	 * @see textcodetoform(), getFormHeight()
	 */
	function get_form($vars, $runtime = 0, $arBroken = array(), $arReq = array())
	{
		$topic_mode = 'form';
		$str_form = '';
		$trClass = 't';
		$v_class_1 = 'td1';
		$v_class_2 = 'td2';
		$v_td1_width = '25%';
		$trDisabled = 'disabled';
		$oForm = new gwForms();

		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitok', $this->oL->m('3_save'));
		$oForm->Set('submitcancel', $this->oL->m('3_cancel'));
		$oForm->Set('formbgcolor', $this->ar_theme['color_2']);
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']);
		$oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
		$oForm->Set('align_buttons', $this->sys['css_align_right']);
		$oForm->Set('charset', $this->sys['internal_encoding']);
		$oForm->Set('method', 'post');
		$oForm->arLtr = array('arPost[y_email]', 'arPost[locale_name]', 'arPost[visualtheme]','arPost[path_log]'
		);
		## ----------------------------------------------------
		##
		// reverse array keys <-- values;
		$arReq = array_flip($arReq);
		// mark fields as "REQUIRED" and make error messages
		while (is_array($vars) && list($key, $val) = each($vars) )
		{
			$arReqMsg[$key] = $arBrokenMsg[$key] = '';
			if (isset($arReq[$key])) { $arReqMsg[$key] = '&#160;<span class="red"><strong>*</strong></span>'; }
			if (isset($arBroken[$key])) { $arBrokenMsg[$key] = ' <span class="red"><strong>' . $this->oL->m('reason_9') .'</strong></span><br/>'; }
		}
		##
		## ----------------------------------------------------
		$oForm->setTag('select', 'class',  'input50');
		unset($this->gw_this['ar_themes_select']['gw_admin']);

		$str_form .= getFormTitleNav($this->oL->m('1137'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';

		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('site_name') . ':' .  $arReqMsg['site_name'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['site_name'] . $oForm->field('input', 'arPost[site_name]', gw_fix_db_to_field($vars['site_name']), 255) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('site_desc') . ':' .  $arReqMsg["site_desc"] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['site_desc'] . $oForm->field("textarea", "arPost[site_desc]", gw_fix_db_to_field($vars['site_desc']), $this->oFunc->getFormHeight($vars['site_desc'])) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('contact_name') . ':' .  $arReqMsg["y_name"] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['y_name'] . $oForm->field("input", "arPost[y_name]", textcodetoform($vars['y_name']), 127) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('contact_email') . ':' .  $arReqMsg["y_email"] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['y_email'] . $oForm->field("input", "arPost[y_email]", textcodetoform($vars['y_email']), 127) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('keywords') . ':' .  $arReqMsg["keywords"] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['keywords'] . $oForm->field("textarea", "arPost[keywords]", gw_fix_db_to_field($vars['keywords']), $this->oFunc->getFormHeight($vars['keywords'])) . '</td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>'; 
		
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
		/* 1.8.7 */
		$ar_link_modes = array(
			GW_PAGE_LINK_ID => $this->oL->m('1300').' (&amp;id=123)',
			GW_PAGE_LINK_URI => $this->oL->m('1301').' (&amp;id=term-123)',
			GW_PAGE_LINK_NAME => $this->oL->m('1302').' (&amp;id=Term)'
		);
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_mod_rewrite]', $vars['is_mod_rewrite']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_mod_rewrite_">' . $this->oL->m('1304') . '</label></td>'.
					'</tr>';
		$oForm->setTag('select', 'class', 'input75');
		$oForm->setTag('select', 'style', '');
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1303') . ':' .  $arReqMsg['pages_link_mode'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['pages_link_mode'] . $oForm->field('select', 'arPost[pages_link_mode]', $vars['pages_link_mode'], 0, $ar_link_modes) . '</td>'.
					'</tr>';

		$oForm->setTag('input', 'class', '');
		$oForm->setTag('input', 'size', '4');
		$oForm->setTag('input', 'maxlength', '4');
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>'; 
		  
		$str_form .= getFormTitleNav($this->oL->m('1136'), '');
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('default_lang') . ':' .  $arReqMsg['locale_name'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['locale_name'] . $oForm->field("select", 'arPost[locale_name]', $vars['locale_name'], 0, $this->oL->getLanguages()) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('visual_theme') . ':' .  $arReqMsg['visualtheme'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $oForm->field("select", "arPost[visualtheme]", $vars['visualtheme'], 0, $this->gw_this['ar_themes_select']);
		if ($this->oSess->is('is-sys-settings'))
		{
			$str_form .= ' <span class="actions-third">'.$this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'=visual-themes', $this->oL->m('3_edit')).'</span>';
		}
		$str_form .= '</td></tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_list_numbers]', $vars['is_list_numbers']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_list_numbers_">' . $this->oL->m('1064') . '</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_list_images]', $vars['is_list_images']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_list_images_">' . $this->oL->m('1065') . '</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_show_topic_descr]', $vars['is_show_topic_descr']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_show_topic_descr_">' . $this->oL->m('1154') . '</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_list_announce]', $vars['is_list_announce']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_list_announce_">' . $this->oL->m('1066') . '</label></td>'.
					'</tr>';

		$oForm->setTag('input', 'class', 'input');
		$oForm->setTag('input', 'size', '11');
		$oForm->setTag('input', 'maxlength', '22');
		$oForm->setTag('input', 'dir', $this->oL->languagelist('1') );

		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['txt_sep_breadcrump'] . $oForm->field('input', 'arPost[txt_sep_breadcrump]', textcodetoform($vars['txt_sep_breadcrump'])) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1098') . $arReqMsg["txt_sep_breadcrump"] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['txt_sep_htmltitle'] . $oForm->field('input', 'arPost[txt_sep_htmltitle]', textcodetoform($vars['txt_sep_htmltitle'])) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1097') . $arReqMsg["txt_sep_breadcrump"] . '</td>'.
					'</tr>';
		$oForm->setTag('input', 'style', '');
		$oForm->setTag('input', 'size', '4');
		$oForm->setTag('input', 'class', 'input0');
		$oForm->setTag('input', 'maxlength', '4');
		$oForm->setTag('input', 'dir', $this->sys['css_dir_numbers'] );
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['page_limit'] . $oForm->field('input', 'arPost[page_limit]', $vars['page_limit']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('page_limit') . $arReqMsg['page_limit'] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['page_limit_search'] . $oForm->field('input', 'arPost[page_limit_search]', $vars['page_limit_search']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1096') . $arReqMsg['page_limit_search'] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['max_dict_updated'] . $oForm->field('input', "arPost[max_dict_updated]", $vars['max_dict_updated']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1062') .  $arReqMsg["max_dict_updated"] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['max_dict_top'] . $oForm->field('input', "arPost[max_dict_top]", $vars['max_dict_top']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1063') . $arReqMsg["max_dict_top"] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['time_new'] . $oForm->field('input', "arPost[time_new]", $vars['time_new']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('time_new') . $arReqMsg["time_new"] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['time_upd'] . $oForm->field('input', "arPost[time_upd]", $vars['time_upd']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('time_upd') . $arReqMsg["time_upd"] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['int_max_char_defn'] . $oForm->field('input', "arPost[int_max_char_defn]", $vars['int_max_char_defn']) .  '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1133') . $arReqMsg["int_max_char_defn"] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_use_xhtml]', $vars['is_use_xhtml']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_use_xhtml_">' . $this->oL->m('1197') . '</label></td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';


		$oForm->setTag('input', 'style', '');
		$oForm->setTag('input', 'size', '');
		$oForm->setTag('input', 'class', 'input');
		$oForm->setTag('input', 'maxlength', '');
		$oForm->setTag('input', 'dir', '');
		
		/* E-mail settings */
		$str_form .= getFormTitleNav($this->oL->m('1351'));
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';

		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1354') . ':' .  $arReqMsg['site_email'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['site_email'] . $oForm->field('input', 'arPost[site_email]', $vars['site_email']) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1353') . ':' .  $arReqMsg['site_email_from'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['site_email_from'] . $oForm->field('input', 'arPost[site_email_from]', $vars['site_email_from']) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1352') . ':' .  $arReqMsg['mail_subject_prefix'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $arBrokenMsg['mail_subject_prefix'] . $oForm->field('input', 'arPost[mail_subject_prefix]', $vars['mail_subject_prefix']) . '</td>'.
					'</tr>';

		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		
		$oForm->setTag('input', 'style', '');
		$oForm->setTag('input', 'size', '4');
		$oForm->setTag('input', 'class', 'input0');
		$oForm->setTag('input', 'maxlength', '4');
		$oForm->setTag('input', 'dir', $this->sys['css_dir_numbers'] );
		
		$str_form .= getFormTitleNav($this->oL->m('1116'));
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['avatar_max_x'] . $oForm->field('input', 'arPost[avatar_max_x]', $vars['avatar_max_x']) .  '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1130') . $arReqMsg["avatar_max_x"] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['avatar_max_y'] . $oForm->field('input', 'arPost[avatar_max_y]', $vars['avatar_max_y']) .  '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1131') . $arReqMsg['avatar_max_y'] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['avatar_max_kb'] . $oForm->field('input', 'arPost[avatar_max_kb]', $vars['avatar_max_kb']) .  '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1132') . ', ' . $this->oL->m('kb') . $arReqMsg['avatar_max_kb'] . '</td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		
#	$oForm->setTag('input', 'class', 'input');
#	$oForm->setTag('input', 'size', '20');
#	$oForm->setTag('input', 'dir', '');

		$str_form .= getFormTitleNav($this->oL->m('1056'));
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_log_search]', $vars['is_log_search']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_log_search_">' . $this->oL->m('1101') . '</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['max_days_searchlog'] . $oForm->field('input', 'arPost[max_days_searchlog]', $vars['max_days_searchlog']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1129') .  $arReqMsg['max_days_searchlog'] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['max_days_searchcache'] . $oForm->field('input', 'arPost[max_days_searchcache]', $vars['max_days_searchcache']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1128') .  $arReqMsg['max_days_searchcache'] . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $arBrokenMsg['max_days_history_terms'] . $oForm->field('input', 'arPost[max_days_history_terms]', $vars['max_days_history_terms']) . '</td>'.
					'<td class="'.$v_class_2.'">' . $this->oL->m('1296') .  $arReqMsg['max_days_history_terms'] . '</td>'.
					'</tr>';
 		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		
		$str_form .= getFormTitleNav($this->oL->m('1135'));
		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<thead><tr><td style="width:'.$v_td1_width.'"></td><td></td></tr></thead><tbody>';

		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_log_ref]', $vars['is_log_ref']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_log_ref_">' . $this->oL->m('1099') . '</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_log_mail]', $vars['is_log_mail']) . '</td>'.
					'<td class="'.$v_class_2.'"><label for="arPost_is_log_mail_">' . $this->oL->m('1100') . '</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('path_log') . ':' .  '</td>'.
					'<td class="disabled" style="text-align:left">' . textcodetoform($this->sys['path_logs']). '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('path_cache') . ':' .  '</td>'.
					'<td class="disabled" style="text-align:left">' . textcodetoform($this->sys['path_cache_sql']). '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1068') . ':' .  '</td>'.
					'<td class="disabled" style="text-align:left">' . textcodetoform($this->sys['path_export']). '</td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', 'post', 1);
		return $oForm->Output($str_form);
	}
	/* Contacting technical support */
	function get_form_support($vars)
	{
		$arSql = $this->oDb->sqlExec('SELECT version() AS v');

		$oForm = new gwForms();
		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitok', $this->oL->m('1035'));
		$oForm->Set('submitcancel', $this->oL->m('3_cancel'));
		$oForm->Set('formbgcolor', $this->ar_theme['color_2']);
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']);
		$oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
		$oForm->Set('align_buttons', $this->sys['css_align_right']);
		$oForm->Set('charset', $this->sys['internal_encoding']);

		$trClass = 'xt';
		$str_form = '';

		$str_form .= getFormTitleNav($this->oL->m('1001'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';

		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tbody>';
		$str_form .= '<tr><td style="width:25%"></td><td></td></tr>';
		$str_form .= '<tr><td class="td1">'.$this->oL->m('y_name').'</td>';
		$str_form .= '<td class="td2">'.
					$oForm->field('input', 'name', $this->oSess->user_get("user_name")).
					'</td></tr>';
		$str_form .= '<tr><td class="td1">'.$this->oL->m('y_email').'</td>';
		$str_form .= '<td class="td2">'.
					$oForm->field('input', 'email', $this->oSess->user_get("user_email")).
					'</td></tr>';
		$str_form .= '<tr><td class="td1">'.$this->oL->m('1025').'</td>';
		$str_form .= '<td class="disabled">'.
					$this->sys['version'].
					$oForm->field('hidden', 'arPost[gw_version]', $this->sys['version']).
					'</td></tr>';
		$str_form .= '<tr><td class="td1">'.$this->oL->m('1026').'</td>';
		$str_form .= '<td class="disabled">'.
					PHP_VERSION.
					$oForm->field('hidden', 'arPost[php_version]', PHP_VERSION).
					'</td></tr>';
		$str_form .= '<tr><td class="td1">'.$this->oL->m('1027').'</td>';
		$str_form .= '<td class="disabled">'.
					PHP_OS.
					$oForm->field('hidden', 'arPost[php_os]', PHP_OS).
					'</td></tr>';
		$str_form .= '<tr><td class="td1">'.$this->oL->m('1028').'</td>';
		$str_form .= '<td class="disabled">'.
					(isset($arSql[0]['v']) ? $arSql[0]['v'] : '').
					$oForm->field('hidden', 'arPost[db_version]', (isset($arSql[0]['v']) ? $arSql[0]['v'] : '')).
					'</td></tr>';
		$str_form .= '<tr><td class="td1">'.$this->oL->m('topic').'</td>';
		$oForm->setTag('select', 'style', 'width:98%');
		$oForm->setTag('select', 'class', 'input');
		$str_form .= '<td class="td2">'.
					$oForm->field('select', 'arPost[id_topic]', 0,  $vars['id_topic'], $this->ar_msg_topics).
					'</td></tr>';
		$str_form .= '<tr><td class="td1">'.$this->oL->m('message').'</td>';
		$str_form .= '<td class="td2">';
		$str_form .= '<textarea class="input" name="arPost[message]" style="height:10em">'.
					htmlspecialchars_ltgt($vars['message']).
					'</textarea>';
		$str_form .= '</td></tr>';
		$str_form .= '<tr><td colspan="2">';
		/* */
		$str_form .= '<table cellspacing="1" cellpadding="0" border="0" width="100%">';
		$str_form .= '<tbody>';
		$str_form .= '<tr>'.
					'<td class="td1">'.$oForm->field('checkbox', "arPost[is_attach]", $vars['is_attach']).'</td>'.
					'<td class="td2">'.'<label for="arPost_is_attach_">'.$this->oL->m(1024).'</label></td>'.
					'</tr>';
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
		$str_form .= $oForm->field('hidden', 'w1', $this->gw_this['vars']['w1']);
		$str_form .= $oForm->field('hidden', 'arPost[is_preview]', $vars['is_preview']);
		$str_form .= '</tbody></table>';
		/* */
		$str_config = gw_get_cfg();
		$str_form .= '</td></tr>';
		$str_form .= '<tr><td colspan="2">';
		$str_form .= '<div style="height:10em;border:1px solid #BCC8E2;overflow:auto;width:100%;color:#777;background:#FFF;font:70% verdana,arial,sans-serif">'.
					htmlspecialchars_ltgt($str_config).
					$oForm->field('hidden', 'arPost[sys_info]', htmlspecialchars_ltgt($str_config)).
					'</div>';
		$str_form .= '</td></tr>';

		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
		return $oForm->Output($str_form);
	}
	/* Preview for contacting technical support */
	function get_form_support_preview($vars)
	{
		$str = '';
		$vars['message'] = nl2br($vars['message']);

		$oForm = new gwForms();
		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitok', $this->oL->m('1036'));
		$oForm->Set('submitcancel', $this->oL->m('3_cancel'));
		$oForm->Set('formbgcolor', $this->ar_theme['color_2']);
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']);
		$oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
		$oForm->Set('align_buttons', $this->sys['css_align_right']);
		$oForm->Set('charset', $this->sys['internal_encoding']);

		$trClass = 'xt';
		$str_form = '';
		$v_class_1 = 'td1';
		$v_class_2 = 'td2';
		$v_td1_width = '20%';
	
		/* preview */
		$str_form .= getFormTitleNav($this->oL->m('1035'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');
		$str_form .= '<table style="text-align:'.$this->sys['css_align_left'].'" class="gw2TableFieldset" width="100%"><tbody>';
		$str_form .= '<tr class="'.$trClass.'"><td>'.$this->oL->m('y_name').'</td><td>'.$vars['name'].'</td></tr>';
		$str_form .= '<tr class="'.$trClass.'"><td>'.$this->oL->m('y_email').'</td><td>'.$vars['email'].'</td></tr>';
		$str_form .= '<tr class="'.$trClass.'"><td>'.$this->oL->m('1025').'</td><td>'.$vars['gw_version'].'</td></tr>';
		$str_form .= '<tr class="'.$trClass.'"><td>'.$this->oL->m('1026').'</td><td>'.$vars['php_version'].'</td></tr>';
		$str_form .= '<tr class="'.$trClass.'"><td>'.$this->oL->m('1027').'</td><td>'.$vars['php_os'].'</td></tr>';
		$str_form .= '<tr class="'.$trClass.'"><td>'.$this->oL->m('1028').'</td><td>'.$vars['db_version'].'</td></tr>';
		$str_form .= '<tr class="'.$trClass.'"><td>'.$this->oL->m('topic').'</td><td>'.$this->ar_msg_topics[$vars['id_topic']].'</td></tr>';
		$str_form .= '<tr style="vertical-align:top"><td class="'.$trClass.'">'.$this->oL->m('message').'</td><td class="xu">'. $vars['message'] .'</td></tr>';
		if ($vars['is_attach'])
		{
			$str_form .= '<tr class="'.$trClass.'"><td>'.$this->oL->m('options').'</td><td>'.$this->oL->m(1024).'</td></tr>';
		}
		$str_form .= '<tr><td style="width:20%"></td><td>';
		for (; list($k, $v) = each($vars);)
		{
			if (!$vars['is_attach'] && ($k == 'sys_info'))
			{
				continue;
			}
			$str_form .= $oForm->field('hidden', 'arPost['. $k .']', htmlspecialchars_ltgt($v));
		}
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
		$str_form .= $oForm->field('hidden', 'w1', $this->gw_this['vars']['w1']);
		$str_form .= $oForm->field('hidden', 'arPost[is_preview]', $vars['is_preview']);
		$str_form .= '</td></tr>';

		$str_form .= '</tbody></table>';
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
				$strR .= $this->oL->m('reason_13');
			}
		}
	}
}
/* */
$oAddonAdm = new gw_addon_settings_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['settings_'.GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';
/* end of file */
?>