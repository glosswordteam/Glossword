<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: top.term_updated.inc.php 496 2008-06-14 06:42:53Z glossword_team $ -->');
}
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `glossword/support/license.html' for details)
 */
/**
 *  Makes the list of last added terms.
 *  This code runs inside function getTop10();
 *  switch keyname: R_TERM_UPDATED
 */
/**
 * Variables:
 * $amount, $arThText,  $arThAlign, $strTopicName, $strData, $curDateMk
 */
	/* 
	1. Sort dictionaries by modification date
	2. Get terms untill the number of terms reaches $amount.
	*/
	$arDicts = array();
	for (reset($gw_this['ar_dict_list']); list($k, $arDs) = each($gw_this['ar_dict_list']);)
	{
		$arDicts[$arDs['date_modified']] = $arDs;
	}
	krsort($arDicts);
	$cnt_terms = 0;
	$ar_terms = array();
	for (; list($k, $arDs) = each($arDicts);)
	{
		$sql = $oSqlQ->getQ('top-term-new', $arDs['tablename'], $sys['time_now_db'], 'date_modified DESC', $amount);
		$arSql = $oDb->sqlExec($sql);
		for (; list($arK, $arV) = each($arSql);)
		{
			$arV['title'] = $arDs['title'];
			$arV['id_dict'] = $arDs['id'];
			if (GW_IS_BROWSE_ADMIN)
			{
				$arV['is_term_edit'] = $oSess->is('is-terms', $arDs['id']);
			}
			else
			{
				switch ($sys['pages_link_mode'])
				{
					case GW_PAGE_LINK_NAME:
						$arV['id_dict'] = urlencode($arDs['title']);
						$arV['id'] = urlencode($arV['term']);
					break;
					case GW_PAGE_LINK_URI:
						$arV['id_dict'] = urlencode($arDs['dict_uri']);
						$arV['id'] = ($arV['term_uri'] == '') ? urlencode($arV['term']) : urlencode($arV['term_uri']);
					break;
					default:
					break;
				}
			}
			/* `-$arK' is trick to show terms with the same date */
			$ar_terms[$arV['date_modified']-$arK] = $arV;
			$cnt_terms++;
		}
		if ($cnt_terms >= $amount)
		{
			break;
		}
	}
	$arThText = array();

	if (GW_IS_BROWSE_ADMIN)
	{
		$arThWidth = array('5%', '50%', '54%');
		$arThAlign = array('center', $sys['css_align_left'], $sys['css_align_left']);
	}
	else
	{
		$arThWidth = array('50%', '49%');
		$arThAlign = array($sys['css_align_left'], $sys['css_align_left']);
	}
	$strTopicName = '';

	if (GW_IS_BROWSE_ADMIN)
	{
		/* The list of allowed dictionaries */
		$ar_allowed_dicts = $oSess->user_get('dictionaries');
	}

	/* For each term */
	for (; list($k, $arV) = each($ar_terms);)
	{
		if ($cnt == $amount) { break; }
		$cnt % 2 ? ($bgcolor = $ar_theme['color_2']) : ($bgcolor = $ar_theme['color_1']);
		$cnt++;
		$strData .= '<tr class="gray" style="background:' . $bgcolor . '">';
		$strData .= '<td class="n xt">' . $cnt . '</td>';
		if (GW_IS_BROWSE_ADMIN)
		{
			$str_edit = '<del>'.$oL->m('3_edit').'</del>';
			$url_term = strip_tags($arV['term']);
			$href_edit = $sys['page_admin']. '?'.GW_ACTION.'='.GW_A_EDIT. '&'. GW_TARGET.'='.GW_T_TERMS. '&id='.$arV['id_dict'] . '&tid='.$arV['id'];

			/* Check for permission */
			if ($oSess->is('is-terms')
				? 1 
				: (($arV['id_user'] == $oSess->id_guest)
				|| ($oSess->is('is-terms-own') && ($arV['id_user'] == $oSess->id_user))) 
				? 1 : 0
			)
			{
				$str_edit = $oHtml->a( $href_edit, $oL->m('3_edit'), $oL->m('terms').': '.$oL->m('3_edit') );
				$url_term = $oHtml->a( $href_edit, strip_tags($arV['term']) );
			}
			$strData .= '<td class="actions-third"><span>' . $str_edit . '</span>&#160;</td>';
			$url_dict = $arV['title'];
		}
		else
		{
			$url_term = $oHtml->a( append_url($sys['page_index']
								. '?' .GW_ACTION.'='.GW_T_TERM.'&d='.$arV['id_dict']
								. '&' .GW_TARGET.'='.$arV['id']), strip_tags($arV['term']), '');
			$url_dict = $oHtml->a( append_url($sys['page_index']
								. '?' .GW_ACTION. '=index&d=' .$arV['id_dict']), strip_tags($arV['title']), '');
		}
		$strData .= '<td class="termpreview">' . $url_term . '</td>';
		$strData .= '<td class="xt">' . $url_dict . '</td>';
		$strData .= '</tr>';
	}
/**
 * end of R_TERM_UPDATED
 */
?>