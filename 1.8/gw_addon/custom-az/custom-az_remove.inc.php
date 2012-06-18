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
	die('<!-- $Id$ -->');
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

/* Keep UTF-8 profile */
if ($this->gw_this['vars']['tid'] == 1)
{
	$this->str .= $this->_get_nav();
	$this->str .= '<div class="xt">'.$this->oL->m('1293').'</div>';
	return;
}

/* Remove from profiles */
$ar_query[] = gw_sql_delete($this->sys['tbl_prefix'].'custom_az', array('id_profile' => $this->gw_this['vars']['tid']));
/* Remove from alphabetic orders */
$ar_query[] = gw_sql_delete($this->sys['tbl_prefix'].'custom_az_profiles', array('id_profile' => $this->gw_this['vars']['tid']));
/* Replace with a default profile */
$ar_query[] = gw_sql_update(array('id_custom_az' => '1'), $this->sys['tbl_prefix'].'dict', '`id_custom_az` = "'.$this->gw_this['vars']['tid'].'"');
/* Redirect */
$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component.'&tid=1', $this->sys['isDebugQ'], 0);
/* end of file */
?>