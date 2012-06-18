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
	die('<!-- $Id: topics_admin.php 515 2008-07-07 00:28:18Z glossword_team $ -->');
}
/**
 *  Actions for topics: add, browse, edit, remove, update, import, export.
 */
/* */
class gw_addon_topic_admin extends gw_addon
{
	/* Autoexec */
	function gw_addon_topic_admin()
	{
		$this->init();
		/* */
#		$this->oL->setHomeDir($this->sys['path_locale']);
#		$this->oL->getCustom('addon_'.$this->component, $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');
	}
	/* */
	function _get_nav()
	{
#		$this->oL->setHomeDir($this->sys['path_locale']);
#		$this->oL->getCustom('addon_'.$this->component, $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');
		return '<div class="actions-secondary">'.
			implode(' ', $this->gw_this['ar_actions_list'][$this->component]).
			'</div>';
	}
	/* */
	function get_form_topic($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
	{
		$str_hidden = '';
		$str_form = '';
		$v_class_1 = 'td1';
		$v_class_2 = 'td2';
		$v_td1_width = '25%';

		$oForm = new gwForms();
		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitdel', $this->oL->m('3_remove'));
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
		$oForm->setTag('select', 'class',  'input50');
		/* */
		$fieldname = 'topic';
		$int_topics = sizeof($vars['topic']);

		$str_form .= getFormTitleNav($this->oL->m('1061'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');

		for (; list($elK, $arV) = each($vars['topic']);)
		{
			$arV['topic_title'] = str_replace(array('{', '}'), array('{%', '%}'), $arV['topic_title']);
			$arV['topic_descr'] = str_replace(array('{', '}'), array('{%', '%}'), $arV['topic_descr']);
			$tmp['strBtnRemove'] = '';
			$tmp['strBtnAdd'] = '<input tabindex="7" type="submit" style="width:24px;height:24px" class="submitcancel" name="'.
								'arControl['.$fieldname.']['.GW_A_ADD.']['.$elK.'][0]" title="'.
								$this->oL->m('3_add').'" value="+"/>';
			if ( $int_topics > 1 )
			{
				$tmp['strBtnRemove'] = '<input tabindex="8" type="submit" style="width:24px;height:24px" class="submitdel" name="'.
									   'arControl['.$fieldname.']['.GW_A_REMOVE.']['.$elK.'][0]" title="'.
									   $this->oL->m('3_remove_topic').'" value="&#215;"/>';
			}
			$tmp['strHtmlTB'] = $tmp['strBtnRemove'] . $tmp['strBtnAdd'];

			$str_form .= '<fieldset class="admform" style="direction:'.$this->sys['css_dir_numbers'].'">';
			$str_form .= '<legend>&#160;';
			$str_form .= $tmp['strHtmlTB'];
			$str_form .= '</legend>';
			$str_form .= '<div style="direction:'.$this->sys['css_dir_text'].'">';

			$str_form .= '<table class="gw2TableFieldset" width="100%">';
			$str_form .= '<tr><td style="width:'.$v_td1_width.'"></td><td>';
			$str_form .= $oForm->field('hidden', 'arPre[topic]['.$elK.'][id_topic_phrase]', $arV['id_topic_phrase']);
			$str_form .= $oForm->field('hidden', 'arPre[topic]['.$elK.'][id_lang]', $arV['id_lang']);
			$str_form .= '</td></tr><tbody>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('dict_name') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('input', 'arPre[topic]['.$elK.'][topic_title]', htmlspecialchars_ltgt($arV['topic_title'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('announce') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('textarea', 'arPre[topic]['.$elK.'][topic_descr]', htmlspecialchars_ltgt($arV['topic_descr']), $this->oFunc->getFormHeight($arV['topic_descr'])) . '</td>'.
						'</tr>';
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('lang') . ':</td>'.
						'<td class="'.$v_class_2.'">' . $oForm->field('select', 'arPre[topic]['.$elK.'][id_lang]', $arV['id_lang'], 0, $this->gw_this['vars']['ar_languages'], '', 'input50' ) . '</td>'.
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
#		global $topic_mode;
#		$topic_mode = 'form';

		$this->sys['topic_mode'] = 'form';
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_ADD) /* adding a topic */
		{
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('3_undertopic') . ':</td>'.
					'<td class="'.$v_class_2.'">'
						   . '<select name="arPre[id_parent]" class="input" style="width:75%">'
						   . '<option value="0">'. $this->oL->m('root_topic') .'</option>'
						   . gw_get_thread_pages($vars['ar'], 0, 1)
						   . '</select>' .
					'</td>'.
					'</tr>';
		}
		else
		{
			$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('3_undertopic') . ':</td>'.
					'<td class="'.$v_class_2.'">'
						   . '<select name="arPre[id_parent]" class="input" style="width:75%">'
						   . '<option value="0">'. $this->oL->m('root_topic') .'</option>'
						   . gw_get_thread_pages($vars['ar'], 0, 1)
						   . '</select>' .
					'</td>'.
					'</tr>';
		}
		$oForm->setTag('input', 'onkeyup', 'gwJS.strNormalize(this)');
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('1060') . ':</td>'.
					'<td class="'.$v_class_2.'">' . $this->sys['server_dir'].'/'.$this->sys['path_temporary'].'/t/'.$this->sys['visualtheme'].'/<br />' . $oForm->field('input', 'arPre[topic_icon]', htmlspecialchars_ltgt($vars['topic_icon'])) . '</td>'.
					'</tr>';
		$oForm->setTag('input', 'onkeyup', '');
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
			
		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
		$str_form .= $str_hidden;
		return $oForm->Output($str_form);
	}
	/* Import and Export */
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
		$str_form .= getFormTitleNav($this->oL->m('3_export'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tr><td style="width:25%"></td><td></td></tr><tbody>';

		$str_form .= '<tr>'.
					'<td class="td1">' . $this->oL->m('1068') . '</td>'.
					'<td class="disabled" style="text-align:left">' . wordwrap($this->sys['path_export'].'/'.$this->filename, 16, "\xe2\x80\x8b", 1). '</td>'.
					'</tr>';

		$str_checkbox = $oForm->field('checkbox', 'arPost[is_include_date]', $vars['is_include_date']);
		$str_label = '<label for="arPost_is_include_date_">'. $this->oL->m('1153') .'</label>';
		$str_form .= '<tr><td class="td1">'. $str_checkbox .'</td><td class="td2">'.  $str_label .'</td></tr>';

		$str_form .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[is_as_file]', $vars['is_as_file']) . '</td>'.
					'<td class="td2">' . '<label for="'.$oForm->text_field2id('arPost[is_as_file]').'">'.$this->oL->m('1299').'</label></td>'.
					'</tr>';

		$str_form .= '</tbody></table>';

		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		return $oForm->Output($str_form);
	}
	/* */
	function export()
	{
		if ($this->gw_this['vars']['post'] == '')
		{
			/* Not submitted */
			$arV['is_include_date'] = 1;
			$arV['is_as_file'] = 0;
			$this->str .= $this->get_form_export($arV);
		}
		else
		{
			$arPost =& $this->gw_this['vars']['arPost'];
			/* Fix on/off options */
			$arIsV = array('is_include_date', 'is_as_file');
			for (; list($k, $v) = each($arIsV);)
			{
				$arPost[$v]  = isset($arPost[$v]) ? $arPost[$v] : 0;
			}
			/* */
			$xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
			$xml .= '<glossword>';
			/* */
			$arSql = $this->oDb->sqlExec('SELECT * FROM `'.$this->sys['tbl_prefix'].'topics`');
			for (; list($k, $arV) = each($arSql);)
			{
				$style_attr = '';
				$id_topic = $arV['id_topic'];
				unset($arV['id_topic']);
				if (!$arPost['is_include_date'])
				{
					unset($arV['date_created']);
					unset($arV['date_modified']);
				}
				$xml .= CRLF . '<topic id="'.$id_topic.'">';
				/* Now serialize all parameters. Fast and easy. */
				$xml .= CRLF . "\t". '<parameters><![CDATA['. serialize($arV) .']]></parameters>';
				/* get topic names */
				$xml .= CRLF . "\t". '<entry>';
				$arSql2 = $this->oDb->sqlExec($this->oSqlQ->getQ('get-topics-lang-adm', $id_topic));
				for (; list($k2, $arV2) = each($arSql2);)
				{
					/* remove encoding name */
					$arV2['id_lang'] = preg_replace("/-([a-z0-9])+$/", '', $arV2['id_lang']);
					/* start topic names */
					$xml .= CRLF . "\t\t". '<lang xml:lang="'.$arV2['id_lang'].'">';
					unset($arV2['id_lang']);
					for (; list($attrK, $attrV) = each($arV2);)
					{
						$xml .= CRLF . "\t\t\t<". $attrK.'>';
						$xml .= ($attrV == '') ? '' : '<![CDATA['.$attrV.']]>';
						$xml .= '</'. $attrK.'>';
					}
					$xml .= CRLF . "\t\t". '</lang>';
				}
				$xml .= CRLF . "\t". '</entry>';
				$xml .= CRLF . '</topic>';
			}
			$xml .= CRLF . '</glossword>';
			/* */
			$filename = 'gw_topics_map_'.date("Y-m[M]-d", $this->sys['time_now_gmt_unix']).'.xml';
			if ($arPost['is_as_file'])
			{
				/* Send headers */
				if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
				{
					header('Content-Type: application/force-download');
				}
				else
				{
					header('Content-Type: application/octet-stream');
				}
				header('Content-Length: '.strlen($xml));
				header('Content-disposition: attachment; filename="'.$filename.'"');
				print $xml;
				exit;
			}
			else
			{
				/* Write to disk */
				$mode = 'w';
				$filename = $this->sys['path_export'] . '/topics/' . $filename;
				$this->str .= '<ul class="xt">';
				$this->str .= '<li><span class="gray">';
				$this->str .= $this->oHtml->a($filename, $filename) . '</span>&#8230; ';
				$isWrite = $this->oFunc->file_put_contents($filename, $xml, $mode);
				$this->str .= ( $isWrite ?  'ok (' . $this->oFunc->number_format(strlen($xml), 0, $this->oL->languagelist('4')) . ' ' . $this->oL->m('bytes') . ')' : $this->oL->m('error') ) . '</li>';
				$this->str .= '</ul>';
			}
		}
		global $strR;
		$strR .= $this->str;
	}
	/* */
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
		$str_form .= getFormTitleNav($this->oL->m('3_import'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');
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
					' style="width:100%;font:85% \'verdana\',arial,sans-serif"'.
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
		$str_form .= '<tr>'.
					'<td class="td1">&#160;</td>'.
					'<td class="td2">'.$this->oL->m('1152').'</td>'.
					'</tr>';
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
	function import()
	{
		$ar_req_fields = array();
		if ($this->gw_this['vars']['post'] == '')
		{
			$arV = array();
			$arV['file_location'] = '';
			$arV['xml'] = '';
			/* Not submitted */
			$this->str .= $this->get_form_import($arV);

			$strHelp = '';
			$strHelp .= '<dl>';
			$strHelp .= '<dt><b>XML</b></dt>';
			$strHelp .= '<dd>' . CRLF.'&lt;'.'?xml version="1.0" encoding="UTF-8"'.'?&gt;'.
						'<br />&lt;glossword&gt;'.
						'<br />&lt;topic id="1"&gt;'.
						'<br />&#160;&lt;parameters&gt;&#8230;&lt;/parameters&gt;'.
						'<br />&#160;&lt;entry&gt;'.
						'<br />&#160;&#160;&lt;lang xml:lang="en"&gt;'.
						'<br />&#160;&#160;&#160;&lt;topic_title&gt;&#8230;&lt;/topic_title&gt;'.
						'<br />&#160;&#160;&#160;&lt;topic_descr&gt;&#8230;&lt;/topic_descr&gt;'.
						'<br />&#160;&#160;&#160;&lt;id_topic_phrase&gt;&#8230;&lt;/id_topic_phrase&gt;'.
						'<br />&#160;&lt;/lang&gt;'.
						'<br />&#160;&lt;/entry&gt;'.
						'<br />&lt;/topic&gt;'.
						'<br />&lt;/glossword&gt;' . '</dd>';
			$strHelp .= '</dl>';
			$this->str .= '<br />'.kTbHelp($this->oL->m('2_tip'), $strHelp);
		}
		else
		{
			global $file_location;
			$arPost =& $this->gw_this['vars']['arPost'];
			/* */
			$xml_file = isset($file_location['tmp_name']) ? $file_location['tmp_name'] : '';
			$file_target = urlencode(time().'_'.$file_location['name']);
			/* Create directory */
			$this->oFunc->file_put_contents($this->sys['path_temporary'].'/t/'.$file_target, '');
			if (is_uploaded_file($xml_file)
				&& move_uploaded_file($xml_file, $this->sys['path_temporary'].'/t/'.$file_target)
				)
			{
				$arPost['xml'] = $this->oFunc->file_get_contents($this->sys['path_temporary'].'/t/'.$file_target);
				/* remove uploaded file */
				unlink($this->sys['path_temporary'].'/t/'.$file_target);
			}
#$this->sys['isDebugQ'] = 1;
			/* Do import using DOM model */
			$oDom = new gw_domxml;
			$oDom->is_skip_white = 0;
			$oDom->strData =& $arPost['xml'];
			$oDom->parse();
			$oDom->strData = '';
			$arXmlLine = $oDom->get_elements_by_tagname('topic');
			$arQ = $q1 = array();
			$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics`';
			$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics_phrase`';
			/* */
			$this->str .= '<ul class="xt">';
			for (; list($k1, $v1) = each($arXmlLine);)
			{
				/* per each topic */
				if (!isset($v1['children'])) { continue; }
				$id_topic = $oDom->get_attribute('id', $v1['tag'], $v1);
				/* <entry> */
				for (reset($v1['children']); list($k2, $v2) = each($v1['children']);)
				{
					if (!is_array($v2)){ continue; }
					switch($v2['tag'])
					{
						case 'parameters':
							$q2 = array();
							$q1 = unserialize($oDom->get_content($v2));
							$q1['id_topic'] = $q2['id_topic'] = $id_topic;
						break;
						case 'entry':
							if (!isset($v2['children'])) { continue; }
							for (reset($v2['children']); list($k3, $v3) = each($v2['children']);)
							{
								$id_lang = $oDom->get_attribute('xml:lang', 'lang', $v3);
								/* for each element */
								if (!is_array($v3) || !isset($v3['children'])) { continue; }
								for (reset($v3['children']); list($k4, $v4) = each($v3['children']);)
								{
									if (trim($v4['tag']) == ''){ continue; }
									$q2[$v4['tag']] = $v4['value'];
								}
								$q2['id_lang'] = $id_lang.'-'.$this->gw_this['vars']['lang_enc'];
								$arQ[] = gw_sql_replace($q2, $this->sys['tbl_prefix'].'topics_phrase');
							}
						break;
					}
				}
				if (!isset($q1['date_created']))
				{
					$q1['date_created'] = $q1['date_modified'] = $this->sys['time_now_gmt_unix'];
				}
				$arQ[] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'topics');
			}
			$this->str .= '</ul>';
			if ($this->sys['isDebugQ'])
			{
				$this->str .= '<ul class="gwsql">';
				for (reset($arQ); list($k, $v) = each($arQ);)
				{
					$this->str .= '<li>'.htmlspecialchars_ltgt($this->oFunc->mb_wordwrap($v, 70, "\n", 1)).'</li>';
				}
				$this->str .= '</ul>';
			}
			else
			{
				$this->str .= postQuery($arQ, 'a=' . GW_A_BROWSE . '&'.GW_TARGET.'=' . $this->addon_name, $this->sys['isDebugQ']);
			}
		}
		global $strR;
		$strR .= $this->str;
	}
	/* */
	function remove()
	{
		$ar =& $this->gw_this['ar_topics_list'];
#		$this->sys['isDebugQ'] = 1;
		$is_error = 0;
		$msg_error = '';
		$arQ = array();
		/* can't remove topic already assigned to another dictionary */
		$sql = sprintf('SELECT id, title FROM `'.TBL_DICT.'` WHERE id_topic = "%d"', $this->gw_this['vars']['tid']);
		$arSql = $this->oDb->sqlExec($sql);
		if (!empty($arSql))
		{
			$is_error = 1;
			$msg_error .= '<br />' . $this->oL->m('reason_3');
		}
		/* warn if selected topic is a parent */
		if (isset($ar[$this->gw_this['vars']['tid']]['ch']))
		{
			$msg_error .= '<br />' . $this->oL->m('reason_2');
			$arKeys = ctlgGetTree($ar, $this->gw_this['vars']['tid']);
			/* Unset the current Topic ID from subtopics tree */
			unset($arKeys[$this->gw_this['vars']['tid']]);
			while (is_array($arKeys) && list($k, $v) = each($arKeys))
			{
				$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics` WHERE id_topic = "' . $v . '"';
				$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics_phrase` WHERE id_topic = "' . $v . '"';
			}
		}
		/* can't delete last root topic */
		$sql = sprintf('SELECT count(*) AS n FROM `'.$this->sys['tbl_prefix'].'topics` WHERE id_parent != "%d"', $this->gw_this['vars']['tid']);
		$arSql = $this->oDb->sqlExec($sql);
		for (; list($arK, $arV) = each($arSql);)
		{
			if($arV['n'] == 1)
			{
				$is_error = 1;
				$msg_error .= '<br />' . $this->oL->m('reason_1');
			}
		}
		/* check if Topic ID is present */
		if (!isset($ar[$this->gw_this['vars']['tid']]))
		{
			$is_error = 1;
			$msg_error .= '<br />' . $this->oL->m('reason_12');
		}

		if ($is_error)
		{
			$this->str .= '<p class="xr"><span class="red">' . $this->oL->m('reason_11') . '</span> '.  $msg_error . '</p>';
		}
		else
		{
			if (!$this->gw_this['vars']['isConfirm']) /* if not confirmed */
			{
				$str_question = '<p class="xr red"><b>' . $this->oL->m('9_remove') .'</b></p>';
				$str_question .= $ar[$this->gw_this['vars']['tid']]['title'];
				$str_question .= '<br />'.$msg_error;
				$oConfirm = new gwConfirmWindow;
				$oConfirm->action = $this->sys['page_admin'];
				$oConfirm->submitok = $this->oL->m('3_remove');
				$oConfirm->submitcancel = $this->oL->m('3_cancel');
				$oConfirm->formbgcolor = $this->ar_theme['color_2'];
				$oConfirm->formbordercolor = $this->ar_theme['color_4'];
				$oConfirm->formbordercolorL = $this->ar_theme['color_1'];
				$oConfirm->setQuestion($str_question);
				$oConfirm->tAlign = 'center';
				$oConfirm->formwidth = '400';
				$oConfirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
				$oConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
				$oConfirm->setField('hidden', 'tid', $this->gw_this['vars']['tid']);
				$oConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
				$this->str .= $oConfirm->Form();
			}
			else
			{
				$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics` WHERE id_topic = "' . $this->gw_this['vars']['tid'] . '"';
				$arQ[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics_phrase` WHERE id_topic = "' . $this->gw_this['vars']['tid'] . '"';
				$this->str .= postQuery($arQ, 'a=' . GW_A_BROWSE . '&'.GW_TARGET.'=' . $this->addon_name, $this->sys['isDebugQ'], 0);
			}
		}
		global $strR;
		$strR .= $this->str;
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
$oAddonAdm = new gw_addon_topic_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['topics_'.GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';
/* end of file */
?>