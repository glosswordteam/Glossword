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
	die('<!-- $Id: menumanager_add_component.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

$this->gw_this['vars']['w1'] = 'primary';

$ar_req_fields = array('id_component_name', 'cname');
/* */
$this->str .= $this->_get_nav();
/* */
if ($this->gw_this['vars']['post'] == '')
{
	/* Default values */
	$vars['is_active'] = 1;
	$vars['id_action_old'] = '';
	$vars['req_permission'] = ':is-sys-settings:';
	$vars['id_component_name'] = $vars['cname'] = '';
	/* Not submitted */
	$this->str .= $this->get_form($vars, 0, 0, $ar_req_fields);
}
else
{
	/* */
	$arPost =& $this->gw_this['vars']['arPost'];
	/* Fix on/off options */
	$arIsV = array('is_active');
	for (; list($k, $v) = each($arIsV);)
	{
		$arPost[$v] = isset($arPost[$v]) ? $arPost[$v] : 0;
	}
	/* Checking posted vars */
	$errorStr = '';
	$ar_broken = validatePostWalk($arPost, $ar_req_fields);
	if (empty($ar_broken))
	{
		/* No errors */
		/* At least one permission should exist! */
		if (!isset($arPost['is_permissions']))
		{
			$arPost['is_permissions'] = array('is-sys-settings');
		}
		$arPost['req_permission'] = $arPost['is_permissions'];
		$arPost['req_permission'] = ':'.implode(':', $arPost['req_permission']).':';
		/* */
		unset($arPost['id_action_old']);
		unset($arPost['is_permissions']);
		$q1 =& $arPost;
		$q1['id_component'] = $this->oDb->MaxId($this->sys['tbl_prefix'].'component', 'id_component');
		$q1['int_sort'] = 999;
		$ar_query[] = gw_sql_insert($q1, $this->sys['tbl_prefix'].'component');
		/* Add one action automatically */
		$q2['id_component'] = $q1['id_component'];
		/* Browse */
		$q2['id_action'] = 2;
		$q2['is_in_menu'] = 1;
		$q2['int_sort'] = 10;
		$q2['req_permission_map'] = $q1['req_permission'];
		$ar_query[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'component_map');
#prn_r( $ar_query );
		$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);
	}
	else
	{
		$arPost['req_permission'] = $arPost['is_permissions'];
		$arPost['req_permission'] = ':'.implode(':', $arPost['req_permission']).':';
		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form($arPost, 1, $ar_broken, $ar_req_fields);
	}
}

/* End of file */
?>