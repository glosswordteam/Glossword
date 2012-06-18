<?php
if (!defined('IN_GW'))
{
	die('<!-- $Id: a.export.inc.php 472 2008-05-14 16:55:36Z glossword_team $ -->');
}
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
// --------------------------------------------------------
/**
 *  Export. External utility for a dictionary.
 *
 *   <line>
 *    <term>A Term</term>
 *    <defn><abbr>abbreviation</abbr>1st definition</defn>
 *    <defn><trns lang="104">translation</trns>2nd definition</defn>
 *   </line>
 *
 */
// --------------------------------------------------------

/**
 *
 */
function getTermDates($id_dict, $DBTABLE)
{
	global $oDb, $oSqlQ, $sys, $oSess;
	global $arDictParam;

	$arSql = $oDb->sqlExec( $oSqlQ->getQ('get-date-mm', $arDictParam['tablename']) );
	$strA = array('max' => time(), 'min' => 0);
	for (; list($arK, $arV) = each($arSql);)
	{
		if (empty($arV['max']) && empty($arV['min']))
		{
			/* no date */
			$strA['max'] = $strA['min'] = $sys['time_now_gmt_unix'];
		}
		else
		{
			$strA['max'] = $arV['max'];
			$strA['min'] = $arV['min'];
		}
	}
	return $strA;
}
/**
 *
 */
function getExportFilename($f, $cnt, $fmt)
{
	$r = strlen($cnt);
	$seq = "_%0" . $r . "d_of_%0" . $r . "d";
	return $f . $seq . '.' . $fmt;
}
/* */
function gw_file_copy($oldname, $newname)
{
	if (is_file($oldname))
	{
		$perms = fileperms($oldname);
		return copy($oldname, $newname) && chmod($newname, $perms);
	}
	else
	{
		die("Cannot copy file: $oldname (it's neither a file nor a directory)");
	}
}
/**
 *
 */
