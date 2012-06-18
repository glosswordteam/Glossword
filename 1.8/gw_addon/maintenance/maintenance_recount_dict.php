<?php
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
class gw_addon_recount_dict extends gw_addon
{
	var $addon_name = 'recount_dict';
	/* Autoexec */
	function gw_addon_recount_dict()
	{
		$this->init_m();
	}
	/* */
	function _recount()
	{
		$arQ = $qDict = array();
		/* For each dictionary */
		for (reset($this->gw_this['ar_dict_list']); list($k, $arDictParam) = each($this->gw_this['ar_dict_list']);)
		{
			/* */
			$sql = 'SELECT sum(int_bytes) AS bytes FROM `' . $arDictParam['tablename'].'`';
			$arSql = $this->oDb->sqlExec($sql);
			$qDict['int_bytes'] = isset($arSql[0]['bytes']) ? $arSql[0]['bytes'] : 0;
			/* */
			$sql = 'SELECT count(*) as n FROM `' . $arDictParam['tablename'].'`
					WHERE `is_active` = "1" AND `date_created` <= ' . $this->sys['time_now_db'];
			$arSql = $this->oDb->sqlExec($sql);
			$qDict['int_terms'] = isset($arSql[0]['n']) ? $arSql[0]['n'] : 0;
			$arQ[] = gw_sql_update($qDict, TBL_DICT, "id = '".$arDictParam['id']."'");
		}
		for (; list($sqlk, $sqlv) = each($arQ);)
		{
			$this->oDb->sqlExec($sqlv);
		}
	}
	/* */
	function alpha()
	{
		if ((mt_rand() % 100) < $this->sys['prbblty_tasks'])
		{
			$this->_recount();
		}
	}
	/* */
	function omega()
	{
	}
}
/* */
$oM = new gw_addon_recount_dict;
$oM->alpha();
$oM->omega();
unset($oM);
/* end of file */
?>