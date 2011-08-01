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
	die('<!-- $Id: terms_export.inc.php 376 2008-03-27 12:11:53Z yrtimd $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();

if (empty($this->gw_this['ar_dict_list']))
{
	$this->str .= '<div class="xu">'.$this->oL->m('reason_4').'</div>';
	$this->str .= '<p class="xu">'.$this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.GW_A_ADD .'&'. GW_TARGET.'='.GW_T_DICTS, $this->oL->m('3_add') ).'</p>';
	return;
}
if (!$this->gw_this['vars']['id'])
{
	/* Provide the list of dictionaries */
	$this->str .= '<div class="xu">'.$this->oL->m('srch_selectdict').':</div>';
	$this->str .= '<ul class="xu">';
	$ar_allowed_dicts = $this->oSess->user_get('dictionaries');
	$cnt_dict = 0;
	for (reset($this->gw_this['ar_dict_list']); list($k, $v) = each($this->gw_this['ar_dict_list']);)
	{
		if ( $this->oSess->is('is-sys-settings')
			|| ( isset($ar_allowed_dicts[$v['id']]) 
				&& $this->oSess->is('is-terms-import') )
			)
		{
			$this->str .= '<li>';
			$this->str .= $this->oHtml->a($this->sys['page_admin'].'?'.GW_ACTION.'='.$this->gw_this['vars'][GW_ACTION] .'&'. GW_TARGET.'='.GW_T_TERMS. '&id='.$v['id'], 'ID:'.$v['id'].' '.$v['title']. ' ('.$v['int_terms'].')' );
			$this->str .= '</li>';
			$cnt_dict++;
		}
	}
	/* No allowed dictionaries */
	if (!$cnt_dict)
	{
		$this->str .= '<li>'.$this->oL->m('reason_13').'</li>';
	}
	$this->str .= '</ul>';
	return;
}

global $oL, $gw_this, $oSess, $oDb, $oSqlQ, $oHtml, $sys, $arFields;
global $file_location, $arDictParam;
global $int_added_terms, $str;

$arPre =& $this->gw_this['vars']['arPre'];
$arPost =& $this->gw_this['vars']['arPost'];

$str = '';

include_once( $this->sys['path_include'].'/a.import.inc.php' );

$this->str .= $str;



?>