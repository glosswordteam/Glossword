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
	die('<!-- $Id: custom-pages_add.inc.php 372 2008-03-27 05:18:49Z yrtimd $ -->');
}
/* Included from $oAddonAdm->alpha(); */


/* */
$this->str .= $this->_get_nav();


$arPre =& $this->gw_this['vars']['arPre'];
$arParsed['ar'] =& $this->ar;
$ar_req_fields = array();

if ($this->gw_this['vars']['post'] == '')
{
	/* set default values */
	$cnt = 0;
	for (; list($id_lang, $arV) = each($this->gw_this['vars']['ar_languages']);)
	{
		$arParsed['page'][$cnt]['id_page_phrase'] = '';
		$arParsed['page'][$cnt]['page_title'] = '';
		$arParsed['page'][$cnt]['page_descr'] = '';
		$arParsed['page'][$cnt]['page_content'] = '';
		$arParsed['page'][$cnt]['page_keywords'] = '';
		$arParsed['page'][$cnt]['id_lang'] = $id_lang;
		$cnt++;
	}
	$arParsed['is_active'] = 1;
	$arParsed['id_parent'] = 0;
	$arParsed['page_icon'] = '';
	$arParsed['page_uri'] = 'page-'. time();
	$arParsed['page_php_1'] = '';
	$arParsed['page_php_2'] = '';
	$is_first = 1;
	/* additional actions */
	if (is_array($arPre))
	{
		$is_first = 0;
		$arParsed = gw_ParsePre($arParsed, $arPre);
	}
	$arBroken = array();
	$this->str .= $this->get_form($arParsed, $is_first, 0, $ar_req_fields);

	/* Editing tips */
	$arHelpMap = array(
			'dict_name'  => 'tip028',
			'announce' => 'tip029',
			'1058'  => 'tip030',
			'keywords'  => 'tip007',
			'1073'  => 'tip031',
			'1059'  => 'tip032'
	);
	$strHelp = '';
	$strHelp .= '<dl>';
	for (; list($k, $v) = each($arHelpMap);)
	{
		$strHelp .= '<dt><strong>' . $this->oL->m($k) . '</strong></dt>';
		$strHelp .= '<dd>' . $this->oL->m($v) . '</dd>';
	}
	$strHelp .= '</dl>';
	$this->str .= '<br />'.kTbHelp($this->oL->m('2_tip'), $strHelp);
}
else
{
#$this->sys['isDebugQ'] = 1;
	$arQ = array();
	/* Fix on/off options */
	$arIsV = array('is_active');
	for (; list($k, $v) = each($arIsV);)
	{
		$arPre[$v]  = isset($arPre[$v]) ? $arPre[$v] : 0;
	}
	$q1 = $q2 = array();
	$id_page_phrase = $this->oDb->MaxId($this->sys['tbl_prefix'].'pages_phrase', 'id_page_phrase');
	$q1['id_page'] = $q2['id_page'] = $this->oDb->MaxId($this->sys['tbl_prefix'].'pages', 'id_page');
	$q1['id_parent'] =& $arPre['id_parent'];
	$q1['is_active'] =& $arPre['is_active'];
	$q1['page_icon'] =& $arPre['page_icon'];
	$q1['page_uri'] =& $arPre['page_uri'];
	$q1['id_user'] = $this->oSess->id_user;
	$q1['page_php_1'] =& $arPre['page_php_1'];
	$q1['page_php_2'] =& $arPre['page_php_2'];
	$q1['date_modified'] = $q1['date_created'] = $this->sys['time_now_gmt_unix'];
	/* */
	for (; list($elK, $arV) = each($arPre['page']);)
	{
		$q2['page_title'] = $arV['page_title'];
		$q2['page_descr'] = $arV['page_descr'];
		$q2['page_content'] = $arV['page_content'];
		$q2['page_keywords'] = $arV['page_keywords'];
		$q2['id_lang'] = $arV['id_lang'];
		$q2['id_page_phrase'] = ($arV['id_page_phrase'] == '') ? $id_page_phrase + $elK : $arV['id_page_phrase'];
		$arQ[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'pages_phrase', 'id_page_phrase = "'. $arV['id_page_phrase'] .'"');
	}
	$sql = gw_sql_insert($q1, $this->sys['tbl_prefix'].'pages', 'id_page = "' . $this->gw_this['vars']['tid'] .'"');
	if ($this->sys['isDebugQ'])
	{
		$arQ[] = $sql;
	}
	else
	{
		/* Insert now */
		$this->oDb->sqlExec($sql);
	}
	/* Sorting subpages */
#	$sql = sprintf('SELECT `id_page` FROM `%s` WHERE `id_parent` = "%d" ORDER BY int_sort ASC', $this->sys['tbl_prefix'].'pages', $q1['id_parent']);
#	$arSql = $this->oDb->sqlExec($sql);
#	$i = 10;
#	for (; list($arK, $arV) = each($arSql);)
#	{
#		$arQ[] = 'UPDATE `' . $this->sys['tbl_prefix'].'pages`' . '
#					SET `int_sort` = ' . $i . '
#					WHERE `id_page` = ' . $arV['id_page'];
#			$i += 10;
#		}
	$this->str .= postQuery($arQ, 'a=' . GW_A_BROWSE . '&'.GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
}


?>