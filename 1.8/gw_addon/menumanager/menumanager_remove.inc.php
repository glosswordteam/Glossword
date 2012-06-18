<?php
/**
 *  Glossword - glossary compiler (http://glossword.info/)
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
	die('<!-- $Id: menumanager_remove.inc.php 491 2008-06-13 10:05:06Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

$ar_q = array();
if (!$this->gw_this['vars']['isConfirm'])
{
	/* Should be confirmed */
	return;
}
/* */
$ar_query = array();
switch ($this->gw_this['vars']['w1'])
{
	case 'primary':
		$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'component` WHERE `id_component` = "'.$this->gw_this['vars']['tid'].'"';
		$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'component_map` WHERE `id_component` = "'.$this->gw_this['vars']['tid'].'"';
	break;
	case 'secondary':
		$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'component_map` WHERE `id` = "'.$this->gw_this['vars']['tid'].'"';
	break;
}
/* Run queries now */
foreach ($ar_query as $q)
{
	$this->oDb->sqlExec($q);
}
$ar_query = array();
/* Check for empty actions */
/*
2 SELECTs:
SELECT cma.id_action
FROM `gw_component_actions` as cma
WHERE cma.id_action NOT IN (SELECT id_action FROM `gw_component_map`)
*/
$sql = 'SELECT cma.id_action 
		FROM `'.$this->sys['tbl_prefix'].'component_actions` as cma
		LEFT JOIN `'.$this->sys['tbl_prefix'].'component_map` AS cmm
		ON cmm.id_action = cma.id_action
		WHERE cmm.id_action IS NULL';
$arSql = $this->oDb->sqlExec($sql);
$ar_ids = array();
foreach ($arSql as $arV)
{
	$ar_ids[] = $arV['id_action'];
}
if (!empty($ar_ids))
{
	$sql = 'DELETE FROM `'.$this->sys['tbl_prefix'].'component_actions` WHERE `id_action` IN ('. implode(',', $ar_ids) .')';
	$this->oDb->sqlExec($sql);
}
$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);


/* end of file */
?>