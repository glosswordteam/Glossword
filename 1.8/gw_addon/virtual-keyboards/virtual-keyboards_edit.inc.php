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
	die('<!-- $Id: virtual-keyboards_edit.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* Page ID is not defined */
if (!$this->gw_this['vars']['tid'])
{
	/* Change heading */
	$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).': '.$this->oL->m('3_browse');
	$this->gw_this['vars'][GW_ACTION] = 'browse';
	$this->sys['path_component_action'] = $this->sys['path_addon'].'/'.$this->gw_this['vars'][GW_TARGET].'/'.$this->gw_this['vars'][GW_TARGET] . '_' . $this->gw_this['vars'][GW_ACTION].'.inc.php';
	include_once( $this->sys['path_component_action'] );
	return;
}

/* */
$this->str .= $this->_get_nav();

/* Get profile settings */
$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-vkbd-profile', $this->gw_this['vars']['tid']), $this->component);
$arSql = isset($arSql[0]) ? $arSql[0] : array();


$ar_query = array( );
//$this->sys['isDebugQ'] = 1;
/* Switching On/off */
if ( $this->gw_this['vars']['mode'] == 'off' ) {
	$ar_query[] = 'UPDATE `' . $this->sys['tbl_prefix'] . 'virtual_keyboard`
							SET `is_index_page` = "0"
							WHERE `id_profile` = "' . $this->gw_this['vars']['tid'] . '"';
	$this->str .= postQuery( $ar_query, GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . $this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0 );
	return;
} elseif ( $this->gw_this['vars']['mode'] == 'on' ) {
	/* Only one virtual keyboard for the index page */
	$ar_query[] = gw_sql_update( array( 'is_index_page' => '0' ), $this->sys['tbl_prefix'] . 'virtual_keyboard', 'is_index_page = \'1\' AND id_profile != \'' . $this->gw_this['vars']['tid'] . '\'' );
	$ar_query[] = 'UPDATE `' . $this->sys['tbl_prefix'] . 'virtual_keyboard`
							SET `is_index_page` = "1"
							WHERE `id_profile` = "' . $this->gw_this['vars']['tid'] . '"';
	$this->str .= postQuery( $ar_query, GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . $this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0 );
	return;
}


$ar_req_fields = array('vkbd_name', 'vkbd_letters');
if ($this->gw_this['vars']['post'] == '')
{

	/* Removing */
	if ($this->gw_this['vars']['remove'])
	{
		/* Change heading */
		$this->sys['id_current_status'] = $this->oL->m($this->ar_component['cname']).
			': '. $this->oL->m('3_remove');

		$msg = $arSql['vkbd_name'];

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
		$oFormConfirm->setField('hidden', GW_ACTION, GW_A_REMOVE);
		$oFormConfirm->setField('hidden', GW_TARGET, $this->gw_this['vars'][GW_TARGET]);
		$oFormConfirm->setField('hidden', $this->oSess->sid, $this->oSess->id_sess);
		$this->str .= $oFormConfirm->Form();
		return;
	}
	/* Not submitted */
	$this->str .= $this->get_form_vkbd($arSql, 0, 0, $ar_req_fields);
}
else
{
	$arPost =& $this->gw_this['vars']['arPost'];
	$arPost['vkbd_name'] = trim($arPost['vkbd_name']);
	$arPost['vkbd_letters'] = str_replace('  ', ' ', $arPost['vkbd_letters']);

	/* Fix on/off options */
	$arIsV = array( 'is_active', 'is_index_page' );
	for (; list($k, $v) = each($arIsV);)
	{
		$arPost[$v]  = isset($arPost[$v]) ? $arPost[$v] : 0;
	}
#$this->sys['isDebugQ'] = 1;
	/* Checking posted vars */
	$errorStr = '';
	$ar_broken = validatePostWalk($arPost, $ar_req_fields);
	if (empty($ar_broken))
	{
		$q1 =& $arPost;
		
		/* Only one virtual keyboard for the index page */
		if ( $arPost['is_index_page'] ) {
			$ar_query[] = gw_sql_update( array( 'is_index_page' => '0' ), $this->sys['tbl_prefix'].'virtual_keyboard', 'is_index_page = \'1\' AND id_profile != \''.$this->gw_this['vars']['tid'].'\'');
		}
		
		$ar_query[] = gw_sql_update($q1, $this->sys['tbl_prefix'].'virtual_keyboard', 'id_profile = "'.$this->gw_this['vars']['tid'].'"');
		$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component.'&tid='.$this->gw_this['vars']['tid'].'&r='.time(), $this->sys['isDebugQ'], 0);
	}
	else
	{
		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form_vkbd($arPost, 1, $ar_broken, $ar_req_fields);
	}
}

?>