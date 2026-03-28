<?php
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
if (!defined('IN_GW'))
{
	die('<!-- $Id: settings_maintenance_9.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */




/* Script variables below */
/* Script functions below */
function gw_show_form()
{
	global $oFunc, $sys, $oL, $gw_this;
	$arStatus = array();
	$arStatus[] = array(sprintf('<strong class="xw">%s</strong>', $oL->m('9_remove')),
					sprintf(
					'<span class="actions-third"><a href="%s">%s</a></span>',
					append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&w1='.$gw_this['vars']['w1'].'&w2=1'),
					$oL->m('1183')
				));
	return $arStatus;
}
/* */
function gw_clear_history()
{
	global $oFunc, $sys, $oL, $oDb, $gw_this;
	$arStatus = array();
	$arTables = array();
	@set_time_limit(3600);
	/* Do not clear the histroy of changes */
#$sys['isDebugQ'] = 1;
	$arQ = array();
	$ar_dict_ids = array_keys($gw_this['ar_dict_list']);
	$sql = $sql_dltd = $arSql = array();
	/* Step 1: Remove terms which are scheduled for removing */
	if (!empty($ar_dict_ids))
	{
		$arQ[] = 'DELETE FROM `'.$sys['tbl_prefix'].'history_terms` WHERE `is_active` = "3"';
		$sql_dltd = 'SELECT `id_dict`, `id_term` FROM `'.$sys['tbl_prefix'].'history_terms` WHERE `is_active` = "3" GROUP BY `id_term`';
	}
	if ($sql_dltd)
	{
		$arSql = $oDb->sqlExec($sql_dltd);
	}
	if (!empty($arSql))
	{
		for (; list($k, $v) = each($arSql);)
		{
			$ar_term_ids_dltd[$v['id_dict']][] = $v['id_term'];
		}
		for (reset($ar_term_ids_dltd); list($id_dict, $v) = each($ar_term_ids_dltd);)
		{
			$arQ[] = 'DELETE FROM `'.$gw_this['ar_dict_list'][$id_dict]['tablename'].'` WHERE `id` IN ('.implode(',', $v).')';
			$arStatus[] = array( $gw_this['ar_dict_list'][$id_dict]['title'], sizeof($v) );
		}
    }
    /* Step 2: Remove terms without history of changes */
	for (reset($gw_this['ar_dict_list']); list($id_dict, $v) = each($gw_this['ar_dict_list']);)
	{
		$arQ[] = 'DELETE FROM `'.$v['tablename'].'` WHERE `is_active` = "3"';
	}
	/* Step 3: Shrink the history to one entry */
	$sql_one = 'SELECT `id`, `id_dict`, `id_term` FROM `'.$sys['tbl_prefix'].'history_terms` WHERE `is_active` != "3" ORDER BY `date_modified` DESC';
	$arSql = $oDb->sqlExec($sql_one);
	$ar_term_ids = array();
	for (; list($k, $v) = each($arSql);)
	{
		if (!isset($ar_term_ids[$v['id_dict']][$v['id_term']]))
		{
			$ar_term_ids[$v['id_dict']][$v['id_term']] = $v['id'];
		}
	}
	for (reset($ar_term_ids); list($id_dict, $v) = each($ar_term_ids);)
	{
		$arQ[] = 'DELETE FROM `'.$sys['tbl_prefix'].'history_terms` WHERE `id` NOT IN ('.implode(',', $v).')';
	}
	/* Run queries */
	if ($sys['isDebugQ'])
	{
		$arStatus[] = array('', '<ul class="gwsql"><li>' . implode(';</li><li>', $arQ). ';</li></ul>');
	}
	else
	{
		/* */
		$is_error = 0;
		for (; list($sqlk, $sqlv) = each($arQ);)
		{
			if (!$oDb->sqlExec($sqlv))
			{
				$is_error = 1;
			}
		}
	}
	if (!$is_error)
	{
		$arStatus[] = array('', $oL->m('2_success'));
	}
	return $arStatus;
}
/* Script action below */
$this->str .= getFormTitleNav($this->oL->m('1305'));
$this->str .= '<div class="margin-inside xu">';
if ($this->gw_this['vars']['w2'])
{
	$this->str .= html_array_to_table_multi(gw_clear_history(), 0);
}
else
{
	$this->str .= html_array_to_table_multi(gw_show_form(), 0);
}
$this->str .= '</div>';
/* end of file */
?>