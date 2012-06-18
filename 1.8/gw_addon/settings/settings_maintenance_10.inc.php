<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: settings_maintenance_10.inc.php 515 2008-07-07 00:28:18Z glossword_team $ -->');
}
/**
 *  Changing Dictionary ID
 */

/* Included from $oAddonAdm->alpha(); */




/* Script variables below */
/* Script functions below */
function gw_show_form($vars, $runtime = 0, $arBroken = array(), $arReq = array())
{
	global $oL, $sys, $oFunc, $oSess, $gw_this, $ar_theme;

	$oForm = new gwForms();
	$oForm->Set('action', $sys['page_admin']);
	$oForm->Set('submitok', $oL->m('2_continue'));
	$oForm->Set('submitcancel', $oL->m('3_cancel'));
	$oForm->Set('formbgcolor', $ar_theme['color_2']);
	$oForm->Set('formbordercolor', $ar_theme['color_4']);
	$oForm->Set('formbordercolorL', $ar_theme['color_1']);
	$oForm->Set('align_buttons', $sys['css_align_right']);
	$oForm->Set('charset', $sys['internal_encoding']);
	
	## ----------------------------------------------------
	##
	// reverse array keys <-- values;
	$arReq = array_flip($arReq);
	// mark fields as "REQUIRED" and make error messages
	while (is_array($vars) && list($key, $val) = each($vars) )
	{
		$arReqMsg[$key] = $arBrokenMsg[$key] = '';
		if (isset($arReq[$key])) { $arReqMsg[$key] = '&#160;<span class="red"><strong>*</strong></span>'; }
		if (isset($arBroken[$key])) { $arBrokenMsg[$key] = ' <span class="red"><strong>' . $oL->m('reason_9') .'</strong></span><br/>'; }
	}
	##
	## ----------------------------------------------------

	$strForm = '';
	$strForm .=  getFormTitleNav( $oL->m(1363), '<span style="float:right">'.$oForm->get_button('submit').'</span>' );
	$strForm .= '<fieldset class="admform"><legend class="xq">&#160;</legend>';
	$strForm .= '<table class="gw2TableFieldset" width="100%">';
	$strForm .= '<tbody>';
	$strForm .= '<tr><td style="width:25%"></td><td></td></tr>';

	$arDictMap = array();
	for (reset($gw_this['ar_dict_list']); list($arK, $arV) = each($gw_this['ar_dict_list']);)
	{
		$arDictMap[$arV['id']] = '[ID: ' .$arV['id'].'] ' . strip_tags($arV['title']);
	}
	asort($arDictMap);
#	$oForm->setTag('select', 'style', 'font-size:150%;width:100%');
#	$oForm->setTag('select', 'size', sizeof($arDictMap));
	/* Auto-select the first dictionary */
	$ark = array_keys($arDictMap);

	$strForm .= '<tr>';
	$strForm .= '<td></td><td class="td2"><p class="div">'.$oL->m(1084).':</p>';
	$oForm->setTag('select', 'style', 'width:98%');
	$oForm->setTag('select', 'class', 'input');
	$strForm .= $arBrokenMsg['id_old'] . $oForm->field('select', 'arPost[id_old]', 0, 0, $arDictMap);
	$strForm .= '</td>';
	$strForm .= '</tr>';

	$oForm->setTag('input', 'style', '');
	$oForm->setTag('input', 'size', '4');
	$oForm->setTag('input', 'class', 'input0');
	$oForm->setTag('input', 'maxlength', '4');
	$oForm->setTag('input', 'dir', $sys['css_dir_numbers'] );
	$strForm .= '<tr>'.
				'<td class="td1">' . $arBrokenMsg['id_new'] . $oForm->field('input', 'arPost[id_new]', $vars['id_new']) . '</td>'.
				'<td class="td2">' . $oL->m(1364) . '<br />' . $oL->m(1300) . $arReqMsg['id_new'] . '</td>'.
				'</tr>';
	$strForm .= '</tbody></table>';
	$strForm .= '</fieldset>';

	$strForm .= $oForm->field('hidden', GW_ACTION, $gw_this['vars'][GW_ACTION]);
	$strForm .= $oForm->field('hidden', GW_TARGET, $gw_this['vars'][GW_TARGET]);
	$strForm .= $oForm->field('hidden', 'w1', $gw_this['vars']['w1']);
	$strForm .= $oForm->field('hidden', $oSess->sid, $oSess->id_sess);

	return $oForm->Output($strForm);
}
/* */
function gw_do_task($id_old, $id_new, $is_visible = 1)
{
	global $oFunc, $sys, $oL, $oDb, $gw_this;
	$arStatus = array();
	/* Should be `id_dict` everywhere... */
	$arQ = array();
	$arQ[] = 'UPDATE `'.$sys['tbl_prefix'].'wordmap` SET dict_id = "'.$id_new.'" WHERE dict_id = "'.$id_old.'"';
	$arQ[] = 'UPDATE `'.$sys['tbl_prefix'].'dict` SET id = "'.$id_new.'" WHERE id = "'.$id_old.'"';
	$arQ[] = 'UPDATE `'.$sys['tbl_prefix'].'history_terms` SET id_dict = "'.$id_new.'" WHERE id_dict = "'.$id_old.'"';
	$arQ[] = 'UPDATE `'.$sys['tbl_prefix'].'map_user_to_term` SET dict_id = "'.$id_new.'" WHERE dict_id = "'.$id_old.'"';
	$arQ[] = 'UPDATE `'.$sys['tbl_prefix'].'dict` SET id = "'.$id_new.'" WHERE id = "'.$id_old.'"';
	$arQ[] = 'UPDATE `'.$sys['tbl_prefix'].'stat_dict` SET id = "'.$id_new.'" WHERE id = "'.$id_old.'"';
	$arQ[] = 'UPDATE `'.$sys['tbl_prefix'].'abbr` SET id_dict = "'.$id_new.'" WHERE id_dict = "'.$id_old.'"';

	if ($is_visible)
	{
		$arQ[] = 'DELETE FROM `'.$sys['tbl_prefix'].'stat_search` WHERE id_dict = "'.$id_old.'"';
		$arQ[] = 'DELETE FROM `'.$sys['tbl_prefix'].'search_results` WHERE id_d = "'.$id_old.'"';
	}
	
	/* Run queries */
	if ($sys['isDebugQ'])
	{
		$arStatus[] = array('', '<ul class="gwsql"><li>' . implode(';</li><li>', $arQ). ';</li></ul>');
	}
	else
	{
		/* */
		for (; list($sqlk, $q) = each($arQ);)
		{
			$oDb->sqlExec($q);
		}
	}
	if ($is_visible)
	{
		$arStatus[] = array('', $oL->m('2_success'));
	}
	return $arStatus;
}
/* Script action below */

