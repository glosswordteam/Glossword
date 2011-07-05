<?php
/**
 * $Id$
 * Class to parse incoming variables.
 * -  Fixes "Slash problem".
 *  - Normalizes a new line character.
 *  - Creates default values.
 *  - Puts $_FILES into $var['_files'].
 *  - Puts $_COOKIES into $var['_cokies'].
 *  - Has a custom number of maximum nesting levels.
 *  - 28 Sep 2009 Handles arrays.
 *  - 11 jan 2010 Added $is_unset to unset everything.
 *
 * Uses:
 *     CRLF, array_merge_clobber()
 */
if (!defined('IS_CLASS_GLOBALS')) { define('IS_CLASS_GLOBALS', 1);
class site_register_globals {
	private $max_nesting_level = 5;
	public $is_unset = 0;
	public function register($ar = array())
	{
		$tmp['_files'] = $tmp['_cookie'] = array();
		foreach ($ar AS $v)
		{
			/* Cookies first */
			if (isset($_COOKIE[$v]) && ($_COOKIE[$v] != ''))
			{
				/* Get values from _COOKIE */
				if (is_string($_COOKIE[$v]))
				{
					$tmp[$v] = $tmp['_cookie'][$v] = urldecode($_COOKIE[$v]);
				}
				else
				{
					foreach ($_COOKIE[$v] as $ck => $cv)
					{
						if (is_string($cv))
						{
							$tmp[$v][$ck] = $tmp['_cookie'][$v][$ck] = urldecode($cv);
						}
					}
				}
			}
			/* _POST and _GET overwrites cookies */
			if (isset($_POST[$v]) && ($_POST[$v] != ''))
			{
				/* Get values from _POST */
				if (is_string($_POST[$v]) || !isset($tmp[$v]))
				{
					$tmp[$v] = $_POST[$v];
				}
				else
				{
					$tmp[$v] = array_merge_clobber($tmp[$v], $_POST[$v]);
				}
				$tmp['_method'] = 'post';
			}
			elseif (isset($_GET[$v]) && ($_GET[$v] != ''))
			{
				/* Get values from _GET */
				if (is_string($_GET[$v]) || !isset($tmp[$v]))
				{
					$tmp[$v] = $_GET[$v];
				}
				else
				{
					$tmp[$v] = array_merge_clobber($tmp[$v], $_GET[$v]);
				}
			}
			else
			{
				/* Default value is '' */
				if (!isset($tmp[$v]))
				{
					$tmp[$v] = '';
				}
			}
			/* Filter incoming variables */
			if (isset($tmp['_cookie'][$v]))
			{
				#$tmp['_cookie'][$v] = $this->fix_newline($tmp['_cookie'][$v]);
				#$tmp['_cookie'][$v] = $this->fix_slash($tmp['_cookie'][$v]);
			}
			else
			{
				$tmp[$v] = $this->fix_newline($tmp[$v]);
				$tmp[$v] = $this->fix_slash($tmp[$v]);
			}
			/* Get values from _FILES */
			if (isset($_FILES[$v]) && ($_FILES[$v] != ''))
			{
				/* Get values from _FILES */
				$tmp['_files'][$v] = $_FILES[$v];
				$tmp['_files'][$v]['name'] = $this->fix_slash($tmp['_files'][$v]['name']);
			}
		}
		if ($this->is_unset)
		{
			$this->_unset();
		}
		return $tmp;
	}
	/* */
	public function fix_newline($v, $level = 0)
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
	public function fix_xss($v, $level = 0)
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
	public function fix_slash($v, $level = 0)
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
	public static function do_default(&$n, $v = '')
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
	public static function do_alphanum(&$v)
	{
		$v = preg_replace('#[^a-zA-Z0-9.,%-]#', '', $v);
	}
	public static function do_numeric_one(&$v)
	{
		$v = preg_replace('#[^0-9]#', '', $v);
		$v = !$v ? 1 : $v;
	}
	/* */
	public static function do_numeric_zero(&$v)
	{
		$v = $v + 0;
		$v = sprintf($v, '%u');
	}
	/* Converts in.date,opt.3 => array('in' => 'date', 'opt' => '3'); */
	public static function subparam($s)
	{
		$ar = explode( ',', $s );
		$r = array();
		foreach ($ar as $v)
		{
			$v = trim( $v );
			/* in.date */
			$arPairs = explode( '.', $v );
			/* in. */
			if ( !isset( $arPairs[1] ) )
			{
				$arPairs[1] = '';
			}
			$ar_v = $arPairs;
			unset( $ar_v[0] );
			/* in.__date.and.time__ */
			$r[$arPairs[0]] = implode( '.', $ar_v );
			/* A custom urldecode */
			$r[$arPairs[0]] = str_replace( array( '%2C', '%2F', '%2B' ), array( ',', '/', '+' ), $r[$arPairs[0]] );
		}
		return $r;
	}
	/* Converts in.date,opt.3 => array('in.date', 'opt.3'); */
	public static function subparam_single($s)
	{
		$ar = explode(',', $s);
		$r = array();
		foreach ($ar as $k => $v)
		{
			$r[$k] = trim($v);
		}
		return $r;
	}
	/* */
	private static function _unset()
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