<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: abbr_admin.php,v 1.2 2006/10/06 12:06:08 yrtimd Exp $ -->');
}
/* */
class gw_addon_abbr_admin extends gw_addon
{
	var $addon_name = 'abbr';
	var $ar_groups;
	/* Autoexec */
	function gw_addon_abbr_admin()
	{
		$this->init();
		$this->oL->setHomeDir($this->sys['path_locale']);
		$this->oL->getCustom('addon_'.$this->addon_name, $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');
		$this->ar_groups = array(
			'1' => $this->oL->m('speech'),
			'2' => $this->oL->m('science'),
			'3' => $this->oL->m('linguistics'),
			'4' => $this->oL->m('languages'),
			'5' => $this->oL->m('custom')
		);
	}
	function _get_nav()
	{
		/* The list of languags */
		$arSql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-abbr-lang'), $this->addon_name);
		$ar_languages = array();
		$id_lang = 'en-utf8';
		while (list($k, $arV) = each($arSql))
		{
			if ($k == 0)
			{
				$id_lang = $arV['id_lang'];
			}
			$str_lang = isset($this->gw_this['vars']['ar_languages'][$arV['id_lang']]) ? $this->gw_this['vars']['ar_languages'][$arV['id_lang']] : $arV['id_lang'];
			if ($arV['id_lang'] != $this->gw_this['vars']['w1'] && ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT))
			{
				$ar_languages[$arV['id_lang']] = $this->oHtml->a(
						$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->addon_name.'&w1=' . $arV['id_lang'].'&tid=' . $this->gw_this['vars']['tid'],
						$str_lang
				);
			}
			else
			{
				$ar_languages[$arV['id_lang']] = $this->oHtml->a(
						$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->addon_name.'&w1=' . $arV['id_lang'].'&w2='.$this->gw_this['vars']['w2'],
						$str_lang
				);
			}
		}
		$ar_topics = array();
		for (reset($this->ar_groups); list($k, $v) = each($this->ar_groups);)
		{
			$ar_topics[] = $this->oHtml->a(
					$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->addon_name.'&w1=' . $this->gw_this['vars']['w1'].'&w2=' . $k,
					$v
			);
		}
		/* Set default language when language ID is not defined */
		$this->gw_this['vars']['w1'] = ($this->gw_this['vars']['w1']) ? $this->gw_this['vars']['w1'] : $id_lang;
		/* Set default topic when Topic ID is not defined */
		$this->gw_this['vars']['w2'] = ($this->gw_this['vars']['w2']) ? $this->gw_this['vars']['w2'] : 1;
		return '<p class="xt gray">'.$this->oL->m('lang').': '. implode(' | ', $ar_languages).'</p>'.
				'<p class="xt gray">'.$this->oL->m('topic').': '. implode(' | ', $ar_topics).'</p>';
	}
	/* */
	function get_form_abbr($vars, $runtime = 0, $ar_broken = array(), $ar_req = array())
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
			$oForm->Set('submitdelname', 'arControl[remove]');
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
		$str_form .= getFormTitleNav($this->oL->m('1137'), '<span style="float:right">'.$oForm->get_button('submit').'</span>');

		$str_form .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
		$str_form .= '<table class="gw2TableFieldset" width="100%">';
		$str_form .= '<tbody><tr><td style="width:'.$v_td1_width.'"></td><td>';
		$str_form .= '</td></tr>';

