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
	die('<!-- $Id: dicts_remove.inc.php 404 2008-04-07 11:37:59Z yrtimd $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

if (!$this->gw_this['vars']['isConfirm'])
{
	/* Should be confirmed */
	return;
}

/* The list of allowed dictionaries */
$ar_allowed_dicts = $this->oSess->user_get('dictionaries');

/* Check for permission */
$is_allow_dict = 0;
if ( $this->oSess->is('is-sys-settings')
		|| $this->oSess->is('is-dicts')
		|| (isset($ar_allowed_dicts[$this->gw_this['vars']['id']]) && $this->oSess->is('is-dicts-own') )
	)
{
	$is_allow_dict = 1;
}
if (!$is_allow_dict)
{
	$this->str .= '<p class="xu">'.$this->oL->m('1297').'</p>';
	$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
	$this->str .= '<div class="xt">'.$this->oL->m('1258').'</div>';
	$this->str .= '<div class="xt">'.$this->oL->m('1260').'</div>';
	return;
}

global $arDictParam;

#$this->sys['isDebugQ'] = 1;

$arQ = array();
$arQ[] = $this->oSqlQ->getQ('del-wordmap-by-dict', $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('del-by-dict_id', TBL_MAP_USER_DICT, $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('del-by-dict_id', TBL_MAP_USER_TERM, $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('del-by-id', TBL_DICT, $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('del-by-id', TBL_STAT_DICT, $this->gw_this['vars']['id']);
$arQ[] = sprintf('DELETE FROM `%s` WHERE id_dict = "%d"', $this->sys['tbl_prefix'].'history_terms', $this->gw_this['vars']['id']);
$arQ[] = sprintf('UPDATE `%s` SET id_dict = "0" WHERE id_dict = "%d"', $this->sys['tbl_prefix'].'abbr', $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('drop-table', $arDictParam['tablename']);
$this->str .= gw_tmp_clear($this->gw_this['vars']['id']);
$this->str .= postQuery($arQ, GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . GW_T_DICTS, $this->sys['isDebugQ'], 0);


?>