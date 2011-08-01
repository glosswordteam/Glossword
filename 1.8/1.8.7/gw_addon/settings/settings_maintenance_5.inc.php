<?php
/**
 * Glossword - glossary compiler (http://glossword.info/)
 * © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id$ -->');
}
/* Included from $oAddonAdm->alpha(); */


/* Script variables below */


/* Script functions below */
function gw_dict_list_cnt($vars)
{
	global $oL, $sys, $tid, $oFunc, $oSess, $gw_this, $ar_theme;

	$oForm = new gwForms();
	$oForm->Set('action', $sys['page_admin']);
	$oForm->Set('submitok', $oL->m('2_continue'));
	$oForm->Set('submitcancel', $oL->m('3_cancel'));
	$oForm->Set('formbgcolor', $ar_theme['color_2']);
	$oForm->Set('formbordercolor', $ar_theme['color_4']);
	$oForm->Set('formbordercolorL', $ar_theme['color_1']);
	$oForm->Set('align_buttons', $sys['css_align_right']);
	$oForm->Set('charset', $sys['internal_encoding']);

	$strForm = '';
	$strForm .= '<table class="gw2TableFieldset" width="100%">';
	$strForm .= '<tbody>';
	$strForm .= '<tr><td style="width:1%"></td><td></td></tr>';
	$strForm .= '<tr>';
	$strForm .= '<td></td><td class="td2"><p class="div">'.$oL->m(1125).'<br />'.$oL->m(1126).'</p>';
	$arDictMap = array();
	for (reset($gw_this['ar_dict_list']); list($arK, $arV) = each($gw_this['ar_dict_list']);)
	{
		$arDictMap[$arV['id']] = strip_tags($arV['title']). ' (' .$arV['int_terms'].')';
	}
	asort($arDictMap);
	$oForm->setTag('select', 'style', 'font-size:110%;width:100%');
	$oForm->setTag('select', 'multiple', 'multiple');
	$oForm->setTag('select', 'size', sizeof($arDictMap));
	/* Auto-select the first dictionary */
	$ark = array_keys($arDictMap);
	$strForm .= $oForm->field('select', 'arPost[id_source][]', $ark[0], 0, $arDictMap);
	$strForm .= '</td>';
	$strForm .= '</tr>';
	$strForm .= '<tr>';
	$strForm .= '<td></td><td class="td2"><p class="div">'.$oL->m(1124).':</p>';
	$oForm->setTag('select', 'multiple', '');
	$oForm->setTag('select', 'size', '');
	$strForm .= $oForm->field('select', 'arPost[id_target]', 0, 0, $arDictMap);
	$strForm .= '</td>';
	$strForm .= '</tr>';
	$strForm .= '<tr>';
	$strForm .= '<td class="td1">';
	$strForm .= $oForm->field('checkbox', 'arPost[is_empty]', 0, 1);
	$strForm .= '</td>';
	$strForm .= '<td class="td2">';
	$strForm .= '<label for="arPost_is_empty_">'.$oL->m(1127).'</label>';

	$strForm .= $oForm->field('hidden', GW_ACTION, $gw_this['vars'][GW_ACTION]);
	$strForm .= $oForm->field('hidden', 'tid', $gw_this['vars']['tid']);
	$strForm .= $oForm->field('hidden', GW_TARGET, $gw_this['vars'][GW_TARGET]);
	$strForm .= $oForm->field('hidden', $oSess->sid, $oSess->id_sess);
	$strForm .= $oForm->field('hidden', 'isConfirm', 1);
	/* */
	$strForm .= '</td></tr>';
	$strForm .= '</tbody></table>';
	return $oForm->Output($strForm);
}
/* */
function gw_dict_merge($vars)
{
	global $gw_this, $oDb, $oL;
	$str = '<div class="xu">';
	$arDictParam_target = getDictParam($vars['id_target']);
	$id_term = $oDb->MaxId($arDictParam_target['tablename'], 'id');
	$int_terms = 0;
	for (reset($vars['id_source']); list($k, $id_dict) = each($vars['id_source']);)
	{
		/* Go for each dictionary
			1. Add term to Target dictionary
			2. Update gw_wordmap
			3. Update gw_map_user_to_term
			4. Remove term from Source if needed.
		*/
		if ($id_dict == $arDictParam_target['id'])
		{
			continue;
		}
		$arDictParam = getDictParam($id_dict);
		$str .= '<br />'.$arDictParam['title'].'...';
		$sql = 'SELECT * FROM `'.$arDictParam['tablename'].'`';
		$arSql = $oDb->sqlExec($sql);
		$q1['dict_id'] = $arDictParam_target['id'];
		for (reset($arSql); list($arK, $arV) = each($arSql);)
		{
			$arQ = array();
			$id_term_prev = $arV['id'];
			$arV['id'] = $id_term;
			$q1['term_id'] = $arV['id'];
			$arQ[] = gw_sql_insert($arV, $arDictParam_target['tablename']);
			$arQ[] = gw_sql_update($q1, TBL_WORDMAP, sprintf('`term_id` = "%d" AND `dict_id` = "%d"', $id_term_prev, $id_dict));
			$arQ[] = gw_sql_update($q1, TBL_MAP_USER_TERM, sprintf('`term_id` = "%d" AND `dict_id` = "%d"', $id_term_prev, $id_dict));
			for (reset($arQ); list($kQ, $vQ) = each($arQ);)
			{
				$oDb->sqlExec($vQ);
			}
			$id_term++;
			$int_terms++;
		}
		if ($vars['is_empty'])
		{
			$sql = 'DELETE FROM `'.$arDictParam['tablename'].'`';
			$oDb->sqlExec($sql);
		}
		$str .= ' <span class="green">OK</span>';
	}
	$str .= '<br /><p>'.$arDictParam_target['title'].'</p>';
	$str .= '<p>'.$oL->m('str_on_page').': <strong>'.$int_terms.'</strong></p>';
	$str .= '</div>';
	return $str;
}
/* Script action below */


$arPost =& $this->gw_this['vars']['arPost'];

$arPost['is_empty'] = isset($arPost['is_empty']) ? 1 : 0;

$this->str .= getFormTitleNav( $this->oL->m(1005) );

if ($this->gw_this['vars']['isConfirm'] == '1')
{
	$this->str .= gw_dict_merge($arPost);
}
else
{
	/* Get the list of dictionaries to recount */
	$this->str .= gw_dict_list_cnt($arPost);
}
/* end of file */
?>