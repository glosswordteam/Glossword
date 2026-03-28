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
	die('<!-- $Id: menumanager_add_menu_item.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

$this->gw_this['vars']['w1'] = 'secondary';
/* */
$this->str .= $this->_get_nav();
/* */
$ar_req_fields = array('id_action','is_in_menu','id_component');

if ($this->gw_this['vars']['post'] == '')
{
	/* Default values */
	$vars['is_active_map'] = 1;
	$vars['is_in_menu'] = 1;
	$vars['req_permission_map'] = ':is-sys-settings:';
	$vars['id_action'] = $vars['id_action_old'] = 2;
	$vars['id_component'] = 0;
	$vars['is_use_id_action'][1] = true;
	$vars['id_component_name'] = $vars['aname_sys'] = $vars['aname'] = $vars['icon'] = '';
	/* Not submitted */
	$this->str .= $this->get_form($vars, 0, 0, $ar_req_fields);
}
else
{
	/* */
	$arPost =& $this->gw_this['vars']['arPost'];
	/* Fix on/off options */
	$arIsV = array('is_active_map');
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
		$arPost['req_permission_map'] = $arPost['is_permissions'];
		$arPost['req_permission_map'] = ':'.implode(':', $arPost['req_permission_map']).':';
		/* */
		$is_use_id_action = $arPost['is_use_id_action'];
		if (!$is_use_id_action[0])
		{
			$q2['id_action'] = $arPost['id_action'] = $this->oDb->MaxId($this->sys['tbl_prefix'].'component_actions', 'id_action');
			$q2['aname_sys'] = $arPost['aname_sys'];
			$q2['aname'] = $arPost['aname'];
			$q2['icon'] = $arPost['icon'];
			$ar_query[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'component_actions');
  		}
		unset($arPost['is_permissions']);
		unset($arPost['is_use_id_action']);
		unset($arPost['aname_sys']);
		unset($arPost['aname']);
		unset($arPost['icon']);
		unset($arPost['id_action_old']);
		$q1 =& $arPost;
		$ar_query[] = gw_sql_insert($q1, $this->sys['tbl_prefix'].'component_map');
#prn_r( $is_use_id_action );
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