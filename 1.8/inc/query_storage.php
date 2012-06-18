<?php
/**
 *  Glossword - glossary compiler (http://glossword.info/)
 *  © 2002-2007 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
$sys['class_queries'] = 'gwtk_query_storage';
class gwtk_query_storage extends gw_query_storage
{
	/* */
	function setQ()
	{
		$arSql = $this->q_import(array('query_storage_global', 'query_storage_sess'));
		return $arSql;
	}
	/* */
	function setAddonQ($ar)
	{
		global $gw_this, $sys;
		$arSql = array();
		while (is_array($ar) && list($k, $v) = each($ar))
		{
			if (file_exists($sys['path_addon'] . '/' . $gw_this['vars'][GW_TARGET] . '/' . $v.$this->str_suffix . '.php'))
			{
				include($sys['path_addon'] . '/' . $gw_this['vars'][GW_TARGET] . '/' . $v.$this->str_suffix . '.php');
				$arSql = array_merge($arSql, $tmp['ar_queries']);
			}
		}
		$this->is_loaded = 1;
		$this->arQ = array_merge($this->arQ, $arSql);
	}
}
/* end of file */
?>