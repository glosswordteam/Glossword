<?php 
if (!defined('IS_CLASS_GW2_HTMLTAGS')) { define('IS_CLASS_GW2_HTMLTAGS', 1);
class gw2_html_tags
{
	var $is_htmlspecialchars = 0;
	var $is_sef = 0;
	var $sef_rule = array();
	var $server_dir = '/';
	var $sef_filename = 'index';
	var $sef_output = 'xhtml';
	var $sef_il = 'eng';
	var $file_index = 'index.php';
	/* &arg[variable]=value, `arg` is $v_get */
	var $v_get;
	function set_tag($tag, $v, $value)
	{
		if ($tag != '')
		{
			$this->tags[$tag][$v] = $value;
		}
	}
	function unset_tag($tag, $v = '')
	{
		if (isset($this->tags[$tag][$v]))
		{
			unset($this->tags[$tag][$v]);
		}
		elseif (isset($this->tags[$tag]))
		{
			$this->tags[$tag] = array();
		}
	}
	/* 02 oct 2007: new <a href=""> builder */
	function a_href($ar_href = array(), $ar_attr = array(), $text = '')
	{
		if (!is_array($ar_href)){ $ar_href = array($ar_href); }
		$filename = $ar_href[0];
		unset($ar_href[0]);
		$this->set_tag('a', 'href', $filename);
		if (!empty($ar_href))
		{
			$this->set_tag('a', 'href', $this->url_normalize($filename.'?'.$this->http_build_query_href($ar_href)));
		}
		/* 26 Jan 2008: print url as text automatically */
		if (!$text)
		{
			$text = $this->tags['a']['href'];
		}
		$this->tags['a'] = array_merge($this->tags['a'], $ar_attr);
		return $this->_render('<a '.$this->http_build_query($this->tags['a']).'>'.$text.'</a>');
	}
	/* Shorthand for http_build_query() */
	function http_build_query_href($ar)
	{
		return $this->http_build_query($ar, '&', '=', '');
	}
	/* replacement for http_build_query */
	function http_build_query($ar = array(), $pairs = ' ', $values_sep = '=', $enclose = '"')
	{
		$str = '';
		if (is_array($ar))
		{
			/* Do sort attributes in a good manner. */
			ksort($ar);
			for (reset($ar); list($k, $v) = each($ar);)
			{
				$str .= (strval($v) == '') ? '' : ($pairs.$k.$values_sep.$enclose.$v.$enclose);
			}
			$str = ltrim($str, $pairs);
		}
		return $str;
	}
	/**
	 * Normalizes URL.
	 * 25 May 2008: debug mode added.
	 */
	function url_normalize($url, $is_debug = 0)
	{
		if ($is_debug)
		{
			return $url;
		}
		/* Convert &amp for sure */
		$url = str_replace('&amp;', '&', $url);
		$url = str_replace('&', '&amp;', $url);
		/* fix for the "slash problem" */
		$url = str_replace('%2F', '%252F', $url);
		/* $ar_path[1] is url parameters */
		preg_match("/\?(.*?)$/", $url, $ar_path);
		/* */
		if (isset($ar_path[1]))
		{
			$filepath = str_replace($ar_path[1], '', $url);
			parse_str($ar_path[1], $ar_url);
			if ($this->is_sef && isset($this->sef_rule))
			{
				foreach ($this->sef_rule as $expr => $rule)
				{
					if (isset($ar_url[$this->v_get]) 
						&& (!$expr || strpos($ar_path[1], $expr))
					)
					{
						$url = $this->url_do_sef($rule, $ar_url[$this->v_get]);
					}
#prn_r( $ar_path[1] );
				}
				$url = ltrim($url, '/');
#prn_r( $this->server_dir.$this->sef_index.'/'.$url, __FILE__.__LINE__ );
				return $this->server_dir.$this->sef_index.'/'.$url;
			}
		}
		return $url;
	}
	/**
	 * Converts an array in a short URL.
	 * array('id' => 'wwwguru.net', 'sef_index' => 'index', 'sef_output' => 'xhtml', 'target' => 'item') => /i/wwwguru.net/index.xhtml
	 * 
	 * @param   array   Rule how to rewrite URL
	 * @param   string  URL written as array
	 * @return  string  A short URL
	 */
	function url_do_sef($rule, $ar_url)
	{
		if (!isset($ar_url['sef_filename']))
		{
			$ar_url['sef_filename'] = $this->sef_filename;
		}
		if (!isset($ar_url['sef_output']))
		{
			$ar_url['sef_output'] = $this->sef_output;
		}
		if (!isset($ar_url['il']))
		{
			$ar_url['il'] = $this->sef_il;
		}
#		prn_r( $ar_url );
		$rule = str_replace(array('/', ',', '.'), array('/$', ',$', '.$'), $rule);
		/* @todo: Optimize */
		for (reset($ar_url); list($k, $v) = each($ar_url);)
		{
			$rule = preg_replace('/\$'.$k.'\b/', urlencode($v), $rule);
		}
		$rule = preg_replace('#\$\w+#', ' ', $rule);
		$url = '';
		$rule = str_replace(array(', ', '. '), ',', $rule);
		/* First part */
		$ar_pt1 = explode('/', $rule);
		/* Second part */
		$ar_pt2 = explode(',', end($ar_pt1));
		$ar_pt1[sizeof($ar_pt1)-1] = '';

		$url .= implode(' ', $ar_pt1);
		$url = rtrim($url);
		$url = preg_replace('# {2,}#', ' ', $url);
		$url = str_replace(' ', '/', $url);

		$rule2 = implode(' ', $ar_pt2);
		$rule2 = rtrim($rule2);
		$rule2 = str_replace(' ', ',', $rule2);
		$url .= ($rule2) ? '/'.$rule2 : '';
#prn_r( $url, __LINE__ );
#		$url .= $this->sys['sef_append'];
		return $url;
	}
	/**
	 * Converts a short url into an array.
	 * /i/wwwguru.net/index.xhtml => array('id' => 'wwwguru.net', 'sef_index' => 'index', 'sef_output' => 'xhtml', 'target' => 'item')
	 * 
	 * @param   string  A short URL
	 * @return  array   URL written as array
	 */
	function url_undo_sef($url)
	{
		$ar_url = array();
		$url = str_replace($this->server_dir, '', $url);
		$url = str_replace($this->sef_index, '', $url);
#		$url = preg_replace('#'.$this->sys['sef_append'].'$#', '', $url);
		$url = str_replace(array('/', ','), "\x01", $url);
		/* Skip urls with question mark '?' */
		if (strpos($url, '?') !== false)
		{
			return array();
		}
		if (!$url || !$this->sef_rule) { return array(); }
		foreach ($this->sef_rule as $expr => $rule)
		{
			$rule = str_replace(array('/', ','), "\x01", $rule);
			$ar_rule = explode("\x01", $rule);
			$ar_p = explode("\x01", $url);
			array_shift($ar_p);
			if ($ar_rule[0] == $ar_p[0])
			{
				unset($ar_rule[0], $ar_p[0]);
				$ar_expr = explode('=', $expr);
				$ar_url['target'] = $ar_expr[1];
				foreach($ar_p as $k => $v)
				{
					$ar_url[$ar_rule[$k]] = $v;
				}
				/* Last parameters */
				$ar_p_last = explode('.', end($ar_p));
				$ar_rule_last = explode('.', end($ar_rule));
				foreach ($ar_p_last as $k => $v)
				{
					if (isset($ar_rule_last[$k]))
					{
						$ar_url[$ar_rule_last[$k]] = $v;
					}
				}
				unset($ar_url[end($ar_rule)]);
			}
		}
		return $ar_url;
	}
	
	
	/* */
	function _render($s)
	{
		if ($this->is_htmlspecialchars)
		{
			$s = htmlspecialchars($s);
		}
		return $s;
	}
}
}
?>