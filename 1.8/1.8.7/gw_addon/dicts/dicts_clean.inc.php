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
	die('<!-- $Id: dicts_clean.inc.php 404 2008-04-07 11:37:59Z yrtimd $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

/* The list of allowed dictionaries */
$ar_allowed_dicts = $this->oSess->user_get('dictionaries');

/* Check for permission */
$is_allow_dict = 0;
if ( $this->oSess->is('is-sys-settings')
		|| $this->oSess->is('is-dicts')
		|| (isset($ar_allowed_dicts[$this->gw_this['vars']['id']]) && $this->oSess->is('is-dicts-own') )
	)
{
	$is_allow_dict = 1;
}
if (!$is_allow_dict)
{
	$this->str .= '<p class="xu">'.$this->oL->m('1297').'</p>';
	$this->str .= '<p class="xu">'.$this->oL->m('reason_13').'</p>';
	$this->str .= '<div class="xt">'.$this->oL->m('1258').'</div>';
	$this->str .= '<div class="xt">'.$this->oL->m('1260').'</div>';
	return;
}

global $arDictParam;

if (!$this->gw_this['vars']['id'])
{
	/* Provide the list of dictionaries */
	$this->str .= '<div class="xu">'.$this->oL->m('srch_selectdict').':</div>';
	$this->str .= '<ul class="xu">';
	$cnt_dict = 0;
	$ar_allowed_dicts = $this->oSess->user_get('dictionaries');
	for (reset($this->gw_this['ar_dict_list']); list($k, $v) = each($this->gw_this['ar_dict_list']);)
	{
		if ( $this->oSess->is('is-sys-settings')
			|| (isset($ar_allowed_dicts[$v['id']])
				&& ( $this->oSess->is('is-dicts') || $this->oSess->is('is-dicts-own') )
				)
			)
		{
			$this->str .= '<li>';
			$this->oHtml->setTag('a', 'onclick', 'return confirm(\''.$this->oL->m('3_clean').': &quot;'.htmlspecialchars($v['title']).'&quot;. '.$this->oL->m('9_remove').'\' )');
			$this->str .= $this->oHtml->a( $this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_CLEAN .'&'. GW_TARGET.'='.GW_T_DICTS. '&id='.$v['id'], 'ID:'.$v['id'].' '.$v['title']. ' ('.$v['int_terms'].')' );
			$this->str .= '</li>';
			$cnt_dict++;
		}
	}
	$this->oHtml->setTag('a', 'onclick', '');
	/* No allowed dictionaries */
	if (!$cnt_dict)
	{
		$this->str .= '<li>'.$this->oL->m('reason_13').'</li>';
	}
	$this->str .= '</ul>';
	return;
}
else
{
#$this->sys['isDebugQ'] = 1;
	$arQ = array();
	$arQ[] = 'TRUNCATE `'.$arDictParam['tablename'].'`';
	$arQ[] = $this->oSqlQ->getQ('del-by-dict_id', TBL_MAP_USER_TERM, $this->gw_this['vars']['id']);
	$arQ[] = $this->oSqlQ->getQ('del-wordmap-by-dict', $this->gw_this['vars']['id']);
	$arQ[] = sprintf('DELETE FROM `%s` WHERE id_dict = "%d"', $this->sys['tbl_prefix'].'history_terms', $this->gw_this['vars']['id']);
	$arQ[] = sprintf('UPDATE `%s` SET int_terms = 0, int_bytes = 0 WHERE `id` = "%d"', $this->sys['tbl_prefix'].'dict', $this->gw_this['vars']['id']);
	/* */
	$arQ[] = 'ALTER TABLE `'.$arDictParam['tablename'].'` PACK_KEYS=0 CHECKSUM=0 DELAY_KEY_WRITE=1 AUTO_INCREMENT=1';
	$arQ[] = 'CHECK TABLE `'.$arDictParam['tablename'].'`';

	$this->str .= gw_tmp_clear( $this->gw_this['vars']['id'] );
	/* Redirect to... */
	$arPost['after'] = GW_AFTER_DICT_UPDATE;
	$str_url = gw_after_redirect_url($arPost['after']);
	$this->str .= postQuery($arQ, $str_url, $this->sys['isDebugQ'], 0);
}

?>