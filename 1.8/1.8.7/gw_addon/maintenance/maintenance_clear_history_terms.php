<?php
if (!defined('IN_GW'))
{
	die("<!-- $Id: maintenance_clear_history_terms.php 322 2008-03-01 23:27:38Z yrtimd $ -->");
}
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
/*
	Maintenance task
*/
/* */
include($sys['path_addon'].'/class.gw_addon.php');
/* */
class gw_addon_clear_history_terms extends gw_addon
{
	var $addon_name = 'clear_history_terms';
	/* Autoexec */
	function gw_addon_clear_history_terms()
	{
		$this->init_m();
	}
	/* */
	function _gw_clear()
	{
		/* Clear history of changes */
		$sql = sprintf('DELETE FROM `%s` WHERE `date_modified` < %d',
				$this->sys['tbl_prefix'].'history_terms',
				$this->sys['time_now_gmt_unix'] - ($this->sys['max_days_history_terms'] * 24) * 3600);
		$this->oDb->sqlExec($sql);
		$this->oDb->sqlExec('CHECK TABLE `'.$this->sys['tbl_prefix'].'history_terms`');
		/* Clear terms */
		$arSql = $this->oDb->sqlExec($this->oSqlQ->getQ('get-history-to-remove'));
		$arTermIds = $arQ = array();
		/* Group terms by dictionary */
		for (; list($k, $v) = each($arSql);)
		{
			$arTermIds[$v['id_dict']][] = $v['id_term'];
			unset($arSql[$k]);
		}
		for (; list($id_dict, $v) = each($arTermIds);)
		{
			$sql = 'DELETE FROM `' . $this->gw_this['ar_dict_list'][$id_dict]['tablename'] . '` WHERE id IN (' . implode(',', $v) . ')';
			$this->oDb->sqlExec($sql);
			$sql = 'DELETE FROM `' . TBL_WORDMAP . '` WHERE term_id IN (' . implode(',', $v) . ')';
			$this->oDb->sqlExec($sql);
			$sql = 'DELETE FROM `' . TBL_MAP_USER_TERM . '` WHERE term_id IN (' . implode(',', $v) . ') AND dict_id = "' . $id_dict . '"';
			$this->oDb->sqlExec($sql);
		}
	}
	/* */
	function alpha()
	{
		if ((mt_rand() % 100) < $this->sys['prbblty_tasks'])
		{
			$this->_gw_clear();
		}
	}
	/* */
	function omega()
	{
	}
}
/* */
$oM = new gw_addon_clear_history_terms;
$oM->alpha();
$oM->omega();
unset($oM);
/* end of file */
?>