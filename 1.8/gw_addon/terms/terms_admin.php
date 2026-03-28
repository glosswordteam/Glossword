<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008-2012 Glossword.biz team <team at glossword dot biz>
 *  © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: terms_admin.php 487 2008-06-12 10:33:21Z glossword_team $ -->');
}
/* */
class gw_addon_terms_admin extends gw_addon
{
	var $str;
	var $ar = array();
	var $ar_component = array();
	/* Current component name */
	var $component;
	/* Autoexec */
	function gw_addon_terms_admin()
	{
		$this->init();
	}
	/* */
	function _get_nav()
	{
		global $arDictParam, $arTermParam;
		$this->oHtml->setTag('a', 'class', 'ext');
		$this->oHtml->setTag('a', 'onclick', 'nw(this);return false');
		
		/* Link to term */
		$url_view_term = '';
		if ( isset( $arDictParam['is_active'] ) 
				&& $arDictParam['is_active'] == 1 ) {
			$url_view_term = (
					$this->gw_this['vars']['tid'] && ($arTermParam['is_active'] != 3) && ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT) ?
						$this->oHtml->a(
							$this->sys['page_index'].'?'.GW_ACTION.'='.GW_T_TERM.'&d='.$arDictParam['uri'].'&'.GW_TARGET.'='.$arTermParam['uri'],
							$this->oL->m('1283'),
							$this->oL->m('terms').': '.$this->oL->m('1283')
						)
					: ''
			);
		}
		$this->oHtml->setTag('a', 'onclick', '');
		$this->oHtml->setTag('a', 'class', '');
		/* */
		return '<div class="actions-secondary">'.
			implode(' ', $this->gw_this['ar_actions_list'][$this->component]) .
			($this->gw_this['vars']['id'] ? ' ' . $this->oHtml->a(
				$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.GW_T_DICTS.'&id=' . $this->gw_this['vars']['id'],
				$this->oL->m('1335').': '.$this->oL->m('3_edit'),
				$this->oL->m('1335').': '.$this->oL->m('3_edit')
			) : '') . 
			(
				$this->gw_this['vars']['tid'] && $this->gw_this['vars']['w1'] != '' && ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT) ?
					$this->oHtml->a(
						$this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_EDIT.'&id='.$this->gw_this['vars']['id'].'&'.GW_TARGET.'='.GW_T_TERMS.'&tid='.$this->gw_this['vars']['tid'],
						$this->oL->m('3_edit'),
						$this->oL->m('terms').': '.$this->oL->m('3_edit')
					)
				: ''
			).
			(
				$this->gw_this['vars']['tid'] && ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT) ?
					$this->oHtml->a(
						$this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_EDIT.'&w1=viewhistory&id='.$this->gw_this['vars']['id'].'&'.GW_TARGET.'='.GW_T_TERMS.'&tid='.$this->gw_this['vars']['tid'],
						$this->oL->m('1294'),
						$this->oL->m('terms').': '.$this->oL->m('1294')
					)
				: ''
			).
			(
				$this->gw_this['vars']['tid'] && ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT) ?
					$this->oHtml->a(
						$this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_EDIT.'&w1=viewkeywords&id='.$this->gw_this['vars']['id'].'&'.GW_TARGET.'='.GW_T_TERMS.'&tid='.$this->gw_this['vars']['tid'],
						$this->oL->m('1284'),
						$this->oL->m('terms').': '.$this->oL->m('1284')
					)
				: ''
			).$url_view_term.
			'</div>';
	}
	/* */
	function get_dates($db_table)
	{
		$arSql = $this->oDb->sqlExec( $this->oSqlQ->getQ('get-date-mm', $db_table) );
		$strA = array('max' => time(), 'min' => 0);
		for (; list($arK, $arV) = each($arSql);)
		{
			if (empty($arV['max']) && empty($arV['min']))
			{
				/* no date */
				$strA['max'] = $strA['min'] = $this->sys['time_now_gmt_unix'];
			}
			else
			{
				$strA['max'] = $arV['max'];
				$strA['min'] = $arV['min'];
			}
		}
		return $strA;
	}
	/* HTML-form for Export */
	function get_form_export($vars)
	{
		$str_form = '';
		/* */
		$oForm = new gwForms();
		$oForm->Set('action', $this->sys['page_admin']);
		$oForm->Set('submitdel', $this->oL->m('3_remove'));
		$oForm->Set('submitok', $this->oL->m('1168').' 2');
		$oForm->Set('submitcancel', $this->oL->m('3_cancel'));
		$oForm->Set('formbgcolor', $this->ar_theme['color_2']);
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']);
		$oForm->Set('formbordercolorL', $this->ar_theme['color_1']);
		$oForm->Set('align_buttons', $this->sys['css_align_right']);
		$oForm->Set('formwidth', '100%');
		$oForm->Set('charset', $this->sys['internal_encoding']);
		/* */
		$str_form .= getFormTitleNav($this->oL->m('3_export'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');

		$str_form .= '<table class="gw2TableFieldset" width="100%"><tbody><tr><td style="vertical-align:top">';

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

		$str_form .= '</td><td style="width:25%;vertical-align:top">';

		$str_form .= '<fieldset class="admform"><legend class="xt gray">'.$this->oL->m('select_format').'</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tr><td style="width:15%"></td><td></td></tr><tbody>';
		foreach($vars['ar_formats'] as $fmt)
		{
			$str_form .= '<tr>'.
						'<td class="td1">' . '<input '.(($vars['fmt_default']==$fmt)?'checked="checked" ':'').'type="radio" name="arPost[fmt]" id="arPost-fmt-'.$fmt.'" value="'.$fmt.'" />' . '</td>'.
						'<td class="td2">' . '<label for="arPost-fmt-'.$fmt.'">'.$fmt.'</label></td>'.
						'</tr>';
		}
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		$str_form .= '</td></tr></tbody></table>';
		/* */
		$strForm = '';
		include($this->sys['path_include'] . '/'. GW_ACTION . '.' . $this->gw_this['vars'][GW_ACTION] . '.js.php');
		$str_form .= $strForm;

		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', 'id', $this->gw_this['vars']['id']);
		$str_form .= $oForm->field('hidden', 'w1', 2);
		return $oForm->Output($str_form);
	}
	/* */
	function get_form_term($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
	{
		global $arDictParam, $arFields, $arTermParam;
		global $oDom;

		$str_form = ''; // all HTML-code for the form
		$tmp = array(); // all temporary variables for this function
		$tmp['cssTrClass'] = 'xt';
		// ----------------------------------------------------
		// prepare HTML-form settings
		$oForm = new gw_htmlforms;
		$oForm->Set('method',          'post' );
		$oForm->Set('action',          $this->sys['page_admin'] );
		$oForm->Set('submitok',        $this->oL->m('3_save')   );
		$oForm->Set('submitcancel',    $this->oL->m('3_cancel') );
		$oForm->Set('formbgcolor',     $this->ar_theme['color_2']  );
		$oForm->Set('formbordercolor', $this->ar_theme['color_4']  );
		$oForm->Set('formbordercolorL',$this->ar_theme['color_1']  );
		$oForm->Set('align_buttons',   $this->sys['css_align_right']);
		$oForm->Set('charset',         $this->sys['internal_encoding']);
		$arDictParam['is_htmled'] = $this->oSess->user_get('is_htmled');
		/* Do not allow to edit a term which is scheduled for deletion */
		if ($vars['is_active'] == 3)
		{
			$oForm->isButtonSubmit = 0;
			$arDictParam['is_htmled'] = 0;
		}
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT)
		{
			$oForm->Set('isButtonDel', 1);
			$oForm->Set('submitdelname', 'remove');
			$oForm->Set('submitdel', $this->oL->m('3_remove'));
		}
		$oForm->Set('Gsys', $this->sys ); /* system, settings */
		$oForm->Set('Gtmp', $tmp ); /* temporary settings for current function */
		$oForm->Set('oL', $this->oL ); /* Language object */
		$oForm->Set('objDom', $oDom );
		$oForm->Set('arDictParam', $this->arDictParam );
		if ($this->sys['is_upload']) { $oForm->Set('enctype', 'multipart/form-data'); }
		$oForm->load_abbr_trns();
		/* ---------------------------------------------------- */
		$str_form .= $oForm->field('hidden', GW_TARGET, GW_T_TERMS);
		$str_form .= $oForm->field('hidden', 'id', $this->gw_this['vars']['id']);
		$str_form .= $oForm->field('hidden', 'tid', $this->gw_this['vars']['tid']);
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		/* */
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_ADD)
		{
			$tmp['intFormHeight'] = 8;
			$str_form .= $oForm->field('hidden', GW_ACTION, GW_A_ADD);
			$str_form .= getFormTitleNav($this->oL->m('term'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');
		}
		else
		{
			$str_form .= $oForm->field('hidden', 'arPre[date_created]', $vars['date_created']);
			$str_form .= $oForm->field('hidden', GW_ACTION, GW_A_EDIT);
			$str_form .= getFormTitleNav(
				$this->oL->m('term'),
				($vars['is_active'] == 3 ? '' : '<span style="float:right">'.
					$oForm->get_button('submit', 2).
					'<input style="width:24px" class="submitdel" title="'. $this->oL->m('3_remove') . ' ' .
					'" type="submit" value="&#215;" name="remove" ' .
					'onclick="vbcontrol.style.visibility=\'hidden\'" /></span>')
			);
		}

		// Send XML array to $oForm class
		$oForm->Set('arEl', $vars );

		// prepare form fields
		$str_form .= '<script type="text/javascript">/*<![CDATA[*/
function switch2edit(id)
{
	el = gw_getElementById("arPre_" + id + "_value_");
	el_d = gw_getElementById("d_" + id);
	el_d.style.display = "none";
	el.style.display = "block";
}
/*]]>*/</script>';
		$str_form .= '<table class="gw2TableFieldset" width="100%"><tbody>';
		$oForm->Set('arFields', $arFields );
		// Go for each configured root field.
		for (reset($arFields); list($fK, $fV) = each($arFields);)
		{
			if (isset($fV[4]) && $fV[4]) // select root elements only here
			{
				if ($fV[0] == 'term') // terms always presents
				{
					$str_form .= $oForm->tag2field($fV[0]);
				}
				else
				{
					// other tags are switchable, parsed inside $oForm class
					if ($arDictParam['is_'.$fV[0]])
					{
						$str_form .= $oForm->tag2field($fV[0]);
					}
				}
			} // end of root elements
		} // end of per-field process
		$str_form .= '</tbody></table>';

		/* 1.6.3 */
		$str_form .= getFormTitleNav( $this->oL->m('options') );
		$tmp['after_post'] = $this->oSess->user_get('after_post_term');
		if (!$tmp['after_post'])
		{
			$tmp['after_post'] = GW_AFTER_TERM_ADD;
		}
		$tmp['ar_after_posting'] = array(
			GW_AFTER_TERM_ADD => $this->oL->m('3_add_term'),
			GW_AFTER_DICT_UPDATE => $this->oL->m('after_post_1'),
			GW_AFTER_SRCH_BACK => $this->oL->m('after_post_3')
		);
		/* */
		$oForm->unsetTag('select');
		$oForm->setTag('select', 'class',  'input50');
		
		$str_form .= '<fieldset class="admform">';
		$str_form .= '<legend onclick="return toggle_collapse(\'term-dates\')" style="cursor:pointer" class="xt gray">';
		$str_form .= '<img style="vertical-align:middle;padding: 0 5px" id="ci-term-dates" src="'.$this->sys['path_img'].'/collapse_off.png" alt="" width="9" height="21" />';
		$str_form .= $this->oL->m('date_created');
		$str_form .= '</legend>';
		$str_form .= '<table style="display:none" id="co-term-dates" class="gw2TableFieldset" cellpadding="3" cellspacing="0" border="0" width="100%"><tbody>';
		$str_form .= '<tr><td style="width:20%"></td><td></td></tr>';
		$str_form .= '<tr>'.
					'<td class="td1">' . $this->oL->m('date_created') . ':</td>'.
					'<td class="td2">' . htmlFormSelectDate("arPost[date_created]", @date("YmdHis", $vars['date_created'] + $this->oSess->user_get_time_seconds() )) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="td1">' . $this->oL->m('date_modif') . ':</td>'.
					'<td class="disabled">' . date_extract_int($vars['date_modified'] + ($this->oSess->user_get_time_seconds()), "%d %F %Y %H:%i") . '&#160;</td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%"><tbody>';
		$str_form .= '<tr>'.
					'<td class="td1">' . $this->oL->m('after_post') . '</td>'.
					'<td class="td2">' . $oForm->field('select', 'arPost[after]', $tmp['after_post'], '', $tmp['ar_after_posting']). '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[after_is_save]', $vars['after_is_save']) . '</td>'.
					'<td class="td2"><label for="arPost_after_is_save_">' . $this->oL->m('after_post_is_save_term') . '</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="td1">' . $oForm->field('checkbox', 'arPost[is_parse_url]', $vars['is_parse_url']) . '</td>'.
					'<td class="td2"><label for="arPost_is_parse_url_">' . $this->oL->m('1118') . '</label></td>'.
					'</tr>';
		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';
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
$oAddonAdm = new gw_addon_terms_admin;
$oAddonAdm->alpha();
/* */
$arPageNumbers['terms_'.GW_A_UPDATE] = '';
/* Do not load old components */
$pathAction = '';
/* end of file */
?>