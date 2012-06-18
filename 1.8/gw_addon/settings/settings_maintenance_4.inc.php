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
	die('<!-- $Id: settings_maintenance_4.inc.php 554 2008-08-17 17:47:08Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */



/* Script functions below */
function gw_get_sql_cache()
{
	global $oDb, $oFunc, $oL, $sys, $gw_this;

	if (($gw_this['vars']['isConfirm'] == '2') && $oDb->sqlExec(sprintf('DELETE FROM `%s`', $sys['tbl_prefix'].'search_results')))
	{
		$arStatus[] = array('<span class="green">'.$oL->m('2_success').'</span>');
	}

	$arTableInfo = $oDb->table_info($sys['tbl_prefix'].'search_results');
	$int_size = ($arTableInfo['Data_length'] > 1) ? $arTableInfo['Data_length']+$arTableInfo['Index_length'] : 0;
	$arStatus[] = array($oL->m('1020'), $oFunc->number_format($int_size, 0, $oL->languagelist('4')));
	$arStatus[] = array($oL->m('1021'), $oFunc->number_format($arTableInfo['Rows'], 0, $oL->languagelist('4')));

	/* Link to confirm */
	if (($int_size > 1) && ($gw_this['vars']['isConfirm'] != 2))
	{
		$arStatus[] = array(sprintf('<strong class="xw">%s</strong>', $oL->m('1023')), sprintf('<span class="actions-third"><a href="%s">%s</a></span>',
			append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&w1='.$gw_this['vars']['w1'].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&isConfirm=2'),
			$oL->m('1183')));
		$arStatus[] = array('&#160;');
	}
	return $arStatus;
}
/* */
function gw_get_file_cache()
{
	global $oFunc, $oL, $sys, $gw_this;

	$ar_tree = gw_parse_tree($sys['path_cache_sql'], ($gw_this['vars']['is'] == 1) ? 1 : 0);
	$arStatus = $ar_tree['names'];
	$arStatus[] = array($oL->m('1022'), $oFunc->number_format($ar_tree['bytes'], 0, $oL->languagelist('4')));
	$arStatus[] = array($oL->m('1021'), $oFunc->number_format($ar_tree['files'], 0, $oL->languagelist('4')));
	/* Link to confirm */
	if (($ar_tree['bytes'] > 1) && ($gw_this['vars']['isConfirm'] != '1'))
	{
		$arStatus[] = array(sprintf('<strong class="xw">%s</strong>', $oL->m('1023')), sprintf('<span class="actions-third"><a href="%s">%s</a></span>',
			append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&w1='.$gw_this['vars']['w1'].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&isConfirm=1'),
			$oL->m('1183')));
		$arStatus[] = array('&#160;');
	}
	return $arStatus;
}
/* */
function gw_parse_tree($path, $is_clean = 0)
{
	$ar = array('files' => 0, 'bytes' => 0, 'names' => array());
	if (!file_exists($path))
	{
		return $ar;
	}
	if (substr($path, -1) != '/')
	{
		$path = $path.'/';
	}
	$h_all = opendir($path);
	while (($filename = readdir($h_all)) !== false)
	{
		if ($filename != '.' && $filename != '..' && !is_dir($filename))
		{
			if ($is_clean)
			{
				$ar['names'][] = '<span class="green">'.$path.$filename.'</span>';
				unlink($path.$filename);
			}
			else
			{
				$ar['files']++;
				$ar['bytes'] += filesize($path.$filename);
			}
		}
	}
	return $ar;
}
/* Script action below */
$this->str .= getFormTitleNav( $this->oL->m(1004) );
$arStatus = array();
$arStatus = array_merge( $arStatus, gw_get_sql_cache() );
$arStatus = array_merge( $arStatus, gw_get_file_cache() );

$this->str .= '<div class="margin-inside xu">';
$this->str .= html_array_to_table_multi($arStatus, 0);
$this->str .= '</div>';

?>