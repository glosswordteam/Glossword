<?php
/**
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2012 Glossword.biz team <team at glossword dot biz>
 * © 2002-2008 Dmitry N. Shilnikov
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die('<!-- $Id: virtual-keyboards_remove.inc.php 421 2008-04-22 23:14:56Z yrtimd $ -->');
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

/* Remove from profiles */
$ar_query[] = gw_sql_delete($this->sys['tbl_prefix'].'virtual_keyboard', array('id_profile' => $this->gw_this['vars']['tid']));
/* Replace with a default profile */
$ar_query[] = gw_sql_update(array('id_vkbd' => '0'), $this->sys['tbl_prefix'].'dict', 'id_vkbd = "'.$this->gw_this['vars']['tid'].'"');
/* */
$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);
/* end of file */
?>