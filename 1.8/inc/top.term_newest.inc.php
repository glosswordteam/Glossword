<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: top.term_newest.inc.php 494 2008-06-13 23:05:30Z glossword_team $ -->');
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
// --------------------------------------------------------
/**
 *  Makes the list of last added terms.
 *  This code runs inside function getTop10();
 *  switch keyname: R_TERM_NEWEST
 */
// --------------------------------------------------------
/**
 * Variables:
 * $amount, $arThText,  $arThAlign, $strTopicName, $strData, $curDateMk
 */

	//
	$ar_sorting[0] = 'date_modified DESC';
	$ar_sorting[1] = 'date_modified DESC';
	$arSql = $oDb->sqlExec(sprintf($oSqlQ->getQ('top-term-new', $arDictParam['tablename'], $sys['time_now_db'], $ar_sorting[$order], $amount)));
	//
	$arThText = array();
	// Sets width for colums
	if ($isItemOnly)
	{
		$arThWidth = array('99%');
	}
	else
	{
		$arThWidth = array('', '29%');
	}
	if ($order == 2)
	{
		global $oCase;
		/* Sort the list of terms by name */
		for (; list($arK, $arV) = each($arSql);)
		{
			$arSqlSorted[urlencode($arV['term'])] = $arV;
			unset($arSql[$arK]);
		}
		ksort($arSqlSorted);
		$arSql =& $arSqlSorted;
	}
	// Sets alignment for colums
	$arThAlign = array($sys['css_align_left'], $sys['css_align_right']);
	$strTopicName = $oL->m('recent');
	// for each item
	for (; list($arK, $arV) = each($arSql);)
	{
		$cnt % 2 ? ($bgcolor = $ar_theme['color_2']) : ($bgcolor = $ar_theme['color_1']);
		$cnt++;
		$strData .= '<tr style="background:' . $bgcolor . '">';
		$strData .= '<td class="xq">' . $cnt . '</td>';

		$oHtml->setTag('a', 'title', (date_extract_int($arV['date_modified'], '%d') / 1) . date_extract_int($arV['date_modified'], '&#160;%F&#160;%Y'));
		switch ($sys['pages_link_mode'])
		{
			case GW_PAGE_LINK_NAME:
				$url = $oHtml->a( append_url ( $sys['page_index']
						. '?'.GW_ACTION.'='.GW_T_TERM.'&d=' . $arDictParam['uri']
						. '&'.GW_TARGET.'=' . urlencode($arV['term'])), strip_tags($arV['term']), '');
			break;
			case GW_PAGE_LINK_URI:
				$arV['term_uri'] = ($arV['term_uri'] == '') ? $arV['term'] : $arV['term_uri'];
				$url = $oHtml->a( append_url ( $sys['page_index']
						. '?'.GW_ACTION.'='.GW_T_TERM.'&d=' . $arDictParam['uri']
						. '&'.GW_TARGET.'=' . urlencode($arV['term_uri'])), strip_tags($arV['term']), '');
			break;
			default:
				$url = $oHtml->a( append_url ( $sys['page_index']
						. '?'.GW_ACTION.'='.GW_T_TERM.'&d=' . $arDictParam['uri']
						. '&'.GW_TARGET.'=' . $arV['id']), strip_tags($arV['term']), '');
			break;
		}
		$ar_top10_list[] = $url;
		
		$strData .= '<td class="termpreview">' . $url . '</td>';
		if (!$isItemOnly)
		{
			$strData .= '<td class="xq" style="white-space:nowrap">';
			$strData .= (date_extract_int($arV['date_modified'], '%d') / 1) . date_extract_int($arV['date_modified'], '&#160;%F&#160;%Y');
			$strData .= '</td>';
		}
		$strData .= '</tr>';
	}
	$oHtml->setTag('a', 'title', '');
/**
 * end of R_TERM_NEWEST
 */
?>