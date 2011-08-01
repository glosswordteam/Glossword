<?php
/**
 * Glossword - glossary compiler (http://glossword.info/)
 * © 2002-2008 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 * This program is free software; you can redistribute it and/or modify
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

/* */
$this->str .= $this->_get_nav();


$ar_req_fields = array('vkbd_name', 'vkbd_letters');
if ($this->gw_this['vars']['post'] == '')
{
	/* Profile */
	$arPost['vkbd_name'] = '';
	$arPost['vkbd_letters'] = '';
	$this->str .= $this->get_form_vkbd($arPost, 0, 0, $ar_req_fields);
}
else
{
	/* */
	$arPost =& $this->gw_this['vars']['arPost'];
	/* Checking posted vars */
	$errorStr = '';
	$ar_broken = validatePostWalk($arPost, $ar_req_fields);
	if (empty($ar_broken))
	{
		$q1 =& $arPost;
		$ar_query[] = gw_sql_insert($q1, $this->sys['tbl_prefix'].'virtual_keyboard');
		$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);
	}
	else
	{
		$this->str .= $this->get_form_vkbd($arPost, 1, $ar_broken, $ar_req_fields);
	}
}




?>