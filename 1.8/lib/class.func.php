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
/* --------------------------------------------------------
 * Library functions for daily use
 * ----------------------------------------------------- */
	$tmp['mtime'] = explode(' ', microtime());
	$tmp['start_time'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
/* --------------------------------------------------------
 * Functions that must work without class initialization
 * ----------------------------------------------------- */
/**
 * Includes a php-file.
 * Replacement for include_once(), require_once().
 * Restructions on access to variables apply.
 * Test results for 1000 included files less than 65 KB:
 *     0.004 gw2_include_once()
 *     0.429 include_once()
 *     0.434 require_once()
 *     0.569 include()
 *     0.574 require()
 * Test results for 1000 included files more than 65 KB:
 *     0.005 gw2_include_once()
 *     0.444 include_once()
 *     0.452 require_once()
 *    23.645 include()
 *    24.791 require()
 * @param  string  Path to file
 */
function gw2_include_once($f)
{
	static $ar = array();
	if (!array_key_exists(($rf = crc32($f)), $ar))
	{
		$ar[$rf] = true;
		if (file_exists($f))
		{
			include_once($f);
#			eval('? >'. file_get_contents($f) .'< ? php');
		}
	}
}
/**
 * Shuffles an array. Replacement for shuffle().
 * @usage: usort($arB, 'gw_rand_cmp');
 * @return  int     Random value
 */
function gw_rand_cmp( $a, $b )
{
	return round( (rand(0, 2) - 1) );
}
/**
 * Replacement for print_r()
 *
 * @param   string  $a Any string, object, or array.
 * @param   string  $c Additional marker for better visual display. Try "__FILE__"
 */
function prn_r($a, $c = '')
{
	if (is_array($a))
	{
		ksort($a);
		$a = htmlspecialchars_ltgt($a);
	}
	elseif (is_object($a) || is_string($a))
	{
		$a = htmlspecialchars_ltgt($a);
	}
	/* Set font size in pixels because function can be called from various places */
	print '<pre style="text-align:left;color:#000;background:#FFF;font: 14px/16px Consolas,\'Courier New\',monospace">';
	if ($c)
	{
		print '===&gt; <strong>'.$c."</strong>\n";
	}
	/* Placing the output into buffer */
	ob_start();
	print_r( $a );
	$b = ob_get_clean();
	/* compress indents */
	$b = preg_replace("/(^)?(    )([\(|\)|\[])?/", "  \\3", $b);
	/* highlight Array and Object */
	$b = str_replace('] => Array', '] => <span style="color:#080">Array</span>', $b);
	$b = str_replace('] => Object', '] => <span style="color:#080">Object</span>', $b);
	/* highlight numeric positive and negative keys */
	$b = preg_replace("/\[(-)?(\d+)\] =>/", '<span style="color:#888">&#91;<span style="color:#00C">\\1\\2</span>] =></span>', $b);
	$b = preg_replace("/\[(.*)\] =>/", '<span style="color:#888">&#91;<span style="color:#C50">\\1</span>] =></span>', $b);
	print $b;
	if ($c)
	{
		print '&lt;===';
	}
	print '</pre>';
}
/**
 * Makes SQL-queries more readable
 *
 * @param   string  $str Any string or array.
 */
function gw_highlight_sql($s)
{
	if (is_array($s))
	{
		/* can't use array_walk() on byself with reference */
		for (reset($s); list($k, $v) = each($s);)
		{
			$s[$k] = gw_highlight_sql($v);
		}
		return $s;
	}
	elseif (is_string($s))
	{
		$s = str_replace('(', '<b>(</b>', $s);
		$s = str_replace(')', '<b>)</b>', $s);
		$s = str_replace('&lt;', '<strong>&lt;</strong>', $s);
		$s = str_replace('&gt;', '<strong>&gt;</strong>', $s);
		$s = str_replace('&lt;/', '<strong>&lt;/</strong>', $s);
		$s = str_replace(',', '<i>,</i>', $s);
	}
	return $s;
}
/**
 * Converts a few characters only, recursive. Faster than htmlspecialchars().
 *
 * @param   string  $str Any string or array.
 * @return  string  Transformed string.
 */
function htmlspecialchars_ltgt($s)
{
	if (is_array($s))
	{
		/* can't use array_walk() on byself with reference */
		for (reset($s); list($k, $v) = each($s);)
		{
			$s[$k] = htmlspecialchars_ltgt($v);
		}
		return $s;
	}
	elseif (is_object($s))
	{
		$ar = get_class_vars(get_class($s));
		for (reset($ar); list($k, $v) = each($ar);)
		{
			$ar[$k] = htmlspecialchars_ltgt($s->$k);
		}
		return $ar;
	}
	elseif (is_string($s))
	{
		return str_replace(array('&','<','>','{','[','"'), array('&amp;','&lt;','&gt;','&#123;','&#091;','&quot;'), $s);
	}
	return $s;
}
function unhtmlspecialchars_ltgt($s)
{
	if (is_array($s))
	{
		/* can't use array_walk() on byself with reference */
		for (reset($s); list($k, $v) = each($s);)
		{
			$s[$k] = unhtmlspecialchars_ltgt($v);
		}
		return $s;
	}
	elseif (is_object($s))
	{
		$ar = get_class_vars(get_class($s));
		for (reset($ar); list($k, $v) = each($ar);)
		{
			$ar[$k] = unhtmlspecialchars_ltgt($s->$k);
		}
		return $ar;
	}
	elseif (is_string($s))
	{
		return str_replace(array('&amp;','&lt;','&gt;','&#123;','&#091;','&quot;'), array('&','<','>','{','[','"'), $s);
	}
	return $s;
}
/* */
function gw_htmlspecialamp($s)
{
	if (!is_string($s)){ return $s; }
	$s = str_replace('&', '&amp;', $s);
	$s = str_replace('"', '&quot;', $s);
	$s = str_replace('\'', '&#039;', $s);
	$s = preg_replace('/&amp;#([0-9]+);/', '&#\\1;', $s);
#	$s = preg_replace('/&amp;([a-z]+);/', '&\\1;', $s);
	return $s;
}
function gw_unhtmlspecialamp($s)
{
	if (!is_string($s)){ return $s; }
	$s = str_replace('&amp;', '&', $s);
	$s = str_replace('&quot;', '"', $s);
	$s = str_replace('&AMP;', '&', $s);
	$s = str_replace('&QUOT;', '"', $s);
	$s = str_replace('&#039;', '\'', $s);
	return $s;
}
/**
 * Fixes 'slash problem', recursive, calls by reference
 *
 * @param   string  $str Any string or array.
 * @param   string  $type Type of quotes to check. [ runtime | gpc ]
 * @param   string  $mode Where to check for quotes. [ php | sql ], 05 may 2003
 */
function gw_fixslash(&$str, $type = 'gpc', $mode = 'php')
{
	$gpc = false;
	$runtime = false;
	if ($type == 'gpc')
	{
		$gpc = true;
	}
	elseif ($type == 'runtime')
	{
		$runtime = true;
	}
	if ($str != '')
	{
		if (is_array($str) || is_object($str))
		{
			for (reset($str); list($k, $v) = each($str);)
			{
				gw_fixslash($str[$k], $type, $mode);
			}
			reset($str);
		}
		else
		{
			if ($gpc && function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
			{
				$str = gw_stripslashes($str);
			}
			elseif ($runtime && function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime() )
			{
				$str = gw_stripslashes($str);
			}
			else
			{
				$str = gw_stripslashes($str, 'light');
			}
			$isFirst = true;
		}
	}
}
/**
 * Replacement for addslashes()
 *
 * @param   string  $str String to convert
 * @param   string  $mode What mode to use to add slashes.
 *                        [ hard - internal by PHP | light - custom ], 06 may 2003
 * @return  string  Slashed string
 */
function gw_addslashes($str, $mode = 'hard')
{
	if ($mode == 'hard')
	{
		$str = addslashes($str);
	}
	$str = str_replace('_', '\\_', $str);
	$str = str_replace('%', '\\%', $str);
	return $str;
}
/**
 * Replacement for stripslashes()
 *
 * @param   string  $str String to convert
 * @param   string  $mode What mode to use to strip slashes.
 *                        [ hard - internal by PHP | light - custom ], 06 may 2003
 * @return  string  Stripped slashes string
 */
function gw_stripslashes($str, $mode = 'hard')
{
	if (!is_string($str)) { return $str; }
	if ($mode == 'hard')
	{
		$str = stripslashes($str);
	}
	$str = str_replace('\\_', '_', $str);
	$str = str_replace('\\%', '%', $str);
	if (ini_get('magic_quotes_sybase') && stripslashes("''") == "''")
	{
		$str = str_replace('\'\'', '\'', $str);
	}
	return $str;
}
/* */
function gw_stripslashes_array(&$ar)
{
	if (is_array($ar) || is_object($ar))
	{
		for (reset($ar); list($k, $v) = each($ar);)
		{
			$ar[$k] = gw_stripslashes($v);
		}
		reset($ar);
	}
}
/**
 * Every value for SQL-query should be passed through this function
 */
function gw_text_sql($t)
{
	if (is_array($t) || is_object($t))
	{
		array_walk($t, 'gw_text_sql');
	}
	else
	{
		$t = gw_addslashes($t);
		/* when magic_quotes_sybase is ON
		   it completely overrides magic_quotes_gpc
		   but this function should add slashes anyway */
		if (ini_get('magic_quotes_sybase'))
		{
			$t = str_replace("''", "'", $t);
			$t = str_replace('\\_', '_', $t);
			$t = str_replace('\\%', '%"', $t);
			$t = str_replace('\\', '\\\\', $t);
			$t = str_replace('\'', '\\\'', $t);
			$t = str_replace('"', '\\"', $t);
			$t = str_replace('_', '\\_', $t);
			$t = str_replace('%', '\\%"', $t);
		}
	}
	return $t;
}
/**
 * Depreciated.
 * 
 * Normalizes new line character. Recursive, calls by reference.
 * Note: Windows - CRLF, *nix - LF, Mac - CR
 *
 * @param string $str String to normalize
 */
function gw_fix_newline(&$t)
{
	if ($t == ''){ return; }
	if (is_array($t) || is_object($t))
	{
		array_walk($t, 'gw_fix_newline');
	}
	else
	{
		/* parsing 10 KB: preg_replace = 0.021457, str_replace = 0.000364 */
		$t = str_replace("\r\n", "\x01", $t);
		$t = str_replace("\n", "\x01", $t);
		$t = str_replace("\r", "\x01", $t);
		$t = str_replace("\x01", CRLF, $t);
	}
}
/**
 * Safely redirects to another location. Replacement for header()
 *
 * @param string $url Resourse locator name
 * @param int    $isDebug [ 0 - silent | 1 - do not redirect and print URL ]
 */
function gwtk_header($url, $isDebug = 0, $fromfile = '', $fromline = '')
{
	global $db;
	/* fixes */
	$url = str_replace("&amp;", "&", $url);
	if (!empty($db)){ $db->close(); }
	$filename = $linenum = 0;
	if ($isDebug || headers_sent($filename, $linenum))
	{
		if ($filename)
		{
			print 'Headers already sent in '.$filename.' on line '. $linenum.'<br />';
		}
		print 'Location:<br />' . sprintf('<a href="%s">%s</a><br />%s <b>%s</b>', $url, $url, $fromfile, $fromline);
		exit;
	}
	if (@preg_match('/Microsoft|WebSTAR|Xitami/', getenv('SERVER_SOFTWARE')))
	{
		exit( header('Refresh: 0; URL=' . $url) );
	}
	/* redirect */
	if (!preg_match("/cgi/", PHP_SAPI))
	{
		@header('HTTP/1.1 301 Moved Permanently');
	}
	header('Location: ' . $url);
	print '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
	print '<HTML><HEAD>';
	print '<TITLE>302 Found</TITLE>';
	print '</HEAD><BODY>';
	print '<H1>Found</H1>';
	print 'The document has moved <a href="'.$url.'">here</a>.<p>';
	print '</BODY></HTML>';
	exit;
}
/**
 * Detects user IP
 *
 * @return  string   IP-address
 */
function gwGetRemoteIp()
{
	$HTTP_X_FW = getenv("HTTP_X_FORWARDED_FOR");
	$HTTP_RA = getenv("REMOTE_ADDR");
	$arIana = array("127.0.", "192.168.", "1.", "0.", "10.", "172.16.", "224.", "240.");
	if ($HTTP_X_FW != '')
	{
		for (reset($arIana); list($k, $v) = each($arIana);) // check values
		{
			if (preg_match("/^" . $v . "/", $HTTP_X_FW) ||
				!preg_match("/^([0-9]{1,3}\.){3,3}[0-9]{1,3}$/", $HTTP_X_FW))
			{
				return $HTTP_RA;
			}
		}
		$HTTP_RA = $HTTP_X_FW;
	}
	if (HTTP_HOST == $HTTP_RA)
	{
		$HTTP_RA = '127.0.0.1';
	}
	return $HTTP_RA;
}

/* Generates a hash value */
if (!function_exists('hash'))
{
	function hash($t, $s, $raw_output = 0)
	{
		if ($t == 'md5') { return(md5($s)); }
		if ($t == 'sha1') { return(sha1($s)); }
		if ($t == 'crc32') { return(crc32($s)); }
	}
}


/* --------------------------------------------------------
 * Other useful functions
 * ----------------------------------------------------- */
class gw_functions {


	function js_addslashes($t)
	{
		return str_replace(array('\\', '\'', "\n", "\r") , array('\\\\', "\\'","\\n", "\\r") , $t);
	}
	/* */
	function text_htmlspecialchars($t)
	{
		return str_replace(array('&', '<', '>', '"'), array('&amp;', '&lt;', '&gt;', '&quot;'), $t);
	}
	/* */
	function text_unhtmlspecialchars($t)
	{
		return str_replace(array('&lt;', '&gt;', '&quot;', '&amp;'), array('<', '>', '"', '&'), $t);
	}
	/**
	 *
	 */
	function text_crc_unsigned($t, $pass = '')
	{
		return sprintf("%010u", crc32($pass.$t));
	}
	/**
	 * Convert HTML-code into Javascript using document.write()
	 *
	 * @param   string Text to output
	 * @return  Javascript code
	 */
	function text_html2js($t)
	{
		$t = '<script type="text/javascript">/*<![CDATA[*/'
			. 'document.write(\''
			. str_replace("'", "\'", $t)
			. '\');/*]]>*/</script>';
		return $t;
	}
	/**
	 * Generates random string. Better characters` strength,
	 * two groups of illegal symbols excluded.
	 *
	 * @param    int    $maxchar  Maximum generated string length
	 * @param    int    $nChar    Character set, 22 Oct 2003, n = numbers, uc = uppercase, lc = lowercase
	 *                            [0 = all | 1 - n | 2 - lc | 3 - uc | 4 - n+lc | 5 - lc+uc ]
	 * @param    string $first    First character for returned string
	 * @return   string Generated text
	 */
	function text_make_uid($maxchar = 8,  $nChar = 0, $first = '')
	{
		/* Exclude bad symbols. */
		/* 1st bad group: 0, 1, l, I */
		/* 2nd bad group: a, c, e, o, p, x, A, C, E, H, O, K, M, P, X */
		$str = "";
		$charN = '23456789';
		$charL = 'bdfghijkmnqrstuvwyz';
		$charU = 'QWRYUSDFGJLZVN';
		$charN = ($nChar == 1)
				? $charN
				: (($nChar == 2) ? $charL
					: (($nChar == 3) ? $charU
					: ($nChar == 4) ? $charN.$charL
					: ($nChar == 5) ? $charL.$charU
					: $charN.$charL.$charU)
				);
		$len = strlen($charN);
		mt_srand( (double) microtime()*1000000);
		for ($i = 0; $i < $maxchar; $i++)
		{
			$sed = mt_rand(0, $len-1);
			$str .= $charN[$sed];
		}
		$str = $first . substr($str, 0, strlen($str) - strlen($first));
		return $str;
	}

	/**
	 * Returns correct number format in HTML-code.
	 * For example, English notation:
	 * 1,234.56
	 * French (also Russian and many others) notation:
	 * 1 234,56
	 * This function also fixes problem with HTML-code
	 * occured by space in every group of thousands (French notation).
	 *
	 * @param   integer $int   numbers
	 * @param   integer $dec   decimals
	 * @return  string  complete HTML-code
	 */
	function number_format($int, $dec = 0, $ar = array('decimal_separator'=> '.', 'thousands_separator'=> ' '))
	{
		return str_replace(' ', '&#160;',
					number_format($int, $dec, $ar['decimal_separator'], $ar['thousands_separator'] )
				);
	}

	/**
	 * Get a random number
	 * @return  float  Random number
	 */
	function make_seed()
	{
		list($usec, $sec) = explode(' ', microtime());
		return (float) $sec + ((float) $usec * 100000);
	}

	/**
	 * Executes php-code.
	 *
	 * @param   string  $filename Full path to filename
	 * @param   int     $is_db_restart Re-connect to database. Useful when included script connects to another database.
	 * @return  string  File results
	 */
	function file_exe_contents($filename, $is_db_restart = 1)
	{
		$str = '';
		if (file_exists($filename))
		{
			ob_start();
			include($filename);
			$str_return = ob_get_contents();
			ob_end_clean();
			if ($is_db_restart)
			{
				global $oDb;
				$oDb = new gwtkDb;
			}
			return $str_return;
		}
		else
		{
			return '[loadfile: file '. $filename . ' does not exist]';
		}
	}

	/**
	 * Get file contents. Binary and fail safe.
	 *
	 * @param   string  $filename Full path to filename
	 * @return  string  File contents
	 */
	function file_get_contents($filename)
	{
		if (!file_exists($filename))
		{
			return '[file_get_contents: file '. $filename . ' does not exist]';
		}
		if (function_exists('file_get_contents')) /* PHP4 CVS only */
		{
			$str = file_get_contents($filename);
		}
		else
		{
			/* file() is binary safe from PHP 4.3.0
			faster: $str = implode('', file($filename));
			*/
			$fd = fopen($filename, "rb");
			$str = fread($fd, filesize($filename));
			fclose($fd);
		}
		if ($str == '')
		{
			return '[loadfile: ' . $filename. ' is empty]';
		}
		if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime()) /* remove slashes, 23 march 2002 */
		{
			$str = stripslashes($str);
		}
		return $str;
	}
	/**
	 * Put contents into a file. Binary and fail safe.
	 *
	 * @param   string  $filename Full path to filename
	 * @param   string  $content File contents
	 * @param   string  $mode [ w = write new file (default) | a = append ]
	 * @return  TRUE if success, FALSE otherwise
	 */
	function file_put_contents($filename, $content, $mode = "w")
	{
		$filename = str_replace('\\', '/', $filename);
		/* new file */
		if (!file_exists($filename))
		{
			/* check & create directories first */
			$arParts = explode('/', $filename);
			$intParts = (sizeof($arParts) - 1);
			$d = '';
			for ($i = 0; $i < $intParts; $i++)
			{
				$d .= $arParts[$i] . '/';
				if (is_dir($d))
				{
					continue;
				}
				else
				{
					$oldumask = umask(0);
					@mkdir($d, 0777);
					@chmod($d, 0777);
					umask($oldumask);
				}
			}
			/* Nothing to write */
			if ($content == '')
			{
				return true;
			}
			/* Write to file */
			$oldumask = umask(0002);
			$fp = @fopen($filename, "wb");
			@chmod($filename, 0777);
			if ($fp)
			{
				fputs($fp, $content);
			}
			else
			{
				return false;
			}
			umask($oldumask);
			fclose($fp);
		}
		else
		{
			/* Append to file */
			/* note: binary mode is transparent */
			if ($fp = @fopen($filename, $mode.'b'))
			{
				$is_allow = flock($fp, 2); /* lock for writing & reading */
				if ($is_allow)
				{
					fputs($fp, $content, strlen($content));
				}
				flock($fp, 3); /* unlock */
				fclose($fp);
			}
			else
			{
				return false;
			}
		}
		return true;
	}
	/**
	 * Removes file from disk
	 */
	function file_remove_f($filename)
	{
		if (file_exists($filename) && is_file($filename) && unlink($filename))
		{
			return true;
		}
		return false;
	}
	/**
	 * Makes string wrapped, multibyte.
	 * 1999
	 * 31 march 2003
	 * 1 Nov 2005
	 * 4 Apr 2008 - fixes for the end of lines
	 *
	 * @param   string  $str A string to wrap
	 * @param   int     $len Maximum length, characters
	 * @param   string  $d Delimiter, default is "\n"
	 * @param   int     $isBinary [ 0 - off | 1 - use binary-safe convertion ]
	 * @return  string  Parsed string
	 */
	function mb_wordwrap($str, $len, $d = "\n", $isBinary = 0)
	{
#		prn_r( $str, 'mb_wordwrap' );
		$arr = array();
		/* return empty string, 31 march 2003 */
		if ($len <= 0) { return $str; };
		$str_temp = '';
		$cnt_char = 0;
		preg_match_all("/./u", $str.' ', $ar_letters);
		for (; list($k, $v) = each($ar_letters[0]);)
		{
#prn_r( $v .' '.$cnt_char );
			if ($cnt_char < $len)
			{
				$str_temp .= $v;
			}
			else
			{
				if ($isBinary)
				{
					$arr[] = $str_temp;
					$str_temp = $v;
					$cnt_char = 0;
				}
				else
				{
					if ($v == ' ' || $v == "\r" || $v == "\n")
					{
						$arr[] = $str_temp;
						$str_temp = $v;
						$cnt_char = 0;
					}
					else
					{
						$str_temp .= $v;
					}
				}
			}
			++$cnt_char;
		}
		$arr[] = $str_temp;
		return implode($d, $arr);
	}
	/* Special for chunking long strings. Returns the first line only. */
	function mb_wordwrap_first($str, $len, $d = "\n", $isBinary = 0)
	{
		global $sys;
		$arr = array();
		$str = str_replace('&#032;', ' ', $str);
		$str = str_replace('&#020;', ' ', $str);
		$str = str_replace('&#32;', ' ', $str);
		$str = str_replace('&#20;', ' ', $str);
		/* return empty string, 31 march 2003 */
		if ($len < 0) { return $str; };
		$str_temp = '';
		$cur_length = 0;
		$ar_words = explode(' ', $str.' ', 100);
		for (; list($k, $v) = each($ar_words);)
		{
			$cur_length += $this->mb_strlen(' '.$v);
			if ($cur_length >= $len)
			{
				return $str_temp.$d;
			}
			$str_temp .= ' '.$v;
		}
		return $str;
		/*
		 too expensive
		preg_match_all("/./u", $str.' ', $ar_letters);
		for (; list($k, $v) = each($ar_letters[0]);)
		{
			if ( $k == ($len * (sizeof($arr) + 1) + $int_char) )
			{
				if ($isBinary)
				{
					$arr[$k] = $str_temp;
					$str_temp = '';
					return $arr[$k].$d;
				}
				else
				{
					if ($v == ' ')
					{
						$int_char = 0;
						$arr[$k] = $str_temp;
						$str_temp = '';
						return $arr[$k].$d;
					}
					else
					{
						$int_char++;
					}
				}
			}
			else if ( $len * (sizeof($arr) + 1) + $int_char >= $slen
				&& ($k) == $slen )
			{
				$arr[$k] = $str_temp;
			}
			$str_temp .= $v;
		}
		return implode($d, $arr);
		*/
	}
	/**
	 * Converts a string with e-mail address
	 * into unresolvable crap for mail robots.
	 *
	 * @param   string  $s String with HTML-tag <a href="mailto:">
	 * @return  string  Parsed string
	 * @see hardWrap()
	 */
	function text_mailto($s)
	{
		preg_match_all("/href=\"mailto:(.*?)\">(.*?)<\/a>/i", $s, $e);
		/* encode `mailto:' */
		if (isset($e[1][0]))
		{
			$s = str_replace($e[1][0], '', $s);
			$s = str_replace(
						'href="mailto:',
						'title="mailto:'. $e[1][0] .'" '.
						'href="mailto:'.$this->text_make_uid(mt_rand(2,8), 2).'@'.$this->text_make_uid(mt_rand(2,8), 2).'.com" onmouseover="this.href=\''
						. $this->mb_wordwrap('mailto:' . strtolower($e[1][0]), mt_rand(2,4), "'+'", 1)
						. "'", $s);
			return $s;
		}
	}
	/**
	 * Coverts a string into sequence of hex values, \xNN
	 *
	 * @param    string  $t Text data
	 * @param    int     $is_x Print `\x' before a character
	 * @return   string  Hex value for string
	 */
	function text_utf2hex($t, $is_x = 1)
	{
		$str = '';
		$len = strlen($t);
		for ($i = 0; $i < $len; $i++)
		{
			$o = ord(substr($t, $i, 1));
			if ($o < 127)
			{
				$str .= substr($t, $i, 1);
			}
			else
			{
				$str .= ($is_x) ? '\x'.dechex($o) : dechex($o);
			}
		}
		return $str;
	}
	/**
	 * Converts a CSS-file contents into one string
	 *
	 * @param    string  $t Text data
	 * @param    int     $is_debug Skip convertion
	 * @return   string  Optimized string
	 */
	function text_smooth_css($t, $is_debug = 0)
	{
		if ($is_debug) { return $t; }
		/* Remove comments */
		$t = preg_replace("/\/\*(.*?)\*\//s", ' ', $t);
		/* Remove new lines, spaces */
		$t = preg_replace("/(\s{2,}|[\r\n|\n|\t|\r])/", ' ', $t);
		/* Join rules */
		$t = preg_replace('/([,|;|:|{|}]) /', '\\1', $t);
		$t = str_replace(' {', '{', $t);
		/* Remove ; for the last attribute */
		$t = str_replace(';}', '}', $t);
		$t = str_replace(' }', '}', $t);
		return $t;
	}
	/**
	 * Converts a HTML-file contents into one string
	 *
	 * @param    string  $t Text data
	 * @param    int     $is_debug Skip convertion
	 * @return   string  Optimized string
	 * @globals  LF
	 */
	function text_smooth_html($t, $is_debug = 0)
	{
		/* Note that <pre>formatted text will be converted into single line too */
		if ($is_debug) { return $t; }
		/* Remove new lines and tabs */
		$t = preg_replace("/(\r\n|\n|\r|\t)/", ' ', $t);
		/* Remove comments */
		$t = preg_replace("/<!--(.*?)-->/si", '', $t);
		/* Connect HTML-tags */
		$t = str_replace('> </' , '></', $t);
		/* \s is not allowed for multibyte characters */
		$t = preg_replace("/ {2,}/", ' ', $t);
		/* Place a newline character if any */
		$t = str_replace(LF, "\n", $t);
		return $t;
	}
	/**
	 * Automatic height for textarea in HTML-forms
	 *
	 * @param    string  $v Text data
	 * @return   int     Number of lines
	 * @see mb_strlen()
	 */
	function getFormHeight($v, $int_max = 25)
	{
		preg_match_all("/\n/", $v, $vLines);
		$n = intval($this->mb_strlen($v) / 60) + count($vLines[0]) + 2;
		if ($n > $int_max) { $n = $int_max; }
		return $n;
	}
	/* */
	function date_gmusertime($int_time_server, $user_offset)
	{
		return $int_time_server - (($user_offset + intval(date('I'))) * 3600);
	}
	/*
		Calculates the number of years passed from a date.
	*/
	function date_get_passed_y($time_unix, $y, $m, $d)
	{
		/* 2678400 is number of seconds in month */
		$years = date("Y", $time_unix) - $y;
		if (date("m", $time_unix + 2678400) < $m)
		{
			$years--;
		}
		if ((date("m", $time_unix + 2678400) == $m)
			&& ($d < intval(date("d", $time_unix))) )
		{
			$years--;
		}
		return $years;
	}
	/**
	 * Get current time with GMT offset
	 * @param float $gmt_offset GMT offset (+3 Moscow, -6 USA & Canada)
	 * @param int $is_use_dst Day time saving
	 */
	function date_get_localtime($gmt_offset, $is_use_dst = 1)
	{
		$r = $gmt_offset * 3600;
		if ($is_use_dst)
		{
			$r += 3600;
		}
		return time() + $r;
	}
	/**
	 * Converts date from `timestamp(14)' into `time()' format
	 *
	 * @param   string  $t Date in timestamp(14) format
	 * @return  int     Unixtime format
	 */
	function date_Ts14toTime($t)
	{
		$t = sprintf("%s", @mktime(substr($t,8,2),substr($t,10,2),substr($t,12,2),substr($t,4,2),substr($t,6,2),substr($t,0,4)));
		if ( $t < 0 ) { $t = 0; }
		return $t;
	}
	/**
	 * Converts seconds into readable time format
	 *
	 * @param   int     $totalsec Amount of seconds
	 * @return  string  Text pattern 00:00:00
	 */
	function date_SecToTime($totalsec)
	{
		$secH = intval($totalsec / 3600);
		$secMin = intval($totalsec / 60);
		$secSec = ($totalsec - ($secMin * 60));
		$secMin = $secMin - ($secH * 60);
		return sprintf("%02d:%02d:%02d", $secH, $secMin, $secSec);
	}
	/**
	 * Finds whether a variable is a positive integer number
	 *
	 * @param   int  $v Some string to check
	 * @return  TRUE if var is a number, FALSE otherwise.
	 */
	function is_num($v)
	{
		if ( preg_match("/^\d+$/", $v) )
		{
			return true;
		}
		return false;
	}
	/**
	 * Get string length, multibyte.
	 *
	 * @param   string  $t Any string content
	 * @return  int     String length
	 */
	function mb_strlen($t, $encoding = 'UTF-8')
	{
		/* --enable-mbstring */
		if (function_exists('mb_strlen'))
		{
			return mb_strlen($t, $encoding);
		}
		else
		{
			return strlen(utf8_decode($t));
		}
	}
	/**
	 * Replacement for substr(), multibyte
	 * Returns the portion of $t specified by the $start and $end parameters.
	 *
	 * @param  string  $t String to substr
	 * @param  int     $start Start position, positive
	 * @param  int     $end End position, positive
	 * @param  string  $encoding Charset encoding [ UTF-8 (default) | windows-1251 | ISO-8859-1 ]
	 * @return string
	 */
	function mb_substr($t, $start = 0, $end = 0, $encoding = 'UTF-8')
	{
		/* --enable-mbstring */
		if (function_exists('mb_substr'))
		{
			return mb_substr($t, $start, $end, $encoding); /* hundred times faster, ~0.000382 */
		}
		$strD = '';
		$pos = $cntLetter = 0;
		$len = strlen($t);
		if ($end == 0)
		{
			$end = $len;
		}
		while ($pos < $len)
		{
			$charAt = substr($t, $pos, 1);
			$asciiPos = ord($charAt);
			$isConcat = (($cntLetter >= $start) && ($cntLetter < ($start + $end))) ? 1 : 0;
			if (($asciiPos >= 240) && ($asciiPos <= 255))
			{
				$char2 = substr($t, $pos, 4);
				$strD .= ($isConcat) ? $char2 : '';
				$cntLetter++;
				$pos += 4;
			}
			elseif (($asciiPos >= 224) && ($asciiPos <= 239))
			{
				$char2 = substr($t, $pos, 3);
				$strD .= ($isConcat) ? $char2 : '';
				$cntLetter++;
				$pos += 3;
			}
			elseif (($asciiPos >= 192) && ($asciiPos <= 223))
			{
				$char2 = substr($t, $pos, 2);
				$strD .= ($isConcat) ? $char2 : '';
				$cntLetter++;
				$pos += 2;
			}
			else
			{
				$strD .= ($isConcat) ? $charAt : '';
				$cntLetter++;
				$pos++;
			}
		}
		return $strD;
	}
	/**
	 * Get character position, multibyte.
	 *
	 * @param   string  $t Any string contents
	 * @param   string  $s Character to find
	 * @param   string  $encoding Charset encoding [ UTF-8 (default) | windows-1251 | ISO-8859-1 ]
	 * @return  int     String position
	 */
	function mb_strpos($t, $s, $encoding = 'UTF-8')
	{
		/* --enable-mbstring */
		if (function_exists('mb_strpos'))
		{
			return mb_strpos($t, $s, 0, $encoding);
		}
		else
		{
			/* convert $s character into something,
			   which will be not converted into question mark "?"
			   after parsing through utf8_decode() */
			$s_new = "\x01";
			$t = str_replace($s, $s_new, $t);
			return strpos(utf8_decode($t), $s_new);
		}
	}
	/**
	 * Detect UTF-8 encoding, multibyte.
	 *
	 * @param   string  $t Any string content
	 * @return  boolean TRUE if the string is UTF-8, FALSE otherwise
	 */
	function is_detect_utf8($t)
	{
		/* --enable-mbstring */
		if (function_exists('mb_detect_encoding') && @ini_get('mbstring.internal_encoding') == 'UTF-8')
		{
			return (mb_detect_encoding($t, 'UTF-8, GB2312, Windows-1251') == 'UTF-8') ? true : false;
		}
		else
		{
			$is_high = preg_match( '/[\x80-\xff]/', $t);
			return ($is_high ? preg_match( '/^([\x00-\x7f]|[\xc0-\xdf][\x80-\xbf]|' .
					'[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xf7][\x80-\xbf]{3})+$/', $t ) : true );
		}
	}
	/* Converts any HTML-entities into characters */
	function gw_numeric2character($t)
	{
		if (function_exists('mb_decode_numericentity'))
		{
			$convmap = array(0x0, 0x2FFFF, 0, 0xFFFF);
			return mb_decode_numericentity($t, $convmap, 'UTF-8');
		}
		return $t;
	}
	/* Converts any characters into HTML-entities */
	function gw_character2numeric($t)
	{
		if (function_exists('mb_encode_numericentity'))
		{
			$convmap = array(0x0, 0x2FFFF, 0, 0xFFFF);
			return mb_encode_numericentity($t, $convmap, 'UTF-8');
		}
		return $t;
	}
	/**
	 * Converts character encoding
	 *
	 * @param   string    $str The string encoded in $from encoding
	 * @param   string    $from Source encoding
	 * @param   string    $to Target encoding
	 * @return  string    Encoded string
	 * @todo    read xD3 (in xD0xD3)
	 */
	function gwConvertCharset($str, $from, $to)
	{
		/* Skip processing when two strings are the same, 6 jan 2003 */
		if ($from == $to)
		{
			return $str;
		}
		/* Process */
		if (function_exists('mb_convert_encoding'))
		{
			/* Some people have the same problem:
				http://bugs.php.net/bug.php?id=23470
				Text returned from mb_convert_encoding() and iconv()
				must be the same, but often it is not
				when only iconv() is correct.
			*/
			$result_mb = @mb_convert_encoding($str, $to, $from);
			$result_iconv = @iconv($from, $to, $str);
			if ($result_mb != $result_iconv)
			{
				return $result_iconv;
			}
			return $result_mb;
		}
		elseif (function_exists('iconv'))
		{
			return iconv($from, $to, $str);
		}
		elseif (function_exists('recode_string')) /* Linux */
		{
			return recode_string($from . '..' . $to, $str);
		}
		else
		{
			print '<br />Error: function <b>iconv</b> not installed. Update your PHP version.';
			return $str;
		}
	}
	/* */
	function math_hexdec($ar)
	{
		for (reset($ar); list($k, $v) = each($ar);)
		{
			$ar[$k] = hexdec($v);
		}
		return $ar;
	}
	/* Inverts color */
	function math_hex2negative($t)
	{
		$arHex = $this->math_hex2ar($t);
		$arDec = $this->math_hexdec($arHex);
		for (reset($arDec); list($k, $v) = each($arDec);)
		{
			$v2 = (255 - $v);
			/* remove gray */
			$v2 = (($v2 > 50) && ($v2 < 150)) ? 255 : $v2;
			/* */
			$arHex[$k] = sprintf("%02X", $v2);
		}
		return implode('', $arHex);
	}
	/**
	 * Converts hex values into array with integer values
	 * @usage math_hexbg2ar('0F0');
	 * @usage math_hexbg2ar('EE4400');
	 */
	function math_hex2ar($t)
	{
		$t = str_replace('#', '', $t);
		/* convert short form into full form */
		if (strlen($t) == 3)
		{
			list($r, $g, $b) = sscanf($t, '%1s%1s%1s');
			$t = $r.$r.$g.$g.$b.$b;
		}
		return $this->str_split($t, 2);
	}
	/* Calculates factorial (a!) of a. */
	function math_fact($a)
	{
		$r = 1;
		for ($f = 1; $f <= $a; $f++)
		{
			$r = $f * $r;
		}
		return $r;
	}
	/**
	 * Fail-safe str_split() function
	 * PHP 5 CVS only
	 */
	function str_split($t, $length = 1)
	{
		if (function_exists('str_split'))
		{
			return str_split($t, $length);
		}
		return explode(':', wordwrap($t, $length, ':', 1));
	}
	/**
	 * Converts dotted IP-address (IPV4) into database storable format.
	 */
	function ip2int($ip)
	{
		return sprintf("%u", ip2long($ip));
	}
	/**
	 * Converts IP-address (IPV4) from storable format into dotted
	 */
	function int2ip($ip)
	{
		return long2ip($ip);
	}
	/**
	 * Create a GZip-compressed string
	 *
	 * @param  string $t Input data
	 * @param  int    $level Gzip compress level [1..9], 1 by default.
	 * @param  int    $is_send_header Use headers class [1 - yes | 0 - no]
	 * @return string GZipped text
	 * @globals  $_SERVER, $oHdr, PHP_VERSION_INT
	 */
	function text_gzip($str_return, $level = 1, $is_send_header = 1)
	{
		global $_SERVER, $oHdr;
		$int_length = strlen($str_return);
		$encoding = 0;
		if (function_exists('crc32') && function_exists('gzcompress'))
		{
			/* strpos() should be always compared as boolean */
			if (strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)
			{
				$encoding = 'x-gzip';
			}
			elseif (strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
			{
				$encoding = 'gzip';
			}
			if ($encoding)
			{
				if (function_exists('gzencode') && PHP_VERSION_INT > 40200)
				{
					$str_return = gzencode($str_return, $level);
				}
				else
				{
					$size = strlen($str_return);
					$crc = crc32($str_return);
					$str_return = "\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\xff";
					$str_return .= substr(gzcompress($str_return, $level), 2, -4);
					$str_return .= pack('V', $crc);
					$str_return .= pack('V', $size);
				}
				if ($is_send_header)
				{
					$oHdr->add('Content-Encoding: ' . $encoding);
					$oHdr->add('Content-Length: ' . strlen($str_return));
				}
			}
		}
		return $str_return;
	}
}
$tmp['mtime'] = explode(' ', microtime());
$tmp['endtime'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
$tmp['time'][__FILE__] = ($tmp['endtime'] - $tmp['start_time']);
/* automatic initialization */
$oFunc = new gw_functions;

?>