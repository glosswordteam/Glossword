<?php
/**
 * $Id$
 * 
 * Class to work with CSS/JS/HTML.
 */
if (!defined('IS_CLASS_OUTPUT')) { define('IS_CLASS_OUTPUT', 1);
class site_output
{
	public $path_css, $path_js;
	public $ar_html_title = array();
	public $ar_html = array();
	public $ar_bc = array();
	private $ar_vars = array();
	private $ar_css_files = array();
	private $ar_css_collection = array();
	private $ar_js_files = array();
	private $ar_js_collection = array();
	private $cnt_bc_key = 0;
	public $css_a = array();
	public $js_a = array();
	public $gzip_enc = false;
	private $cfg;

	public function __construct($ar_cfg = array())
	{
		$ar_cfg_default = array(
			'path_css' => 'css',
			'path_js' => 'js',
			'is_js_debug' => 0,
			'is_css_debug' => 0,
			'is_keep' => 0,
			'str_title_separator' => ' + ',
		);
		foreach ($ar_cfg as $k => $v)
		{
			$ar_cfg_default[$k] = $v;
		}
		$this->cfg =& $ar_cfg_default;
	}
	/**
	 * 
	 */
	public function set($k, $v = '')
	{
		$this->ar_vars[$k] = $v;
	}
	/**
	 * 
	 */
	public function append_html( $v = '' )
	{
		$this->ar_html[] = $v;
	}
	/**
	 * 
	 */
	public function append_bc( $v = '', $url = '', $cnt_key = '' )
	{
		$this->cnt_bc_key = ( $cnt_key != '' ) ? $cnt_key : $this->cnt_bc_key;
		$this->ar_bc[$this->cnt_bc_key] = array( $v, $url );
		++$this->cnt_bc_key;
	}
	/**
	 * 
	 */
	public function get_breadcrumbs()
	{
		ksort( $this->ar_bc );
		return $this->ar_bc;
	}
	/**
	 * 
	 */
	public function get_html()
	{
		$s = implode('', $this->ar_html);
		/* Remove new lines, spaces */
		#$s = htmlspecialchars($s);
		#$s = str_replace(array("\r\n","\n","\r","\t"), ' ', $s);
		#$s = preg_replace("/(\s{2,})/", ' ', $s);
		return $this->replace_vars($s, $this->ar_vars, $this->cfg['is_keep']);
#		return $this->s;
	}
	/**
	 * 
	 */
	public function append_html_title($s)
	{
		$this->ar_html_title[] = $s;
	}
	public function get_html_title()
	{
		/* HTML-title */
		krsort($this->ar_html_title);
		return implode($this->cfg['str_title_separator'], $this->ar_html_title);
	}
	/**
	 * Add Javascript code.
	 */
	public function append_js($v)
	{
		$this->js_a[] = $v;
	}
	/**
	 * Add Javascript file.
	 */
	public function append_js_file($filename)
	{
		$this->ar_js_files[] = $filename;
	}
	/**
	 * Add Javascript collection.
	 */
	public function append_js_collection($filename)
	{
		$this->ar_js_collection[] = $filename;
	}
	/**
	 * Get Javascript collection.
	 */
	public function get_js_collection()
	{
		return implode(',', $this->ar_js_collection);
	}
	/**
	 * Get compiled Javascript.
	 */
	public function get_js()
	{
		return $this->_js_compile();
	}
	private function _js_compile()
	{
		$s = '';
		/* Collect files contents first */
		foreach ($this->ar_js_files as $filename)
		{
			if (file_exists($filename))
			{
				 $s .= implode('', file($filename));
			}
			else
			{
				print '<div>!'.$filename.'</div>';
			}
		}
		/* Collect strings after */
		$s .= implode('', $this->js_a);
		/* Debug mode */
		if ($this->cfg['is_js_debug']) { return $s; }
		/* Remove comments */
		$s = preg_replace("/\/\*(.*?)\*\//s", ' ', $s);
		/* Remove new lines, spaces */
#		$s = str_replace(array("\r\n","\n","\r","\t"), ' ', $s);
		$s = preg_replace("/(\s{2,})/", ' ', $s);
		/* Join rules */
		$s = preg_replace('/([,|;|:|{|}|=|\)|\(|\+|\-|\*|\?|<]) /', '\\1', $s);
		$s = preg_replace('/ ([=|\(|\)|\+|\?|\}|:|\-|<])/', '\\1', $s);
		return $this->replace_vars($s, $this->ar_vars, 1);
	}
	/**
	 *
	 */
	public function append_css($v = '')
	{
		$this->css_a[] = $v;
	}
	/**
	 * Add CSS collection.
	 */
	public function append_css_collection($filename)
	{
		$this->ar_css_collection[] = $filename;
	}
	/**
	 * Get CSS collection.
	 */
	public function get_css_collection()
	{
		return implode(',', $this->ar_css_collection);
	}
	/* */
	public function get_css()
	{
		return $this->_css_compile();
	}
	/* */
	public function append_css_file($filename)
	{
		$this->ar_css_files[] = $filename;
	}
	/* */
	private function _css_compile()
	{
		$s = '';
		foreach ($this->ar_css_files as $filename)
		{
			if (file_exists($filename) && is_file($filename))
			{
				 $s .= implode('', file($filename));
			}
			else
			{
				print '._css_compile_error{ background-url("'.$filename."\") }\n";
			}
		}
		if ($this->cfg['is_css_debug']) { return $s; }
		/* Remove comments */
		$s = preg_replace("/\/\*(.*?)\*\//s", ' ', $s);
		/* Remove new lines, spaces */
		$s = str_replace(array("\r\n","\n","\r","\t"), ' ', $s);
		$s = preg_replace("/(\s{2,})/", ' ', $s);
		/* Join rules */
		$s = preg_replace('/([,|;|:|{|}]) /', '\\1', $s);
		$s = str_replace(' {', '{', $s);
		$s = str_replace(' .', '.', $s);
		/* Remove ; for the last attribute */
		$s = str_replace( array(';}', ' }'), '}', $s);
		return $this->replace_vars($s, $this->ar_vars, $this->cfg['is_keep']);
	}
	/**
	 * Replaces text with the variables.
	 */
	public static function replace_vars($t = '', $ar = array(), $is_keep = 0)
	{
		$arCmd = array();
		/* Search for template variables, {%varname%} */
		$preg = "/({%)([A-Za-z0-9:\/\-_]+)(%})/i";
		if (preg_match_all($preg, $t, $tmp['ar_matches']))
		{
			foreach($tmp['ar_matches'][2] as $k => $cmd_src)
			{
				$arCmd[$k] = $tmp['ar_matches'][1][$k].$cmd_src.$tmp['ar_matches'][3][$k];
				$tmp['cmd'] = trim($cmd_src);
				$tmp['cmd'] = isset($ar[$tmp['cmd']]) ? $ar[$tmp['cmd']] : ($is_keep ? $arCmd[$k] : '');
				$arRpl[$k] = $tmp['cmd'];
			}
			/* replaces variables only */
			$t = str_replace($arCmd, $arRpl, $t);
		}
		return $t;
	}
	/**
	 * Displays information box
	 */
	public function get_note_afterpost($s, $status = true)
	{
		$id = substr( hash('md5', $s), 0, 16 );
		$s = '<span class="'.($status == true ? "state-ok" : "state-warning" ).'">'.$s.'</span>';
		return '<div class="note-afterpost" id="note-'.$id.'">'.
			'<a href="#" onclick="getElementById(\'note-'.$id.'\').style.display=\'none\';return false" style="text-decoration:none;font-size:120%;padding:0 5px;display:block;float:right">&times;</a>'.
			$s.'</div>';
	}
	/* Gzip-encoding */
	public function gzip($s, $level = 5)
	{
		if ( function_exists('crc32') && function_exists('gzcompress') )
		{
			if ( strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false )
			{
				$this->gzip_enc = 'x-gzip';
			}
			if ( strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false )
			{
				$this->gzip_enc = 'gzip';
			}
			if ( $this->gzip_enc !== false )
			{
				if ( function_exists('gzencode') )
				{
					$s = gzencode($s, $level);
				}
				else
				{
					$s_src = $s;
					$size = strlen( $s );
					$crc = crc32( $s );
					$s = "\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\xff";
					$s .= substr( gzcompress($s_src, $level), 2, -4 );
					$s .= pack( 'V', $crc );
					$s .= pack( 'V', $size );
				}
			}
		}
		return $s;
	}
}
}
?>