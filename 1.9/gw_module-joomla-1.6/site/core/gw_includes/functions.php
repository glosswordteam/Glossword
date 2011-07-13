<?php
/**
 * $Id$
 */
if (!defined('IS_CLASS_GW_FUNCTIONS')) { define('IS_CLASS_GW_FUNCTIONS', 1);

/**
 * Returns requested address.
 */
function get_request_uri()
{
	$str_pos = TRUE;
	if ($_SERVER['QUERY_STRING'] != '')
	{
		$str_pos = strpos($_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING']);
	}
	if (isset($_SERVER['REQUEST_URI']) && $str_pos)
	{
		$uri = $_SERVER['REQUEST_URI'];
	}
	else
	{
		if (isset($_SERVER['argv']))
		{
			$uri = $_SERVER['SCRIPT_NAME'] .'?'. $_SERVER['argv'][0];
		}
		elseif (isset($_SERVER['QUERY_STRING']))
		{
			$uri = $_SERVER['SCRIPT_NAME'] .'?'. $_SERVER['QUERY_STRING'];
		}
		else
		{
			$uri = $_SERVER['SCRIPT_NAME'];
		}
	}
	/* */
	$_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 0);
	if (isset($_SERVER['QUERY_STRING']))
	{
		$_SERVER['REQUEST_URI'] .= ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
	}
	/* IIS */
	if (@preg_match('/Microsoft|WebSTAR|Xitami/', getenv('SERVER_SOFTWARE')))
	{
		$uri = $_SERVER['REQUEST_URI'];
		$uri = mb_convert_encoding($uri, "UTF-8", "windows-1251");
		$uri = str_replace('%', '%25', $uri);
	}
	return $uri;
}



/**
 * Analog for str_getcsv(), Since 5.3.0
 * 
 * @param string $input The string to parse.
 * @param string $delimiter Set the field delimiter (one character only).
 * @param string $enclosure Set the field enclosure character (one character only). 
 * @param string $escape Set the escape character (one character only). 
 * @return array Parsed CSV-line.
 */
if ( !function_exists( 'str_getcsv' ) ) 
{
	function str_getcsv( $input, $delimiter = ",", $enclosure = '"', $escape = "\\" ) 
	{
		$fp = fopen( "php://memory", 'r+' );
		fputs( $fp, $input );
		rewind( $fp );
		$data = fgetcsv( $fp, null, $delimiter, $enclosure );
		fclose( $fp );
		return $data;
	}
}
/**
 * Analog for str_putcsv()
 */
if ( !function_exists( 'str_putcsv' ) ) 
{
	function str_putcsv( $input, $delimiter = ",", $enclosure = '"', $escape = "\\" ) 
	{
		$fp = fopen( "php://memory", 'r+' );
		fputcsv( $fp, $input, $delimiter, $enclosure );
		rewind( $fp );
		$data = fread( $fp, 1048576 );
		fclose( $fp );
		return rtrim( $data, "\n" );
	}
}


/**
 * Replacement for ini_get()
 * 
 * @param string  $ini PHP directive.
 * @return TRUE for on/off directives, int for mixed integer directives.
 */
function gw_ini_get( $ini )
{
	$val_ini = strtolower( ini_get( $ini ) );
	$val_ini = ($val_ini == 'on' || $val_ini == '1') ? true : $val_ini;
	$val_ini = ($val_ini === 'off' || $val_ini === '0' || $val_ini === '') ? false : $val_ini;
	
	/* Try to detect megabytes, 100K, 16M, 1G */
	if ( preg_match( '/[0-9]+[kmgb]+$/', $val_ini ) )
	{
		/* 16M */
		if ( preg_match( '/[0-9]+[mgb]+$/', $val_ini ) )
		{
			/* 1G */
			if ( preg_match( '/[0-9]+[gb]+$/', $val_ini ) )
			{
				$val_ini = intval( $val_ini ) * 1024;
			}
			$val_ini = intval( $val_ini ) * 1024;
		}
		$val_ini = intval( $val_ini ) * 1024;
	}
	return $val_ini;
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
	print '<pre style="text-align:left;background:#FFF;font: 14px/16px Consolas,\'Courier New\',monospace">';
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

/**
 * Replacement for htmlspecialchars(). Can parse arrays.
 *
 * @param   string  $s Any string or array.
 */
function gw_htmlspecialchars($s)
{
	if (is_array($s))
	{
		/* can't use array_walk() on byself with reference */
		for (reset($s); list($k, $v) = each($s);)
		{
			$s[$k] = gw_htmlspecialchars($v);
		}
		return $s;
	}
	elseif (is_string($s))
	{
		$s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
	}
	return $s;
}
/**
 * Replacement for gw_htmlspecialchars_decode(). Can parse arrays.
 *
 * @param   string  $s Any string or array.
 */
function gw_htmlspecialchars_decode($s)
{
	if (is_array($s))
	{
		/* can't use array_walk() on byself with reference */
		for (reset($s); list($k, $v) = each($s);)
		{
			$s[$k] = htmlspecialchars_decode($v, ENT_QUOTES);
		}
		return $s;
	}
	elseif (is_string($s))
	{
		$s = htmlspecialchars_decode($s, ENT_QUOTES);
	}
	return $s;
}

/**
 * Inserts value into array between numeric keys. 
 * Note: Works with the first keys only for multidimensional arrays.
 *
 * @param   array   Source array data
 * @param   string  Array key
 * @param   string  New value
 */
if (!function_exists('array_insert'))
{
function array_insert( &$ar, $k, $v )
{
	if ( !is_array( $ar ) ) { return false; }
	$ar = array_merge(
			array_slice( $ar, 0, ($k + 1) ),
			array( ($k + 1) => $v ),
			array_slice( $ar, ($k + 1) )
	);
}
}

/**
 * Merges arrays and clobber any existing key/value pairs
 * Keeps numeric keys, they will be not renumbered.
 *
 * @param   array   $a1 First array
 * @param   array   $a2 Second array
 * @return  array   Merged arrays
 */
if (!function_exists('array_merge_clobber'))
{
function array_merge_clobber($a1, $a2)
{
	if (!is_array($a1) || !is_array($a2)) { return false; }
	$arNew = $a1;
	while (list($k, $v) = each($a2))
	{
		if (is_array($v) && isset($arNew[$k]) && is_array($arNew[$k]))
		{
			$arNew[$k] = array_merge_clobber($arNew[$k], $v);
		}
		else
		{
			$arNew[$k] = $v;
		}
	}
	return $arNew;
}
}


/**
 * Optimizes HTML-code. Light version.
 */
function text_smooth_light($t)
{
	$t = str_replace(array("\r\n", "\n", "\t"), " ", $t);
	$t = preg_replace("/<!--(.*?)-->/s", '',$t);
	$t = str_replace("<div", "\n<div", $t);
	$t = str_replace("<tr", "\n<tr", $t);
	$t = str_replace("<table", "\n<table", $t);
	$t = str_replace("\n</", '</', $t);
	return $t;
}

/* */
function text_smooth($s, $is_debug = 0)
{
	/* skip optimization when debug mode */
	if ($is_debug)
	{
		return $s;
	}
	/* Protect pre */
	if (preg_match_all("/<pre(.*?)>(.*?)<\/pre>/s", $s, $ar))
	{
		foreach($ar[2] as $v)
		{
			$s = str_replace($v, '<![BASE64['.base64_encode($v).']]>', $s);
		}
	}
	/* Protect textarea */
	if (preg_match_all("/<textarea(.*?)>(.*?)<\/textarea>/s", $s, $ar))
	{
		foreach ($ar[2] as $v)
		{
			$s = str_replace($v, '<![BASE64['.base64_encode($v).']]>', $s);
		}
	}

	$s = str_replace(array("\r\n", "\n", "\t"), " ", $s);
	$s = preg_replace("/<!--(.*?)-->/s", '',$s);
	$s = preg_replace("/ {2,}/", " ", $s);

	/* restore all base64 */
	if (preg_match_all("/<\!\[BASE64\[(.*?)\]\]>/s", $s, $ar))
	{
		foreach ($ar[0] as $v)
		{
			$s = str_replace($v, base64_decode(str_replace(array('<![BASE64[', ']]>'), '', $v)), $s);
		}
	}
	return $s;
}






/* Helps to build a context menu */
class site_jsMenu {
	private $ar_menu_items = array();
	public $icon = '&#8801;&#8801;'; /* Windows Vista+ only */
	public $classname = 'btn';
	public $event = 'onclick';
	/* Add an item to menu */
	public function append($href, $text, $classname = '', $onclick = '')
	{
		$this->ar_menu_items[] = array($href, $text, $classname, $onclick);
	}
	/* get the whole HTML-code */
	public function get_html()
	{
		$s = '<span><a'.($this->classname ? ' class="'.$this->classname.'"' : '').' href="#" '.$this->event.'="return jsMenu.show(this, ';
		$ar = array();
		foreach ($this->ar_menu_items as $k => $v)
		{
			/* Optimize javascript: [1,2,'',''] => [1,2] but [1,2,'',4] => [1,2,'',4]*/
			if ($v[3] == '')
			{ 
				unset($v[3]);
				if ($v[2] == '')
				{ 
					unset($v[2]);
				}
			}
			$ar[] = '[\''.implode("','", $v).'\']';
			/* auto-clean */
			unset($this->ar_menu_items[$k]);
		}
		$s .= implode(',', $ar);
		/* No items in menu */
		if (empty($ar))
		{
			$s .= '[null, null]';
		}
		$s .= ')">'.$this->icon.'</a></span>';
		return $s;
	}
}



/* */
class tkit_functions {


	/* */
	public static function str_binary( $s, $len = 32, $pad_string = "\0" )
	{
		return str_pad( substr( $s, 0,  $len ), $len , $pad_string );
	}
	/* */
	public static function get_crc_u( $s )
	{
		return sprintf( "%u", crc32( $s ) );
	}
	/* */
	static public function hexbin($s)
	{
		return pack("H*", $s);
	}
	/* */
	static public function make_str_random( $a = 6 )
	{
		if ( $a < 1 ) { $a = 1; }
		$b = 'bdfghijkmnqrstuwzQWRUSDFGJLZN23456789';
		$c = strlen($b);
		$s = '';
		for ($i = 0; $i < $a; $i++)
		{
			$s .= $b[mt_rand(0, $c-1)];
		}
		return $s;
	}
	
	/** 
	 * Draws progressbar in HTML+CSS 
	 *
	 * @param	int	$percent
	 * @param	text	$color_txt Progress bar text color
	 * @param	text	$color_bg Progress bar Background color
	 * @return	text	HTML-code
	 */
	function text_progressbar($percent = 100, $color_txt = '#000', $color_bg = '#6C3')
	{
		return '<div style="text-align:center;background:#F6F6F6;margin:5px 0;width:100%;border:1px solid #CCC"><div style="font:90% sans-serif;color:'.$color_txt.';background:'.$color_bg.';width:'.$percent.'%">'.$percent.'%</div></div>';
	}
	
	/**
	 * Get string length, multibyte.
	 *
	 * @param   string  $t Any string content
	 * @return  int     String length
	 */
	public static function mb_strlen($t, $encoding = 'UTF-8')
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
	 * Returns part of a string without breaking words.
	 * 11 Feb 2010: removed spaces before and after \dots (...)
	 * 
	 * @uses mb_strlen();
	 */
	public function smart_substr( $t, $in_start = 0, $in_end = 128, $encoding = 'UTF-8' )
	{
		$charsmore = '…';
		/* Smart substr */
		$ar_snippet = explode( ' ', strip_tags( $t ) );
		$cnt_word = $cut_from = 0;
		$cut_to = sizeof( $ar_snippet );
		$s_title = '';
		foreach ( $ar_snippet as $word_s )
		{
			$s_title .= $word_s.' ';
			if ( $this->mb_strlen( $s_title, $encoding ) > $in_end )
			{
				$cut_to = $cnt_word;
				break;
			}
			++$cnt_word;
		}
		$cnt_word = 0;
		foreach ( $ar_snippet as $ks => $word_s )
		{
			if ( $cnt_word < $cut_from || $cnt_word > $cut_to )
			{
				unset( $ar_snippet[$ks] );
			}
			if ( $cnt_word == $cut_to )
			{
				$ar_snippet[$ks] = $charsmore;
			}
			++$cnt_word;
		}
		ksort( $ar_snippet );
		return str_replace( array(' '.$charsmore, $charsmore.' '), $charsmore, implode(' ', $ar_snippet) );
	}
	/**
	 * Makes string wrapped, multibyte.
	 * 1999
	 * 2003
	 * Nov 1, 2005
	 * Sep 21, 2009
	 *
	 * @param   string  $str A string to wrap
	 * @param   int     $len Maximum length, characters
	 * @param   string  $d Delimiter, default is "\n"
	 * @param   int     $isBinary [ 0 - off | 1 - use binary-safe convertion ]
	 * @return  string  Parsed string
	 * @uses mb_strlen(), mb_substr()
	 */
	function mb_wordwrap($str, $len, $d = "\n", $isBinary = 0)
	{
		$arr = array();
		/* return empty string, 31 march 2003 */
		if ($len <= 0) { return $str; };
		$slen = $this->mb_strlen($str);
		/* too short */
		if ($slen <= $len) { return $str; };
		
		$ar_words = explode(' ', $str);
#prn_r( $ar_words );
		$ar_new = $ar_sorted = array();
		$cnt = 0;
		$cnt_new = 0;
		/* */
		foreach ($ar_words as $w)
		{
			$cnt += $this->mb_strlen($w);
			if ($cnt >= $len)
			{
				$ar_new[$cnt_new][$cnt] = $w;
				$cnt = 0;
				++$cnt_new;
			}
			else
			{
				$ar_new[$cnt_new][$cnt] = $w;
			}
		}
		#prn_r( $ar_new );
		--$len;
		/* */
		$key = 0;
		foreach ($ar_new as $k => $ar_w)
		{
			$cnt_letters_total = 0;
			foreach ($ar_w as $cnt_letters => $w)
			{
				if ($cnt_letters > $len && $isBinary)
				{
					$cnt_chars = 0;
					$pos_start = 0;
					$ar_str = array();
					$cnt_part = 0;
					if ($this->mb_strlen($w) < $len)
					{
						++$cnt_part;
					}
					$int_pos = $cnt_letters;
					preg_match_all("/./u", $w, $ar_chars);
					foreach ($ar_chars[0] as $char)
					{
						$int_pos = $cnt_letters_total + $cnt_chars;
						if ($int_pos <= $len)
						{
							$ar_str[$cnt_part][$int_pos] = $char;
						}
						else
						{
							if ($pos_start > $len)
							{
								++$cnt_part;
								$pos_start = 0;
							}
							else
							{
								if ($cnt_part == 0)
								{
									++$cnt_part;
								}
								$cnt_part = $cnt_part;
							}
							$ar_str[$cnt_part][$int_pos] = $char;
							++$pos_start;
						}
						++$cnt_chars;
					}
					#prn_r(  $ar_str );
					unset($ar_w[$cnt_letters]);
					foreach ($ar_str as $kw => $vw)
					{
						++$key;
						$ar_sorted[$key] = implode('', $vw);
					}
				}
				else
				{
					if (!isset($ar_sorted[$key]))
					{
						$ar_sorted[$key] = ' '.$ar_w[$cnt_letters];
					}
					else
					{
						$ar_sorted[$key] .= ' '.$ar_w[$cnt_letters];
					}
				}
				$cnt_letters_total =+ $cnt_letters;
			}
			++$key;
		}
#		prn_r( $ar_sorted );
		return implode($d, $ar_sorted);
	}

	/**
	 * Replacement for substr(), multibyte
	 * Returns the portion of $t specified by the $start and $end parameters.
	 *
	 * @param  string  $t String to substr
	 * @param  int     $start Start position, positive
	 * @param  int     $end End position, positive
	 * @param  string  $encoding Charset encoding [ utf-8 (default) | windows-1251 | ISO-8859-1 ]
	 * @return string
	 */
	public static function mb_substr( $t, $start = 0, $end = 0, $encoding = 'utf-8' )
	{
		/* --enable-mbstring */
		if ( function_exists( 'mb_substr' ) )
		{
			if ( $encoding == 'utf-8' )
			{
				return mb_substr( $t, $start, $end );
			}
			return mb_substr( $t, $start, $end, $encoding ); /* hundred times faster, ~0.000382 */
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
	 * Get file contents. Binary- and fail-safe.
	 *
	 * @param   string  $filename Full path to filename
	 * @return  string  File contents
	 */
	static public function file_get_contents($filename)
	{
		if (!file_exists($filename))
		{
			return '[file_get_contents: file '. $filename . ' does not exist]';
		}
		if (function_exists('file_get_contents')) /* since PHP4 CVS */
		{
			$str = file_get_contents($filename);
		}
		else
		{
			$str = implode('', file($filename));
		}
		if ($str == '')
		{
			return '[file_get_contents: ' . $filename. ' is empty]';
		}
		/* Remove slashes, 23 March 2002 */
		if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime())
		{
			$str = stripslashes($str);
		}
		return $str;
	}
	/**
	 * Put contents into a file. Binary- and fail-safe.
	 *
	 * @param   string  $filename Full path to filename
	 * @param   string  $content File contents
	 * @param   string  $mode [ w = write new file (default) | a = append ]
	 * @return  TRUE if success, FALSE otherwise
	 */
	static function file_put_contents($filename, $content, $mode = "w")
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
	 * Removes a file or an empty directory from disk
	 *
	 * @param	string	$filename Full path to filename.
	 * @return	TRUE if success, FALSE otherwise.
	 */
	static public function file_remove($filename)
	{
		if (file_exists($filename) && is_file($filename) && unlink($filename))
		{
			return true;
		}
		elseif (file_exists($filename) && is_dir($filename) && rmdir($filename))
		{
			return true;
		}
		return false;
	}

}

}
/* */
?>