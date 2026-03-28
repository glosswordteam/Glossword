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
	die('<!-- $Id: topics_add.inc.php 497 2008-06-14 07:15:56Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */


/* */
$this->str .= $this->_get_nav();


$arPre =& $this->gw_this['vars']['arPre'];
$arParsed['ar'] =& $this->gw_this['ar_topics_list'];
$ar_req_fields = array();
if ($this->gw_this['vars']['post'] == '')
{
	/* set default values */
	$cnt = 0;
	for (; list($id_lang, $arV) = each($this->gw_this['vars']['ar_languages']);)
	{
		$arParsed['topic'][$cnt]['id_topic_phrase'] = '';
		$arParsed['topic'][$cnt]['topic_title'] = '';
		$arParsed['topic'][$cnt]['topic_descr'] = '';
		$arParsed['topic'][$cnt]['id_lang'] = $id_lang;
		$cnt++;
	}
	$arParsed['is_active'] = 1;
	$arParsed['id_parent'] = 0;
	$arParsed['topic_icon'] = '';
	$is_first = 1;
	/* additional actions */
	if (is_array($arPre))
	{
		$is_first = 0;
		$arParsed = gw_ParsePre($arParsed, $arPre);
	}
	$arBroken = array();
	$this->str .= $this->get_form_topic($arParsed, $is_first, 0, $ar_req_fields);
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
	$id_topic_phrase = $this->oDb->MaxId($this->sys['tbl_prefix'].'topics_phrase', 'id_topic_phrase');
	$q1['id_topic'] = $q2['id_topic'] = $this->oDb->MaxId($this->sys['tbl_prefix'].'topics', 'id_topic');
	$q1['id_parent'] = $arPre['id_parent'];
	$q1['is_active'] = $arPre['is_active'];
	$q1['topic_icon'] = $arPre['topic_icon'];
	$q1['id_user'] = $this->oSess->id_user;
	$q1['int_sort'] = 999;
	$q1['date_modified'] = $q1['date_created'] = $this->sys['time_now_gmt_unix'];
	/* */
	for (; list($elK, $arV) = each($arPre['topic']);)
	{
		$q2['topic_title'] = $arV['topic_title'];
		$q2['topic_descr'] = $arV['topic_descr'];
		$q2['id_lang'] = $arV['id_lang'];
		$q2['id_topic_phrase'] = ($arV['id_topic_phrase'] == '') ? $id_topic_phrase + $elK : $arV['id_topic_phrase'];
		$arQ[] = gw_sql_insert($q2, $this->sys['tbl_prefix'].'topics_phrase', 'id_topic_phrase = "'. $arV['id_topic_phrase'] .'"');
	}
	$arQ[] = gw_sql_insert($q1, $this->sys['tbl_prefix'].'topics', 'id_topic = "' . $this->gw_this['vars']['tid'] .'"');
	/* Sorting subpages */
#	$sql = sprintf('SELECT id_topic FROM `%s` WHERE id_parent = "%d" ORDER BY int_sort ASC', $this->sys['tbl_prefix'].'topics', $q1['id_parent']);
#	$arSql = $this->oDb->sqlExec($sql);
#	$i = 10;
#	for (; list($arK, $arV) = each($arSql);)
#	{
#		$arQ[] = 'UPDATE ' . $this->sys['tbl_prefix'].'topics' . '
#					SET `int_sort` = ' . $i . '
#					WHERE `id_topic` = ' . $arV['id_topic'];
#		$i += 10;
#	}
	$this->str .= postQuery($arQ, GW_ACTION.'='.GW_A_BROWSE .'&'. GW_TARGET.'='.$this->gw_this['vars'][GW_TARGET], $this->sys['isDebugQ'], 0);
}






?>