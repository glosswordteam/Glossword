<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IN_GW'))
{
	die("<!-- $Id$ -->");
}
/*
	Maintenance task
*/
/* */
include($sys['path_addon'].'/class.gw_addon.php');
/* */
class gw_addon_clear_import_sessions extends gw_addon
{
	var $addon_name = 'clear_import_sessions';
	/* Autoexec */
	function gw_addon_clear_import_sessions()
	{
		$this->init_m();
	}
	/* */
	function _gw_clear()
	{
		/* Clear import sessions */
		$sql = sprintf('DELETE FROM `%s` WHERE `date_start` < %s OR `date_end` = "0"',
				$this->sys['tbl_prefix'].'import_sessions',
				$this->sys['time_now_gmt_unix'] - ($this->sys['max_days_history_terms'] * 24) * 3600);
		$this->oDb->sqlExec($sql);
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
$oM = new gw_addon_clear_import_sessions;
$oM->alpha();
$oM->omega();
unset($oM);
/* end of file */
?>