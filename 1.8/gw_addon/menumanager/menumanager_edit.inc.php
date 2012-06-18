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
	die('<!-- $Id: menumanager_edit.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */



/* Sorting order */
if ($this->gw_this['vars']['w2'])
{
	$sql_sort = 'int_sort';
	switch ($this->gw_this['vars']['w2'])
	{
		case 'up';
			$sql_sort = 'int_sort - 15';
		break;
		case 'down';
			$sql_sort = 'int_sort + 15';
		break;
	}
	switch ($this->gw_this['vars']['w1'])
	{
		case 'primary':
			$sql_table = $this->sys['tbl_prefix'].'component';
			/* Update sorting index */
			$sql = 'UPDATE `'.$sql_table.'` SET int_sort = ('.$sql_sort.') WHERE `id_component` = "'.$this->gw_this['vars']['tid'].'"';
			$this->oDb->sqlExec($sql);
			$sql = 'SELECT id_component FROM `'.$sql_table.'` ORDER BY int_sort ASC';
			$ar_sorted = $this->oDb->sqlExec($sql);
			/* Rebuild sorting index */
			$arQ = array();
			$int_sort = 10;
			for (; list($k, $arV) = each($ar_sorted);)
			{
				$arQ[] = 'UPDATE `'.$sql_table.'` SET int_sort = '.$int_sort.' WHERE `id_component` = "'.$arV['id_component'].'"';
				$int_sort += 10;
			}
		break;
		case 'secondary':
			$sql_table = $this->sys['tbl_prefix'].'component_map';
			/* Update sorting index */
			$sql = 'UPDATE `'.$sql_table.'` SET int_sort = ('.$sql_sort.') WHERE `id` = "'.$this->gw_this['vars']['tid'].'"';
			$this->oDb->sqlExec($sql);
			$sql = 'SELECT id FROM `'.$sql_table.'` WHERE `id_component` = "'.$this->gw_this['vars']['w3'].'" ORDER BY int_sort ASC';
			$ar_sorted = $this->oDb->sqlExec($sql);
			/* Rebuild sorting index */
			$arQ = array();
			$int_sort = 10;
			for (; list($k, $arV) = each($ar_sorted);)
			{
				$arQ[] = 'UPDATE `'.$sql_table.'` SET int_sort = '.$int_sort.' WHERE `id` = "'.$arV['id'].'"';
				$int_sort += 10;
			}
		break;
	}
#prn_r( $sql );
#prn_r( $arQ );
	/* Redirect */
	postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component, $this->sys['isDebugQ'], 0);
	return;
}
/* */
$this->str .= $this->_get_nav();
/* */
switch ($this->gw_this['vars']['w1'])
{
	case 'primary':
		/* Get component settings */
		$arSql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-components-actions', 'cm.id_component = "'.$this->gw_this['vars']['tid'].'"', '1=1'));
		$vars = isset($arSql[0]) ? $arSql[0] : array();
		if (empty($arSql))
		{
			$this->str .= '<p class="xu">'.$this->oL->m('1297').'</p>';
			return;
		}
		/* Change heading */
		$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
			': '. $this->oL->m($this->ar_component['aname']). 
			': <tt>'. $this->oL->m($vars['cname']).'</tt>';

		/* Removing */
		if ($this->gw_this['vars']['remove'])
		{
			/* Change heading */
			$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
				': '. $this->oL->m('3_remove').
				': <tt>'. $this->oL->m($vars['id_component_name']).'</tt>';
		
			$msg = '&quot;'.$this->oL->m($vars['id_component_name']).'&quot;';
			
			$oFormConfirm = new gwConfirmWindow;
			$oFormConfirm->action = $this->sys['page_admin'];
			$oFormConfirm->submitok = $this->oL->m('3_remove');
			$oFormConfirm->submitcancel = $this->oL->m('3_cancel');
			$oFormConfirm->formbgcolor = $this->ar_theme['color_2'];
			$oFormConfirm->formbordercolor = $this->ar_theme['color_4'];
			$oFormConfirm->formbordercolorL = $this->ar_theme['color_1'];
			$oFormConfirm->setQuestion('<p class="xr"><strong class="red">' . $this->oL->m('9_remove') .
									'</strong></p><p class="xt"><span class="gray">'. $this->oL->m('3_remove').
									': </span>'.$msg.'</p>');
			$oFormConfirm->tAlign = 'center';
			$oFormConfirm->formwidth = '400';
			$oFormConfirm->setField('hidden', 'tid', $this->gw_this['vars']['tid']);
			$oFormConfirm->setField('hidden', 'w1', $this->gw_this['vars']['w1']);
			$oFormConfirm->setField('hidden', 'w2', $this->gw_this['vars']['w2']);
			$oFormConfirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
			$oFormConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
			$oFormConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
			$this->str .= $oFormConfirm->Form();
			return;
		}
		/* Editing */
		$ar_req_fields = array('id_component_name', 'cname');
		if ($this->gw_this['vars']['post'] == '')
		{
			/* Default settings */
			$vars['id_action_old'] = $vars['id_action'];
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
				$sql_table = $this->sys['tbl_prefix'].'component';
				$sql_where = '`id_component` = "'.$this->gw_this['vars']['tid'].'"';
				/* */
				unset($arPost['id_action_old']);
				$q1 =& $arPost;
				$ar_query[] = gw_sql_update($q1, $sql_table, $sql_where);
				$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component.'&note_afterpost='.$this->oL->m('1332'), $this->sys['isDebugQ'], 0);
			}
			else
			{
				$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
				$this->str .= $this->get_form($arPost, 1, $ar_broken, $ar_req_fields);
			}
		}
	break;
	case 'secondary':
		/* Get an action settings */
		$arSql = $this->oDb->sqlRun($this->oSqlQ->getQ('get-components-actions', 'cmm.id = "'.$this->gw_this['vars']['tid'].'"', '1=1'));
		$vars = isset($arSql[0]) ? $arSql[0] : array();
		/* Change heading */
		$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
			': '. $this->oL->m($this->ar_component['aname']).
			': <tt>'. $this->oL->m($vars['cname']).
			': '. $this->oL->m($vars['aname']).'</tt>';

		$ar_req_fields = array('id_action');
		/* Removing */
		if ($this->gw_this['vars']['remove'])
		{
			/* Change heading */
			$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
				': '. $this->oL->m('3_remove').
				': <tt>'. $this->oL->m($vars['id_component_name']).
				': '. $this->oL->m($vars['aname']).'</tt>';
		
			$msg = '&quot;'.$this->oL->m($vars['aname']).'&quot;';
			
			$oFormConfirm = new gwConfirmWindow;
			$oFormConfirm->action = $this->sys['page_admin'];
			$oFormConfirm->submitok = $this->oL->m('3_remove');
			$oFormConfirm->submitcancel = $this->oL->m('3_cancel');
			$oFormConfirm->formbgcolor = $this->ar_theme['color_2'];
			$oFormConfirm->formbordercolor = $this->ar_theme['color_4'];
			$oFormConfirm->formbordercolorL = $this->ar_theme['color_1'];
			$oFormConfirm->setQuestion('<p class="xr"><strong class="red">' . $this->oL->m('9_remove') .
									'</strong></p><p class="xt"><span class="gray">'. $this->oL->m('3_remove').
									': </span>'.$msg.'</p>');
			$oFormConfirm->tAlign = 'center';
			$oFormConfirm->formwidth = '400';
			$oFormConfirm->setField('hidden', 'tid', $this->gw_this['vars']['tid']);
			$oFormConfirm->setField('hidden', 'w1', $this->gw_this['vars']['w1']);
			$oFormConfirm->setField('hidden', 'w2', $this->gw_this['vars']['w2']);
			$oFormConfirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
			$oFormConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
			$oFormConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
			$this->str .= $oFormConfirm->Form();
			return;
		}
		/* Editing */
		if ($this->gw_this['vars']['post'] == '')
		{
			/* Default settings */
			$vars['is_use_id_action'][1] = true;
			$vars['id_action_old'] = $vars['id_action'];
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
					$q2['aname_sys'] = $arPost['aname_sys'];
					$q2['aname'] = $arPost['aname'];
					$q2['icon'] = $arPost['icon'];
					$ar_query[] = gw_sql_update($q2, $this->sys['tbl_prefix'].'component_actions', '`id_action` = "'.$arPost['id_action_old'].'"');
		  		}
				unset($arPost['is_permissions']);
				unset($arPost['is_use_id_action']);
				unset($arPost['id_action_old']);
				unset($arPost['aname_sys']);
				unset($arPost['aname']);
				unset($arPost['icon']);
				unset($arPost['is_permissions']);
				$q1 =& $arPost;
				$ar_query[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'component_map', '`id` = "'.$this->gw_this['vars']['tid'].'"');
#prn_r( $arPost );
#prn_r( $ar_query );
				$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component.'&note_afterpost='.$this->oL->m('1332'), $this->sys['isDebugQ'], 0);
			}
			else
			{
				$arPost['req_permission'] = $arPost['is_permissions'];
				$arPost['req_permission'] = ':'.implode(':', $arPost['req_permission']).':';
				$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
				$this->str .= $this->get_form($arPost, 1, $ar_broken, $ar_req_fields);
			}
		}
	break;
}

/* end of file */
?>