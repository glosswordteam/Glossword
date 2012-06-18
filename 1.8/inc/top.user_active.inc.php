<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: top.user_active.inc.php 494 2008-06-13 23:05:30Z glossword_team $ -->');
}
/**
 *  Glossword - glossary compiler (http://glossword.info/dev/) 
 *  © 2002-2005 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
// --------------------------------------------------------
/**
 *  Makes the list of last added dictionaries.
 *  This code runs inside function getTop10();
 *  switch keyname: R_USER_ACTIVE
 */
// --------------------------------------------------------
/**
 * Variables:
 * $amount, $arThText,  $arThAlign, $strTopicName, $strData, $curDateMk
 */
	$arSql = $oDb->sqlRun(sprintf($oSqlQ->getQ('top-user-terms', $amount)), 'st');
#prn_r( $arSql );
	$arThText = array($oL->m('contact_name'), $oL->m('termsamount'));
	/*  Sets width for colums */
	$arThWidth = array('70%', '29%');
	/* Sets alignment for colums */
	$arThAlign = array($sys['css_align_left'], $sys['css_align_right']);
	/* for each dictionary */
	for (; list($arK, $arV) = each($arSql);)
	{
		$cnt % 2 ? ($bgcolor = $ar_theme['color_2']) : ($bgcolor = $ar_theme['color_1']);
		$cnt++;        
		$strData .= '<tr class="xt gray" style="background:' . $bgcolor . '">';
		$strData .= '<td>' . $cnt . '</td>';
		$strData .= '<td>' . $oHtml->a($sys['page_index'].'?'.GW_ACTION.'='.GW_A_PROFILE.'&t=view&id='.$arV['id_user'], $arV['user_name']) . '</td>';
		$strData .= '<td>';
		$strData .= $oFunc->number_format($arV['int_items'], 0, $oL->languagelist('4'));
		$strData .= '</td>';
		$strData .= '</tr>';
	}

/**
 * end of R_USER_ACTIVE
 */
?>