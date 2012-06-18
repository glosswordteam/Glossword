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
/**
 *  $Id: class.globals.php 479 2008-06-07 05:24:07Z glossword_team $
 */
/**
 * Filter global variables
 * Requires:
 *     PHP_VERSION_INT
 *     $sys['token']
 *     CRLF
 * 2 Apr 2008: added nesting level
 */
if (!defined('IS_CLASS_GLOBALS'))
{
	define('IS_CLASS_GLOBALS', 1);

class gw_register_globals
{
	var $max_nesting_level = 10;
	function register($ar = array())
	{
		if (!is_array($ar)) { return array(); }
		global $sys;
		global $_GET, $_POST, $_FILES, $_COOKIE;
		$tmp = array();
		$tmp['_files'] = $tmp['_cookie'] = array();
		for (reset($ar); list($k, $v) = each($ar);)
		{
			if (isset($_POST[$v]) && ($_POST[$v] != ''))
			{
				/* get values from _POST */
				$tmp[$v] = $_POST[$v];
				$tmp['_method'] = 'post';
			}
			elseif (isset($_GET[$v]) && ($_GET[$v] != ''))
			{
				/* get values from _GET */
				$tmp[$v] = $_GET[$v];
			}
			elseif (isset($_COOKIE[$v.$sys['token']]) && ($_COOKIE[$v.$sys['token']] != ''))
			{
				/* get values from _COOKIE */
				$tmp['_cookie'][$v] = urldecode($_COOKIE[$v.$sys['token']]);
			}
			else
			{
				/* default */
				$tmp[$v] = '';
			}
			/* filter incoming */
			if (isset($tmp['_cookie'][$v]))
			{
				$tmp['_cookie'][$v] = $this->fix_newline($tmp['_cookie'][$v]);
				$tmp['_cookie'][$v] = $this->fix_slash($tmp['_cookie'][$v]);
			}
			else
			{
				$tmp[$v] = $this->fix_newline($tmp[$v]);
				$tmp[$v] = $this->fix_slash($tmp[$v]);
			}
			/* */
			if (isset($_FILES[$v]) && ($_FILES[$v] != ''))
			{
				/* get values from FILES */
				$tmp['_files'][$v] = $_FILES[$v];
				$tmp['_files'][$v]['name'] = $this->fix_slash($tmp['_files'][$v]['name']);
			}
		}
		return $tmp;
	}
	/** 
	 * 1.8.7: Normalizes new line character 
	 * 1.8.10: New lines replaced with constant CRLF using strstr().
	 */
	function fix_newline($v, $level = 0)
	{
		if (is_array($v))
		{
			$level++;
			if ($level <= $this->max_nesting_level)
			{
				foreach ($v as $k1 => $v1)
				{
					$v[$k1] = $this->fix_newline($v[$k1], $level);
				}
			}
		}
		else
		{
			return strtr($v, array("\r\n" => CRLF, "\n" => CRLF, "\r" => CRLF));
		}
		return $v;
	}
	/* 1.8.7: Fixes "Slash" problem */
	function fix_slash($v, $level = 0)
	{
		if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
		{
			if (is_array($v))
			{
				$level++;
				if ($level <= $this->max_nesting_level)
				{
					foreach ($v as $k1 => $v1)
					{
						$v[$k1] = $this->fix_slash($v[$k1], $level);
					}
				}
			}
			else
			{
				$v = stripslashes($v);
			}
		}
		return $v;
	}
	
	function sprintf(&$t, $format = '%d')
	{
		return sprintf($format, $t);
	}
	function do_default(&$t, $v)
	{
		$t = (trim($t) == '') ? $v : $t;
	}
	function do_numeric(&$t)
	{
		$t = $this->sprintf($t, '%d');
		$t = ($t == 0) ? 1 : $t;
	}
	function do_numeric_zero(&$t)
	{
		$t = $t+0;
		$t = $this->sprintf($t, '%u');
	}
	function do_substring(&$t, $int_limit = 1024)
	{
		$t = substr($t, 0, $int_limit);
	}
	function do_substring_specials(&$t, $int_limit = 1024)
	{
		htmlspecialchars($this->do_substring(strip_tags($t), $int_limit));
	}
}
/* Auto initialization */
$oGlobals = new gw_register_globals;
/* We don't need any global variables, really */
$ar = array_merge($_POST, $_GET, $_COOKIE, $_FILES );
for (; list($k, $v) = each($ar);)
{
	unset($$k);
}
unset($ar);
}
/* end of file */
?>