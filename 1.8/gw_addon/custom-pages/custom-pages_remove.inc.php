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
	die('<!-- $Id: custom-pages_remove.inc.php 395 2008-04-06 10:20:18Z yrtimd $ -->');
}
/* Included from $oAddonAdm->alpha(); */

$ar_q = array();
if (!$this->gw_this['vars']['isConfirm'])
{
	/* Should be confirmed */
	return;
}
/* Enter debug mode */
#$this->sys['isDebugQ'] = 1;

/* Check permission for the page */
$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-custompages-adm', $this->gw_this['vars']['tid']));
$arParsed = isset($arSql[0]) ? $arSql[0] : array('id_user' => -1);
$is_allow_edit = ($this->oSess->is('is-cpages') ? 1 : ($this->oSess->is('is-cpages-own') && ($arParsed['id_user'] == $this->oSess->id_user)) ? 1 : 0);
if (!$is_allow_edit)
{
	$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
	return;
}

/* Read the three */
$arKeys = ctlgGetTree($this->ar, $this->gw_this['vars']['tid']);
/* */
$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'pages` WHERE `id_page` IN ('. implode(', ', $arKeys).')';
$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'pages_phrase` WHERE `id_page` IN ('. implode(', ', $arKeys).')';
$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);
/* end of file */
?>