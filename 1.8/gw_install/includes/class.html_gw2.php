<?php
/**
 * Class to work with HTML.
 */
if (!defined('IS_CLASS_GW2_HTML')) { define('IS_CLASS_GW2_HTML', 1);
class gw2_html
{
	public $ar_html_title, $str_html, $html_a, $path_css, $path_js;
	public $ar_css_files, $css_a;
	public $ar_js_files, $js_a;
	public $is_css_debug, $is_js_debug = 0;
	public $gzip_enc = false;
	public $is_gzip_css, $is_gzip_js = false;
	function append($s)
	{
		$this->str_html .= $s;
	}
	/**
	 * 
	 */
	function a($k, $v = '')
	{
		$this->html_a[$k] = $v;
	}
	function g()
	{
		/* Remove new lines, spaces */
		$this->str_html = str_replace(array("\r\n","\n","\r","\t"), ' ', $this->str_html);
		$this->str_html = preg_replace("/(\s{2,})/", ' ', $this->str_html);
		return $this->replace_vars($this->str_html, $this->html_a, 1);
#		return $this->str_html;
	}
	/**
	 * 
	 */
	function append_html_title($s)
	{
		$this->ar_html_title[] = $s;
	}
	function get_html_title()
	{
		/* HTML-title */
		krsort($this->ar_html_title);
		return implode(' + ',  $this->ar_html_title);
	}
	/**
	 * 
	 */
	function js_a($k, $v = '')
	{
		$this->js_a[$k] = $v;
	}
	function js_g()
	{
		return $this->_js_compile();
	}
	function js_a_file($filename)
	{
		$this->ar_js_files[] = $filename;
	}
	private function _js_compile()
	{
		$s = '';
		foreach ($this->ar_js_files as $filename)
		{
			if (file_exists($this->path_js.'/'.$filename))
			{
				 $s .= implode('', file($this->path_js.'/'.$filename));
			}
			else
			{
				print '<div>!'.$this->path_js.'/'.$filename.'</div>';
			}
		}
		if ($this->is_js_debug) { return $s; }
		/* Remove comments */
		$s = preg_replace("/\/\*(.*?)\*\//s", ' ', $s);
		/* Remove new lines, spaces */
#		$s = str_replace(array("\r\n","\n","\r","\t"), ' ', $s);
		$s = preg_replace("/(\s{2,})/", ' ', $s);
		/* Join rules */
		$s = preg_replace('/([,|;|:|{|}|=|\)|\+|\*|<]) /', '\\1', $s);
		$s = preg_replace('/ ([=|\(|<])/', '\\1', $s);
		return $this->replace_vars($s, $this->js_a, 1);
	}
	/**
	 *
	 */
	function css_a($k, $v = '')
	{
		$this->css_a[$k] = $v;
	}
	function css_g()
	{
		return $this->_css_compile();
	}
	function css_a_file($filename)
	{
		$this->ar_css_files[] = $filename;
	}
	private function _css_compile()
	{
		$s = '';
		foreach ($this->ar_css_files as $filename)
		{
			if (file_exists($this->path_css.'/'.$filename))
			{
				 $s .= implode('', file($this->path_css.'/'.$filename));
			}
			else
			{
				print '<div>! '.$this->path_css.'/'.$filename.'</div>';
			}
		}
		if ($this->is_css_debug) { return $s; }
		/* Remove comments */
		$s = preg_replace("/\/\*(.*?)\*\//s", ' ', $s);
		/* Remove new lines, spaces */
#		$s = str_replace(array("\r\n","\n","\r","\t"), ' ', $s);
		$s = preg_replace("/(\s{2,})/", ' ', $s);
		/* Join rules */
		$s = preg_replace('/([,|;|:|{|}]) /', '\\1', $s);
		$s = str_replace(' {', '{', $s);
		$s = str_replace(' .', '.', $s);
		/* Remove ; for the last attribute */
		$s = str_replace(array(';}', ' }'), '}', $s);
		return $this->replace_vars($s, $this->css_a, 1);
	}
	/* */
	static function replace_vars($t = '', $ar = array(), $is_keep = 0)
	{
		$arCmd = array();
		/* Search for template tags */
		$preg = "/({)([ A-Za-z0-9:\/\-_]+)(})/i";
		if (preg_match_all($preg, $t, $tmp['tpl_matches']))
		{
			while (list($k, $cmd_src) = each($tmp['tpl_matches'][2]))
			{
				$arCmd[$k] = $tmp['tpl_matches'][1][$k].$cmd_src.$tmp['tpl_matches'][3][$k];
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
	function get_note_afterpost($s, $status = true)
	{
		$id = substr(hash('md5', $s), 0, 16);
		$s = '<span class="'.($status == true ? "state-ok" : "state-warning" ).'">'.$s.'</span>';
		return '<div class="note-afterpost" id="note-'.$id.'">'.
			'<a href="#" onclick="getElementById(\'note-'.$id.'\').style.display=\'none\';return false" style="text-decoration:none;font-size:120%;padding:0 5px;display:block;float:right">&times;</a>'.
			$s.'</div>';
	}
	/* Gzip-encoding */
	function gzip($s, $level = 5)
	{
		if (function_exists('crc32') AND function_exists('gzcompress'))
		{
			if (strpos(' '.$this->HTTP_ACCEPT_ENCODING, 'x-gzip') !== false)
			{
				$this->gzip_enc = 'x-gzip';
			}
			if (strpos(' '.$this->HTTP_ACCEPT_ENCODING, 'gzip') !== false)
			{
				$this->gzip_enc = 'gzip';
			}
			if ($this->gzip_enc !== false)
			{
				if (function_exists('gzencode'))
				{
					$s = gzencode($s, $level);
				}
				else
				{
					$s_src = $s;
					$size = strlen($s);
					$crc = crc32($s);
					$s = "\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\xff";
					$s .= substr(gzcompress($s_src, $level), 2, -4);
					$s .= pack('V', $crc);
					$s .= pack('V', $size);
				}
			}
		}
		return $s;
	}

}
}
?>