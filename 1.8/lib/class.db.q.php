<?php
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008-2012 Glossword.biz team <team at glossword dot biz>
 *  © 2002-2008 Dmitry N. Shilnikov
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
if (!defined('IS_CLASS_DBQ'))
{
	define('IS_CLASS_DBQ', 1);

class gw_query_storage {

	var $str_suffix = '';
	var $is_loaded = 0;
	var $arQ = array();
	/* */
	function set_suffix($v)
	{
		 $this->str_suffix = $v;
	}
	/* */
	function q_import($ar = array())
	{
		global $sys;
		$arSql = array();
		if ($this->is_loaded) { return $this->arQ; }
		while (is_array($ar) && list($k, $v) = each($ar))
		{
			if (file_exists($sys['path_include']. '/' . $v . $this->str_suffix . '.php'))
			{
				include($sys['path_include']. '/' . $v . $this->str_suffix . '.php');
				$arSql = array_merge($arSql, $tmp['ar_queries']);
			}
		}
		$this->is_loaded = 1;
		$this->arQ =& $arSql;
		return $arSql;
	}
	/* */
	function setCustomQ()
	{
		return array();
	}
	/* */
	function setQ()
	{
		$arSql = $this->q_import( array('query_storage_global') );
		return $arSql;
	}
	/**/
	function getQ()
	{
		$args = func_get_args();
		$ar = array();
		/* 8 parameters allowed */
		/* See also `return sprintf' at the end of the function */
		for ($i = 0; $i <= 8; $i++)
		{
			$ar[] = isset($args[$i]) ? $args[$i] : '';
		}
		$arSql = array_merge( $this->setQ(), $this->setCustomQ() );
		if (isset($arSql[$ar[0]]))
		{
			$arSql[$ar[0]] = preg_replace("/[ |\t]{2,}/", ' ', $arSql[$ar[0]]);
			return sprintf( $arSql[$ar[0]], $ar[1], $ar[2], $ar[3], $ar[4], $ar[5], $ar[6], $ar[7], $ar[8] );
		}
	}
} /* end of class */
}

?>