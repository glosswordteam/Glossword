<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: top.search_last.inc.php 494 2008-06-13 23:05:30Z glossword_team $ -->');
}
/**
 *  Glossword - glossary compiler (http://glossword.info/) 
 *  © 2002-2006 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
/**
 *  Makes the list of last searched terms.
 *  This code runs inside function getTop10();
 *  switch keyname: SEARCH_LAST
 */
/**
 * Variables:
 * $amount, $arThText,  $arThAlign, $strTopicName, $strData, $curDateMk
 */

	//
	$arSql = $oDb->sqlExec($oSqlQ->getQ('top-search-last', $amount));
	if (sizeof($arSql) == 0)
	{
		return '';
	}
	/*
    [0] => Array
        (
            [id_dict] => 0
            [q] => Gigabyte
            [date_created] => 1151162264
            [cnt] => 4
        )
	*/
	$arThText = array();
	$arDs = array();
	// Sets width for colums
	if ($isItemOnly)
	{
		$arThWidth = array('99%');
	}
	else
	{
		$arThWidth = array('', '30%');
	}    
	$arThText = array($oL->m('srch_3'), $oL->m('dict'));
	// Sets alignment for colums
	$arThAlign = array($sys['css_align_left'], $sys['css_align_right']);
	// for each dictionary
	for (; list($arK, $arV) = each($arSql);)
	{
		$cnt % 2 ? ($bgcolor = $ar_theme['color_2']) : ($bgcolor = $ar_theme['color_1']);
		$cnt++;
		$strData .= '<tr class="gray" style="background:' . $bgcolor . '">';
		$strData .= '<td class="xq">' . $cnt . '</td>';

		$url = $oHtml->a( $sys['page_index']
							. '?'.GW_ACTION.'='.GW_A_SEARCH.'&d=' . $arV['id_dict']
							. '&srch[adv]=all&srch[by]=d&srch[in]=-1&q=' . urlencode($arV['q']), $arV['q']);
		$strData .= '<td class="termpreview">' . $url . '</td>';

		if ($arV['id_dict'])
		{
			$arDs = getDictParam($arV['id_dict']);
			switch ($sys['pages_link_mode'])
			{
				case GW_PAGE_LINK_NAME:
					$arV['id_dict'] = urlencode($arDs['title']);
				break;
				case GW_PAGE_LINK_URI:
					$arV['id_dict'] = urlencode($arDs['dict_uri']);
				break;
				default:
				break;
			}
		}
		$strData .= '<td class="xt">';
		$strData .= $arV['id_dict'] 
				? $oHtml->a(
					$sys['page_index'] . '?'.GW_ACTION.'=list&p=1&d=' . $arV['id_dict'],
					$arDs['title'])
				: $oL->m('1113');
		$strData .= '</td>';
		if (!$isItemOnly)
		{
#			$strData .= '<td class="xq" style="white-space:nowrap">';
#			$strData .= (date_extract_int($arV['date_created'], '%d') / 1) . date_extract_int($arV['date_created'], '&#160;%F&#160;%Y');
#			$strData .= '</td>';
		}
		$strData .= '</tr>';
	}
	unset($arDs);
/* end of file */
?>