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
	die('<!-- $Id: topics_remove.inc.php 497 2008-06-14 07:15:56Z glossword_team $ -->');
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

/* Check permission to edit the page */
/* Check permission to edit the topic */
$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-topics-adm', $this->gw_this['vars']['tid']), 'page');
$arParsed = isset($arSql[0]) ? $arSql[0] : array();
$is_allow_edit = ($this->oSess->is('is-topics') ? 1 : ($this->oSess->is('is-topics-own') && ($arParsed['id_user'] == $this->oSess->id_user)) ? 1 : 0);
if (!$is_allow_edit)
{
	$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
	return;
}

$ar =& $this->gw_this['ar_topics_list'];
$is_error = 0;
$msg_error = '';
$ar_query = array();
/* can't remove topic already assigned to another dictionary */
$sql = sprintf('SELECT id, title FROM `'.$this->sys['tbl_prefix'].'dict` WHERE id_topic = "%d"', $this->gw_this['vars']['tid']);
$arSql = $this->oDb->sqlExec($sql);
if (!empty($arSql))
{
	$is_error = 1;
	$msg_error .= '<br />' . $this->oL->m('reason_3');
}
/* can't delete last root topic */
$sql = sprintf('SELECT count(*) AS n FROM `'.$this->sys['tbl_prefix'].'topics` WHERE id_parent != "%d"', $this->gw_this['vars']['tid']);
$arSql = $this->oDb->sqlExec($sql);
for (; list($arK, $arV) = each($arSql);)
{
	if($arV['n'] == 1)
	{
		$is_error = 1;
		$msg_error .= '<br />' . $this->oL->m('reason_1');
	}
}
/* check if Topic ID is present */
if (!isset($ar[$this->gw_this['vars']['tid']]))
{
	$is_error = 1;
	$msg_error .= '<br />' . $this->oL->m('reason_12');
}
if ($is_error)
{
	$this->str .= '<p class="xr"><span class="red">' . $this->oL->m('reason_11') . '</span> '.  $msg_error . '</p>';
	return;
}

/* Read the three */
$arKeys = ctlgGetTree($ar, $this->gw_this['vars']['tid']);
/* */
$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics` WHERE `id_topic` IN ('. implode(', ', $arKeys).')';
$ar_query[] = 'DELETE FROM `'.$this->sys['tbl_prefix'].'topics_phrase` WHERE `id_topic` IN ('. implode(', ', $arKeys).')';

$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE .'&'. GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);

/* end of file */
?>