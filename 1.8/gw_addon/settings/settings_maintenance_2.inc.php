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
	die('<!-- $Id: settings_maintenance_2.inc.php 492 2008-06-13 22:58:27Z glossword_team $ -->');
}
/* Included from $oAddonAdm->alpha(); */



/* Script functions below */
function gw_html_contents()
{
	global $is, $arStatus, $oL, $sys, $gw_this;
	$str = '';
	
	$arKeyUnused = getUnusedKeywords();
	$arWordlist = getTableInfo( $sys['tbl_prefix'].'wordlist' );
	$intKeyUnused = sizeof($arKeyUnused);
	$arStatus[] = array($oL->m('1010'), sprintf('<strong>%s</strong>', number_format($arWordlist['Rows'], 0, '', ' ')));
	$arStatus[] = array($oL->m('1011'), sprintf('<strong class="red">%s</strong>', number_format($intKeyUnused, 0, '', ' ')));
	$arStatus[] = array($oL->m('1012'), sprintf('<strong class="green">%s</strong>', number_format(($arWordlist['Rows']-$intKeyUnused), 0, '', ' ')));
	$intKbWordlist = ($arWordlist['Data_free'] + $arWordlist['Data_length'] + $arWordlist['Index_length']);
	$arStatus[] = array($oL->m('1013'), sprintf('<strong>%s</strong>', number_format($intKbWordlist, 0, '', ' ')));
	if ($intKeyUnused > 0)
	{
		$arStatus[] = array($oL->m('1014'), sprintf('<strong class="red">%s</strong>',
			number_format((($intKbWordlist / $arWordlist['Rows']) * $intKeyUnused), 0, '', ' '))
		);
		$arStatus[] = array($oL->m('1015'), sprintf('<strong class="green">%s</strong>',
			number_format((($intKbWordlist / $arWordlist['Rows']) * ($arWordlist['Rows']-$intKeyUnused)), 0, '', ' '))
		);
		/* Link to confirm */
		if ($is == '')
		{
			$arStatus[] = array('&#160;');
			$arStatus[] = array(sprintf('<strong class="xw">%s</strong>', $oL->m('1016')), sprintf('<span class="actions-third"><a href="%s">%s</a></span>',
			append_url($sys['page_admin'].'?'.GW_ACTION.'='.$gw_this['vars'][GW_ACTION].'&w1='.$gw_this['vars']['w1'].'&'.GW_TARGET.'='.$gw_this['vars'][GW_TARGET].'&isConfirm=1'),
			$oL->m('1183')));
			$arStatus[] = array('&#160;');
		}
	}
	else
	{
		$arStatus[] = array('&#160;');
		$arStatus[] = array($oL->m('1017'), '');
		$arStatus[] = array('&#160;');
	}
	$str .= '<div class="margin-inside xu">';
	$str .= $oL->m('1009');
	$str .= html_array_to_table_multi($arStatus, 0, array('65%', '35%'));
	$str .= '</div>';
	return $str;
}
/* */
function gw_optimize_keywords()
{
	global $id, $arStatus, $oDb, $oL;
	$arKeyUnused = getUnusedKeywords();
	$sql_o = 'DELETE FROM '.TBL_WORDLIST.' WHERE word_id IN (\'%s\')';
	$arKeyId = array();
	for (reset($arKeyUnused); list($k, $v) = each($arKeyUnused);)
	{
		$arKeyId[] = $v['word_id'];
	}
	if (!empty($arKeyId))
	{
		$sql = sprintf($sql_o, implode("', '", $arKeyId));
		if ($oDb->sqlExec($sql, '', 0))
		{
			$arStatus[] = array(sprintf('<strong class="red">%s</strong>', $oL->m('1018')), '');
		}
	}
	$oDb->sqlExec('CHECK TABLE `'.TBL_WORDMAP.'`');
	$oDb->sqlExec('CHECK TABLE `'.TBL_WORDLIST.'`');
	$oDb->sqlExec('ALTER TABLE `'. TBL_WORDLIST .'` PACK_KEYS=1 CHECKSUM=0 DELAY_KEY_WRITE=1');
	$oDb->sqlExec('ALTER TABLE `'. TBL_WORDMAP .'` PACK_KEYS=1 CHECKSUM=0 DELAY_KEY_WRITE=1');
	$oDb->sqlExec('OPTIMIZE TABLE `'.TBL_WORDLIST.'`');
	$oDb->sqlExec('OPTIMIZE TABLE `'.TBL_WORDMAP.'`');
	$arStatus[] = array(sprintf('<strong class="red">%s</strong>', $oL->m('1019')), '');
}
/* */
function getUnusedKeywords()
{
	global $oDb, $gw_this;
	$sys['max_wordmap_clear'] = 5000;
	/* 1.7.0: clear wordmap for terms which are not in dictionaries */
	for (reset($gw_this['ar_dict_list'] ); list($k, $v) = each($gw_this['ar_dict_list'] );)
	{
		$sql = 'SELECT wm.term_id FROM `'.TBL_WORDMAP.'` as wm
				LEFT JOIN `'.$v['tablename'].'` as t
		  		ON t.id = wm.term_id
				WHERE wm.dict_id = '.$v['id'].'
				AND t.id IS NULL
				LIMIT '.$sys['max_wordmap_clear'].'
				';
		$arSql = $oDb->sqlExec($sql, '', 0);
		$ar_term_id = array();
		for (reset($arSql); list($k2, $v2) = each($arSql);)
		{
			$ar_term_id[] = $v2['term_id'];
		}
		if (!empty($ar_term_id))
		{
			$sql2 = 'DELETE FROM `'.TBL_WORDMAP.'` WHERE term_id IN ('.implode(',', $ar_term_id).')';
			$oDb->sqlExec($sql2, '', 0);
		}
	}
	/* */
	$sql = 'SELECT '.TBL_WORDLIST.'.word_id
			FROM `'.TBL_WORDLIST.'`
			LEFT JOIN `'.TBL_WORDMAP.'`
			ON '.TBL_WORDLIST.'.word_id = '.TBL_WORDMAP.'.word_id
			WHERE '.TBL_WORDMAP.'.word_id IS NULL
			ORDER BY '.TBL_WORDLIST.'.word_text';
	$arSql = $oDb->sqlExec($sql, '', 0);
	return $arSql;
}
/* Script action below */

$this->str .= getFormTitleNav($this->oL->m(1008));
if ($this->gw_this['vars']['isConfirm'] == '1')
{
	$this->str .= gw_optimize_keywords();
}
$this->str .= gw_html_contents();

?>