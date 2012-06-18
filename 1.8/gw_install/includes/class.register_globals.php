<?php
/**
* Class to parse incoming variables.
*  - Have a custom number of maximum nesting level.
*  - Normalizes a new line character.
* -  Fixes "Slash problem".
*  - Puts $_FILES into $var['_files'].
*  - Puts $_COOKIES into $var['_cokies'].
*  - Can create a defaul values.
* 
* Uses:
*     - CRLF
*/
if (!defined('CRLF')) { define('CRLF', "\n"); }
if (!defined('IS_CLASS_GW2_GLOBALS')) { define('IS_CLASS_GW2_GLOBALS', 1);
class tkit_register_globals {
	private $max_nesting_level = 5;
	function register($ar = array())
	{
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
			elseif (isset($_COOKIE[$v]) && ($_COOKIE[$v] != ''))
			{
				/* get values from _COOKIE */
				$tmp[$v] = $tmp['_cookie'][$v] = urldecode($_COOKIE[$v]);
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
		$this->_unset();
		return $tmp;
	}
	/* */
	function fix_newline($v, $level = 0)
	{
		if (is_array($v))
		{
			++$level;
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
			return str_replace(array("\r\n","\n","\r"), CRLF, $v);
		}
		return $v;
	}
	/* */
	function fix_xss($v, $level = 0)
	{
		if (is_array($v))
		{
			++$level;
			if ($level <= $this->max_nesting_level)
			{
				foreach ($v as $k1 => $v1)
				{
					$v[$k1] = $this->fix_xss($v[$k1], $level);
				}
			}
		}
		else
		{
			$v = preg_replace('/\0+/', '', $v);
			$v = preg_replace('/(\\\\0)+/', '', $v);
			$arb = array(
						'document.cookie' => '[removed]',
						'document.write'  => '[removed]',
						'window.location' => '[removed]',
						"Redirect\s+302"  => '[removed]'
					);
			for (reset($arb); list($k, $v2) = each($arb);)
			{
				$v = preg_replace("#".$k."#i", $v2, $v);
			}
		}
		return $v;
	}
	/**
	 * Fixes "slash problem".
	 * 2 Apr 2008: added the maximum nesting level
	 */
	function fix_slash($v, $level = 0)
	{
		if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
		{
			if (is_array($v))
			{
				++$level;
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
	/* */
	static function do_default(&$n, $v = '')
	{
		if (!isset($n))
		{
			$n = $v;
		}
		else if ($n == '')
		{
			$n = '';
		}
	}
	/* */
	static function do_alphanum(&$v)
	{
		$v = preg_replace('#[^a-zA-Z0-9_.,%\-]#', '', $v);
	}
	static function do_numeric_one(&$v)
	{
		$v = preg_replace('#[^0-9]#', '', $v);
		$v = !$v ? 1 : $v;
	}
	static function do_numeric_zero(&$v)
	{
		$v = $v + 0;
		$v = sprintf($v, '%u');
	}
	/* */
	static function _unset()
	{
		foreach (array($_GET, $_POST, $_COOKIE) as $global)
		{
			if (is_array($global))
			{
				foreach ($global as $k => $v)
				{
					global $$k;
					$$k = NULL;
				}
			}
			else
			{
				global $global;
				$$global = NULL;
			}
		}
	}
}}
?>