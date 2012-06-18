<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: top.dict_newest.inc.php 494 2008-06-13 23:05:30Z glossword_team $ -->');
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
 *  Makes the list of last added dictionaries.
 *  This code runs inside function getTop10();
 *  switch keyname: R_DICT_NEWEST
 */
/**
 * Variables:
 * $amount, $arThText,  $arThAlign, $strTopicName, $strData, $curDateMk
 */
	$arSql = $oDb->sqlExec( $oSqlQ->getQ('top-dict-new', $sys['time_now_db'], $amount) );
	//
	$arThText = array($oL->m('th_1'), $oL->m('th_5'));
	// Sets width for colums
	$arThWidth = array('', '30%');
	// Sets alignment for colums
	$arThAlign = array($sys['css_align_left'], $sys['css_align_right']);
	// for each dictionary
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
		$strData .= '<tr class="gray xt" style="background:' . $bgcolor . '">';
		$strData .= '<td>' . $cnt . '</td>';
		$strData .= '<td>' . $oHtml->a($sys['page_index']."?a=list&p=1&d=" . $arV['id'], $arV['title']) . '</td>';
		$strData .= '<td>';
		$strData .= (date_extract_int($arV['date_created'], '%d') / 1) . date_extract_int($arV['date_created'], ' %F %Y');
		$strData .= '</td>';
		$strData .= '</tr>';
	}
/**
 * end of R_DICT_NEWEST
 */
?>