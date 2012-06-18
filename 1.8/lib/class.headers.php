<?php 
/**
 *  Glossword - glossary compiler (http://glossword.biz/)
 *  © 2008 Glossword.biz team
 *  © 2002-2004 Dmitry N. Shilnikov <dev at glossword dot info>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  (see `http://creativecommons.org/licenses/GPL/2.0/' for details)
 */
/* --------------------------------------------------------
 * Simple HTTP-headers class
 * ----------------------------------------------------- */
if (!defined('IN_GW'))
{
	die('<!-- $Id: class.headers.php 470 2008-05-14 16:25:33Z yrtimd $ -->');
}
/* ----------------------------------------------------- */
if (!defined('IS_CLASS_HEADERS'))
{
	define('IS_CLASS_HEADERS', 1);

class gw_headers
{
	var $is_debug = GW_DEBUG_HTTP;
	var $arH = array();
	var $arHText = array();
	
	function add($str)
	{
		if ($str != '')
		{
			array_push($this->arH, $str);
		}
	}
	function output()
	{
		for (reset($this->arH); list($k, $v) = each($this->arH);)
		{
			@header($v);
			if ($this->is_debug)
			{
				$this->arHText[]  = $v;
			}
			else
			{
			}
		}
	}
	function get()
	{
		return $this->arH;
	}
} /* end of class */
/* Autostart */
$oHdr = new gw_headers;
}

?>