function getFormExport($vars, $runtime = 0, $arBroken = array(), $arReq = array())
{
	global $sys, ${GW_ACTION}, $id, $oL, $arPost, $oFunc, ${GW_SID}, $ar_theme, $gw_this;
	$strForm = '';
	$trClass = 'xt';
	$form = new gwForms();
	$form->Set('action',   $GLOBALS['sys']['page_admin']);
	$form->Set('submitok',  $oL->m('3_next'). ' &gt;&gt;');
	$form->Set('submitdel',  $oL->m('3_remove'));
	$form->Set('submitcancel',  $oL->m('3_cancel'));
	$form->Set('formbgcolor',    $ar_theme['color_2']);
	$form->Set('formbordercolor', $ar_theme['color_4']);
	$form->Set('formbordercolorL', $ar_theme['color_1']);
	$form->Set('align_buttons',   $GLOBALS['sys']['css_align_right']);
	$form->Set('charset', $sys['internal_encoding']);
	## ----------------------------------------------------
	##
	 // check vars
	// reverse array keys <-- values;
	$arReq = array_flip($arReq);
	// mark fields as "REQUIRED" and make error messages
	while(is_array($vars) && list($key, $val) = each($vars) )
	{
		$arReqMsg[$key] = $arBrokenMsg[$key] = '';
		if (isset($arReq[$key])) { $arReqMsg[$key] = ' <span style="color:#E30"><b>*</b></span>'; }
		if (isset($arBroken[$key])) { $arBrokenMsg[$key] = ' <span class="'.$trClass.'" style="color:#E30"><b>' . $oL->m('reason_9') .'</b></span>'; }
	} // end of while
	##
	## ----------------------------------------------------


	$strForm .= '<table cellspacing="0" cellpadding="2" border="0" width="100%">';
	$strForm .= '<tbody><tr><td class="td1" style="vertical-align:top">';

		$strForm .= getFormTitleNav($oL->m('timeframe'), '');

		$strForm .= '<table class="gw2TableFieldset" width="100%">';
		$strForm .= '<tbody><tr>';
		$strForm .= '<td class="td2">'. $oL->m('tip001') . '</td>';
		$strForm .= '</tr>';
		$strForm .= '<tr>';
		$strForm .= '<td class="td2">';
		$strForm .= '<table cellspacing="0" cellpadding="1" border="0" width="100%">'.
					'<tbody><tr>'.
					'<td style="width:1%">'.$oL->m('from_time').'&#160;</td><td>' . htmlFormSelectDate("arPost[date_min]", @date("YmdHis", $vars['min'])) . '</td>'.
					'</tr>'.
					'<tr>'.
					'<td>'.$oL->m('till_time').'&#160;</td><td>' . htmlFormSelectDate("arPost[date_max]", @date("YmdHis", $vars['max'])) . '</td>'.
					'</tr>'.
					'</tbody></table>';
		$strForm .= '</td>';
		$strForm .= '</tr>';
		$strForm .= '<tr class="xt gray">'.
					'<td style="text-align:center;background:'.$ar_theme['color_1'].'">';
		$strForm .= '<a href="javascript:setToday();">' . $oL->m('today') . '</a>';
		$strForm .= ' | <a href="javascript:setD();">' . $oL->m('yesterday') . '</a>';
		$strForm .= ' | <a href="javascript:setM();">' . $oL->m('month') . '</a>';
		$strForm .= ' | <a href="javascript:setAll();">' . $oL->m('3_all_time') . '</a>';
		$strForm .= '</td>';
		$strForm .= '</tr>';
		$strForm .= '</tbody></table>';

	$strForm .= '</td><td style="vertical-align:top;width:30%">';
		// Select format
		$strForm .= getFormTitleNav($oL->m('select_format'), '');

		$strForm .= '<table class="gw2TableFieldset" width="100%">';
		reset($vars['arFmt']);
		for (; list($k, $v) = each($vars['arFmt']);)
		{
			$strForm .= '<tr>';
			$arBoxId['id'] = 'r_'.$v;
			$checked = ($vars['fmt_default'] == $v) ? 1 : 0;
			$strForm .= '<td class="td1">' . $form->field("radio", "arPost[fmt]", $k, $checked, $arBoxId) . '</td>';
			$strForm .= '<td class="td2"><label for="r_'.$v.'">'.$v.'</label></td>';
			$strForm .= '</tr>';
		}
		$strForm .= '</table>';

	$strForm .= '</td>';
	$strForm .= '</tr>';
	$strForm .= '</tbody></table>';

	include($sys['path_include'] . '/'. GW_ACTION . '.' . $gw_this['vars'][GW_ACTION] . '.js.php');

	$strForm .= $form->field('hidden', 'id', $id);
	$strForm .= $form->field('hidden', GW_TARGET, GW_T_DICT);
	$strForm .= $form->field('hidden', GW_ACTION, GW_A_EXPORT);
	$strForm .= $form->field('hidden', $oSess->sid, $oSess->id_sess);

	if ($arPost[GW_ACTION] == GW_A_ADD)
	{
		$strForm .= $form->field('hidden', 'arPost['.GW_ACTION.']', GW_A_ADD);
	}
	else
	{
		$strForm .= $form->field('hidden', 'arPost['.GW_ACTION.']', GW_A_UPDATE);
	}
	return $form->Output($strForm);
}
// --------------------------------------------------------
// Language
$oL->getCustom('export', $gw_this['vars'][GW_LANG_I].'-'.$gw_this['vars']['lang_enc'], 'join');
// --------------------------------------------------------
// Prepare variables
if ($arPost == '') { $arPost = array(); }
if (!isset($arPost[GW_ACTION])) { $arPost[GW_ACTION] = GW_A_ADD; }
//
$is_idadd   = isset($arPost['is_idadd']) ? $arPost['is_idadd'] : 0;
$is_idupdate= isset($arPost['is_idupdate']) ? $arPost['is_idupdate'] : 1;
$is_struc   = isset($arPost['is_struc']) ? $arPost['is_struc'] : 1;
//
$arReq = array('date_min', 'date_max');
//
// --------------------------------------------------------
switch ($arPost[GW_ACTION])
{
case GW_A_ADD:
## --------------------------------------------------------
## Show HTML-form
	if ($post == '') // not saved
	{
		// get MAX and MIN date from terms
		$vars = getTermDates($id, $DBTABLE);
		$ar_formats = file_readDirD($sys['path_include'] . '/', "/^export_/");
		$vars['arFmt'] = str_replace('export_', '', $ar_formats);
		$vars['arFmt'] = str_replace('_', ' ', $vars['arFmt']);
		// set default export option
		$vars['fmt_default'] = 'XML';
		/* Adjust time */
		$vars['min'] = $vars['min'] + ($oSess->user_get('gmt_offset') * 3600);
		$vars['max'] = $vars['max'] + ($oSess->user_get('gmt_offset') * 3600);
		$strR .= getFormExport($vars, 0, 0, $arReq);
	}
	else
	{
		if (!isset($arPost['fmt']))
		{
			$ar_formats = file_readDirD($sys['path_include'] . '/', "/^export_/");
			$tmp['ar_min_his'] = explode(':', $arPost['date_minS']);
			$tmp['ar_max_his'] = explode(':', $arPost['date_maxS']);
			/* hour, minute, second, month, day, year  */
			$vars['min']  = @mktime($tmp['ar_min_his'][0], $tmp['ar_min_his'][1], $tmp['ar_min_his'][2],
								$arPost['date_minM'], $arPost['date_minD'], $arPost['date_minY']);
			$vars['max']  = @mktime($tmp['ar_max_his'][0], $tmp['ar_max_his'][1], $tmp['ar_max_his'][2],
								$arPost['date_maxM'], $arPost['date_maxD'], $arPost['date_maxY']);
			$vars['arFmt'] = str_replace('export_', '', $ar_formats);
			$vars['fmt_default'] = 'XML';
			$strR .= getFormExport($vars, 0, 0, $arReq);
		}
		else
		{
			/* Increase time limit */
			@set_time_limit(3600);
			$pathExportFmt = $sys['path_include'].'/'. $arPost['fmt']. '/' . 'index.inc.php';
			file_exists($pathExportFmt)
				? include_once($pathExportFmt)
				: printf($oL->m('reason_10'), $pathExportFmt);
		}
	}
##
## --------------------------------------------------------
break;
default:
break;
}

/* end of file */
?>