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
	die('<!-- $Id: top.dict_updated.inc.php 496 2008-06-14 06:42:53Z glossword_team $ -->');
}
/**
 *  Makes the list of last updated dictionaries.
 *  This code runs inside function getTop10();
 *  switch keyname: DICT_UPDATED
 */
/**
 * Variables:
 * $amount, $arThText,  $arThAlign, $strTopicName, $strData, $curDateMk
 */
	if ($isItemOnly)
	{
		$arThText = array();
		$arThWidth = array('99%');
		$arThAlign = array($sys['css_align_left']);
	}
	else
	{
		$arThText = array($oL->m('th_1'), $oL->m('date_modif'));
		$arThWidth = array('', '30%');
		$arThAlign = array($sys['css_align_left'], $sys['css_align_right']);
		if (GW_IS_BROWSE_ADMIN)
		{
			$arThText = array($oL->m('action'), $oL->m('th_1'), $oL->m('date_modif'));
		}
	}
	if (GW_IS_BROWSE_ADMIN)
	{
		/* The list of allowed dictionaries */
		$ar_allowed_dicts = $oSess->user_get('dictionaries');

		$arSql = $oDb->sqlExec(sprintf($oSqlQ->getQ('top-dict-updated-adm', $amount)));
		$arThWidth = array('15%', '55%', '29%');
		$arThAlign = array('center', $sys['css_align_left'], $sys['css_align_right']);
	}
	else
	{
		$arSql = $oDb->sqlRun(sprintf($oSqlQ->getQ('top-dict-updated', $sys['time_now_db'], $amount)), 'st');
	}
	$strTopicName = '';
	/* for each dictionary */
	for (; list($arK, $arV) = each($arSql);)
	{
		$cnt % 2 ? ($bgcolor = $ar_theme['color_2']) : ($bgcolor = $ar_theme['color_1']);
		$cnt++;
		$strData .= '<tr style="background:' . $bgcolor . '">';
		$strData .= '<td class="n xt">' . $cnt . '</td>';
		if (GW_IS_BROWSE_ADMIN)
		{
			if ( $oSess->is('is-sys-settings')
				|| $oSess->is('is-dicts')
				|| (isset($ar_allowed_dicts[$arV['id']]) && $oSess->is('is-dicts-own') )
			)
			{
				$strData .= '<td class="actions-third"><span>';
				$strData .= $oHtml->a($sys['page_admin'].'?'.GW_ACTION. '='.GW_A_EDIT .'&'. GW_TARGET.'='.GW_T_DICTS. '&id='.$arV['id'] . '&tid='.$arV['id'], $oL->m('3_edit'), $oL->m('1335').': '.$oL->m('3_edit'));
				$strData .= ' ';
				$strData .= $oHtml->a($sys['page_admin'].'?'.GW_ACTION. '='. GW_A_ADD .'&'. GW_TARGET.'='.GW_T_TERMS. '&id='.$arV['id'] . '&tid='.$arV['id'], $oL->m('3_add_term'), $oL->m('terms').': '.$oL->m('3_add'));
				$strData .= '&#160;</span></td>';
				$strData .= '<td class="termpreview">' . $oHtml->a($sys['page_admin'].'?'.GW_ACTION. '='.GW_A_EDIT .'&'. GW_TARGET.'='.GW_T_DICTS. '&id='.$arV['id'] . '&tid='.$arV['id'], $arV['title']) . '</td>';
			}
			else
			{
				$strData .= '<td class="actions-third"><span><del>'.$oL->m('3_edit').'</del> <del>'.$oL->m('3_add_term').'</del>&#160;</span></td>';
				$strData .= '<td class="termpreview">'.$arV['title'].'&#160;</td>';
			}
		}
		else
		{
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$arV['id'] = urlencode($arV['title']);
				break;
				case GW_PAGE_LINK_URI:
					$arV['id'] = urlencode($arV['dict_uri']);
				break;
				default:
				break;
			}
			$strData .= '<td class="xt">' . $oHtml->a($sys['page_index'] . '?' .GW_ACTION. '=list&p=1&d=' . $arV['id'], $arV['title']) . '</td>';
		}
		if (!$isItemOnly || GW_IS_BROWSE_ADMIN)
		{
			$strData .= '<td class="xt gray">';
			$strData .= (date_extract_int($arV['date_modified'], '%d') / 1) . date_extract_int($arV['date_modified'], ' %F %Y');
			$strData .= '</td>';
		}
		$strData .= '</tr>';
	}
/**
 * end of DICT_UPDATED
 */
?>