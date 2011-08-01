<?php
/**
 * © 2004 Dmitry N. Shilnikov <dev at glossword dot info>
 */
$sys['class_queries'] = 'gwtk_query_storage';
class gwtk_query_storage extends gw_query_storage
{
	function q_import($ar = array())
	{
		global $sys;
		if ($this->is_loaded) { return $this->arQ; }
		$arSql = array();
		while (is_array($ar) && list($k, $v) = each($ar))
		{
			if (file_exists($sys['path_install']. '/' . $v . $this->str_suffix . '.php'))
			{
				include($sys['path_install']. '/' . $v . $this->str_suffix . '.php');
				$arSql = array_merge($arSql, $tmp['ar_queries']);
			}
		}
		$this->is_loaded = 1;
		$this->arQ =& $arSql;
		return $arSql;
	}
	/* */
	function setQ()
	{
		$arSql = $this->q_import(array('query_install'));
		return $arSql;
	}
}

?>