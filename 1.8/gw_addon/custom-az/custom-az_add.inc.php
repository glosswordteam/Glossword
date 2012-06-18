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


$this->str .= '<table cellpadding="0" cellspacing="0" width="100%" border="0">';
$this->str .= '<tbody><tr>';
$this->str .= '<td style="width:'.$this->left_td_width.';background:'.$this->ar_theme['color_2'].';vertical-align:top">';

$this->str .= '<h3>'.$this->oL->m('2_page_custom_az_browse').'</h3>';
$this->str .= '<ul class="gwsql xu"><li>';
$this->str .= implode('</li><li>', $this->ar_profiles_browse);
$this->str .= '</li></ul>';

$this->str .= '</td>';
$this->str .= '<td style="padding-left:1em;vertical-align:top">';



/* */
$this->str .= $this->_get_nav();


$ar_req_fields = array('profile_name');
if ($this->gw_this['vars']['post'] == '')
{
	/* Profile */
	$arPost['profile_name'] = '';
	$arPost['is_active'] = 1;
	$this->str .= $this->get_form_custom_az($arPost, 0, 0, $ar_req_fields);
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
		$q1 =& $arPost;
		$q1['id_profile'] = $this->oDb->MaxId($this->sys['tbl_prefix'].'custom_az_profiles', 'id_profile');
		$ar_query[] = gw_sql_insert($q1, $this->sys['tbl_prefix'].'custom_az_profiles');
		$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_BROWSE.'&'.GW_TARGET.'='.$this->component.'&tid='.$q1['id_profile'], $this->sys['isDebugQ'], 0);
	}
	else
	{
		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form_custom_az($arPost, 1, $ar_broken, $ar_req_fields);
	}
}


$this->str .= '</td></tr></tbody></table>';


?>