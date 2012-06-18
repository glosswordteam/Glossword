<?php
if (!defined('IS_CLASS_GW2_FUNCTIONS')) { define('IS_CLASS_GW2_FUNCTIONS', 1);

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


/* */
class tkit_functions {
	/* */
	static function make_int16($s)
	{
		$h = hash('md5', $s);
		$n = '';
		for ($i = 0; $i < 32; $i++)
		{
			$n .= hexdec(substr($h, $i, 2));
			if (strlen($n) > 16)
			{
				$n = substr($n, 0, 16); 
				break;
			}
			$i++;
		}
		return $n;
	}
	static function hexbin($s)
	{
		return pack("H*", $s);
	}
	/* */
	static function make_str_random($a = 6)
	{
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
	 * Get file contents. Binary and fail safe.
	 *
	 * @param   string  $filename Full path to filename
	 * @return  string  File contents
	 */
	static function file_get_contents($filename)
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
		/* Remove slashes, 23 march 2002 */
		if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime())
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
	 * Removes a file or en empty directory from disk
	 */
	static function file_remove($filename)
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