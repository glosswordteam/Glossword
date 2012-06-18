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
	die('<!-- $Id: log_search_admin.php,v 1.2 2006/10/06 12:06:09 yrtimd Exp $ -->');
}
/* */
class gw_addon_log_search_admin extends gw_addon
{
	var $addon_name = 'log-search';
	var $int_found;
	var $int_pages;
	/* Autoexec */
	function gw_addon_log_search_admin()
	{
		$this->init();
		$this->oL->getCustom('export', $this->oSess->user_get('locale_name'), 'join');
		$this->oL->setHomeDir($this->sys['path_locale']);
		$this->oL->getCustom('addon_'.$this->addon_name, $this->gw_this['vars'][GW_LANG_I].'-'.$this->gw_this['vars']['lang_enc'], 'join');
	}
	/* */
	function get_menu_period()
	{
		/* the list of time periods */
		return '<div class="actions-secondary"><span>'.$this->oL->m('period').':</span> '.
			$this->oHtml->a($this->sys['page_admin'].'?'. GW_ACTION.'='.GW_A_BROWSE.'&'. GW_TARGET.'='.$this->addon_name.'&'.
#					'id='.$this->gw_this['vars']['id'].'&'.
					'uid='.($this->sys['max_days_searchlog']*24).'&'.
					'p=1', $this->oL->m('3_all_time')
				).' '.
			$this->oHtml->a($this->sys['page_admin'].'?'. GW_ACTION.'='.GW_A_BROWSE.'&'. GW_TARGET.'='.$this->addon_name.'&'.
#					'id='.$this->gw_this['vars']['id'].'&'.
					'uid=720&'.
					'p=1', $this->oL->m('month')
				).' '.
			$this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->addon_name.'&'.
#					'id='.$this->gw_this['vars']['id'].'&'.
					'uid=168&'.
					'p=1', $this->oL->m('week')
				).' '.
			$this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->addon_name.'&'.
#					'id='.$this->gw_this['vars']['id'].'&'.
					'uid=24&'.
					'p=1', $this->oL->m('24_hours')
				).'</div>';
	}
	/* */
	function get_menu_reports()
	{
		return '<div class="actions-secondary"><span>'.$this->oL->m('web_stat').':</span> '.
			$this->oHtml->a($this->sys['page_admin'].'?'. GW_ACTION.'='.GW_A_BROWSE.'&'. GW_TARGET.'='.$this->addon_name.'&'.
					'p=1', $this->oL->m('3_profile').' 1'
				).' '.
		$this->oHtml->a($this->sys['page_admin'].'?'. GW_ACTION.'='.GW_A_BROWSE.'&'. GW_TARGET.'='.$this->addon_name.'&'.
					'&w1=rp03&'.
					'p=1', $this->oL->m('3_profile').' 2'
				).' '.
		$this->oHtml->a($this->sys['page_admin'].'?'. GW_ACTION.'='.GW_A_BROWSE.'&'. GW_TARGET.'='.$this->addon_name.'&'.
					'&w1=rp04&'.
					'p=1', $this->oL->m('3_profile').' 3'
				).
		'</div>';
	}
	/* */
	function browse()
	{
		if (in_array(strtolower('_'.$this->gw_this['vars']['w1']), get_class_methods($this)))
		{
			$this->{'_'.$this->gw_this['vars']['w1']}();
			return;
		}

		if (!$this->gw_this['vars']['uid'])
		{
			$this->gw_this['vars']['uid'] = 168;
		}
		$sql_where = sprintf(' sr.date_created < %d AND sr.date_created > %d',
			$this->sys['time_now_gmt_unix'],
			$this->sys['time_now_gmt_unix'] - ($this->gw_this['vars']['uid'] * 3600)
		);
		/* Count */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('cnt-search_results', $sql_where), $this->addon_name);
		$this->int_found = isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
		$this->int_pages = ceil($this->int_found / $this->sys['page_limit_search']);
		if ( ($this->gw_this['vars']['p'] < 1) || ($this->gw_this['vars']['p'] > $this->int_pages) ){ $this->gw_this['vars']['p'] = 1; }
		$sql_limit = $this->oDb->prn_limit($this->int_found, $this->gw_this['vars']['p'], $this->sys['page_limit_search']);
		/* The list of search queries */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-search_results', $sql_where, $sql_limit), $this->addon_name);
		/* */
		$this->_print_report($arSql);
	}
	/* */
	function clean()
	{
		$str_question = '<p class="xr red"><b>' . $this->oL->m('9_remove') .'</b></p>';
		if (!$this->gw_this['vars']['isConfirm']) /* if not confirmed */
		{
			/* Search queries log */
			$arTableInfo = $this->oDb->table_info($this->sys['tbl_prefix'].'stat_search');
			$int_size = ($arTableInfo['Data_length'] > 1) ? $arTableInfo['Data_length']+$arTableInfo['Index_length'] : 0;
			$str_question .= '<p class="xt">'.$this->oL->m('log_search').', '.$this->oL->m('bytes').': '. $this->oFunc->number_format($int_size, 0, $this->oL->languagelist('4')).'</p>';

			/* Not confirmed */
			$oConfirm = new gwConfirmWindow;
			$oConfirm->action = $this->sys['page_admin'];
			$oConfirm->submitok = $this->oL->m('3_clean');
			$oConfirm->submitcancel = $this->oL->m('3_cancel');
			$oConfirm->formbgcolor = $this->ar_theme['color_2'];
			$oConfirm->formbordercolor = $this->ar_theme['color_4'];
			$oConfirm->formbordercolorL = $this->ar_theme['color_1'];
			$oConfirm->setQuestion($str_question);
			$oConfirm->tAlign = 'center';
			$oConfirm->formwidth = '400';
			$oConfirm->setField('hidden', GW_ACTION, $this->gw_this['vars'][GW_ACTION]);
			$oConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
			$oConfirm->setField("hidden", $this->oSess->sid, $this->oSess->id_sess);
			$this->str .= $oConfirm->Form();
		}
		else
		{
#			$this->sys['isDebugQ'] = 1;
			$arQ[] = sprintf('TRUNCATE TABLE %s', $this->sys['tbl_prefix'].'stat_search');
			$this->str .= postQuery($arQ, 'a=' . GW_A_BROWSE . '&'.GW_TARGET.'=' . $this->addon_name, $this->sys['isDebugQ'], 0);
		}
		global $strR;
		$strR .= $this->str;
	}
	/* */
	function _rp01()
	{
		$sql_where = sprintf(' sr.q = "%s"', gw_text_sql($this->gw_this['vars']['q']));
		$sql_group_by = ' GROUP BY sr.date_created';

		/* Count */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('cnt-rp01', $sql_where), $this->addon_name);
		$this->int_found = isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
		$this->int_pages = ceil($this->int_found / $this->sys['page_limit']);
		if ( ($this->gw_this['vars']['p'] < 1) || ($this->gw_this['vars']['p'] > $this->int_pages) ){ $this->gw_this['vars']['p'] = 1; }
		$sql_limit = $this->oDb->prn_limit($this->int_found, $this->gw_this['vars']['p'], $this->sys['page_limit']);
		/* The list of search queries */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-rp01', $sql_where, '', $sql_limit), $this->addon_name);
		/* */
		$this->_print_report($arSql);
	}
	/* */
	function _rp02()
	{
		$sql_where = sprintf(' sr.id_dict = "%d"',
			$this->gw_this['vars']['id']
		);
#		$sql_group_by = ' GROUP BY sr.date_created';

		/* Count */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('cnt-rp01', $sql_where), $this->addon_name);
		$this->int_found = isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
		$this->int_pages = ceil($this->int_found / $this->sys['page_limit']);
		if ( ($this->gw_this['vars']['p'] < 1) || ($this->gw_this['vars']['p'] > $this->int_pages) ){ $this->gw_this['vars']['p'] = 1; }
		$sql_limit = $this->oDb->prn_limit($this->int_found, $this->gw_this['vars']['p'], $this->sys['page_limit']);
		/* The list of search queries */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-rp01', $sql_where, '', $sql_limit), $this->addon_name);
		/* */
		$this->_print_report($arSql);
	}

	/* 1.8.8: The most wanted but not found */
	function _rp03()
	{
		/* Count */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('cnt-rp03'), $this->addon_name);
		$this->int_found = sizeof($arSql);
		$this->int_pages = ceil($this->int_found / $this->sys['page_limit']);
		if ( ($this->gw_this['vars']['p'] < 1) || ($this->gw_this['vars']['p'] > $this->int_pages) ){ $this->gw_this['vars']['p'] = 1; }
		$sql_limit = $this->oDb->prn_limit($this->int_found, $this->gw_this['vars']['p'], $this->sys['page_limit']);
		/* The list of search queries */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-rp03', $sql_limit), $this->addon_name);
		/* */
		global $strR;

		$str_pages = getNavToolbar($this->int_pages, $this->gw_this['vars']['p'],
					$this->sys['page_admin'].'?'.GW_ACTION.'='.$this->gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$this->addon_name.'&'.
					'q='.$this->gw_this['vars']['q'].'&'.
					'w1='.$this->gw_this['vars']['w1'].'&'.
					'w2='.$this->gw_this['vars']['w2'].'&'.
					'id='.$this->gw_this['vars']['id'].'&'.
					'uid='.$this->gw_this['vars']['uid'].'&p='
		);

		$strR .= '<table cellspacing="1" cellpadding="3" border="0" width="100%"><tbody><tr class="xt gray">';
		$strR .= '<td></td>';
		$strR .= '<td style="width:55%;text-align:'.$this->sys['css_align_right'].'">'. $str_pages.'</td>';
		$strR .= '</tr></tbody></table>';

		$strR .= '<table class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
		$strR .= '<thead><tr style="color:'.$this->ar_theme['color_1'].';background:'.$this->ar_theme['color_6'].'">';
		$strR .= '<th style="width:1%">N</th>';
		$strR .= '<th>' . $this->oL->m('srch_3') . '</th>';
		$strR .= '<th style="width:6%">' . $this->oL->m('requests_total') . '</th>';
		$strR .= '<th style="width:6%">' . $this->oL->m('srch_matches') . '</th>';
		$strR .= '</tr></thead><tbody>';

		if (empty($arSql))
		{
			$strR .= '<tr class="gray">';
			$strR .= '<td style="text-align:'.$this->sys['css_align_right'].'"><span class="xt">&#160;</span></td>';
			$strR .= '<td colspan="3" class="termpreview">'. $this->oL->m('1297').'</td>';
			$strR .= '</tr>';
		}

		$cnt_row = 1;
		while (list($k, $arV) = each($arSql))
		{
			$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];
			$num_class = ($arV['found'] == 0) ? ' red' : '';
			$strR .= '<tr class="gray" style="background:'.$bgcolor.'">';
			$strR .= '<td style="text-align:'.$this->sys['css_align_right'].'"><span class="xt'.$num_class.'">&#160;' .  $cnt_row . '&#160;</span></td>';
			$strR .= '<td class="termpreview">';
			$strR .= $this->oHtml->a(
						$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_BROWSE. '&w1=rp01&' .GW_TARGET.'='.$this->addon_name.'&q=' .urlencode($arV['q']),
						$arV['q']);
			$strR .= '</td>';
			$strR .= '</td>';
			$strR .= '<td class="xt'.$num_class.'">';
			$strR .= $arV['n'];
			$strR .= '</td>';
			$strR .= '<td class="xt'.$num_class.'">';
			$strR .= $arV['found'];
			$strR .= '</td>';
			++$cnt_row;
		}
		$strR .= '</tbody></table>';

		/* 1.8.8: More reports */
		$strR .= $this->get_menu_reports();
    }
	/* 1.8.8: The most wanted and found */
	function _rp04()
	{
		/* Count */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('cnt-rp04'), $this->addon_name);
		$this->int_found = sizeof($arSql);
		$this->int_pages = ceil($this->int_found / $this->sys['page_limit']);
		if ( ($this->gw_this['vars']['p'] < 1) || ($this->gw_this['vars']['p'] > $this->int_pages) ){ $this->gw_this['vars']['p'] = 1; }
		$sql_limit = $this->oDb->prn_limit($this->int_found, $this->gw_this['vars']['p'], $this->sys['page_limit']);
		/* The list of search queries */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-rp04', $sql_limit), $this->addon_name);
		/* */
		global $strR;

		$str_pages = getNavToolbar($this->int_pages, $this->gw_this['vars']['p'],
					$this->sys['page_admin'].'?'.GW_ACTION.'='.$this->gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$this->addon_name.'&'.
					'q='.$this->gw_this['vars']['q'].'&'.
					'w1='.$this->gw_this['vars']['w1'].'&'.
					'w2='.$this->gw_this['vars']['w2'].'&'.
					'id='.$this->gw_this['vars']['id'].'&'.
					'uid='.$this->gw_this['vars']['uid'].'&p='
		);

		$strR .= '<table cellspacing="1" cellpadding="3" border="0" width="100%"><tbody><tr class="xt gray">';
		$strR .= '<td></td>';
		$strR .= '<td style="width:55%;text-align:'.$this->sys['css_align_right'].'">'. $str_pages.'</td>';
		$strR .= '</tr></tbody></table>';

		$strR .= '<table class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
		$strR .= '<thead><tr style="color:'.$this->ar_theme['color_1'].';background:'.$this->ar_theme['color_6'].'">';
		$strR .= '<th style="width:1%">N</th>';
		$strR .= '<th>' . $this->oL->m('srch_3') . '</th>';
		$strR .= '<th style="width:6%">' . $this->oL->m('requests_total') . '</th>';
		$strR .= '<th style="width:6%">' . $this->oL->m('srch_matches') . '</th>';
		$strR .= '</tr></thead><tbody>';

		if (empty($arSql))
		{
			$strR .= '<tr class="gray">';
			$strR .= '<td style="text-align:'.$this->sys['css_align_right'].'"><span class="xt">&#160;</span></td>';
			$strR .= '<td colspan="3" class="termpreview">'. $this->oL->m('1297').'</td>';
			$strR .= '</tr>';
		}
		
		$cnt_row = 1;
		while (list($k, $arV) = each($arSql))
		{
			$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];
			$num_class = ($arV['found'] == 0) ? ' red' : '';
			$strR .= '<tr class="gray" style="background:'.$bgcolor.'">';
			$strR .= '<td style="text-align:'.$this->sys['css_align_right'].'"><span class="xt'.$num_class.'">&#160;' .  $cnt_row . '&#160;</span></td>';
			$strR .= '<td class="termpreview">';
			$strR .= $this->oHtml->a(
						$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_BROWSE. '&w1=rp01&' .GW_TARGET.'='.$this->addon_name.'&q=' .urlencode($arV['q']),
						$arV['q']);
			$strR .= '</td>';
			$strR .= '</td>';
			$strR .= '<td class="xt'.$num_class.'">';
			$strR .= $arV['n'];
			$strR .= '</td>';
			$strR .= '<td class="xt'.$num_class.'">';
			$strR .= $arV['found'];
			$strR .= '</td>';
			++$cnt_row;
		}
		$strR .= '</tbody></table>';

		/* 1.8.8: More reports */
		$strR .= $this->get_menu_reports();
    }
	/* */
	function _print_report($arSql)
	{
		global $strR;
		$str_pages = getNavToolbar($this->int_pages, $this->gw_this['vars']['p'],
					$this->sys['page_admin'].'?'.GW_ACTION.'='.$this->gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$this->addon_name.'&'.
					'q='.$this->gw_this['vars']['q'].'&'.
					'w1='.$this->gw_this['vars']['w1'].'&'.
					'w2='.$this->gw_this['vars']['w2'].'&'.
					'id='.$this->gw_this['vars']['id'].'&'.
					'uid='.$this->gw_this['vars']['uid'].'&p='
		);
	
		$strR .= '<table cellspacing="1" cellpadding="0" border="0" width="100%"><tbody>';
		$strR .= '<tr><td class="xt gray" style="text-align:'.$this->sys['css_align_right'].'">'. $str_pages.'</td></tr>';
		$strR .= '<tr><td>'. $this->get_menu_period().'</td></tr>';
		$strR .= '</tbody></table>';
		
		$strR .= '<table class="tbl-browse" cellspacing="1" cellpadding="0" border="0" width="100%">';
		$strR .= '<thead><tr style="color:'.$this->ar_theme['color_1'].';background:'.$this->ar_theme['color_6'].'">';
		$strR .= '<th style="width:1%">N</th>';
		$strR .= '<th>' . $this->oL->m('srch_3') . '</th>';
		$strR .= '<th style="width:30%">' . $this->oL->m('dict') . '</th>';
		$strR .= '<th style="width:6%">' . $this->oL->m('srch_matches') . '</th>';
		$strR .= '<th style="width:20%">' . $this->oL->m('action') . '</th>';
		$strR .= '</tr></thead><tbody>';


		if (empty($arSql))
		{
			$strR .= '<tr class="gray">';
			$strR .= '<td style="text-align:'.$this->sys['css_align_right'].'"><span class="xt">&#160;</span></td>';
			$strR .= '<td colspan="4" class="termpreview">'. $this->oL->m('1297').'</td>';
			$strR .= '</tr>';
		}
		
		$cnt_row = 1;
		while (list($k, $arV) = each($arSql))
		{
			$bgcolor = $cnt_row % 2 ? $this->ar_theme['color_1'] : $this->ar_theme['color_2'];
			$num_class = ($arV['found'] == 0) ? ' red' : '';
			$strR .= '<tr class="gray" style="background:'.$bgcolor.'">';
			$strR .= '<td style="text-align:'.$this->sys['css_align_right'].'"><span class="xt'.$num_class.'">&#160;' .  $cnt_row . '&#160;</span></td>';
			$strR .= '<td class="termpreview">';
			$strR .= $this->oHtml->a(
						$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_BROWSE. '&w1=rp01&' .GW_TARGET.'='.$this->addon_name.'&q=' .urlencode($arV['q']),
						$arV['q']);
			$strR .= '</td>';
			$strR .= '<td class="xt">';

			if ($arV['id_dict'])
			{
				$arDictParam = getDictParam($arV['id_dict']);
			}
			$strR .= $arV['id_dict']
					? $this->oHtml->a(
						$this->sys['page_admin'] . '?'.GW_ACTION.'='.GW_A_BROWSE. '&w1=rp02&' .GW_TARGET.'='.$this->addon_name. '&id=' . $arV['id_dict'],
						$arDictParam['title'])
					: $this->oL->m('1113');
			$strR .= '</td>';
			$strR .= '<td class="xt'.$num_class.'">';
			$strR .= $arV['found'];
			$strR .= '</td>';
			$strR .= '<td class="xt'.$num_class.'">';
			$strR .= date_extract_int($arV['date_created'] + ($this->oSess->user_get_time_seconds()), "%H:%i %d %M %Y");
			$strR .= '</td>';
			$strR .= '</tr>';
			++$cnt_row;
		}
		$strR .= '</tbody></table>';

		if (!empty($arSql))
		{
			$strR .= '<table cellspacing="1" cellpadding="4" border="0" width="100%"><tbody><tr class="xt gray">';
			$strR .= '<td>' . $this->oL->m('srch_matches') .': <strong>'. $this->oFunc->number_format($this->int_found, 0, $this->oL->languagelist('4')) . '</strong></td>';
			$strR .= '<td style="width:70%;text-align:'.$this->sys['css_align_right'].'">'. $str_pages.'</td>';
			$strR .= '</tr></tbody></table>';
		}

		/* 1.8.8: More reports */
		$strR .= $this->get_menu_reports();
	}
	/* */
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
$oAddonAdm = new gw_addon_log_search_admin;
$oAddonAdm->alpha();
/* Do not load old components */
$pathAction = '';
/* end of file */
?>