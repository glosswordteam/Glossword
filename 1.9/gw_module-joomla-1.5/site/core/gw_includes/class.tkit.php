<?php
/**
 * Translation Kit - (http://tkit.info/)
 * © 2002-2009 Dmitry N. Shilnikov <dmitry.shilnikov at gmail dot com>
 * File-based version.
 * PHP 5
 *
 * @version $Id: class.tkit.php 134 2009-09-20 08:10:28Z dshilnikov $
 */
class tkit
{
	public $path_locale;
	/* Language settings */
	public $ar_ls;
	/* Phrases */
	public $a = array();
	/* Called phrases */
	public $ac = array();
	/* Used phrases */
	public $au = array();
	/* Debug information */
	public $is_debug = 0;
	public $ar_debug = array();
	private $ar_lang_list = array();

	/**
	 * Format size in bytes. 1 KB, 10 MB, 1.2 GB
	 */
	public function bytes($n)
	{
		$ar_byte_units = explode(' ', $this->ar_ls['byte_units']);
		if ($n <= 1024)
		{
			return $n.'&#160;'.$ar_byte_units[0];
		}
		elseif ($n <= 1024*1024)
		{
			return $this->number_format($n/1024, 1).'&#160;'.$ar_byte_units[1];
		}
		elseif ($n <= 1024*1024*1024)
		{
			return $this->number_format($n/1024/1024, 1).'&#160;'.$ar_byte_units[2];
		}
		elseif ($n <= 1024*1024*1024*1024)
		{
			return $this->number_format($n/1024/1024/1024, 1).'&#160;'.$ar_byte_units[3];
		}
		elseif ($n <= 1024*1024*1024*1024*1024)
		{
			return $this->number_format($n/1024/1024/1024/1024, 1).'&#160;'.$ar_byte_units[4];
		}
		return $n;
	}
	/**
	 * Format date usign translaiton phrases.
	 *
	 * A usual date() format with exceptions:
	 * %FL - month, string, long, lowercase; i.e. "january"
	 * %FS - month, string, long, capitalized; i.e. "January"
	 * %ML - month, string, 3 letters, lowercase; i.e. "jan"
	 * %M - month, string, 3 letters, capitalized; i.e. "Jan"
	 */
	public function date($s = "%d %M %Y %H:%i:%s", $seconds)
	{
		$ar_months_decl = explode(' ', ' ' .$this->ar_ls['month_decl']);
		$ar_months_long = explode(' ', ' ' .$this->ar_ls['month_long']);
		$ar_months_short = explode(' ', ' ' . $this->ar_ls['month_short']);
		/* convert $secods to YYYYMMDDHHMMSS */
		$d = @date("YmdHis", $seconds);
		$s = str_replace( "%d", (substr($d, 6, 2)/1), $s ); /* removes leading 0 from date */
		$s = str_replace( "%m", substr($d, 4, 2), $s );
		$s = str_replace( "%f", str_replace('_', ' ', $ar_months_decl[(substr($d,4,2)/1)]), $s );
		$s = str_replace( "%F", str_replace('_', ' ', $ar_months_long[(substr($d, 4, 2)/1)]), $s );
		$s = str_replace( "%M", str_replace('_', ' ', $ar_months_short[(substr($d, 4, 2)/1)]), $s );
		$s = str_replace( "%Y", substr($d,0,4), $s );
		$s = str_replace( "%H", substr($d,8,2), $s );
		$s = str_replace( "%i", substr($d,10,2), $s );
		$s = str_replace( "%s", substr($d,12,2), $s );
		$s = str_replace( "%c", @date("c", $seconds), $s );
		return $s;
	}
	/* Accepts only decimal */
	public function string_format($f, $s, $t = '#')
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
	public function number_format($n, $decimals = 0, $dec_point = ',', $thousands_sep = '&#160;')
	{
		$n = sprintf('%1.'.$decimals.'f', $n);
		$dec_point = $this->ar_ls['decimal_separator'];
		$thousands_sep = $this->ar_ls['thousands_separator'];
		$b = explode('.', $n);
		$rn = '';
		if ($n < 0)
		{
			$minus = '-';
			$b[0] = 0 - $b[0];
		}
		else
		{
			$minus = '';
		}
		$l = strlen($b[0]);
		/* Reverse string */
		for ($i = $l; $i > 3; $i -= 3)
		{
			$rn = $thousands_sep . substr($b[0], $i - 3, 3) . $rn;
		}
		/* sprintf() used to correct 0.79 to 0.790 */
		/* str_replace() used to correct decimals */
		return $minus . substr($b[0], 0, $i) . $rn . ($decimals
				? $dec_point.(isset($b[1])
					? str_replace('0.', '', sprintf('%1.'.$decimals.'f', '0.'.$b[1]))
					: str_repeat(0, $decimals))
				: '');
	}
	/**
	 * The list of available languages for the project
	 */
	public function get_lang_list()
	{
		if (!empty($this->ar_lang_list))
		{
			return $this->ar_lang_list;
		}
		$cnt = 0;
		foreach (glob($this->path_locale."/*-settings.php") as $filename)
		{
			include($filename);
			$ar[$cnt]['lang_uri'] = $a['lang_uri'];
			$ar[$cnt]['lang_name'] = $a['lang_name'];
			$ar[$cnt]['lang_native'] = $a['lang_native'];
			++$cnt;
		}
		$this->ar_lang_list = $ar;
		return $this->ar_lang_list;
	}
	/* */
	public function load_lang_settings( $lang )
	{
		/* Default language settings */
		$a = array(
			'id_lang'=>'1',
			'lang_name'=>'English',
			'lang_native'=>'American',
			'region'=>'en_US',
			'locale_winapi'=>'1033',
			'isocode1'=>'en',
			'isocode3'=>'eng',
			'lang_uri'=>'en',
			'direction'=>'ltr',
			'thousands_separator'=>',',
			'decimal_separator'=>'.',
			'list_separator'=>';',
			'month_short'=>'Jan Feb Mar Apr May Jul Jul Aug Sep Oct Nov Dec',
			'month_long'=>'January February March April May June July August September October November December',
			'month_decl'=>'January February March April May June July August September October November December',
			'day_of_week'=>'Mon Tue Wed Thu Fri Sat Sun',
			'byte_units'=>'B KB MB GB TB PB EB'
		);
		/* Load language settings */
		if (file_exists($this->path_locale.'/'.$lang.'-settings.php'))
		{
			include_once($this->path_locale.'/'.$lang.'-settings.php');
		}
		$this->ar_ls =& $a;
	}
	/**
	 * Loads phrases by tag.
	 *
	 * @access	public
	 * @uses array_merge_clobber() 
	 */
	public function import_tag( $ar = array(), $lang_uri )
	{
		settype( $ar, 'array' );
		$this->load_lang_settings( $lang_uri );
		/* Correct the current Language URI */
		$lang_uri = $this->ar_ls['lang_uri'];
		/* Prepare filename to file */
		$path = $this->path_locale.'/'.$lang_uri.'-%s.php';
		$a = array();
		for (; list($k, $v) = each($ar);)
		{
		    if ( file_exists( sprintf( $path, $v ) ) )
			{
			    include_once( sprintf( $path, $v ) );
			}
			else
			{
			    #print '<br />Error loading: '.sprintf( $path, $v );
			}
			$this->a = array_merge_clobber( $this->a, $a );
		}
		return true;
	}
	/* @access	public */
	public function &get_phrases_all()
	{
		return $this->a;
	}
	/* @access	public */
	public function get_phrases_called()
	{
		return $this->ac;
	}
	/* */
	public function _($pid)
	{
		$arg = func_get_args();
		unset($arg[0]);
		$value = isset($this->a[$pid]) ? $this->a[$pid] : $pid;
		for (;list($arg_num, $arg_val) = each($arg);)
		{
			$value = str_replace('%'.$arg_num, $arg_val, $value);
		}
		/* Collect debug information */
		if ( $this->is_debug )
		{
			$s = debug_backtrace();
			$this->ar_debug[] = array(
				'args' => implode(', ', $s[0]['args']),
				'file' => $s[0]['file'],
				'line' => $s[0]['line'],
				'value' => $value
			);
			/* Collect called PIDs */
			$this->ac[$pid] = $value;
		}
		return $value;
	}
	/* compatibility */
	public function m($pid)
	{
		return $this->_($pid);
	}
}
?>