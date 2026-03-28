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
	die('<!-- $Id: settings_edit.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */

/* */
$this->str .= $this->_get_nav();
/* */

/* Editing */
$ar_req_fields = array(
	'path_tpl', 'path_log', 'path_cache',
	'site_name', 'site_desc', 'y_email', 'y_name', 'locale_name', 'visualtheme',
	'page_limit', 'time_new', 'time_upd', 'int_max_char_defn', 'srch_len',
	'max_dict_updated', 'max_dict_top', 'page_limit_search','prbblty_tasks',
	'max_days_searchlog', 'max_days_searchcache', 'max_days_history_temrs',
	'avatar_max_x', 'avatar_max_y', 'avatar_max_kb','site_email','site_email_from','mail_subject_prefix'
);

if ($this->gw_this['vars']['post'] == '')
{
	/* Default settings */
	$vars = $this->sys;
	/* Not submitted */
	$this->str .= $this->get_form($vars, 0, 0, $ar_req_fields);
}
else
{
#	$this->sys['isDebugQ']  = 1;
	/* */
	$arPost =& $this->gw_this['vars']['arPost'];
	/* Fix on/off options */
	$arIsV = array('is_list_numbers','is_list_images','is_list_announce',
		'is_log_search', 'is_log_ref', 'is_log_mail', 'is_show_topic_descr',
		'is_use_xhtml', 'is_mod_rewrite'
	);
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
		$arPost['site_name'] = gw_fix_input_to_db($arPost['site_name']);
		$arPost['site_desc'] = gw_fix_input_to_db($arPost['site_desc']);
		/* 1.8.4: write OpenSearch */
		$str_oo = $this->oFunc->file_get_contents($this->sys['path_tpl'].'/common/opensearch.xml');
		$str_oo = str_replace('{v:site_name}', strip_tags($this->sys['site_name']), $str_oo);
		$str_oo = str_replace('{v:site_desc}', strip_tags($this->sys['site_desc']), $str_oo);
		$str_oo = str_replace('{v:server_url}', strip_tags($this->sys['server_url']), $str_oo);
		$this->oFunc->file_put_contents($this->sys['path_temporary'].'/opensearch.xml', $str_oo);
		/* */
		$arPost['cache_zlib'] = isset($arPost['cache_zlib']) ? 1 : 0;
		$arPost['page_limit'] = preg_replace("/[^0-9]/", '', $arPost['page_limit']);
		$arPost['time_new'] = preg_replace("/[^0-9]/", '', $arPost['time_new']);
		$arPost['time_upd'] = preg_replace("/[^0-9]/", '', $arPost['time_upd']);
		$arPost['int_max_char_defn'] = preg_replace("/[^0-9]/", '', $arPost['int_max_char_defn']);
		/* */
		for (reset($arPost); list($k, $v) = each($arPost);)
		{
			$q = array();
			$q['settings_key'] = $k;
			$q['settings_val'] = $v;
			$ar_query[] = gw_sql_replace($q, $this->sys['tbl_prefix'].'settings', '`settings_key` = "' . gw_text_sql($k) .'"');
		}
		gw_tmp_clear();
		$this->str .= postQuery($ar_query, GW_ACTION.'='.GW_A_EDIT.'&'.GW_TARGET.'='.$this->component.'&note_afterpost='.$this->oL->m('1332').'&r='.time(), $this->sys['isDebugQ'], 0);
	}
	else
	{
		$this->oTpl->addVal( 'v:note_afterpost', gw_get_note_afterpost($this->oL->m(1370)) );
		$this->str .= $this->get_form($arPost, 1, $ar_broken, $ar_req_fields);
	}
}

?>