$ar_req_fields = array('id_new', 'id_old');

if ($this->gw_this['vars']['post'] == '')
{
	$vars['id_new'] = $vars['id_old'] = 1;
	/* Default settings */
	if (!empty($this->gw_this['ar_dict_list']))
	{
		$vars['id_new'] = max(array_keys($this->gw_this['ar_dict_list'])) + 1;
	}
	/* Not submitted */
	$this->str .= gw_show_form($vars, 0, 0, $ar_req_fields);
}
else
{
	/* Do not run SQL-queries */
$this->sys['isDebugQ'] = 0;
	/* */
	$arPost =& $this->gw_this['vars']['arPost'];
	
	/* Checking posted vars */
	$arPost['id_new'] = preg_replace("/([^0-9])+/", '', $arPost['id_new']);
	$arPost['id_new'] = trim($arPost['id_new']);
	
	$errorStr = '';
	$ar_broken = validatePostWalk($arPost, $ar_req_fields);
	/* Check for old ID */
	if (!isset($arPost['id_old']))
	{
		$arPost['id_old'] = 1;
		$ar_broken['id_old'] = 1;
	}
	/* Check for new ID */
	if ($arPost['id_new'] == $arPost['id_old'] 
		|| isset($this->gw_this['ar_dict_list'][$arPost['id_new']])
		|| $arPost['id_new'] == 0
	)
	{
		$ar_broken['id_new'] = 1;
	}

	if (empty($ar_broken))
	{
		$this->str .= getFormTitleNav($this->oL->m(1363));
		$this->str .= '<div class="contents xu">';
		/* 4 => 12344, 3 => 4, 12344 => 4 */
		$this->str .= html_array_to_table_multi( gw_do_task($arPost['id_new'], 12344, 0), 0 );
		$this->str .= html_array_to_table_multi( gw_do_task($arPost['id_old'], $arPost['id_new']), 0 );
		$this->str .= html_array_to_table_multi( gw_do_task(12344, $arPost['id_new'], 0), 0 );
		$this->str .= '</div>';
		
	}
	else
	{
		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= gw_show_form($arPost, 1, $ar_broken, $ar_req_fields);
	}

}

/* end of file */
?>