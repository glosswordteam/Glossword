<?php
/**
 * Translation Kit - (http://tkit.info/)
 * © 2002-2008 Dmitry N. Shilnikov <dev at wwwguru dot net>
 * File-based version.
 * 
 * Requires:
 *      array_merge_clobber()
 * 
 * @version $Id: class.tkit.php 3 2008-06-21 07:22:47Z glossword_team $
 */
class tkit
{
	var $path_locale;
	/* Language settings */
	var $arL;
	/* Languages */
	var $ar_languages = array();
	/* Phrases */
	var $a = array();
	/* Files */
	var $f = array();
	/* Called phrases */
	var $ac = array();
	/* Used phrases */
	var $au = array();
	/* Debug information */
	var $is_debug = 0;
	var $ar_debug = array();
	
	/* Accepts only decimal */
	function string_format($f, $s, $t = '#')
	{
		$n = preg_match_all("/($t+)/", $f, $m);
		foreach ($m[0] as $match)
		{
			$mlen = strlen($match);
			$f = preg_replace("/$t+/", substr($s, 0, $mlen), $f, 1);
			$s = substr($s, $mlen);
		}
		return $f;
	}
	/* Replacement for number_format() */
	function number_format($n, $decimals = 0, $dec_point = ',', $thousands_sep = '&#160;') 
	{
		$dec_point = $this->arL['decimal_separator'];
		$thousands_sep = $this->arL['thousands_separator'];
		$b = explode('.', $n);
		$rn = '';
		$l = strlen($b[0]);
		/* Reverse string */
		for ($i = $l; $i > 3; $i -= 3)
		{ 
			$rn = $thousands_sep . substr($b[0], $i - 3, 3) . $rn;
		}
		/* sprintf() used to correct 0.79 to 0.790 */
		/* str_replace() used to correct decimals */
		return substr($b[0], 0, $i) . $rn . ($decimals 
				? $dec_point.(isset($b[1]) 
					? str_replace('0.', '', sprintf('%0.'.$decimals.'f', '0.'.$b[1]))
					: str_repeat(0, $decimals))
				: '');
	}
	/* */
	function load_languages()
	{
		$this->ar_languages = array();
		foreach (glob($this->path_locale."/*-settings.php") as $filename)
		{
			include($filename);
			$this->ar_languages[] = $a;
		}
	}
	/* */
	function get_languages()
	{
		$ar = array();
		foreach ($this->ar_languages as $v)
		{
			$ar[$v['lang_uri']] = $v['lang_name'].' - '.$v['lang_native'];
		}
		return $ar;
	}
	/* */
	function load_lang_settings($lang)
	{
		$this->load_languages();
		/* Default language settings */
		$a = array(
			'id_lang' => '1',
			'lang_name' => 'English',
			'lang_native' => 'American', 
			'lang_uri' => 'english',
			'isocode2' => 'en', 
			'isocode3' => 'eng', 
			'direction' => 'ltr', 
			'thousands_separator' => ',',
			'decimal_separator' => '.', 
			'part_separator' => ' '
		);
		/* Load language settings */
		if (file_exists($this->path_locale.'/'.$lang.'-settings.php'))
		{
			include($this->path_locale.'/'.$lang.'-settings.php');
		}
		$this->arL =& $a;
	}
	/* Load phrases by tag */
	function import_tag($ar = array(), $lang)
	{
		settype($ar, 'array');
		$this->load_lang_settings($lang);
		/* Load phrases */
		$path = $this->path_locale.'/'.$lang.'-%s.php';
		$a = array();
		for (; list($k, $v) = each($ar);)
		{
			$filename = sprintf($path, $v);
			/* Serialized version */
			if (file_exists($filename) && !isset($this->f[$filename]))
			{
				$a = @unserialize(file_get_contents($filename));
				array_walk($a, create_function('&$v','$v=urldecode($v);'));
				$this->f[$filename] = true;
			}
			$this->a = array_merge_clobber($a, $this->a);
		}
		/* */
		return;
	}
	function tkit_urldecode($a)
	{
		
	}
	/* @access	public */
	function &get_phrases_all()
	{
		return $this->a;
	}
	/* @access	public */
	function get_phrases_called()
	{
		return $this->ac;
	}
	/* */
	function _($pid)
	{
		$arg = func_get_args();
		unset($arg[0]);
		$value = isset($this->a[$pid]) ? $this->a[$pid] : $pid;
		for (;list($arg_num, $arg_val) = each($arg);)
		{
			$value = str_replace('%'.$arg_num, $arg_val, $value);
		}
		/* Collect debug information */
		if ($this->is_debug)
		{
			$s = debug_backtrace();
			$this->ar_debug[] = array(
				'args' => implode(', ', $s[0]['args']),
				'file' => $s[0]['file'],
				'line' => $s[0]['line'],
				'value' => $value
			);
		}
		/* Collect called PIDs */
#		$this->ac[$pid] = $value;
		return $value;
	}
	/* compatibility */
	function m($pid)
	{
		return $this->_($pid);
	}
}
if (!function_exists('array_merge_clobber'))
{
	function array_merge_clobber($a1, $a2)
	{
		return $a1;
	}
}

?>