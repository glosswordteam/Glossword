<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: top.dict_averagehits.inc.php 494 2008-06-13 23:05:30Z glossword_team $ -->');
}
/**
 *  Glossword - glossary compiler (http://glossword.info/) 
 *  © 2002-2007 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
/**
 *  Calculates the average hits per dictionary.
 *  This code runs inside function getTop10();
 *  switch keyname: R_DICT_AVERAGEHITS
 */
/**
 * Variables:
 * $amount, $arThText,  $arThAlign, $strTopicName, $strData, $curDateMk
 */
	/* do not cache, $sys['time_now_db'] is used */

	$arSql = $oDb->sqlExec($oSqlQ->getQ('top-dict-hits-avg', $sys['time_now_db'], $sys['time_now_db'], $amount));
	/* */
	$sumHits = $sumAvg = 0;
	$arThText = array($oL->m('th_1'), $oL->m('th_3'), $oL->m('th_4'));
	/* Sets width for colums */
	$arThWidth = array('', '15%', '15%');
	/* Sets alignment for colums */
	$arThAlign = array($sys['css_align_left'], $sys['css_align_right'], $sys['css_align_right']);
	/* for each dictionary */
	for (; list($arK, $arV) = each($arSql);)
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
		$cnt % 2 ? ($bgcolor = $ar_theme['color_2']) : ($bgcolor = $ar_theme['color_1']);
		$cnt++;        
		$strData .= '<tr class="xt gray" style="background:'.$bgcolor.'">';
		$strData .= '<td>' . $cnt . '</td>';
		$strData .= '<td>' . $oHtml->a($sys['page_index']. "?a=list&p=1&d=" . $arV['id'], $arV['title']) . '</td>';
		$strData .= '<td style="text-align:'.$sys['css_align_right'].'">' . $oFunc->number_format($arV['hits_avg'], 0, $oL->languagelist('4')) . '</td>';
		$strData .= '<td style="text-align:'.$sys['css_align_right'].'">' . $oFunc->number_format($arV['hits'], 0, $oL->languagelist('4')) . '</td>';
		$strData .= '</tr>';
		$sumAvg += $arV['hits_avg'];
		$sumHits += $arV['hits'];
	}
	// table footer
	$strData .= '<tr class="xt" style="background:' . $ar_theme['color_3'] . '">';
	$strData .= '<td></td>';
	$strData .= '<td>' . $oHtml->a($sys['page_index'], '<b>'.$oL->m('catalog').'&#160;&gt;</b>') . '</td>';
	$strData .= '<td style="text-align:'.$sys['css_align_right'].'">' . $oFunc->number_format($sumAvg, 0, $oL->languagelist('4')) . '</td>';
	$strData .= '<td style="text-align:'.$sys['css_align_right'].'">' . $oFunc->number_format($sumHits, 0, $oL->languagelist('4')) . '</td>';
	$strData .= '</tr>';
/**
 * end of R_DICT_AVERAGEHITS
 */
?>