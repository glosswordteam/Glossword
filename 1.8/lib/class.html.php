<?php
/**
 *  All HTML-tags are here
 *  ==============================================
 * Glossword - glossary compiler (http://glossword.biz/)
 * © 2008-2012 Glossword.biz team <team at glossword dot biz>
 * © 1999-2008 Dmitry N. Shilnikov
 */
/* -------------------------------------------------------- */
/**
 * Usage:
 *
 * include('class.html.php');
 *
 * 1.
 * $oHtml->setTag('a', 'class', 'ahref');
 * print $oHtml->a('index.php', 'home page');
 *
 * $oHtml->unsetTag('a');
 * print $oHtml->a('index.php', 'home page');
 *
 * Results:
 * 1.
 * <a href="index.php" class="ahref">home page</a>
 * <a href="index.php">home page</a>
 *
 */
/* ------------------------------------------------------*/

if (!defined('IS_CLASS_HTML'))
{
	define('IS_CLASS_HTML', 1);

/* ------------------------------------------------------*/
$tmp['mtime'] = explode(' ', microtime());
$tmp['start_time'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
/* ------------------------------------------------------*/
class gw_html {

// --------------------------------------------------------
// Default variables

	/* do parse through `htmlspecialchars' function before to output */
	var $is_htmlspecialchars = 0;
	/* :cool: setup custom open tag! */
	var $tagOpen    = '<';
	/* :cool: setup custom close tag!*/
	var $tagClose   = '>';
	/* default attributes for tags */
	var $tags = array('a' => array('href' => ''));
	/* current virtual directory name */
	var $server_dir = '';

// --------------------------------------------------------
// mod_rewrite configuration, for Apache webservers only

	var $ar_except = array('admin');
	/* Append Session ID */
	var $id_sess_name = 's';
	var $id_sess = 0;
	var $is_append_sid = 0;
	var $ar_url_append = array();
	/* Do rebuild URL parameters for <a href="">  */
	var $is_mod_rewrite = 0;
	/* The rules to rebuild URL parameters.
	   rule `array('/a/id/')' will always convert `?a=go&id=1 into `/go/1/'
	   rule `array('a=view' => '/a/id/')' will convert `?a=view&id=1' into `/view/1/'
	   only when `a' is `view'. */
	var $mod_rewrite_rule = array('/a/id/');
	/* String to add at the end of URL, /a/id/123.xhtml */
	var $mod_rewrite_suffix = '.xhtml';
	/* String to add at the end of URL, /a/index.xhtml */
	var $mod_rewrite_index = 'index.xhtml';

// --------------------------------------------------------
// Support functions

	/* */
	function setTag($tag, $variable, $value)
	{
		/* exclude bad rules */
		if ($tag == 'a' && $variable == 'target') /* no `target' attribute in XHTML 1.1 */
		{
			$variable = 'onclick';
			$value = 'window.open(this.href);return false;';
		}
		if ($tag != '')
		{
			$this->tags[$tag][$variable] = $value;
		}
	}
	/* */
	function unsetTag($tag, $var = '')
	{
		if (isset($this->tags[$tag][$var]))
		{
			unset($this->tags[$tag][$var]);
		}
		elseif (isset($this->tags[$tag]))
		{
			$this->tags[$tag] = array();
		}
	}
	/* */
	function setVar($var, $value)
	{
		$this->$var = $value;
	}
	/* */
	function unsetVar($var)
	{
		if (isset($this->$var))
		{
			unset($this->$var);
		}
	}
	/* */
	function paramValue($ar, $delimeter = ' ', $frame = '"')
	{
		$str = '';
		if (is_array($ar))
		{
			/* Do sort attributes in a good manner. */
			ksort($ar);
			for (reset($ar); list($k, $v) = each($ar);)
			{
				if (is_array($v)) { continue; }
				$str .= ($v != '') ? ($delimeter . $k . '=' . $frame.$v.$frame) : '';
			}
			$str = preg_replace("/^&amp;/", '', $str);
			$str = preg_replace("/^&/", '', $str);
		}
		return $str;
	}
	/* */
	function url_normalize($url)
	{
		$url_new = '';
		/* Do normalize URL in a good manner. */
		if ( preg_match("/\?/", $url) )
		{
			/* Removes &amp; to avoid any problems with & */
			$url = str_replace('&amp;', '&', $url);
			if ($this->is_append_sid)
			{
				$url = $url.'&'.$this->id_sess_name.'='.$this->id_sess;
			}
			for (reset($this->ar_url_append); list($k, $v) = each($this->ar_url_append);)
			{
				$url .= '&'.$k.'='.$v;
			}
			list($file, $param) = explode("?", $url);
			$ar = explode('&', $param);
			/* remove empty values, 2 feb 2004 */
			for (reset($ar); list($ka, $va) = each($ar);)
			{
				@list($src, $trg) = explode('=', $va);
				if ($trg == '')
				{
					unset($ar[$ka]);
				}
			}
			sort($ar);
			$url = $file .'?'. implode('&', $ar);
			if (($this->is_mod_rewrite) && !preg_match("/^http/", $url))
			{
				$arQ = array();
				/* Exception mode */
				for (reset($this->ar_except); list($k, $v) = each($this->ar_except);)
				{
					if ( preg_match("/".$v."/", $url) )
					{
						return $this->server_dir . '/' . str_replace("&", "&amp;", $url);
					}
				}
				$url_new = $this->server_dir . '/';
				/* depends on magic_quotes_gpc, 11 aug 2003 */
				parse_str($param, $arQ);
				if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
				{
					gw_stripslashes_array($arQ);
				}
				for (reset($this->mod_rewrite_rule); list($kR, $vR) = each($this->mod_rewrite_rule);)
				{
					if ($this->is_append_sid)
					{
						$vR = '/'.$this->id_sess_name.$vR;
					}
					if (strval($kR) != '0') /* conditions detected */
					{
						list($if_src, $if_trg) = explode('=', $kR);
						if (isset($arQ[$if_src]) && ($arQ[$if_src] == $if_trg)) /* condition found */
						{
							if ($vR == '') /* no rule given */
							{
								/* remove server directory, 17 oct 2004 */
								$url = preg_replace('/('. str_replace('/', '\/', preg_quote($this->server_dir)) .')?(\/)?/', '', $url);
								$url = $this->server_dir . '/' . str_replace('&', '&amp;', $url);
								return $url;
							}
							else
							{
								$url_new .= $this->url_rule2dir($arQ, $vR);
								break;
							}
						}
					}
					else /* default rule for all conditions */
					{
						$url_new .= $this->url_rule2dir($arQ, $vR);
					}
				}
				$url = $url_new.$this->mod_rewrite_suffix;
#				$url = preg_replace('/\/([-]{2,})'.$this->mod_rewrite_suffix.'/', $this->mod_rewrite_index, $url);
				$url = preg_replace('/([-]{2,})'.$this->mod_rewrite_suffix.'/', $this->mod_rewrite_suffix, $url);
				$url = preg_replace('/([,]{2,})'.$this->mod_rewrite_suffix.'/', $this->mod_rewrite_suffix, $url);
#				$url = str_replace(',,'.$this->mod_rewrite_suffix, $this->mod_rewrite_index, $url);
				$url = str_replace('..'.$this->mod_rewrite_suffix, $this->mod_rewrite_index, $url);
				$url = str_replace('/-'.$this->mod_rewrite_suffix, '/'.$this->mod_rewrite_index, $url);
				$url = str_replace('/'.$this->mod_rewrite_suffix, '/'.$this->mod_rewrite_index, $url);
			}
			$url = str_replace('&', '&amp;', $url);
		}
		else
		{
			$url = $url . '?';
			for (reset($this->ar_url_append); list($k, $v) = each($this->ar_url_append);)
			{
				$url .= '&'.$k.'='.$v;
			}
			if ($this->is_append_sid)
			{
				$url = $url.'&'.$this->id_sess_name.'='.$this->id_sess;
			}
			$url = str_replace('?&', '?', $url);
			$url = preg_replace('/\?$/', '', $url);
		}
		return $url;
	}
	/* */
	function url_dir2str($url)
	{
		global $sys;
		$url = urldecode($url);
		$url = str_replace($this->server_dir, '', $url);
		/* kontakti.xhtml -> i.xhmlt */
		$url = str_replace($this->mod_rewrite_index, '', $url);
		$arP = explode('/', $url);
		$url = '';
		for (reset($this->mod_rewrite_rule); list($kR, $vR) = each($this->mod_rewrite_rule);)
		{
			if ($this->is_append_sid)
			{
				$vR = '/'.$this->id_sess_name.$vR;
			}
			if ($kR != '0') /* conditions detected */
			{
				list($if_src, $if_trg) = explode('=', $kR);
				$arRule = explode("/", $vR);
				for (reset($arP); list($kP, $vP) = each($arP);)
				{	
					if (in_array($if_src, $arRule) && ($arP[$kP] == $if_trg) ) /* condition found */
					{
						$url = $this->url_rule2str($arP, $arRule);
					}
				}
			}
			else /* default rule for all conditions */
			{
				if ($url == '')
				{
					$arRule = explode("/", $vR);
					$url = $this->url_rule2str($arP, $arRule);
				}
			}
		}
		$url = str_replace($this->mod_rewrite_suffix, '', $url);
		$url = str_replace('?&amp;', '?', $url);
		$url = str_replace('?&', '?', $url);
		return $url;
	}
	/**
	 * 
	 *
	 * @param array  $arQ URL parameters
	 * @param string $str mod_rewrite Rule
	 */
	function url_rule2dir($arQ, $str)
	{
		$url = '';
		$arRule = explode('/', $str);
		$arUrl = array();
		while (list($k, $v) = each($arRule))
		{
			if (isset($arQ[$v]) )
			{
#				if ($arQ[$v] == '') { continue; }
				$arUrl[] = urlencode($arQ[$v]);
			}
		}
		$url .= implode('/', $arUrl);
		$arRule2 = array();
		$str_implode = ',';
		if (strpos( end($arRule), ','))
		{
			$str_implode = ',';
			$url .= '/';
		}
		elseif (strpos( end($arRule), '.'))
		{
			$str_implode = '.';
			$url .= '/';
		}
		elseif (strpos( end($arRule), '-'))
		{
			$str_implode = '-';
			$url .= '/';
		}
		$arRule2 = explode($str_implode, end($arRule));
		$arUrl = array();
		if (sizeof($arRule2) > 1 )
		{
			while (list($k, $v) = each($arRule2))
			{
				$arQ[$v] = isset($arQ[$v]) ? $arQ[$v] : '';
				$arUrl[] = urlencode($arQ[$v]);
			}
		}
		$url .= implode($str_implode, $arUrl);
		/* 21 Aug 2007: fix %2F bug */
		$url = str_replace('%2F', '%252F', $url);
		/* 22 Jun 2008: fix %2C because %2C used in the rules */
		$url = str_replace('%2C', '%252C', $url);
		return $url;
	}
	/**
	 * 
	 *
	 * @param array  $arP URL parameters
	 * @param array $str mod_rewrite Rule
	 */
	function url_rule2str($arP, $arRule)
	{
		$url = '';
		reset($arRule);
		while (list($k, $v) = each($arRule))
		{
			if (($v != '') 
				&& isset($arP[$k]) && ($arP[$k] != '') 
				&& !strpos($v, ',') && !strpos($v, '-') 
				&& ($arP[$k] != $this->mod_rewrite_suffix)
				)
			{
				$url .= '&' . $v . '=' . urlencode($arP[$k]);
			}
			/* parse additional parameters ".xhtml?param=value" */
			if (isset($arP[$k]) && strpos($arP[$k], $this->mod_rewrite_suffix.'?'))
			{
				$arParamParts = explode($this->mod_rewrite_suffix.'?', $arP[$k]);
				$arP[$k] = $arParamParts[0];
				$url .= '&'.$arParamParts[1];
			}
		}
		$arRule2 = array();
		$str_split = ' '; /* default rule delimeter */
		if (strpos( end($arRule), ','))
		{
			$str_split = ',';
		}
		elseif (strpos( end($arRule), '.'))
		{
			$str_split = '.';
		}
		elseif (strpos( end($arRule), '-'))
		{
			$str_split = '-';
		}
		$arRule2 = explode($str_split, end($arRule));
		$arP2 = explode($str_split, str_replace($this->mod_rewrite_suffix, '', end($arP)));
		$int_p = sizeof($arP2);
		while (list($k, $v) = each($arRule2))
		{
			if (($v != '') && isset($arP2[$k]) 
				&& ($arP2[$k] != '') 
				&& ($int_p > 0)
				&& ($arP2[$k] != $this->mod_rewrite_suffix))
			{
				$url .= '&' . $v . '=' . urlencode($arP2[$k]);
			}
		}
		$url = str_replace('%252C', ',', $url);
		$url = str_replace('%252D', '-', $url);
		$url = str_replace('%252F', '/', $url);
		return $url;
	}

/* --------------------------------------------------------
 * Tags functions
 * ----------------------------------------------------- */
	/* */
	function tag_open($t, $arAttr = array())
	{
		$t = preg_replace("/[^a-zA-Z0-9_:]/", '', $t);
		$extras = $this->paramValue($arAttr);
		return sprintf($this->tagOpen. '%s%s' .$this->tagClose, $t, $extras);
	}
	/* */
	function tag_close($t)
	{
		$t = preg_replace("/[^a-zA-Z0-9_:]/", '', $t);
		return $this->tagOpen.'/'.$t.$this->tagClose;
	}
	/* */
	function a($url, $text = '', $title = '')
	{
		$this->setTag( 'a', 'href', $this->url_normalize($url) );
		$this->setTag( 'a', 'title', htmlspecialchars($title) );
		$extras = $this->paramValue($this->tags['a']);
		return $this->_results(
					sprintf(
						$this->tagOpen. 'a%s' .$this->tagClose. '%s' .$this->tagOpen. '/a' .$this->tagClose,
						$extras, $text
					)
				);
	}
	/* */
	function input($name = '', $value = '')
	{
		$this->setTag('input', 'value', $value);
		$this->setTag('input', 'name', $name);
		$extras = $this->paramValue($this->tags['input']);
		return $this->_results(
					sprintf(
						$this->tagOpen. 'input%s /' . $this->tagClose,
						$extras
					)
				);
	}
	/* */
	function img($src = '', $alt = '')
	{
		if ($src != '')
		{
			$this->setTag('img', 'src', $this->url_normalize($src));
		}
		if ($alt != '')
		{
			$this->setTag('img', 'alt', strip_tags($alt));
		}
		$extras = $this->paramValue($this->tags['img']);
		return $this->_results(
					sprintf(
						$this->tagOpen. 'img%s /' .$this->tagClose,
						$extras
					)
				);
	}
	/* */
	function table()
	{
		$extras = $this->paramValue($this->tags['table']);
		return $this->_results(
					sprintf(
						$this->tagOpen. 'table%s' .$this->tagClose,
						$extras
					)
				);
	}

// --------------------------------------------------------
// Output control functions

	/**
	 * @param   string  $str HTML-code to print
	 * @access  private
	 * @return  string
	 */
	function _results($str)
	{
		if ($this->is_htmlspecialchars)
		{
			return htmlspecialchars($str);
		}
		return $str;
	}

} /* end of class */

/* automatic initialization */
/* $oHtml = new gw_html; */
/* ------------------------------------------------------ */
$tmp['mtime'] = explode(' ', microtime());
$tmp['endtime'] = (float)$tmp['mtime'][1] + (float)$tmp['mtime'][0];
$tmp['time'][__FILE__] = ($tmp['endtime'] - $tmp['start_time']);
}
?>