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
class gw_addon_recount_user extends gw_addon
{
	var $addon_name = 'recount_user';
	/* Autoexec */
	function gw_addon_recount_user()
	{
		$this->init_m();
	}
	/* */
	function _recount()
	{
		$sql = 'SELECT user_id, count(*) AS n
				FROM `'.$this->sys['tbl_prefix'].'map_user_to_term`
				GROUP BY user_id
		';
		$arSql = $this->oDb->sqlExec($sql);
		$arQ = array();
		while (list($k, $arV) = each($arSql))
		{
			$arQ[] = gw_sql_update(array('int_items' => $arV['n']), TBL_USERS, "id_user = '".$arV['user_id']."'");
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
$oM = new gw_addon_recount_user;
$oM->alpha();
$oM->omega();
unset($oM);
/* end of file */
?>