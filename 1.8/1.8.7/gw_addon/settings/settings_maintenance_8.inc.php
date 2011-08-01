<?php
/**
 * Glossword - glossary compiler (http://glossword.info/)
 * This program is free software; you can redistribute it and/or modify
 * © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
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

function gw_show_form()
{
	global $oFunc, $sys, $oL, $gw_this;
	$arStatus = array();
	$arStatus[] = array(sprintf('<strong>%s</strong>', $oL->m('9_remove')),
					sprintf(
					'<a href="%s">%s</a>',
					append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&tid='.$gw_this['vars']['tid'].'&w1=1'),
					$oL->m('1185')
				));
	return $arStatus;
}
/* */
function gw_export_sqltable($tablename)
{
	global $sys, $oDb, $oFunc, $oL;
	$filename = $sys['path_export'] . '/sql_backup_' . @date("Y-mM-d") .'/backup_' . $tablename . '.sql';
	$strQ = '';
	$strQ .= 'SET NAMES \'utf8\';' . CRLF;
	$strQ .= 'DROP TABLE IF EXISTS `' . $tablename . '`;' . CRLF;
	$sql = 'SHOW CREATE TABLE `' . $tablename . '`';
	$arSql = $oDb->sqlExec($sql);
	$strQ .= $arSql[0]['Create Table'].';'.CRLF;
	$q_mysql = 'SQL_BIG_RESULT';
	$sql = 'SELECT '.$q_mysql.' * FROM `' . $tablename . '`';
	$arSql = $oDb->sqlExec($sql);
	/* */
	for (; list($arK, $arV) = each($arSql);)
	{
		for (reset($arV); list($kV, $vV) = each($arV);)
		{
		if (($arV[$kV] != '') && preg_match('/(word_|abbr_|settings|code|topic|page_|character|term|defn)/', $kV))
			{
				$arV[$kV] = '0x' . bin2hex($arV[$kV]);
			}
		}
		$strQ .= gw_sql_insert($arV, $tablename, 0) . ';';
	}
	$isWrite = $oFunc->file_put_contents( $filename, $strQ, 'w');
	return ( $isWrite ?  '<span class="xt"><span class="gray">'. $filename . '</span><br />' . $oFunc->number_format(strlen($strQ), 0, $oL->languagelist(4)) . ' ' . $oL->m('bytes'): $oL->m('error') ).'</span>';
}
/* */
function gw_backup()
{
	global $oFunc, $sys, $oL, $gw_this;
	$arStatus = array();
	$arTables = array();
	@set_time_limit(3600);
	switch ($gw_this['vars']['w1'])
	{
		case 1:
			/* See also settings_maintenance_8.inc.php */
			$arTables = array('abbr', 'abbr_phrase', 'component', 'component_map', 'component_actions',
				'dict', 'map_user_to_dict', 'map_user_to_term', 'pages', 'pages_phrase', 'search_results',
				'sessions', 'settings', 'stat_dict', 'stat_search', 'theme', 'theme_group', 'captcha',
				'theme_settings', 'topics', 'topics_phrase', 'users', 'wordlist', 'wordmap',
				'history_terms', 'custom_az', 'custom_az_profiles', 'virtual_keyboard','auth_restore'
			);
			foreach ($arTables as $k => $tablename)
			{
				/* Add table prefix */
				$arTables[$k] = $sys['tbl_prefix'].$tablename;
			}
		break;
		case 2:
			foreach ($gw_this['ar_dict_list'] as $arDictParam)
			{
				$arTables[] = $arDictParam['tablename'];
			}
		break;
		case 3:
			$arTables = array($sys['tbl_prefix'].'wordlist');
		break;
		case 4:
			$arTables = array($sys['tbl_prefix'].'wordmap');
		break;
	}
	$gw_this['vars']['w1']++;
	/* */
	for (reset($arTables); list($k, $v) = each($arTables);)
	{
		$arStatus[] = array($v, gw_export_sqltable($v));
	}
	/* */
	if ($gw_this['vars']['w1'] < 5)
	{
		$arStatus[] = array('', sprintf(
					'<a href="%s">%s</a>',
					append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&tid='.$gw_this['vars']['tid'].'&w1='.$gw_this['vars']['w1']),
					$oL->m('1185')
				));
	}
	else
	{
		$arStatus[] = array('', $oL->m('2_success'));
	}
	return $arStatus;
}
/* Script action below */
$this->str .= getFormTitleNav($this->oL->m('1266'));
$this->str .= '<div class="contents xu">';
if ($this->gw_this['vars']['w1'])
{
	$this->str .= html_array_to_table_multi(gw_backup(), 0);
}
else
{
	$this->str .= html_array_to_table_multi(gw_show_form(), 0);
}
	$strR .= '</div>';
/* end of file */
?>