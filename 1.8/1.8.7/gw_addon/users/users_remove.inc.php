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
	die('<!-- $Id: users_remove.inc.php 396 2008-04-06 11:08:15Z yrtimd $ -->');
}
/* Included from $oAddonAdm->alpha(); */

$ar_q = array();
if (!$this->gw_this['vars']['isConfirm'])
{
	/* Should be confirmed */
	return;
}
/* Keep guest user */
if ($this->gw_this['vars']['tid'] == 1)
{
	$this->str .= $this->_get_nav();
	$this->str .= '<div class="xt">'.$this->oL->m('1293').'</div>';
	return;
}

/* Enter debug mode */
#$this->sys['isDebugQ'] = 1;

$ar_query = array();
/**
 * Remove user:
 * 1. remove User ID from gw_users
 * 2. remove User ID from gw_users_map
 * 3. re-assign dictionary IDs to admin
 * 4. re-assing term IDs to admin
 */
$ar_query[] = 'DELETE FROM `' . $this->oSess->db_table_users . '` WHERE id_user = "' . gw_text_sql($this->gw_this['vars']['tid']) . '" LIMIT 1';
$ar_query[] = 'DELETE FROM `' . TBL_MAP_USER_DICT . '` WHERE user_id = "' . gw_text_sql($this->gw_this['vars']['tid']) . '"';
/* Change owner */
$q1['id_user'] = $this->oSess->user_get('id_user');
$ar_query[] = gw_sql_update($q1, TBL_DICT, 'id_user = "' . gw_text_sql($this->gw_this['vars']['tid']) . '"');
$ar_query[] = gw_sql_update($q1, TBL_MAP_USER_TERM, 'id_user = "' . gw_text_sql($this->gw_this['vars']['tid']) . '"');

$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);
/* end of file */
?>