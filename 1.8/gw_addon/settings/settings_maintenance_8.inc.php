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
	die('<!-- $Id: settings_maintenance_8.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
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


	/* Recommented tasks */	
	$arStatus[] = array('<br />'.$oL->m('see').':', '');
	$arStatus[] = array(sprintf('<a href="%s">%s</a>',
					append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&w1=2'),
					$oL->m('1002')
				), '');
	$arStatus[] = array(sprintf('<a href="%s">%s</a>',
					append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&w1=4'),
					$oL->m('1004')
				), '');
	$arStatus[] = array(sprintf('<a href="%s">%s</a>',
					append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&w1=9'),
					$oL->m('1305')
				), '');
	
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
	global $oFunc, $sys, $oL, $gw_this, $oDb;
	global $gw_cnt_dicts;
	$arStatus = array();
	$arTables = array();
	@set_time_limit(3600);
	
	/* */
	switch ($gw_this['vars']['w2'])
	{
		case 1:
			/* Clean-up the history of changes */
			$sql = 'DELETE FROM `'.$sys['tbl_prefix'].'history_terms` WHERE `is_active` = "3"';
			$oDb->sqlExec($sql);

			/* See also class.uninstall.php */
			$arTables = array('abbr', 'abbr_phrase', 'component', 'component_map', 'component_actions',
				'dict', 'pages', 'pages_phrase', 'search_results',
				'sessions', 'settings', 'stat_dict', 'stat_search', 'captcha',
				'topics', 'topics_phrase', 'users',
				'import_sessions', 'custom_az', 'custom_az_profiles', 'virtual_keyboard','auth_restore'
			);
			foreach ($arTables as $k => $tablename)
			{
				/* Add table prefix */
				$arTables[$k] = $sys['tbl_prefix'].$tablename;
			}
		break;
		case 2:
			$arDictTables = $arDictIds = array();
			$sql = 'SELECT * FROM `'.$sys['tbl_prefix'].'dict` ORDER BY id ASC';
			$arSql = $oDb->sqlExec($sql);

			/* create valid dictionary list */
			for (reset($arSql); list($arK, $arV) = each($arSql);)
			{
				if ($arV['id'] >= $gw_this['vars']['w3'])
				{
					$arDictTables[] = $arV['tablename'];
					$arDictIds[] = $arV['id'];
				}
			}
			if (empty($arSql)){ $arDictIds[0] = 0; }

			/* Set the next Dictionary Id */
			$gw_this['vars']['w3'] = isset($arDictIds[1]) ? $arDictIds[1] : ($arDictIds[0] + 1);
			$gw_cnt_dicts = sizeof($arDictTables);
			/* Select one dictionary table */
			if ($gw_cnt_dicts > 0)
			{
				$arTables[] = $arDictTables[0];
				--$gw_cnt_dicts;
			}
			$arDictTables = array();

			/* create a new list of not yet updated dictionaries  */
			for (reset($arSql); list($arK, $arV) = each($arSql);)
			{
				if ($arV['id'] >= $gw_this['vars']['w3'])
				{
					$arDictTables[] = $arV['tablename'];
				}
			}
			/* Back to one step */
			if ($gw_cnt_dicts > 0)
			{
				--$gw_this['vars']['w2'];
			}
			else
			{
				$gw_this['vars']['w3'] = '';
			}
		break;
		case 3:
			$gw_cnt_dicts = 0;
			$arTables = array($sys['tbl_prefix'].'map_user_to_dict', $sys['tbl_prefix'].'map_user_to_term');
		break;
		case 4:
			$gw_cnt_dicts = 0;
			$arTables = array($sys['tbl_prefix'].'theme_settings', $sys['tbl_prefix'].'theme', $sys['tbl_prefix'].'theme_group');
		break;
		case 5:
			$gw_cnt_dicts = 0;
			$arTables = array($sys['tbl_prefix'].'history_terms');
		break;
		case 6:
			$gw_cnt_dicts = 0;
			$arTables = array($sys['tbl_prefix'].'wordlist');
		break;
		case 7:
			$gw_cnt_dicts = 0;
			$arTables = array($sys['tbl_prefix'].'wordmap');
		break;
	}
	++$gw_this['vars']['w2'];
	/* */
	if ($gw_this['vars']['w2'] < 8)
	{
		$arStatus[] = array('', sprintf(
					'<span class="actions-third"><a href="%s">%s <span id="countdown"></span></a>',
					append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&w1='.$gw_this['vars']['w1'].'&w2='.$gw_this['vars']['w2'].'&w3='.$gw_this['vars']['w3']),
					$oL->m('1183')).'</span>'
					);
	}
	else
	{
		$arStatus[] = array('', $oL->m('2_success'));
	}
	/* Display the number of dictionaries */
	if ($gw_this['vars']['w2'] == 2 || $gw_this['vars']['w2'] == 3)
	{
		$arStatus[] = array('', $oL->m('1335').': <strong>' . ($gw_cnt_dicts) . '</strong>' );
	}
	/* */
	for (reset($arTables); list($k, $v) = each($arTables);)
	{
		$arStatus[] = array($v, gw_export_sqltable($v));
	}
	return $arStatus;
}
/* Script action below */
global $gw_cnt_dicts;
$int_pbar = 0;
$gw_cnt_dicts = sizeof($this->gw_this['ar_dict_list']);

$str_proc = '';
if ($this->gw_this['vars']['w2'])
{
	$str_proc .= html_array_to_table_multi(gw_backup(), 0);

	if ($this->gw_this['vars']['w2'] < 8)
	{
		$url_refresh = $this->sys['server_url'].'/'.$this->sys['file_admin'].'?'.GW_ACTION.'='.$this->gw_this['vars'][GW_ACTION].'&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET].'&w1='.$this->gw_this['vars']['w1'].'&w2='.$this->gw_this['vars']['w2'].'&w3='.$this->gw_this['vars']['w3'];
		$this->oTpl->addVal( 'v:meta_refresh', gethtml_metarefresh($url_refresh, $this->sys['time_refresh']) );
		/* countdown */
		$this->oTpl->addVal( 'v:javascripts', '
		<script type="text/javascript">
			var total_sec = '.($this->sys['time_refresh']).';
			function display_countdown() {
				document.getElementById("countdown").innerHTML = total_sec--;
				if (total_sec > -1) {
					setTimeout(\'display_countdown()\', 1000);
				}
			}
			display_countdown();
		</script>');
	}
}
else
{
	$str_proc .= html_array_to_table_multi(gw_show_form(), 0);
}
$int_steps_total = 8 + sizeof($this->gw_this['ar_dict_list']);
$int_step = $this->gw_this['vars']['w2'] + (sizeof($this->gw_this['ar_dict_list']) - $gw_cnt_dicts);
$int_pbar = intval( (100/$int_steps_total)*$int_step );
/* Set background color for progress bar */
$color_bg = ($int_pbar == 100) ? $this->ar_theme['color_4'] : $this->ar_theme['color_5'];

$this->str .= text_progressbar($int_pbar, $this->ar_theme['color_black'], $color_bg);
$this->str .= getFormTitleNav($this->oL->m('1266'));
$this->str .= '<div class="margin-inside xu">';
$this->str .= $str_proc;
$this->str .= '</div>';

/* end of file */
?>