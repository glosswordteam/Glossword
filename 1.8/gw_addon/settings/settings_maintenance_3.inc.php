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
	die('<!-- $Id: settings_maintenance_3.inc.php 490 2008-06-13 02:12:52Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */


/* Script functions below */
function gw_dict_list_cnt($vars)
{
	global $oL, $sys, $oFunc, $gw_this, $ar_theme, $oSess;

	$oForm = new gwForms();
	$oForm->Set('action', $sys['page_admin']);
	$oForm->Set('submitok', $oL->m('2_continue'));
	$oForm->Set('submitcancel', $oL->m('3_cancel'));
	$oForm->Set('formbgcolor', $ar_theme['color_2']);
	$oForm->Set('formbordercolor', $ar_theme['color_4']);
	$oForm->Set('formbordercolorL', $ar_theme['color_1']);
	$oForm->Set('align_buttons', $sys['css_align_right']);
	$oForm->Set('charset', $sys['internal_encoding']);

	$trClass = '';
	$strForm = '';

	$strForm .= '<table style="text-align:'.$sys['css_align_left'].'" cellspacing="3" cellpadding="0" border="0" width="100%">';
	$strForm .= '<tbody><tr>'.
				'<td>';
	$strForm .= '<span class="xt gray"><a href="#" onclick="setCheckboxes(true); return false;">'.$oL->m('select_on').'</a>'.
				' &#8226; '.
				'<a href="#" onclick="setCheckboxes(false); return false;">'.$oL->m('select_off').'</a></span>';
	$strForm .= '<table class="xt" cellspacing="1" cellpadding="0" border="0" width="100%">';
	$strForm .= '<tbody><tr><td style="width:1%"></td><td style="width:9%"></td><td style="width:90%"></td></tr>';

	$ar_dict_ids = array();

	/* Per each dictionary */
	for (reset($gw_this['ar_dict_list']); list($id_dict, $arDictParam) = each($gw_this['ar_dict_list']);)
	{
		$arDictParam = getDictParam($id_dict);
		$ar_dict_ids[] = $arDictParam['id'];
		$is_assigned = 0;
		/* $vars['dictionaries'] is flipped */
		if (isset($vars['dictionaries'][$arDictParam['id']]))
		{
			$is_assigned = 1;
		}
		$str_external_link = $arDictParam['is_active'] ? '<a href="'.$sys['page_index'].'?a=list&amp;d='. $arDictParam['uri'] .'" onclick="window.open(this.href);return false;">&gt;&gt;&gt;</a> ' : '';
		$strForm .= '<tr>'.
					'<td>' .
					$oForm->field('checkbox', 'arPost[dictionaries]['. $arDictParam['id'] . ']', $is_assigned) .
					'</td><td>'.
					$oFunc->number_format($arDictParam['int_terms'], 0, $oL->languagelist('4')).
					'</td><td class="td2 actions-third">'.
					$str_external_link .
					'<label for="arPost_dictionaries_' . $arDictParam['id'] . '_">'.
					$arDictParam['title'] .
					'</label></td>'.
					'</tr>';
	}
	$strForm .= $oForm->field('hidden', $oSess->sid, $oSess->id_sess);
	$strForm .= $oForm->field('hidden', GW_ACTION, $gw_this['vars'][GW_ACTION]);
	$strForm .= $oForm->field('hidden', GW_TARGET, $gw_this['vars'][GW_TARGET]);
	$strForm .= $oForm->field('hidden', 'tid', $gw_this['vars']['tid']);
	$strForm .= $oForm->field('hidden', 'w1', $gw_this['vars']['w1']);
	$strForm .= $oForm->field('hidden', 'isConfirm', 1);
	$strForm .= '</tbody></table>';
	/* Check/Uncheck All */
	$strForm .= '<script type="text/javascript">/*<![CDATA[*/';
	$strForm .= '
		function setCheckboxes(is_check) {
			str = "";
			ardict = [' . implode(',', $ar_dict_ids) . '];
			for (i = 0; i < ardict.length; i++) {
				gw_getElementById("arPost_dictionaries_" + ardict[i] + "_").checked = is_check;
			}
		}
	';
	$strForm .= '/*]]>*/</script>';
	/* */
	$strForm .= '</td></tr>';
	$strForm .= '</tbody></table>';
	return $oForm->Output($strForm);
}
/* */
function gw_dict_recount($vars)
{
	global $gw_this, $oDb;
	if (empty($vars))
	{
		return;
	}
	$str = '';
	$str .= '<ul class="xt">';
	/* Per each dictionary */
	for (; list($id_dict, $v) = each($vars['dictionaries']);)
	{
		$arQ = array();
		global $arDictParam;
		$arDictParam = getDictParam($id_dict);
		if (!isset($arDictParam['tablename']))
		{
			continue;
		}
		$qDict['int_terms'] = gw_sys_dict_count_terms();
		$qDict['int_bytes'] = gw_sys_dict_count_kb();
		$arQ[] = gw_sql_update($qDict, TBL_DICT, "id = '".$arDictParam['id']."'");

		$arQ[] = 'CHECK TABLE `' . $arDictParam['tablename'] .'`';
		$arQ[] = 'ALTER TABLE `'. $arDictParam['tablename'] .'` PACK_KEYS=1 CHECKSUM=0 DELAY_KEY_WRITE=1';
		$arQ[] = 'OPTIMIZE TABLE `'. $arDictParam['tablename'] .'`';
		/* */
		for (; list($sqlk, $sqlv) = each($arQ);)
		{
			$oDb->sqlExec($sqlv);
		}
		$str .= '<li><span class="green"><strong>'.$qDict['int_terms']. '</strong></span> ' . $arDictParam['title'] .'</li>';
	}
	$str .= '</ul>';
	return $str;
}
/* Script action below */

$arPost =& $this->gw_this['vars']['arPost'];

$this->str .= getFormTitleNav($this->oL->m(1003));
if ($this->gw_this['vars']['isConfirm'] == '1')
{
	$this->str .= gw_dict_recount($arPost);
}
else
{
	/* Check all dictionaries by default */
	for (reset($this->gw_this['ar_dict_list']); list($k, $arDictParam) = each($this->gw_this['ar_dict_list']);)
	{
		$arPost['dictionaries'][$arDictParam['id']] = 1;
	}
}
/* Get the list of dictionaries to recount */
$this->str .= gw_dict_list_cnt($arPost);

?>