		$oForm->setTag('select', 'class',  'input50');
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $oForm->field('checkbox', 'arPost[is_active]', $vars['is_active']) . '</td>'.
					'<td class="'.$v_class_2.'">' . '<label for="'.$oForm->text_field2id('arPost[is_active]').'">'.$this->oL->m('1320').'</label></td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('abbr_short') . $ar_req_msg['abbr_short'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['abbr_short'] . $oForm->field('textarea', 'arPost[abbr_short]', $vars['abbr_short']) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('abbr_long') . $ar_req_msg['abbr_long'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['abbr_long'] . $oForm->field('textarea', 'arPost[abbr_long]', $vars['abbr_long']) . '</td>'.
					'</tr>';
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_ADD)
		{
			$oForm->setTag('input', 'class', 'input50');
			$oForm->setTag('input', 'maxlength', '4');
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('id_abbr') . $ar_req_msg['id_abbr'] . '</td>'.
						'<td class="'.$v_class_2.'">' . $ar_broken_msg['id_abbr'] . $oForm->field('input', 'arPost[id_abbr]', $vars['id_abbr']) . '</td>'.
						'</tr>';
		}
		else
		{
			$str_form .= '<tr>'.
						'<td class="'.$v_class_1.'">' . $this->oL->m('id_abbr') . '</td>'.
						'<td class="disabled">' . sprintf("%03d", $vars['id_abbr']) . '</td>'.
						'</tr>';
		}
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('id_group') . $ar_req_msg['id_group'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['id_group'] . $oForm->field('select', 'arPost[id_group]', $vars['id_group'], 0, $this->ar_groups) . '</td>'.
					'</tr>';
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('lang') . $ar_req_msg['id_lang'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['id_lang'] . $oForm->field('select', 'arPost[id_lang]', $vars['id_lang'], 0, $this->gw_this['vars']['ar_languages']) . '</td>'.
					'</tr>';

		$ar_dict = array(0 => $this->oL->m('1113'));
		/* Per each dictionary */
		for (reset($this->gw_this['ar_dict_list']); list($k, $arDictParam) = each($this->gw_this['ar_dict_list']);)
		{
			$ar_dict[$arDictParam['id']] = $arDictParam['title'];
		}
		$str_form .= '<tr>'.
					'<td class="'.$v_class_1.'">' . $this->oL->m('dict') . $ar_req_msg['id_dict'] . '</td>'.
					'<td class="'.$v_class_2.'">' . $ar_broken_msg['id_dict'] . $oForm->field('select', 'arPost[id_dict]', $vars['id_dict'], 0, $ar_dict) . '</td>'.
					'</tr>';

		$str_form .= '</tbody></table>';
		$str_form .= '</fieldset>';

		$str_form .= $oForm->field('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
		$str_form .= $oForm->field("hidden", GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		if ($this->gw_this['vars'][GW_ACTION] == GW_A_EDIT)
		{
			$str_form .= $oForm->field('hidden', 'arPost[id_abbr]', $vars['id_abbr']);
			$str_form .= $oForm->field('hidden', 'arPost[id_abbr_phrase]', $vars['id_abbr_phrase']);
			$str_form .= $oForm->field('hidden', 'w1', $this->gw_this['vars']['w1']);
		}
		$str_form .= $oForm->field('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$str_form .= $oForm->field('hidden', 'tid', $vars['id_abbr_phrase']);
		$str_form .= $oForm->field('hidden', 'w3', $vars['id_abbr']);
		$str_form .= $oForm->field('hidden', 'p', $this->gw_this['vars']['p']);
		$str_form .= $str_hidden;
		return $oForm->Output($str_form);
	}
	/* */
	function browse()
	{
		global $strR;

		$strR .= $this->_get_nav();

		$strR .= '<table class="tbl-browse gray" cellspacing="1" cellpadding="0" border="0" width="100%">';
		$strR .= '<thead><tr style="color:'.$this->ar_theme['color_1'].';background:'.$this->ar_theme['color_6'].'">';
		$strR .= '<th style="width:1%">N</th>';
		$strR .= '<th style="width:1%">ID</th>';
		$strR .= '<th>' . $this->oL->m('abbr_short') . '</th>';
		$strR .= '<th style="width:46%">' . $this->oL->m('abbr_long') . '</th>';
		$strR .= '<th style="width:22%">' . $this->oL->m('action') . '</th>';
		$strR .= '<th style="width:5%">' . $this->oL->m('1320') . '</th>';
		$strR .= '</tr></thead><tbody>';

		$isDn = $isUp = 1;
		$isReset = 0;

		/* The list of line feeds */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-abbr-adm', $this->gw_this['vars']['w1'], $this->gw_this['vars']['w2']), $this->addon_name);
		$cnt_row = 1;
		while (list($k, $arV) = each($arSql))
		{
			$isReset = 0;
			if ($k == 0) { $isReset = 1; }
			if (!$isUp && !$isDn) { $isReset = 0; }
			$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];

			$strR .= '<tr id="abbr'.$arV['id_abbr'].'" style="background:'.$bgcolor.'">';
			$strR .= '<td class="xt n" style="text-align:'.$this->sys['css_align_right'].'">' .  $cnt_row . '</td>';
			$strR .= '<td class="xt">'.sprintf("%03d", $arV['id_abbr']).'</td>';
			$strR .= '<td class="xu"><table><tbody><tr><td>';
			$strR .= $this->oHtml->a(
						$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->addon_name.'&tid=' . $arV['id_abbr'].'&w1=' . $this->gw_this['vars']['w1'],
						$arV['abbr_short'], '', '', $this->oL->m('3_edit') );
			$strR .= '&#160;</td></tr><t/body></table></td>';
			$strR .= '<td class="xu">';
			$strR .= $this->oHtml->a(
						$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->addon_name.'&tid=' . $arV['id_abbr'].'&w1=' . $this->gw_this['vars']['w1'],
						$arV['abbr_long'], '', '', $this->oL->m('3_edit') );
			$strR .= '</td>';
			$strR .= '<td class="actions-third" style="text-align:center">';
			$strR .= $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->addon_name.'&tid=' . $arV['id_abbr'].'&w1=' . $this->gw_this['vars']['w1'], $this->oL->m('3_edit'));
			$strR .= ' ';

			$this->oHtml->setTag('a', 'onclick', 'return confirm(\''.$this->oL->m('3_remove').': &quot;'.htmlspecialchars($arV['abbr_short'].' '.$arV['abbr_long']).'&quot;. '.$this->oL->m('9_remove').'\' )');
			$strR .= $this->oHtml->a( $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_REMOVE.'&'.GW_TARGET.'='.$this->addon_name.'&tid=' . $arV['id_abbr'].'&w1=' . $this->gw_this['vars']['w1'].'&w2=' . $this->gw_this['vars']['w2'].'&isConfirm=1', $this->oL->m('3_remove'));
			$this->oHtml->setTag('a', 'onclick', '');
			$strR .= '</td>';

			/* 1.8.7: Turn on/off */
			$href_onoff = $this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&tid='.$arV['id_abbr'].'&w1='.$this->gw_this['vars']['w1'].'&w2='.$this->gw_this['vars']['w2'];
			$strR.= '<td class="actions-third" style="width:1%;text-align:center">';
			$strR .= ($arV['is_active']
						? $this->oHtml->a($href_onoff.'&mode=off', '<span class="green">'.$this->oL->m('is_1').'</span>')
						: $this->oHtml->a($href_onoff.'&mode=on', '<span class="red">'.$this->oL->m('is_0').'</span>', $this->oL->m('1057') ) );
			$strR .= '</td>';

			$strR .= '</tr>';
			$cnt_row++;
		}
		$strR .= '</tbody></table>';
		$strR .= CRLF.'<script type="text/javascript">/*<![CDATA[*/';
		$strR .= 'window.scrollTo(0, jsUtils.GetRealPos(gw_getElementById("abbr'.$this->gw_this['vars']['w3'].'")).top );';
		$strR .= '/*]]>*/</script>';
	}
	/* */
	function edit()
	{
		global $strR;
		$ar_req_fields = array('abbr_short', 'abbr_long', 'id_group', 'id_lang');

		$strR .= $this->_get_nav();

		/* Switching On/off */
#		$this->sys['isDebugQ'] = 1;
		$arQ = array();
		if ($this->gw_this['vars']['mode'] == 'off')
		{
			$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'abbr`
								SET `is_active` = "0"
								WHERE `id_abbr` = "'.$this->gw_this['vars']['tid'].'"';
			$strR .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&w1='.$this->gw_this['vars']['w1'].'&w2='.$this->gw_this['vars']['w2'], $this->sys['isDebugQ'], 0);
			return;
		}
		elseif ($this->gw_this['vars']['mode'] == 'on')
		{
			$arQ[] = 'UPDATE `'.$this->sys['tbl_prefix'].'abbr`
								SET `is_active` = "1"
								WHERE `id_abbr` = "'.$this->gw_this['vars']['tid'].'"';
			$strR .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&w1='.$this->gw_this['vars']['w1'].'&w2='.$this->gw_this['vars']['w2'], $this->sys['isDebugQ'], 0);
			return;
		}
		if ($this->gw_this['vars']['post'] == '')
		{
			/* Get Abbr settings */
			$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-abbr-by-id', $this->gw_this['vars']['tid'], $this->gw_this['vars']['w1']), $this->addon_name);
			$arSql = isset($arSql[0]) ? $arSql[0] : array();
			/* Abbreviation not found */
			if (empty($arSql))
			{
				return;
			}
			/* Not submitted */
			$this->str .= $this->get_form_abbr($arSql, 0, 0, $ar_req_fields);
		}
		else
		{
			$arPost =& $this->gw_this['vars']['arPost'];
			$arPost['abbr_short'] = trim($arPost['abbr_short']);
			$arPost['abbr_long'] = trim($arPost['abbr_long']);
			/* Fix on/off options */
			$arIsV = array('is_active');
			for (; list($k, $v) = each($arIsV);)
			{
				$arPost[$v] = isset($arPost[$v]) ? $arPost[$v] : 0;
			}
			/* Checking posted vars */
			$errorStr = '';
			$ar_broken = validatePostWalk($arPost, $ar_req_fields);
			if (empty($ar_broken))
			{
				$q1 =& $arPost;
				$q2['is_active'] = $q1['is_active'];
				$q2['id_group'] = $q1['id_group'];
				$q2['id_dict'] = $q1['id_dict'];
				$ar_query[] = gw_sql_update($q2, $this->sys['tbl_prefix'].'abbr', 'id_abbr = "'.$q1['id_abbr'].'"');
				unset($q1['is_active']);
				unset($q1['id_abbr']);
				unset($q1['id_group']);
				unset($q1['id_dict']);
				$ar_query[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'abbr_phrase', 'id_abbr_phrase = "'.$this->gw_this['vars']['tid'].'"');
				$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->addon_name.'&w1='.$q1['id_lang'].'&w2='.$q2['id_group'].'&r='.time().'&w3='.$this->gw_this['vars']['w3'], $this->sys['isDebugQ'], 0);
			}
			else
			{
				$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
				$this->str .= $this->get_form_abbr($arPost, 1, $ar_broken, $ar_req_fields);
			}
		}
		$strR .= $this->str;
	}
	/* */
	function add()
	{
		global $strR;
		$ar_req_fields = array('abbr_short', 'abbr_long', 'id_group', 'id_lang');
		if ($this->gw_this['vars']['post'] == '')
		{
			$arPost['abbr_long'] = '';
			$arPost['abbr_short'] = '';
			$arPost['id_abbr_phrase'] = '';
			$arPost['id_abbr'] = $this->oDb->MaxId($this->sys['tbl_prefix'].'abbr', 'id_abbr');
			$arPost['id_lang'] = $this->sys['locale_name'];
			$arPost['id_group'] = $this->oSess->user_get('abbr_id_group');
			/* 23 may 2006: select "Custom" on first run */
			if ($arPost['id_group'] == '') { $arPost['id_group'] = 5; }
			$arPost['id_dict'] = 0;
			$arPost['is_active'] = 1;
			$this->str .= $this->get_form_abbr($arPost, 0, 0, $ar_req_fields);

			$arHelpMap = array(
						'id_abbr'  => 'id_abbr_help',
						'dict'  => 'id_dict_help',
					 );
			$strHelp = '';
			$strHelp .= '<dl>';
			for (; list($k, $v) = each($arHelpMap);)
			{
				$strHelp .= '<dt><b>' . $this->oL->m($k) . '</b></dt>';
				$strHelp .= '<dd>' . $this->oL->m($v) . '</dd>';
			}
			$strHelp .= '</dl>';
			$this->str .= '<br />'.kTbHelp($this->oL->m('2_tip'), $strHelp);

		}
		else
		{
			$arPost =& $this->gw_this['vars']['arPost'];
			/* Fix on/off options */
			$arIsV = array('is_active');
			for (; list($k, $v) = each($arIsV);)
			{
				$arPost[$v] = isset($arPost[$v]) ? $arPost[$v] : 0;
			}
			/* Checking posted vars */
			$errorStr = '';
			$ar_broken = validatePostWalk($arPost, $ar_req_fields);
			if (empty($ar_broken))
			{
				$q1 =& $arPost;
				$id_lang = $q1['id_lang'];
				/* Use assigned Abbreviation ID or create new */
				if ($q1['id_abbr'])
				{
					/* Fixes for Abbreviation ID */
					$q1['id_abbr'] = preg_replace("/![0-9]/", '', $q1['id_abbr']);
					$q2['id_abbr'] = $q1['id_abbr'];
				}
				else
				{
					$q1['id_abbr'] = $q2['id_abbr'] = $this->oDb->MaxId($this->sys['tbl_prefix'].'abbr', 'id_abbr');
				}
				$q2['is_active'] = $q1['is_active'];
				$q2['id_group'] = $q1['id_group'];
				$q2['id_dict'] = $q1['id_dict'];
				$ar_query[] = gw_sql_replace($q2, $this->sys['tbl_prefix'].'abbr');
				unset($q1['is_active']);
				unset($q1['id_group']);
				unset($q1['id_dict']);
				$ar_query[] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'abbr_phrase');
				$ar_languages = $this->gw_this['vars']['ar_languages'];
				/* Add empty values for other languages */
				unset($ar_languages[$q1['id_lang']]);
				for (reset($ar_languages); list($kl, $vl) = each($ar_languages);)
				{
					$q1['id_lang'] = $kl;
					$q1['abbr_short'] = $q1['abbr_long'] = '';
					$ar_query[] = gw_sql_replace($q1, $this->sys['tbl_prefix'].'abbr_phrase');
				}
				$this->oSess->user_set('abbr_id_group', $q2['id_group']);
				$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_ADD.'&'.GW_TARGET.'='.$this->addon_name.'&arPost[id_lang]='.$id_lang, $this->sys['isDebugQ'], 0);
			}
			else
			{
				$arPost['id_abbr_phrase'] = '';
				$this->str .= $this->get_form_abbr($arPost, 1, $ar_broken, $ar_req_fields);
			}
		}
		$strR .= $this->str;
	}
	/* */
	function update()
	{
		if ($this->gw_this['vars']['tid'])
		{
			$this->edit();
		}
		else
		{
			$this->add();
		}
	}
	/* */
	function remove()
	{
		global $strR;
		/* Get Abbr settings */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-abbr-by-id', $this->gw_this['vars']['tid'], $this->gw_this['vars']['w1']), $this->addon_name);
		$arSql = isset($arSql[0]) ? $arSql[0] : array();
		/* Abbreviation not found */
		if (empty($arSql))
		{
			return;
		}
		if (!$this->gw_this['vars']['isConfirm']) /* if not confirmed */
		{
			/* */
			$str_question = '<p class="xr"><span class="s"><b>' . $this->oL->m('9_remove') .'</b></span></p>'.
				'<p class="xt">'.$arSql['abbr_short'].'<br />'.$arSql['abbr_long'].'</p>';
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
			$oConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
			$oConfirm->setField('hidden', 'tid', $this->gw_this['vars']['tid']);
			$oConfirm->setField('hidden', 'w1', $this->gw_this['vars']['w1']);
			$oConfirm->setField('hidden', 'w2', $this->gw_this['vars']['w2']);
			$oConfirm->setField('hidden', 'arPost[id_abbr]', $arSql['id_abbr']);
			$strR .= $oConfirm->Form();
		}
		else
		{
			$ar_query[] = 'DELETE FROM `' . $this->sys['tbl_prefix'].'abbr` WHERE id_abbr = "' . $arSql['id_abbr'] . '"';
			$ar_query[] = 'DELETE FROM `' . $this->sys['tbl_prefix'].'abbr_phrase` WHERE id_abbr = "' . $arSql['id_abbr']. '"';
			$strR .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->addon_name.'&w1='.$this->gw_this['vars']['w1'].'&w2='.$this->gw_this['vars']['w2'], $this->sys['isDebugQ'], 0);
		}
	}
	/* 1.8.6.5 */
	function alpha()
	{
		global $strR;
		if (is_array($this->gw_this['vars']['arControl']))
		{
			$arControl = array_keys($this->gw_this['vars']['arControl']);
			$this->gw_this['vars'][GW_ACTION] = $arControl[0];
			$this->gw_this['vars']['tid'] = $this->gw_this['vars']['arPost']['id_abbr'];
		}

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

			if (in_array(strtolower($this->gw_this['vars'][GW_ACTION]), get_class_methods($this)))
			{
				$this->{$this->gw_this['vars'][GW_ACTION]}();
			}
		}
		else
		{
			$this->sys['id_current_status'] = '';
			$strR .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
			$strR .= '<p class="xt">'.$this->gw_this['vars'][GW_TARGET] .': '. $this->gw_this['vars'][GW_ACTION].'</p>';
		}
	}
}
/* */
$oAbbrAdm = new gw_addon_abbr_admin;
$oAbbrAdm->alpha();
/* Do not load old components */
$pathAction = '';
/* end of file */
?>