<?php 
/**
 * © 2003-2004 Dmitry N. Shilnikov <dev at glossword dot info>
 * http://glossword.info/dev/
 */
/* --------------------------------------------------------
 * Simple HTTP-headers class
 * ----------------------------------------------------- */
if (!defined('IN_GW'))
{
	die('<!-- $Id: class.headers.php,v 1.7 2006/10/06 12:11:57 yrtimd Exp $ -->');
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