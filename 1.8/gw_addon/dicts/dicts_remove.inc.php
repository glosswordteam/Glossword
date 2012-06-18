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
	die('<!-- $Id: dicts_remove.inc.php 499 2008-06-15 13:59:46Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

#$this->sys['isDebugQ'] = 1;

/** 
 * Conditions:
 * - User is admin.
 * - User is the creator of the dictionary AND it is allowed to edit his own dictionaries.
 * - User can edit other user's dictionaries.
 */
$is_allow_dict = 0;

/* Dictionary ID specified */
if ($this->gw_this['vars']['id'])
{
	global $arDictParam;
	
	if (!$this->gw_this['vars']['isConfirm'])
	{
		/* Should be confirmed */
		/* 1.8.9: Quick remove added, confirmation is not needed */
		#return;
	}
	/* */
	if ( $this->oSess->is('is-sys-settings')
		|| $this->oSess->is('is-dicts')
		|| ($arDictParam['id_user'] == $this->oSess->id_user && $this->oSess->is('is-dicts-own'))
		)
	{
		$is_allow_dict = 1;
		/* Run tasks below */
	}
	if (!$is_allow_dict)
	{
		$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
		$this->str .= '<div class="xt">'.$this->oL->m('1258').'</div>';
		$this->str .= '<div class="xt">'.$this->oL->m('1260').'</div>';
		return;
	}
}
elseif (!$this->gw_this['vars']['id'])
{
	/* No dictionary ID specified */
	/* Provide the list of dictionaries */
	$this->str .= '<div class="margin-inside">';
	$this->str .= '<div class="xu">'.$this->oL->m('srch_selectdict').':</div>';
	$this->str .= '<ul class="gwsql">';
	$cnt_dict = 0;
	$ar_allowed_dicts = $this->oSess->user_get('dictionaries');
	for (reset($this->gw_this['ar_dict_list']); list($k, $v) = each($this->gw_this['ar_dict_list']);)
	{
		if ( $this->oSess->is('is-sys-settings')
			|| $this->oSess->is('is-dicts')
			|| ($v['id_user'] == $this->oSess->id_user && $this->oSess->is('is-dicts-own'))
			)
		{
			$this->oHtml->setTag('a', 'onclick', 'return confirm(\''.$this->oL->m('3_remove').': &quot;'.htmlspecialchars($v['title']).'&quot;. '.$this->oL->m('9_remove').'\' )');
			$this->str .= '<li>'.gw_dict_browse_for_select($v).'</li>';
			$cnt_dict++;
		}
	}
	$this->oHtml->setTag('a', 'onclick', '');
	/* No allowed dictionaries */
	if (!$cnt_dict)
	{
		$this->str .= '<li>'.$this->oL->m('reason_4').'</li>';
		$this->str .= '<li>'.$this->oL->m('reason_13').'</li>';
	}
	$this->str .= '</ul>';
	$this->str .= '</div>';
	return;
}
/* */
$arQ = array();
$arQ[] = $this->oSqlQ->getQ('del-wordmap-by-dict', $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('del-by-dict_id', TBL_MAP_USER_DICT, $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('del-by-dict_id', TBL_MAP_USER_TERM, $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('del-by-id', TBL_DICT, $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('del-by-id', TBL_STAT_DICT, $this->gw_this['vars']['id']);
$arQ[] = sprintf('DELETE FROM `%s` WHERE id_dict = "%d"', $this->sys['tbl_prefix'].'history_terms', $this->gw_this['vars']['id']);
$arQ[] = sprintf('UPDATE `%s` SET id_dict = "0" WHERE id_dict = "%d"', $this->sys['tbl_prefix'].'abbr', $this->gw_this['vars']['id']);
$arQ[] = $this->oSqlQ->getQ('drop-table', $arDictParam['tablename']);
$this->str .= gw_tmp_clear($this->gw_this['vars']['id']);
$this->str .= postQuery($arQ, GW_ACTION . '=' . GW_A_BROWSE . '&' . GW_TARGET . '=' . GW_T_DICTS, $this->sys['isDebugQ'], 0);

?>